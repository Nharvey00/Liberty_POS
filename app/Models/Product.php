<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = ['name', 'price', 'new_cylinder_price', 'stock_quantity', 'empty_quantity', 'standard_capacity_kg'];

    protected $appends = ['is_accessory', 'is_cylinder'];

    public function isAccessory(): bool
    {
        return is_null($this->standard_capacity_kg);
    }

    public function isCylinder(): bool
    {
        return !$this->isAccessory();
    }

    public function getIsAccessoryAttribute(): bool
    {
        return $this->isAccessory();
    }

    public function getIsCylinderAttribute(): bool
    {
        return $this->isCylinder();
    }

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