<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\Product;
use App\Models\TankLoan;
use App\Models\TankLoanReturn;
use App\Http\Requests\StoreStockInRequest;
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
        $query = StockIn::with(['product', 'voidedByUser', 'tankLoanReturn.tankLoan.customer'])->latest();

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('remarks', $like, "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search, $like) {
                      $pq->where('name', $like, "%{$search}%");
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
            // lockForUpdate prevents race conditions if multiple managers do stock ins simultaneously
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);
            
            $quantityReceived = (int)$validated['quantity_received'];
            $emptyReturnedQty = $product->isAccessory() 
                ? 0 
                : ((isset($validated['empty_returned_qty']) && $validated['empty_returned_qty'] !== '') ? (int)$validated['empty_returned_qty'] : 0);

            // Security: Prevent emptying more shells than exist
            if ($emptyReturnedQty > 0 && $product->empty_quantity < $emptyReturnedQty) {
                throw ValidationException::withMessages([
                    'empty_returned_qty' => 'Cannot return more empty shells to the supplier than are currently in stock.'
                ]);
            }

            // 1. Log the audit trail
            $validated['quantity_received'] = $quantityReceived;
            $validated['empty_returned_qty'] = $emptyReturnedQty;
            StockIn::create($validated);

            // 2. Adjust real inventory
            $product->stock_quantity += $quantityReceived;
            $product->empty_quantity -= $emptyReturnedQty;
            $product->save();
        });

        return redirect()->route('stock-ins.index')->with('success', 'Delivery logged and inventory updated.');
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
            $lockedStockIn = StockIn::lockForUpdate()->findOrFail($stockIn->id);

            if ($lockedStockIn->is_voided) {
                throw ValidationException::withMessages([
                    'void_reason' => 'This Stock In record has already been voided.'
                ]);
            }

            $product = Product::withTrashed()->lockForUpdate()->findOrFail($lockedStockIn->product_id);

            $qtyReceived = (int) $lockedStockIn->quantity_received;
            $emptyReturnedToSupplier = (int) $lockedStockIn->empty_returned_qty;
            $emptyShellsReceived = (int) ($lockedStockIn->empty_shells_received ?? 0);

            // HARD SAFEGUARD 1: Check filled stock before reversing
            if ($qtyReceived > 0 && $product->stock_quantity < $qtyReceived) {
                throw ValidationException::withMessages([
                    'void_reason' => "Cannot void this Stock In record: reversing {$qtyReceived} filled tank(s) for '{$product->name}' would cause current stock ({$product->stock_quantity}) to drop below 0 because those tanks have already been sold or moved."
                ]);
            }

            // HARD SAFEGUARD 2: Check empty shell stock before reversing Huram empty shell return
            if ($emptyShellsReceived > 0 && $product->empty_quantity < $emptyShellsReceived) {
                throw ValidationException::withMessages([
                    'void_reason' => "Cannot void this Stock In record: reversing {$emptyShellsReceived} returned empty shell(s) for '{$product->name}' would cause current empty shell stock ({$product->empty_quantity}) to drop below 0 because those shells have already been moved."
                ]);
            }

            // Reverse inventory adjustments
            $product->stock_quantity -= $qtyReceived;
            $product->empty_quantity += $emptyReturnedToSupplier;
            $product->empty_quantity -= $emptyShellsReceived;
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
        });

        return redirect()->route('stock-ins.index')->with('success', 'Stock In record voided and inventory reversed.');
    }
}