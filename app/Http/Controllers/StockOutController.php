<?php

namespace App\Http\Controllers;

use App\Models\StockOut;
use App\Models\Product;
use App\Http\Requests\StoreStockOutRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockOutController extends Controller
{
    public function index()
    {
        $stockOuts = StockOut::with('product')->latest()->paginate(15);
        return view('stock_outs.index', compact('stockOuts'));
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

            $quantityRemoved = $validated['quantity_removed'] ?? 0;
            $emptyQuantityRemoved = $validated['empty_quantity_removed'] ?? 0;

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
            StockOut::create($validated);

            // 2. Safely deduct from inventory
            $product->stock_quantity -= $quantityRemoved;
            $product->empty_quantity -= $emptyQuantityRemoved;
            $product->save();
        });

        return redirect()->route('stock-outs.index')->with('success', 'Stock adjustment recorded safely.');
    }
}