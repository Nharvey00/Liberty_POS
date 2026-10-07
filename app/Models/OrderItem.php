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
        'is_swap',
        'residual_kg',
        'actual_consumed_kg',
        'subtotal',
    ];

    protected $casts = [
        'is_swap'            => 'boolean',
        'quantity'           => 'integer',
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