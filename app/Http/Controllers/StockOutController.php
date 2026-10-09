<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use App\Models\Product;
use App\Http\Requests\StoreStockOutRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOutController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $search = $request->query('search');
        $query = StockOut::with('product')->latest();

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
        return view('stock_outs.create', compact('products'));
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
            $validated['quantity_removed'] = $quantityRemoved;
            $validated['empty_quantity_removed'] = $emptyQuantityRemoved;
            StockOut::create($validated);

            // 2. Safely deduct from inventory
            $product->stock_quantity -= $quantityRemoved;
            $product->empty_quantity -= $emptyQuantityRemoved;
            $product->save();
        });

        return redirect()->route('stock-outs.index')->with('success', 'Stock adjustment recorded safely.');
    }
}