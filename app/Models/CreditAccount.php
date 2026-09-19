<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditAccount extends Model
{
    protected $fillable = ['customer_id', 'agreed_monthly_payment', 'is_active'];

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