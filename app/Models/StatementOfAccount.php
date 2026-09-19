<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StatementOfAccount extends Model
{
    protected $fillable = [
        'credit_account_id', 
        'billing_period_start', 
        'billing_period_end', 
        'total_due', 
        'is_paid'
    ];

    public function creditAccount()
    {
        return $this->belongsTo(CreditAccount::class);
    }
}