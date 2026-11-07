<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TankLoanReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'tank_loan_id',
        'stock_in_id',
        'quantity_returned',
        'returned_as',
        'received_by',
        'remarks',
        'notes',
        'returned_at',
        'is_voided',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'quantity_returned' => 'integer',
        'returned_at'       => 'datetime',
        'is_voided'         => 'boolean',
        'voided_at'         => 'datetime',
    ];

    public function setNotesAttribute($value): void
    {
        $this->attributes['remarks'] = $value;
    }

    public function getNotesAttribute(): ?string
    {
        return $this->attributes['remarks'] ?? null;
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
        $query = $driver === 'pgsql'
            ? $query->whereRaw('tank_loan_returns.is_voided = false')
            : $query->where('tank_loan_returns.is_voided', false);

        return $query
            ->whereHas('tankLoan', function ($lq) {
                $lq->where('status', '!=', 'voided');
            })
            ->where(function ($sq) use ($driver) {
                $sq->whereNull('stock_in_id')
                   ->orWhereHas('stockIn', function ($siq) use ($driver) {
                       if ($driver === 'pgsql') {
                           $siq->whereRaw('stock_ins.is_voided = false');
                       } else {
                           $siq->where('stock_ins.is_voided', false);
                       }
                   });
            });
    }

    public function tankLoan()
    {
        return $this->belongsTo(TankLoan::class);
    }

    public function stockIn()
    {
        return $this->belongsTo(StockIn::class);
    }

    public function receivedByUser()
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by')->withTrashed();
    }
}
