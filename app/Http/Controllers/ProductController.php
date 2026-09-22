<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    public function index()
    {
        $products = Product::orderBy('name')->paginate(15);
        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        
        $validated['stock_quantity'] = $validated['stock_quantity'] ?? 0;
        $validated['empty_quantity'] = $validated['empty_quantity'] ?? 0;

        Product::create($validated);

        return redirect()->route('products.index')->with('success', 'Product registered successfully.');
    }

    public function show(Product $product)
    {
        $product->load([
            'stockIns' => function ($query) { $query->latest()->take(10); }, 
            'stockOuts' => function ($query) { $query->latest()->take(10); }
        ]);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(UpdateProductRequest $request, Product $product)
    {
        // STRICT RULE: UpdateProductRequest explicitly EXCLUDES stock_quantity and empty_quantity
        // This enforces the business rule that stock can only be adjusted via Stock In/Out controllers
        $product->update($request->validated());

        return redirect()->route('products.index')->with('success', 'Product details updated successfully.');
    }
}