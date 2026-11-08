<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use App\Models\Product;
use App\Models\Customer;
use App\Models\TankLoan;
use App\Models\InventoryMovement;
use App\Http\Requests\StoreStockOutRequest;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class StockOutController extends Controller
{
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = StockOut::with(['product', 'user', 'approver', 'voidedByUser', 'tankLoan.customer', 'movement'])->latest();

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('reason', $like, "%{$search}%")
                  ->orWhere('reference_no', $like, "%{$search}%")
                  ->orWhereHas('movement', function ($mq) use ($search, $like) {
                      $mq->where('reason', $like, "%{$search}%");
                  })
                  ->orWhereHas('product', function ($pq) use ($search, $like) {
                      $pq->withTrashed()->where('name', $like, "%{$search}%");
                  });
            });
        }

        $stockOuts = $query->paginate(15)->withQueryString();
        return view('stock_outs.index', compact('stockOuts', 'search'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get();
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        return view('stock_outs.create', compact('products', 'customers'));
    }

    public function store(StoreStockOutRequest $request)
    {
        $validated = $request->validated();

        $items = isset($validated['items']) && is_array($validated['items'])
            ? $validated['items']
            : [[
                'product_id'             => $validated['product_id'],
                'quantity_removed'       => $validated['quantity_removed'] ?? 0,
                'empty_quantity_removed' => $validated['empty_quantity_removed'] ?? 0,
            ]];

        DB::transaction(function () use ($validated, $items) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            // Lock all involved products in deterministic ascending ID order to prevent deadlocks
            $productIds = collect($items)->pluck('product_id')->unique()->sort()->values();
            Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();

            $sharedReferenceNo = !empty($validated['reference_no']) ? trim($validated['reference_no']) : null;
            $batchReferenceNo  = $sharedReferenceNo;

            $reasonText = trim((string) $validated['reason']);
            if (!empty($validated['remarks'])) {
                $reasonText .= ' — ' . trim((string) $validated['remarks']);
            }

            $lowerReason = strtolower($reasonText);
            $movementType = InventoryMovement::TYPE_MANUAL_ADJUSTMENT;
            $category     = $validated['movement_category'] ?? 'manual_adjustment';

            if (str_contains($lowerReason, 'damage') || str_contains($lowerReason, 'leak')) {
                $movementType = InventoryMovement::TYPE_DAMAGED_WRITEOFF;
                $category     = 'damaged';
            } elseif (str_contains($lowerReason, 'expire')) {
                $movementType = InventoryMovement::TYPE_EXPIRED_WRITEOFF;
                $category     = 'expired';
            } elseif (str_contains($lowerReason, 'lost') || str_contains($lowerReason, 'shrinkage')) {
                $movementType = InventoryMovement::TYPE_LOST_WRITEOFF;
                $category     = 'lost';
            } elseif (str_contains($lowerReason, 'supplier')) {
                $movementType = InventoryMovement::TYPE_SUPPLIER_RETURN;
                $category     = 'supplier_return';
            }

            $isHuram = in_array($validated['reason'], ['Loaned Tank (Huram)', 'Loaned (Huram)'], true);
            $stockOutReason = trim((string) $validated['reason']);

            foreach ($items as $index => $itemData) {
                $product = Product::lockForUpdate()->findOrFail($itemData['product_id']);

                if ($isHuram && $product->isAccessory()) {
                    $errorKey = isset($validated['items']) ? "items.{$index}.product_id" : 'product_id';
                    throw ValidationException::withMessages([
                        $errorKey => 'Only LPG cylinders can be loaned as Huram tanks.',
                    ]);
                }

                $quantityRemoved = (isset($itemData['quantity_removed']) && $itemData['quantity_removed'] !== '') ? (int) $itemData['quantity_removed'] : 0;
                $emptyQuantityRemoved = $product->isAccessory()
                    ? 0
                    : ((isset($itemData['empty_quantity_removed']) && $itemData['empty_quantity_removed'] !== '') ? (int) $itemData['empty_quantity_removed'] : 0);

                if ($isHuram && !empty($validated['tank_condition_out'])) {
                    if ($validated['tank_condition_out'] === 'empty_shell' && $emptyQuantityRemoved === 0 && $quantityRemoved > 0) {
                        $emptyQuantityRemoved = $quantityRemoved;
                        $quantityRemoved = 0;
                    } elseif ($validated['tank_condition_out'] === 'filled' && $quantityRemoved === 0 && $emptyQuantityRemoved > 0) {
                        $quantityRemoved = $emptyQuantityRemoved;
                        $emptyQuantityRemoved = 0;
                    }
                }

                // Security: Prevent negative stock quantities
                if ($quantityRemoved > 0 && $product->stock_quantity < $quantityRemoved) {
                    $errorKey = isset($validated['items']) ? "items.{$index}.quantity_removed" : 'quantity_removed';
                    throw ValidationException::withMessages([
                        $errorKey => "Cannot remove more filled stock for '{$product->name}' than is currently available ({$product->stock_quantity})."
                    ]);
                }

                if ($emptyQuantityRemoved > 0 && $product->empty_quantity < $emptyQuantityRemoved) {
                    $errorKey = isset($validated['items']) ? "items.{$index}.empty_quantity_removed" : 'empty_quantity_removed';
                    throw ValidationException::withMessages([
                        $errorKey => "Cannot remove more empty shells for '{$product->name}' than are currently available ({$product->empty_quantity})."
                    ]);
                }

                $filledBefore = (int) $product->stock_quantity;
                $emptyBefore  = (int) $product->empty_quantity;
                $filledAfter  = $filledBefore - $quantityRemoved;
                $emptyAfter   = $emptyBefore - $emptyQuantityRemoved;

                // 1. Log the StockOut record with actor identity and before/after snapshots
                $stockOut = StockOut::create([
                    'product_id'             => $product->id,
                    'user_id'                => Auth::id(),
                    'approved_by'            => Auth::id(),
                    'reference_no'           => $batchReferenceNo,
                    'movement_category'      => $category,
                    'quantity_removed'       => $quantityRemoved,
                    'empty_quantity_removed' => $emptyQuantityRemoved,
                    'filled_qty_before'      => $filledBefore,
                    'filled_qty_after'       => $filledAfter,
                    'empty_qty_before'       => $emptyBefore,
                    'empty_qty_after'        => $emptyAfter,
                    'reason'                 => $stockOutReason,
                ]);

                // Auto-number from the first committed row ID so all items in the batch share the reference
                if ($batchReferenceNo === null) {
                    $batchReferenceNo = 'SO-' . date('Ymd') . '-' . str_pad((string) $stockOut->id, 4, '0', STR_PAD_LEFT);
                    $stockOut->update(['reference_no' => $batchReferenceNo]);
                }

                // 2. Safely deduct from inventory
                $product->stock_quantity = $filledAfter;
                $product->empty_quantity = $emptyAfter;
                $product->updated_by     = Auth::id();
                $product->save();

                // 3. Entry Point A: If Reason is Loaned Tank (Huram), create linked TankLoan record
                if ($isHuram && !empty($validated['customer_id']) && Schema::hasTable('tank_loans')) {
                    $conditionOut = $validated['tank_condition_out'] ?? ($quantityRemoved > 0 ? 'filled' : 'empty_shell');
                    $qtyBorrowed = $conditionOut === 'filled' ? $quantityRemoved : $emptyQuantityRemoved;

                    if ($qtyBorrowed > 0) {
                        TankLoan::create([
                            'customer_id'        => $validated['customer_id'],
                            'product_id'         => $product->id,
                            'stock_out_id'       => $stockOut->id,
                            'tank_condition_out' => $conditionOut,
                            'quantity_borrowed'  => $qtyBorrowed,
                            'quantity_returned'  => 0,
                            'status'             => 'borrowed',
                            'notes'              => $validated['remarks'] ?? null,
                            'created_by'         => Auth::id(),
                            'borrowed_at'        => now(),
                        ]);
                    }
                }

                // 4. Record dedicated InventoryMovement ledger entry
                InventoryLedgerService::recordMovement($product, [
                    'reference_no'      => $batchReferenceNo,
                    'movement_type'     => $movementType,
                    'filled_qty_before' => $filledBefore,
                    'filled_qty_change' => -$quantityRemoved,
                    'filled_qty_after'  => $filledAfter,
                    'empty_qty_before'  => $emptyBefore,
                    'empty_qty_change'  => -$emptyQuantityRemoved,
                    'empty_qty_after'   => $emptyAfter,
                    'unit_cost'         => $product->cost_price,
                    'unit_price'        => $product->price,
                    'source_type'       => 'StockOut',
                    'source_id'         => $stockOut->id,
                    'user_id'           => Auth::id(),
                    'approved_by'       => Auth::id(),
                    'reason'            => $reasonText,
                ]);

                // 5. Record centralized AuditLog event
                AuditTrailService::record([
                    'correlation_id'        => $correlationId,
                    'module'                => 'inventory',
                    'action'                => 'stock.out',
                    'entity_type'           => 'StockOut',
                    'entity_id'             => $stockOut->id,
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
                        'product_id'             => $product->id,
                        'product_name'           => $product->name,
                        'stock_quantity'         => $filledAfter,
                        'empty_quantity'         => $emptyAfter,
                        'quantity_removed'       => $quantityRemoved,
                        'empty_quantity_removed' => $emptyQuantityRemoved,
                        'movement_category'      => $category,
                    ],
                    'reason'                => $reasonText,
                ]);
            }
        });

        $count = count($items);
        $msg = $count > 1
            ? "Stock adjustment recorded safely for {$count} items."
            : 'Stock adjustment recorded safely.';

        return redirect()->route('stock-outs.index')->with('success', $msg);
    }

    /**
     * Void a Stock Out record (Strictly restricted to Level 3 Admin Owner).
     */
    public function void(Request $request, StockOut $stockOut)
    {
        if (!Auth::user()?->isOwner()) {
            abort(403, 'Unauthorized action. Only the Admin Owner (Level 3) can void Stock Out records.');
        }

        $validated = $request->validate([
            'void_reason' => 'required|string|min:5|max:500',
        ]);

        DB::transaction(function () use ($stockOut, $validated) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            $lockedStockOut = StockOut::lockForUpdate()->findOrFail($stockOut->id);

            if ($lockedStockOut->is_voided) {
                throw ValidationException::withMessages([
                    'void_reason' => 'This Stock Out record has already been voided.'
                ]);
            }

            $product = Product::withTrashed()->lockForUpdate()->findOrFail($lockedStockOut->product_id);

            // LINKED HURAM SAFEGUARD: Check if linked to a Customer Loaned Tank (Huram)
            if (Schema::hasTable('tank_loans')) {
                $linkedLoan = TankLoan::where('stock_out_id', $lockedStockOut->id)->lockForUpdate()->first();
                if ($linkedLoan) {
                    if ((int) $linkedLoan->quantity_returned > 0) {
                        throw ValidationException::withMessages([
                            'void_reason' => "Cannot void this Stock Out record because {$linkedLoan->quantity_returned} loaned tank(s) have already been returned by the customer. Please void the customer's tank return record(s) in Stock In History first."
                        ]);
                    }

                    $linkedLoan->update([
                        'status' => 'voided',
                        'voided_at' => now(),
                        'voided_by' => Auth::id(),
                        'void_reason' => $validated['void_reason'],
                    ]);
                }
            }

            $qtyRemoved   = (int) $lockedStockOut->quantity_removed;
            $emptyRemoved = (int) $lockedStockOut->empty_quantity_removed;

            $filledBefore = (int) $product->stock_quantity;
            $emptyBefore  = (int) $product->empty_quantity;
            $filledAfter  = $filledBefore + $qtyRemoved;
            $emptyAfter   = $emptyBefore + $emptyRemoved;

            // Add the originally deducted quantity back to current stock
            $product->stock_quantity = $filledAfter;
            $product->empty_quantity = $emptyAfter;
            $product->updated_by = Auth::id();
            $product->save();

            // Mark Stock Out as voided (non-destructive)
            $lockedStockOut->is_voided = true;
            $lockedStockOut->voided_at = now();
            $lockedStockOut->voided_by = Auth::id();
            $lockedStockOut->void_reason = $validated['void_reason'];
            $lockedStockOut->save();

            $voidRef = 'VOID-' . ($lockedStockOut->reference_no ?? ('SO-' . $lockedStockOut->id));

            // Record compensating reversal in InventoryMovement ledger
            InventoryLedgerService::recordMovement($product, [
                'reference_no'      => $voidRef,
                'movement_type'     => InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
                'filled_qty_before' => $filledBefore,
                'filled_qty_change' => $qtyRemoved,
                'filled_qty_after'  => $filledAfter,
                'empty_qty_before'  => $emptyBefore,
                'empty_qty_change'  => $emptyRemoved,
                'empty_qty_after'   => $emptyAfter,
                'unit_cost'         => $product->cost_price,
                'unit_price'        => $product->price,
                'source_type'       => 'StockOut',
                'source_id'         => $lockedStockOut->id,
                'user_id'           => Auth::id(),
                'approved_by'       => Auth::id(),
                'reason'            => 'VOID Stock Out: ' . $validated['void_reason'],
            ]);

            // Record AuditLog event
            AuditTrailService::record([
                'correlation_id'        => $correlationId,
                'module'                => 'inventory',
                'action'                => 'stock.out.voided',
                'entity_type'           => 'StockOut',
                'entity_id'             => $lockedStockOut->id,
                'transaction_reference' => $lockedStockOut->reference_no ?? $voidRef,
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

        return redirect()->route('stock-outs.index')->with('success', 'Stock Out record voided and inventory restored.');
    }
}