<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;

class ProductController extends Controller
{
    public function index(\Illuminate\Http\Request $request)
    {
        $search = $request->query('search');
        $query = Product::orderBy('name');

        if ($search) {
            $query->where('name', 'ilike', "%{$search}%");
        }

        $products = $query->paginate(15)->withQueryString();
        return view('products.index', compact('products', 'search'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        
        $validated['stock_quantity'] = (isset($validated['stock_quantity']) && $validated['stock_quantity'] !== '') ? (int)$validated['stock_quantity'] : 0;
        $validated['empty_quantity'] = (isset($validated['empty_quantity']) && $validated['empty_quantity'] !== '') ? (int)$validated['empty_quantity'] : 0;
        $validated['new_cylinder_price'] = (isset($validated['new_cylinder_price']) && $validated['new_cylinder_price'] !== '') ? $validated['new_cylinder_price'] : null;
        $validated['standard_capacity_kg'] = (isset($validated['standard_capacity_kg']) && $validated['standard_capacity_kg'] !== '') ? $validated['standard_capacity_kg'] : null;

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
        $validated = $request->validated();
        $validated['new_cylinder_price'] = (isset($validated['new_cylinder_price']) && $validated['new_cylinder_price'] !== '') ? $validated['new_cylinder_price'] : null;
        $validated['standard_capacity_kg'] = (isset($validated['standard_capacity_kg']) && $validated['standard_capacity_kg'] !== '') ? $validated['standard_capacity_kg'] : null;

        $product->update($validated);

        return redirect()->route('products.index')->with('success', 'Product details updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->stock_quantity > 0 || $product->empty_quantity > 0) {
            return back()->withErrors('Cannot delete product that still has existing inventory stock or empty shells on hand. Clear inventory first.');
        }

        if ($product->orderItems()->exists()) {
            return back()->withErrors('Cannot delete product that has existing sales order history.');
        }

        if ($product->stockIns()->exists() || $product->stockOuts()->exists()) {
            return back()->withErrors('Cannot delete product that has stock movement history.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }
}