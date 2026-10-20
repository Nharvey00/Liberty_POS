<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    protected $fillable = [
        'product_id',
        'user_id',
        'approved_by',
        'reference_no',
        'supplier_name',
        'quantity_received',
        'empty_received_qty',
        'empty_returned_qty',
        'unit_cost',
        'filled_qty_before',
        'filled_qty_after',
        'empty_qty_before',
        'empty_qty_after',
        'remarks',
    ];

    protected $casts = [
        'quantity_received'  => 'integer',
        'empty_received_qty' => 'integer',
        'empty_returned_qty' => 'integer',
        'unit_cost'          => 'decimal:2',
        'filled_qty_before'  => 'integer',
        'filled_qty_after'   => 'integer',
        'empty_qty_before'   => 'integer',
        'empty_qty_after'    => 'integer',
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