<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use App\Models\Product;
use App\Models\Customer;
use App\Models\TankLoan;
use App\Http\Requests\StoreStockOutRequest;
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
        $query = StockOut::with(['product', 'voidedByUser', 'tankLoan.customer'])->latest();

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('reason', $like, "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search, $like) {
                      $pq->where('name', $like, "%{$search}%");
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

        DB::transaction(function () use ($validated) {
            // lockForUpdate prevents race conditions
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            $quantityRemoved = (isset($validated['quantity_removed']) && $validated['quantity_removed'] !== '') ? (int)$validated['quantity_removed'] : 0;
            $emptyQuantityRemoved = $product->isAccessory() 
                ? 0 
                : ((isset($validated['empty_quantity_removed']) && $validated['empty_quantity_removed'] !== '') ? (int)$validated['empty_quantity_removed'] : 0);

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

            // 1. Log the audit trail
            $stockOut = StockOut::create([
                'product_id' => $product->id,
                'quantity_removed' => $quantityRemoved,
                'empty_quantity_removed' => $emptyQuantityRemoved,
                'reason' => $validated['reason'],
            ]);

            // 2. Safely deduct from inventory
            $product->stock_quantity -= $quantityRemoved;
            $product->empty_quantity -= $emptyQuantityRemoved;
            $product->save();

            // 3. Entry Point A: If Reason is Loaned Tank (Huram), create linked TankLoan record
            if ($validated['reason'] === 'Loaned Tank (Huram)' && !empty($validated['customer_id']) && Schema::hasTable('tank_loans')) {
                $conditionOut = $validated['tank_condition_out'] ?? ($quantityRemoved > 0 ? 'filled' : 'empty_shell');
                $qtyBorrowed = $conditionOut === 'filled' ? $quantityRemoved : $emptyQuantityRemoved;

                if ($qtyBorrowed > 0) {
                    TankLoan::create([
                        'customer_id' => $validated['customer_id'],
                        'product_id' => $product->id,
                        'stock_out_id' => $stockOut->id,
                        'tank_condition_out' => $conditionOut,
                        'quantity_borrowed' => $qtyBorrowed,
                        'quantity_returned' => 0,
                        'status' => 'borrowed',
                        'notes' => $validated['remarks'] ?? null,
                        'created_by' => Auth::id(),
                        'borrowed_at' => now(),
                    ]);
                }
            }
        });

        return redirect()->route('stock-outs.index')->with('success', 'Stock adjustment recorded safely.');
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

            // Add the originally deducted quantity back to current stock
            $product->stock_quantity += (int) $lockedStockOut->quantity_removed;
            $product->empty_quantity += (int) $lockedStockOut->empty_quantity_removed;
            $product->save();

            // Mark Stock Out as voided (non-destructive)
            $lockedStockOut->is_voided = true;
            $lockedStockOut->voided_at = now();
            $lockedStockOut->voided_by = Auth::id();
            $lockedStockOut->void_reason = $validated['void_reason'];
            $lockedStockOut->save();
        });

        return redirect()->route('stock-outs.index')->with('success', 'Stock Out record voided and inventory restored.');
    }
}