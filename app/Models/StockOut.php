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
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'quantity_removed'       => 'integer',
        'empty_quantity_removed' => 'integer',
        'filled_qty_before'      => 'integer',
        'filled_qty_after'       => 'integer',
        'empty_qty_before'       => 'integer',
        'empty_qty_after'        => 'integer',
        'voided_at'              => 'datetime',
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

    public function tankLoan()
    {
        return $this->hasOne(TankLoan::class, 'stock_out_id');
    }

    public function movement()
    {
        return $this->hasOne(InventoryMovement::class, 'source_id')
            ->where('source_type', 'StockOut')
            ->oldest('id');
    }

    public function getShortReasonAttribute(): string
    {
        $reason = trim((string) $this->reason);
        if (str_contains($reason, ' — ')) {
            return trim(explode(' — ', $reason, 2)[0]);
        }
        return $reason !== '' ? $reason : 'Adjustment';
    }

    public function getNotesAttribute(): ?string
    {
        if (!empty($this->tankLoan?->notes)) {
            return trim((string) $this->tankLoan->notes);
        }

        $movementReason = trim((string) ($this->movement?->reason ?? ''));
        if ($movementReason !== '') {
            if (str_contains($movementReason, ' — ')) {
                return trim(explode(' — ', $movementReason, 2)[1]);
            }
            if ($movementReason !== trim((string) $this->reason)) {
                return $movementReason;
            }
        }

        $rawReason = trim((string) $this->reason);
        if (str_contains($rawReason, ' — ')) {
            return trim(explode(' — ', $rawReason, 2)[1]);
        }

        return null;
    }
}