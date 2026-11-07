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
        'empty_shells_received',
        'empty_returned_qty',
        'unit_cost',
        'filled_qty_before',
        'filled_qty_after',
        'empty_qty_before',
        'empty_qty_after',
        'remarks',
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'quantity_received'     => 'integer',
        'empty_received_qty'    => 'integer',
        'empty_shells_received' => 'integer',
        'empty_returned_qty'    => 'integer',
        'unit_cost'             => 'decimal:2',
        'filled_qty_before'     => 'integer',
        'filled_qty_after'      => 'integer',
        'empty_qty_before'      => 'integer',
        'empty_qty_after'       => 'integer',
        'voided_at'             => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (StockIn $stockIn) {
            $emptyReceived = max(
                (int) ($stockIn->attributes['empty_received_qty'] ?? 0),
                (int) ($stockIn->attributes['empty_shells_received'] ?? 0)
            );
            $stockIn->attributes['empty_received_qty'] = $emptyReceived;
            $stockIn->attributes['empty_shells_received'] = $emptyReceived;
        });
    }

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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
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