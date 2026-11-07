<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_account_id',
        'user_id',
        'amount',
        'payment_method',
        'reference_no',
        'notes',
        'balance_before',
        'balance_after',
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

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function ledgerEntry()
    {
        return $this->hasOne(CreditLedger::class);
    }
}