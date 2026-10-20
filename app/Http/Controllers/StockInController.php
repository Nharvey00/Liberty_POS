<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Http\Requests\StoreStockInRequest;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StockInController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $search = $request->query('search');
        $query = StockIn::with(['product', 'user', 'approver'])->latest();

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('remarks', $like, "%{$search}%")
                  ->orWhere('reference_no', $like, "%{$search}%")
                  ->orWhere('supplier_name', $like, "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search, $like) {
                      $pq->withTrashed()->where('name', $like, "%{$search}%");
                  });
            });
        }

        $stockIns = $query->paginate(15)->withQueryString();
        return view('stock_ins.index', compact('stockIns', 'search'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        return view('stock_ins.create', compact('products'));
    }

    public function store(StoreStockInRequest $request)
    {
        $validated = $request->validated();

        $items = isset($validated['items']) && is_array($validated['items'])
            ? $validated['items']
            : [[
                'product_id'         => $validated['product_id'],
                'quantity_received'  => $validated['quantity_received'] ?? 0,
                'empty_received_qty' => $validated['empty_received_qty'] ?? 0,
                'empty_returned_qty' => $validated['empty_returned_qty'] ?? 0,
                'unit_cost'          => $validated['unit_cost'] ?? null,
            ]];

        DB::transaction(function () use ($validated, $items) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            // Lock all involved products in deterministic ascending ID order to prevent deadlocks
            $productIds = collect($items)->pluck('product_id')->unique()->sort()->values();
            Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();

            $sharedReferenceNo = !empty($validated['reference_no']) ? trim($validated['reference_no']) : null;
            $batchReferenceNo  = $sharedReferenceNo;

            foreach ($items as $index => $itemData) {
                $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

                $quantityReceived = (isset($itemData['quantity_received']) && $itemData['quantity_received'] !== '') ? (int) $itemData['quantity_received'] : 0;
                $emptyReceivedQty = $product->isAccessory()
                    ? 0
                    : ((isset($itemData['empty_received_qty']) && $itemData['empty_received_qty'] !== '') ? (int) $itemData['empty_received_qty'] : 0);
                $emptyReturnedQty = $product->isAccessory()
                    ? 0
                    : ((isset($itemData['empty_returned_qty']) && $itemData['empty_returned_qty'] !== '') ? (int) $itemData['empty_returned_qty'] : 0);

                // Security: Prevent returning more empty shells than exist (accounting for empty shells received in the same row)
                if ($emptyReturnedQty > 0 && ($product->empty_quantity + $emptyReceivedQty) < $emptyReturnedQty) {
                    $errorKey = isset($validated['items']) ? "items.{$index}.empty_returned_qty" : 'empty_returned_qty';
                    throw ValidationException::withMessages([
                        $errorKey => "Cannot return more empty shells for '{$product->name}' than are currently in stock."
                    ]);
                }

                $filledBefore = (int) $product->stock_quantity;
                $emptyBefore  = (int) $product->empty_quantity;
                $filledAfter  = $filledBefore + $quantityReceived;
                $emptyAfter   = $emptyBefore + $emptyReceivedQty - $emptyReturnedQty;

                $rawUnitCost = $itemData['unit_cost'] ?? ($validated['unit_cost'] ?? null);
                $unitCost = ($rawUnitCost !== null && $rawUnitCost !== '')
                    ? (float) $rawUnitCost
                    : ($product->cost_price ?? null);

                // 1. Log the StockIn record with before/after snapshots and actor identity
                $stockInPayload = [
                    'product_id'         => $product->id,
                    'user_id'            => Auth::id(),
                    'approved_by'        => Auth::id(),
                    'reference_no'       => $batchReferenceNo,
                    'supplier_name'      => $validated['supplier_name'] ?? null,
                    'quantity_received'  => $quantityReceived,
                    'empty_returned_qty' => $emptyReturnedQty,
                    'unit_cost'          => $unitCost,
                    'filled_qty_before'  => $filledBefore,
                    'filled_qty_after'   => $filledAfter,
                    'empty_qty_before'   => $emptyBefore,
                    'empty_qty_after'    => $emptyAfter,
                    'remarks'            => $validated['remarks'] ?? null,
                ];
                if (Schema::hasColumn('stock_ins', 'empty_received_qty')) {
                    $stockInPayload['empty_received_qty'] = $emptyReceivedQty;
                }
                $stockIn = StockIn::create($stockInPayload);

                // Auto-number from the first committed row ID so all items in the same delivery share the batch reference
                if ($batchReferenceNo === null) {
                    $batchReferenceNo = 'SI-' . date('Ymd') . '-' . str_pad((string) $stockIn->id, 4, '0', STR_PAD_LEFT);
                    $stockIn->update(['reference_no' => $batchReferenceNo]);
                }

                // 2. Adjust real inventory
                $product->stock_quantity = $filledAfter;
                $product->empty_quantity = $emptyAfter;
                if (!is_null($unitCost) && $unitCost > 0) {
                    $product->cost_price = $unitCost;
                }
                $product->updated_by = Auth::id();
                $product->save();

                // 3. Record dedicated InventoryMovement ledger entry
                InventoryLedgerService::recordMovement($product, [
                    'reference_no'      => $batchReferenceNo,
                    'movement_type'     => InventoryMovement::TYPE_STOCK_IN,
                    'filled_qty_before' => $filledBefore,
                    'filled_qty_change' => $quantityReceived,
                    'filled_qty_after'  => $filledAfter,
                    'empty_qty_before'  => $emptyBefore,
                    'empty_qty_change'  => $emptyReceivedQty - $emptyReturnedQty,
                    'empty_qty_after'   => $emptyAfter,
                    'unit_cost'         => $unitCost,
                    'unit_price'        => $product->price,
                    'source_type'       => 'StockIn',
                    'source_id'         => $stockIn->id,
                    'user_id'           => Auth::id(),
                    'approved_by'       => Auth::id(),
                    'reason'            => $validated['remarks'] ?? 'Supplier delivery / stock replenishment',
                ]);

                // 4. Record centralized AuditLog event
                AuditTrailService::record([
                    'correlation_id'        => $correlationId,
                    'module'                => 'inventory',
                    'action'                => 'stock.in',
                    'entity_type'           => 'StockIn',
                    'entity_id'             => $stockIn->id,
                    'transaction_reference' => $batchReferenceNo,
                    'status'                => 'completed',
                    'approval_status'       => 'approved',
                    'approver_user_id'      => Auth::id(),
                    'approved_at'           => now(),
                    'before_values'         => [
                        'product_id'     => $product->id,
                        'product_name'   => $product->name,
                        'stock_quantity' => $filledBefore,
                        'empty_quantity' => $emptyBefore,
                    ],
                    'after_values'          => [
                        'product_id'         => $product->id,
                        'product_name'       => $product->name,
                        'stock_quantity'     => $filledAfter,
                        'empty_quantity'     => $emptyAfter,
                        'quantity_received'  => $quantityReceived,
                        'empty_received_qty' => $emptyReceivedQty,
                        'empty_returned_qty' => $emptyReturnedQty,
                        'supplier_name'      => $stockIn->supplier_name,
                        'unit_cost'          => $unitCost,
                    ],
                    'reason'                => $validated['remarks'] ?? 'Supplier delivery recorded',
                ]);
            }
        });

        $count = count($items);
        $msg = $count > 1
            ? "Delivery logged for {$count} items and inventory updated."
            : 'Delivery logged and inventory updated.';

        return redirect()->route('stock-ins.index')->with('success', $msg);
    }
}