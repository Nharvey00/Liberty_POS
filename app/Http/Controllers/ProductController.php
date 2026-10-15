<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
use Illuminate\Support\Facades\Auth;
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
        
        $validated['stock_quantity'] = (isset($validated['stock_quantity']) && $validated['stock_quantity'] !== '') ? (int) $validated['stock_quantity'] : 0;
        $validated['empty_quantity'] = (isset($validated['empty_quantity']) && $validated['empty_quantity'] !== '') ? (int) $validated['empty_quantity'] : 0;
        $validated['cost_price'] = (isset($validated['cost_price']) && $validated['cost_price'] !== '') ? (float) $validated['cost_price'] : 0.00;
        $validated['reorder_level'] = (isset($validated['reorder_level']) && $validated['reorder_level'] !== '') ? (int) $validated['reorder_level'] : 5;
        $validated['new_cylinder_price'] = (isset($validated['new_cylinder_price']) && $validated['new_cylinder_price'] !== '') ? $validated['new_cylinder_price'] : null;
        $validated['standard_capacity_kg'] = (isset($validated['standard_capacity_kg']) && $validated['standard_capacity_kg'] !== '') ? $validated['standard_capacity_kg'] : null;
        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();

        DB::transaction(function () use ($validated) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            $product = Product::create($validated);
            $referenceNo = $product->sku ?: ('PRD-' . str_pad((string) $product->id, 4, '0', STR_PAD_LEFT));

            if ($product->stock_quantity > 0 || $product->empty_quantity > 0) {
                InventoryLedgerService::recordMovement($product, [
                    'reference_no'      => $referenceNo,
                    'movement_type'     => InventoryMovement::TYPE_INITIAL_STOCK,
                    'filled_qty_before' => 0,
                    'filled_qty_change' => (int) $product->stock_quantity,
                    'filled_qty_after'  => (int) $product->stock_quantity,
                    'empty_qty_before'  => 0,
                    'empty_qty_change'  => (int) $product->empty_quantity,
                    'empty_qty_after'   => (int) $product->empty_quantity,
                    'unit_cost'         => $product->cost_price,
                    'unit_price'        => $product->price,
                    'source_type'       => 'Product',
                    'source_id'         => $product->id,
                    'user_id'           => Auth::id(),
                    'approved_by'       => Auth::id(),
                    'reason'            => 'Initial stock registered upon product creation',
                ]);
            }

            AuditTrailService::record([
                'correlation_id'        => $correlationId,
                'module'                => 'products',
                'action'                => 'product.created',
                'entity_type'           => 'Product',
                'entity_id'             => $product->id,
                'transaction_reference' => $referenceNo,
                'status'                => 'completed',
                'after_values'          => [
                    'name'                 => $product->name,
                    'sku'                  => $product->sku,
                    'price'                => (float) $product->price,
                    'cost_price'           => (float) $product->cost_price,
                    'new_cylinder_price'   => $product->new_cylinder_price !== null ? (float) $product->new_cylinder_price : null,
                    'standard_capacity_kg' => $product->standard_capacity_kg !== null ? (float) $product->standard_capacity_kg : null,
                    'stock_quantity'       => (int) $product->stock_quantity,
                    'empty_quantity'       => (int) $product->empty_quantity,
                    'reorder_level'        => (int) $product->reorder_level,
                ],
                'reason'                => 'New product registered in catalog',
            ]);
        });

        return redirect()->route('products.index')->with('success', 'Product registered successfully.');
    }

    public function show(Product $product)
    {
        $product->load([
            'stockIns' => function ($query) { $query->with('user')->latest()->take(10); }, 
            'stockOuts' => function ($query) { $query->with('user')->latest()->take(10); },
            'inventoryMovements' => function ($query) { $query->with(['user', 'approver'])->latest()->take(25); },
        ]);

        $productAuditLogs = AuditLog::where('entity_type', 'Product')
            ->where('entity_id', (string) $product->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('products.show', compact('product', 'productAuditLogs'));
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
        if (isset($validated['cost_price']) && $validated['cost_price'] !== '') {
            $validated['cost_price'] = (float) $validated['cost_price'];
        }
        if (isset($validated['reorder_level']) && $validated['reorder_level'] !== '') {
            $validated['reorder_level'] = (int) $validated['reorder_level'];
        }
        $validated['updated_by'] = Auth::id();

        $beforeValues = [
            'name'                 => $product->name,
            'sku'                  => $product->sku,
            'price'                => (float) $product->price,
            'cost_price'           => (float) ($product->cost_price ?? 0),
            'new_cylinder_price'   => $product->new_cylinder_price !== null ? (float) $product->new_cylinder_price : null,
            'standard_capacity_kg' => $product->standard_capacity_kg !== null ? (float) $product->standard_capacity_kg : null,
            'reorder_level'        => (int) ($product->reorder_level ?? 5),
        ];

        $priceChanged = ((float) $product->price !== (float) $validated['price'])
            || ((float) ($product->new_cylinder_price ?? 0) !== (float) ($validated['new_cylinder_price'] ?? 0))
            || (isset($validated['cost_price']) && (float) ($product->cost_price ?? 0) !== (float) $validated['cost_price']);

        $product->update($validated);
        $product->refresh();

        $afterValues = [
            'name'                 => $product->name,
            'sku'                  => $product->sku,
            'price'                => (float) $product->price,
            'cost_price'           => (float) ($product->cost_price ?? 0),
            'new_cylinder_price'   => $product->new_cylinder_price !== null ? (float) $product->new_cylinder_price : null,
            'standard_capacity_kg' => $product->standard_capacity_kg !== null ? (float) $product->standard_capacity_kg : null,
            'reorder_level'        => (int) ($product->reorder_level ?? 5),
        ];

        $referenceNo = $product->sku ?: ('PRD-' . str_pad((string) $product->id, 4, '0', STR_PAD_LEFT));

        AuditTrailService::record([
            'module'                => 'products',
            'action'                => $priceChanged ? 'product.price_changed' : 'product.updated',
            'entity_type'           => 'Product',
            'entity_id'             => $product->id,
            'transaction_reference' => $referenceNo,
            'status'                => 'completed',
            'before_values'         => $beforeValues,
            'after_values'          => $afterValues,
            'reason'                => $priceChanged ? 'Product pricing or cost updated' : 'Product catalog details updated',
        ]);

        return redirect()->route('products.index')->with('success', 'Product details updated successfully.');
    }

    public function destroy(Product $product)
    {
        if (!auth()->user()->isManagerOrOwner()) {
            abort(403, 'Unauthorized action.');
        }

        $referenceNo = $product->sku ?: ('PRD-' . str_pad((string) $product->id, 4, '0', STR_PAD_LEFT));

        $product->delete();

        AuditTrailService::record([
            'module'                => 'products',
            'action'                => 'product.archived',
            'entity_type'           => 'Product',
            'entity_id'             => $product->id,
            'transaction_reference' => $referenceNo,
            'status'                => 'completed',
            'before_values'         => [
                'name'   => $product->name,
                'status' => 'active',
            ],
            'after_values'          => [
                'name'       => $product->name,
                'status'     => 'archived',
                'deleted_at' => optional($product->deleted_at)->toIso8601String(),
            ],
            'reason'                => 'Product archived (soft-deleted) from active catalog',
        ]);

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    public function restore($id)
    {
        if (!auth()->user()->isManagerOrOwner()) {
            abort(403, 'Unauthorized action.');
        }

        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();

        $referenceNo = $product->sku ?: ('PRD-' . str_pad((string) $product->id, 4, '0', STR_PAD_LEFT));

        AuditTrailService::record([
            'module'                => 'products',
            'action'                => 'product.restored',
            'entity_type'           => 'Product',
            'entity_id'             => $product->id,
            'transaction_reference' => $referenceNo,
            'status'                => 'completed',
            'before_values'         => [
                'name'   => $product->name,
                'status' => 'archived',
            ],
            'after_values'          => [
                'name'   => $product->name,
                'status' => 'active',
            ],
            'reason'                => 'Product restored to active catalog',
        ]);

        return redirect()->route('products.index', ['status' => 'deleted'])->with('success', 'Product restored successfully.');
    }
}