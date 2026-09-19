<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name', 'price', 'new_cylinder_price', 'stock_quantity', 'empty_quantity', 'standard_capacity_kg'];

    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function stockIns()
    {
        return $this->hasMany(StockIn::class);
    }

    public function stockOuts()
    {
        return $this->hasMany(StockOut::class);
    }
}