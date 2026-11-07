<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockIn extends Model
{
    protected $fillable = [
        'product_id',
        'quantity_received',
        'empty_returned_qty',
        'empty_shells_received',
        'remarks',
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'quantity_received'     => 'integer',
        'empty_returned_qty'    => 'integer',
        'empty_shells_received' => 'integer',
        'is_voided'             => 'boolean',
        'voided_at'             => 'datetime',
    ];

    public function setIsVoidedAttribute($value): void
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        $bool = filter_var($value, FILTER_VALIDATE_BOOLEAN);
        $this->attributes['is_voided'] = $driver === 'pgsql' ? ($bool ? 'true' : 'false') : $bool;
    }

    public function getIsVoidedAttribute($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function scopeValid($query)
    {
        $driver = \Illuminate\Support\Facades\DB::connection()->getDriverName();
        return $driver === 'pgsql'
            ? $query->whereRaw('is_voided = false')
            : $query->where('is_voided', false);
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by')->withTrashed();
    }

    public function tankLoanReturn()
    {
        return $this->hasOne(TankLoanReturn::class, 'stock_in_id');
    }
}