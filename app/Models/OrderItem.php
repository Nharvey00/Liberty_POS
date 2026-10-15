<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'unit_price',
        'is_swap',
        'residual_kg',
        'actual_consumed_kg',
        'subtotal',
    ];

    /**
     * Note: 'is_swap' is intentionally NOT cast to boolean. The accessor/mutator below
     * stores 'true'/'false' strings for PostgreSQL compatibility; a boolean cast would make
     * Eloquent's dirty check treat both values as true and silently skip the UPDATE.
     */
    protected $casts = [
        'quantity'           => 'integer',
        'unit_price'         => 'decimal:2',
        'residual_kg'        => 'decimal:2',
        'actual_consumed_kg' => 'decimal:2',
        'subtotal'           => 'decimal:2',
    ];

    public function setIsSwapAttribute($value): void
    {
        $this->attributes['is_swap'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function getIsSwapAttribute($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }
}