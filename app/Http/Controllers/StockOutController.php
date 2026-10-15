<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use App\Models\Product;
use App\Models\InventoryMovement;
use App\Http\Requests\StoreStockOutRequest;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOutController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $search = $request->query('search');
        $query = StockOut::with(['product', 'user', 'approver'])->latest();

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('reason', $like, "%{$search}%")
                  ->orWhere('reference_no', $like, "%{$search}%")
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
        return view('stock_outs.create', compact('products'));
    }

    public function store(StoreStockOutRequest $request)
    {
        $validated = $request->validated();

        DB::transaction(function () use ($validated) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            // lockForUpdate prevents race conditions
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            $quantityRemoved = (isset($validated['quantity_removed']) && $validated['quantity_removed'] !== '') ? (int) $validated['quantity_removed'] : 0;
            $emptyQuantityRemoved = $product->isAccessory() 
                ? 0 
                : ((isset($validated['empty_quantity_removed']) && $validated['empty_quantity_removed'] !== '') ? (int) $validated['empty_quantity_removed'] : 0);

            // Security: Prevent negative stock quantities
            if ($quantityRemoved > 0 && $product->stock_quantity < $quantityRemoved) {
                throw ValidationException::withMessages([
                    'quantity_removed' => 'Cannot remove more filled stock than is currently available.'
                ]);
            }

            if ($emptyQuantityRemoved > 0 && $product->empty_quantity < $emptyQuantityRemoved) {
                throw ValidationException::withMessages([
                    'empty_quantity_removed' => 'Cannot remove more empty shells than are currently available.'
                ]);
            }

            $filledBefore = (int) $product->stock_quantity;
            $emptyBefore  = (int) $product->empty_quantity;
            $filledAfter  = $filledBefore - $quantityRemoved;
            $emptyAfter   = $emptyBefore - $emptyQuantityRemoved;

            $referenceNo = !empty($validated['reference_no']) ? trim($validated['reference_no']) : null;

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

            // 1. Log the StockOut record with actor identity and before/after snapshots
            $stockOut = StockOut::create([
                'product_id'             => $product->id,
                'user_id'                => Auth::id(),
                'approved_by'            => Auth::id(),
                'reference_no'           => $referenceNo,
                'movement_category'      => $category,
                'quantity_removed'       => $quantityRemoved,
                'empty_quantity_removed' => $emptyQuantityRemoved,
                'filled_qty_before'      => $filledBefore,
                'filled_qty_after'       => $filledAfter,
                'empty_qty_before'       => $emptyBefore,
                'empty_qty_after'        => $emptyAfter,
                'reason'                 => $reasonText,
            ]);

            // Auto-number from the committed row ID so concurrent requests can never collide
            if ($referenceNo === null) {
                $referenceNo = 'SO-' . date('Ymd') . '-' . str_pad((string) $stockOut->id, 4, '0', STR_PAD_LEFT);
                $stockOut->update(['reference_no' => $referenceNo]);
            }

            // 2. Safely deduct from inventory
            $product->stock_quantity = $filledAfter;
            $product->empty_quantity = $emptyAfter;
            $product->updated_by     = Auth::id();
            $product->save();

            // 3. Record dedicated InventoryMovement ledger entry
            InventoryLedgerService::recordMovement($product, [
                'reference_no'      => $referenceNo,
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

            // 4. Record centralized AuditLog event
            AuditTrailService::record([
                'correlation_id'        => $correlationId,
                'module'                => 'inventory',
                'action'                => 'stock.out',
                'entity_type'           => 'StockOut',
                'entity_id'             => $stockOut->id,
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
        });

        return redirect()->route('stock-outs.index')->with('success', 'Stock adjustment recorded safely.');
    }
}