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
        'payment_id',
        'user_id',
        'balance_before',
        'balance_after',
        'notes',
        'created_at',
    ];

    protected $casts = [
        'amount'         => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after'  => 'decimal:2',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }
}