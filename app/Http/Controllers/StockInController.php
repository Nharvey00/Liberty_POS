<?php

namespace App\Http\Controllers;

use App\Models\StockIn;
use App\Models\Product;
use App\Http\Requests\StoreStockInRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockInController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $search = $request->query('search');
        $query = StockIn::with('product')->latest();

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
}