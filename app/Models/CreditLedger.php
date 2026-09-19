<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditLedger extends Model
{
    protected $fillable = [
        'credit_account_id', 
        'transaction_type', 
        'amount', 
        'order_id', 
        'payment_id'
    ];

    public function creditAccount()
    {
        return $this->belongsTo(CreditAccount::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }
}