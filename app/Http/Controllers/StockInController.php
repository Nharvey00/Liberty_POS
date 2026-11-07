<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\Product;
use App\Models\TankLoan;
use App\Models\TankLoanReturn;
use App\Models\InventoryMovement;
use App\Http\Requests\StoreStockInRequest;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StockInController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = StockIn::with(['product', 'user', 'approver', 'voidedByUser', 'tankLoanReturn.tankLoan.customer'])->latest();

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
                'product_id'            => $validated['product_id'],
                'quantity_received'     => $validated['quantity_received'] ?? 0,
                'empty_received_qty'    => max((int) ($validated['empty_received_qty'] ?? 0), (int) ($validated['empty_shells_received'] ?? 0)),
                'empty_shells_received' => max((int) ($validated['empty_received_qty'] ?? 0), (int) ($validated['empty_shells_received'] ?? 0)),
                'empty_returned_qty'    => $validated['empty_returned_qty'] ?? 0,
                'unit_cost'             => $validated['unit_cost'] ?? null,
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
                    : max(
                        (isset($itemData['empty_received_qty']) && $itemData['empty_received_qty'] !== '') ? (int) $itemData['empty_received_qty'] : 0,
                        (isset($itemData['empty_shells_received']) && $itemData['empty_shells_received'] !== '') ? (int) $itemData['empty_shells_received'] : 0
                    );
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
                    'product_id'            => $product->id,
                    'user_id'               => Auth::id(),
                    'approved_by'           => Auth::id(),
                    'reference_no'          => $batchReferenceNo,
                    'supplier_name'         => $validated['supplier_name'] ?? null,
                    'quantity_received'     => $quantityReceived,
                    'empty_received_qty'    => $emptyReceivedQty,
                    'empty_shells_received' => $emptyReceivedQty,
                    'empty_returned_qty'    => $emptyReturnedQty,
                    'unit_cost'             => $unitCost,
                    'filled_qty_before'     => $filledBefore,
                    'filled_qty_after'      => $filledAfter,
                    'empty_qty_before'      => $emptyBefore,
                    'empty_qty_after'       => $emptyAfter,
                    'remarks'               => $validated['remarks'] ?? null,
                ];
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

    /**
     * Void a Stock In record (Strictly restricted to Level 3 Admin Owner).
     */
    public function void(Request $request, StockIn $stockIn)
    {
        if (!Auth::user()?->isOwner()) {
            abort(403, 'Unauthorized action. Only the Admin Owner (Level 3) can void Stock In records.');
        }

        $validated = $request->validate([
            'void_reason' => 'required|string|min:5|max:500',
        ]);

        DB::transaction(function () use ($stockIn, $validated) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            $lockedStockIn = StockIn::lockForUpdate()->findOrFail($stockIn->id);

            if ($lockedStockIn->is_voided) {
                throw ValidationException::withMessages([
                    'void_reason' => 'This Stock In record has already been voided.'
                ]);
            }

            $product = Product::withTrashed()->lockForUpdate()->findOrFail($lockedStockIn->product_id);

            $qtyReceived = (int) $lockedStockIn->quantity_received;
            $emptyReturnedToSupplier = (int) $lockedStockIn->empty_returned_qty;
            $emptyReceived = max((int) ($lockedStockIn->empty_shells_received ?? 0), (int) ($lockedStockIn->empty_received_qty ?? 0));

            $filledBefore = (int) $product->stock_quantity;
            $emptyBefore  = (int) $product->empty_quantity;

            // HARD SAFEGUARD 1: Check filled stock before reversing
            if ($qtyReceived > 0 && $filledBefore < $qtyReceived) {
                throw ValidationException::withMessages([
                    'void_reason' => "Cannot void this Stock In record: reversing {$qtyReceived} filled tank(s) for '{$product->name}' would cause current stock ({$filledBefore}) to drop below 0 because those tanks have already been sold or moved."
                ]);
            }

            // HARD SAFEGUARD 2: Check empty shell stock before reversing empty shells received
            if ($emptyReceived > 0 && $emptyBefore < $emptyReceived) {
                throw ValidationException::withMessages([
                    'void_reason' => "Cannot void this Stock In record: reversing {$emptyReceived} returned empty shell(s) for '{$product->name}' would cause current empty shell stock ({$emptyBefore}) to drop below 0 because those shells have already been moved."
                ]);
            }

            $filledAfter = $filledBefore - $qtyReceived;
            $emptyAfter  = $emptyBefore + $emptyReturnedToSupplier - $emptyReceived;

            // Reverse inventory adjustments
            $product->stock_quantity = $filledAfter;
            $product->empty_quantity = $emptyAfter;
            $product->updated_by = Auth::id();
            $product->save();

            // Mark Stock In as voided (non-destructive)
            $lockedStockIn->is_voided = true;
            $lockedStockIn->voided_at = now();
            $lockedStockIn->voided_by = Auth::id();
            $lockedStockIn->void_reason = $validated['void_reason'];
            $lockedStockIn->save();

            // LINKED HURAM RETURN SAFEGUARD: If linked to a TankLoanReturn, reverse return count on parent TankLoan
            if (Schema::hasTable('tank_loan_returns')) {
                $linkedReturn = TankLoanReturn::where('stock_in_id', $lockedStockIn->id)->lockForUpdate()->first();
                if ($linkedReturn && !$linkedReturn->is_voided) {
                    $tankLoan = TankLoan::lockForUpdate()->find($linkedReturn->tank_loan_id);
                    if ($tankLoan) {
                        $newReturned = max(0, (int) $tankLoan->quantity_returned - (int) $linkedReturn->quantity_returned);
                        $tankLoan->quantity_returned = $newReturned;
                        if ($tankLoan->status !== 'voided') {
                            $tankLoan->status = $newReturned > 0 ? 'partially_returned' : 'borrowed';
                            $tankLoan->returned_at = null;
                        }
                        $tankLoan->save();
                    }

                    $linkedReturn->is_voided = true;
                    $linkedReturn->voided_at = now();
                    $linkedReturn->voided_by = Auth::id();
                    $linkedReturn->void_reason = $validated['void_reason'];
                    $linkedReturn->save();
                }
            }

            $voidRef = 'VOID-' . ($lockedStockIn->reference_no ?? ('SI-' . $lockedStockIn->id));

            // Record compensating reversal in InventoryMovement ledger
            InventoryLedgerService::recordMovement($product, [
                'reference_no'      => $voidRef,
                'movement_type'     => InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'filled_qty_before' => $filledBefore,
                'filled_qty_change' => -$qtyReceived,
                'filled_qty_after'  => $filledAfter,
                'empty_qty_before'  => $emptyBefore,
                'empty_qty_change'  => $emptyReturnedToSupplier - $emptyReceived,
                'empty_qty_after'   => $emptyAfter,
                'unit_cost'         => $lockedStockIn->unit_cost ?? $product->cost_price,
                'unit_price'        => $product->price,
                'source_type'       => 'StockIn',
                'source_id'         => $lockedStockIn->id,
                'user_id'           => Auth::id(),
                'approved_by'       => Auth::id(),
                'reason'            => 'VOID Stock In: ' . $validated['void_reason'],
            ]);

            // Record AuditLog event
            AuditTrailService::record([
                'correlation_id'        => $correlationId,
                'module'                => 'inventory',
                'action'                => 'stock.in.voided',
                'entity_type'           => 'StockIn',
                'entity_id'             => $lockedStockIn->id,
                'transaction_reference' => $lockedStockIn->reference_no ?? $voidRef,
                'status'                => 'voided',
                'approval_status'       => 'approved',
                'approver_user_id'      => Auth::id(),
                'approved_at'           => now(),
                'before_values'         => [
                    'product_id'     => $product->id,
                    'product_name'   => $product->name,
                    'stock_quantity' => $filledBefore,
                    'empty_quantity' => $emptyBefore,
                    'is_voided'      => false,
                ],
                'after_values'          => [
                    'product_id'     => $product->id,
                    'product_name'   => $product->name,
                    'stock_quantity' => $filledAfter,
                    'empty_quantity' => $emptyAfter,
                    'is_voided'      => true,
                ],
                'reason'                => $validated['void_reason'],
            ]);
        });

        return redirect()->route('stock-ins.index')->with('success', 'Stock In record voided and inventory reversed.');
    }
}