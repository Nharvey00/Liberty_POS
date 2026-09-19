<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = ['credit_account_id', 'amount'];

    public function creditAccount()
    {
        return $this->belongsTo(CreditAccount::class);
    }

    public function ledgerEntry()
    {
        return $this->hasOne(CreditLedger::class);
    }
}