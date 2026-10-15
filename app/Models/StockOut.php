<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockOut extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'approved_by',
        'reference_no',
        'movement_category',
        'quantity_removed',
        'empty_quantity_removed',
        'filled_qty_before',
        'filled_qty_after',
        'empty_qty_before',
        'empty_qty_after',
        'reason',
    ];

    protected $casts = [
        'quantity_removed'       => 'integer',
        'empty_quantity_removed' => 'integer',
        'filled_qty_before'      => 'integer',
        'filled_qty_after'       => 'integer',
        'empty_qty_before'       => 'integer',
        'empty_qty_after'        => 'integer',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }
}