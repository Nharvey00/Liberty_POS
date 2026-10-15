<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'sku',
        'price',
        'cost_price',
        'new_cylinder_price',
        'stock_quantity',
        'empty_quantity',
        'reorder_level',
        'standard_capacity_kg',
        'created_by',
        'updated_by',
    ];

    protected $appends = ['is_accessory', 'is_cylinder'];

    public function isAccessory(): bool
    {
        return is_null($this->standard_capacity_kg) || (float) $this->standard_capacity_kg <= 0;
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

    public function inventoryMovements()
    {
        return $this->hasMany(InventoryMovement::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by')->withTrashed();
    }
}