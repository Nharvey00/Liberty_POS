<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\Product;
use App\Http\Requests\StoreStockInRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockInController extends Controller
{
    public function index()
    {
        $stockIns = StockIn::with('product')->latest()->paginate(15);
        return view('stock_ins.index', compact('stockIns'));
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
            
            $quantityReceived = $validated['quantity_received'];
            $emptyReturnedQty = $validated['empty_returned_qty'] ?? 0;

            // Security: Prevent emptying more shells than exist
            if ($emptyReturnedQty > 0 && $product->empty_quantity < $emptyReturnedQty) {
                throw ValidationException::withMessages([
                    'empty_returned_qty' => 'Cannot return more empty shells to the supplier than are currently in stock.'
                ]);
            }

            // 1. Log the audit trail
            StockIn::create($validated);

            // 2. Adjust real inventory
            $product->stock_quantity += $quantityReceived;
            $product->empty_quantity -= $emptyReturnedQty;
            $product->save();
        });

        return redirect()->route('stock-ins.index')->with('success', 'Delivery logged and inventory updated.');
    }
}