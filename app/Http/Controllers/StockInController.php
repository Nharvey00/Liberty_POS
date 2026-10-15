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

        DB::transaction(function () use ($validated) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            // lockForUpdate prevents race conditions if multiple managers do stock ins simultaneously
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            
            $quantityReceived = (int) $validated['quantity_received'];
            $emptyReturnedQty = $product->isAccessory() 
                ? 0 
                : ((isset($validated['empty_returned_qty']) && $validated['empty_returned_qty'] !== '') ? (int) $validated['empty_returned_qty'] : 0);

            // Security: Prevent emptying more shells than exist
            if ($emptyReturnedQty > 0 && $product->empty_quantity < $emptyReturnedQty) {
                throw ValidationException::withMessages([
                    'empty_returned_qty' => 'Cannot return more empty shells to the supplier than are currently in stock.'
                ]);
            }

            $filledBefore = (int) $product->stock_quantity;
            $emptyBefore  = (int) $product->empty_quantity;
            $filledAfter  = $filledBefore + $quantityReceived;
            $emptyAfter   = $emptyBefore - $emptyReturnedQty;

            $unitCost = (isset($validated['unit_cost']) && $validated['unit_cost'] !== '')
                ? (float) $validated['unit_cost']
                : ($product->cost_price ?? null);

            $referenceNo = !empty($validated['reference_no']) ? trim($validated['reference_no']) : null;

            // 1. Log the StockIn record with before/after snapshots and actor identity
            $stockIn = StockIn::create([
                'product_id'         => $product->id,
                'user_id'            => Auth::id(),
                'approved_by'        => Auth::id(),
                'reference_no'       => $referenceNo,
                'supplier_name'      => $validated['supplier_name'] ?? null,
                'quantity_received'  => $quantityReceived,
                'empty_returned_qty' => $emptyReturnedQty,
                'unit_cost'          => $unitCost,
                'filled_qty_before'  => $filledBefore,
                'filled_qty_after'   => $filledAfter,
                'empty_qty_before'   => $emptyBefore,
                'empty_qty_after'    => $emptyAfter,
                'remarks'            => $validated['remarks'] ?? null,
            ]);

            // Auto-number from the committed row ID so concurrent requests can never collide
            if ($referenceNo === null) {
                $referenceNo = 'SI-' . date('Ymd') . '-' . str_pad((string) $stockIn->id, 4, '0', STR_PAD_LEFT);
                $stockIn->update(['reference_no' => $referenceNo]);
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
                'reference_no'      => $referenceNo,
                'movement_type'     => InventoryMovement::TYPE_STOCK_IN,
                'filled_qty_before' => $filledBefore,
                'filled_qty_change' => $quantityReceived,
                'filled_qty_after'  => $filledAfter,
                'empty_qty_before'  => $emptyBefore,
                'empty_qty_change'  => -$emptyReturnedQty,
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
                'transaction_reference' => $referenceNo,
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
                    'empty_returned_qty' => $emptyReturnedQty,
                    'supplier_name'      => $stockIn->supplier_name,
                    'unit_cost'          => $unitCost,
                ],
                'reason'                => $validated['remarks'] ?? 'Supplier delivery recorded',
            ]);
        });

        return redirect()->route('stock-ins.index')->with('success', 'Delivery logged and inventory updated.');
    }
}