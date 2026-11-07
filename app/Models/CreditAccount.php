<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditAccount extends Model
{
    use HasFactory;

    public const STATUS_PENDING   = 'pending';
    public const STATUS_APPROVED  = 'approved';
    public const STATUS_REJECTED  = 'rejected';
    public const STATUS_SUSPENDED = 'suspended';
    public const STATUS_CLOSED    = 'closed';

    protected $fillable = [
        'customer_id',
        'credit_limit',
        'agreed_monthly_payment',
        'is_active',
        'status',
        'requested_by',
        'approved_by',
        'approved_at',
        'approval_remarks',
    ];

    /**
     * Improvement #1: Cast types prevent comparison bugs in forms and templates.
     * is_active MUST be boolean — the edit form's == 0 / == 1 check depends on it.
     */
    protected $casts = [
        'credit_limit'           => 'decimal:2',
        'agreed_monthly_payment' => 'decimal:2',
        'approved_at'            => 'datetime',
    ];

    public function setIsActiveAttribute($value): void
    {
        $this->attributes['is_active'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function getIsActiveAttribute($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Filter by the active flag in a driver-safe way.
     * PostgreSQL stores a native boolean (compare with a boolean literal, never an integer);
     * SQLite/MySQL may hold the 'true'/'false' strings written by the mutator.
     */
    public function scopeActive($query, bool $active = true)
    {
        if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'pgsql') {
            return $query->where('is_active', \Illuminate\Support\Facades\DB::raw($active ? 'true' : 'false'));
        }

        return $query->where(function ($q) use ($active) {
            $q->where('is_active', $active ? 'true' : 'false')
              ->orWhere('is_active', $active ? 1 : 0);
        });
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function requester()
    {
        return $this->belongsTo(User::class, 'requested_by')->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    public function ledgers()
    {
        return $this->hasMany(CreditLedger::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function statements()
    {
        return $this->hasMany(StatementOfAccount::class);
    }
    
    // Dynamic Accessor to calculate real-time remaining balance
    public function getRemainingBalanceAttribute()
    {
        $totalCharges = $this->ledgers()->where('transaction_type', 'Charge')->sum('amount');
        $totalPayments = $this->ledgers()->where('transaction_type', 'Payment')->sum('amount');
        
        return $totalCharges - $totalPayments;
    }
}