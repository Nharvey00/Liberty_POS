<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CreditAccount extends Model
{
    use HasFactory;

    protected $fillable = ['customer_id', 'agreed_monthly_payment', 'is_active'];

    /**
     * Improvement #1: Cast types prevent comparison bugs in forms and templates.
     * is_active MUST be boolean — the edit form's == 0 / == 1 check depends on it.
     */
    protected $casts = [
        'is_active'              => 'boolean',
        'agreed_monthly_payment' => 'decimal:2',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
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