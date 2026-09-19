<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    protected $fillable = ['product_id', 'quantity_received', 'empty_returned_qty', 'remarks'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}