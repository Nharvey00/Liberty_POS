<?php

namespace App\Services;

use App\Models\InventoryMovement;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class InventoryLedgerService
{
    /**
     * Record an immutable inventory movement entry with before/after stock snapshots.
     *
     * @param  array<string, mixed>  $attributes
     */
    public static function recordMovement(Product $product, array $attributes): InventoryMovement
    {
        $filledBefore = (int) ($attributes['filled_qty_before'] ?? $product->stock_quantity);
        $filledChange = (int) ($attributes['filled_qty_change'] ?? 0);
        $filledAfter  = (int) ($attributes['filled_qty_after'] ?? ($filledBefore + $filledChange));

        $emptyBefore  = (int) ($attributes['empty_qty_before'] ?? $product->empty_quantity);
        $emptyChange  = (int) ($attributes['empty_qty_change'] ?? 0);
        $emptyAfter   = (int) ($attributes['empty_qty_after'] ?? ($emptyBefore + $emptyChange));

        $actorId = $attributes['user_id'] ?? Auth::id();

        return InventoryMovement::create([
            'movement_uuid'         => (string) Str::uuid(),
            'reference_no'          => $attributes['reference_no'] ?? null,
            'product_id'            => $product->id,
            'product_name_snapshot' => $product->name,
            'movement_type'         => $attributes['movement_type'],
            'filled_qty_before'     => $filledBefore,
            'filled_qty_change'     => $filledChange,
            'filled_qty_after'      => $filledAfter,
            'empty_qty_before'      => $emptyBefore,
            'empty_qty_change'      => $emptyChange,
            'empty_qty_after'       => $emptyAfter,
            'unit_cost'             => $attributes['unit_cost'] ?? $product->cost_price ?? null,
            'unit_price'            => $attributes['unit_price'] ?? $product->price ?? null,
            'source_type'           => $attributes['source_type'] ?? null,
            'source_id'             => $attributes['source_id'] ?? null,
            'user_id'               => $actorId,
            'approved_by'           => $attributes['approved_by'] ?? null,
            'reason'                => $attributes['reason'] ?? null,
            'status'                => $attributes['status'] ?? 'completed',
        ]);
    }
}
