<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function searchApi(\Illuminate\Http\Request $request)
    {
        $search = $request->query('query');
        
        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        $products = Product::select('id', 'name')
            ->where('name', $like, "%{$search}%")
            ->take(10)
            ->get()
            ->map(function ($p) {
                return ['id' => $p->id, 'name' => $p->name];
            });
            
        return response()->json($products);
    }

    public function index(\Illuminate\Http\Request $request)
    {
        $search = $request->query('search');
        $query = Product::orderBy('name');

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('name', $like, "%{$search}%");
        }

        if (auth()->check() && auth()->user()->isManagerOrOwner()) {
            if ($request->query('status') === 'deleted') {
                $query->onlyTrashed();
            }
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
        if (!auth()->user()->isManagerOrOwner()) {
            abort(403, 'Unauthorized action.');
        }

        $product->delete();

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function restore($id)
    {
        if (!auth()->user()->isManagerOrOwner()) {
            abort(403, 'Unauthorized action.');
        }

        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        return redirect()->route('products.index', ['status' => 'deleted'])->with('success', 'Product restored successfully.');
    }
}