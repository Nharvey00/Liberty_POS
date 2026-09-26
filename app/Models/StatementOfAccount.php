<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StatementOfAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'credit_account_id',
        'billing_period_start',
        'billing_period_end',
        'total_due',
        'is_paid'
    ];

    /**
     * Improvement #2: Cast types for reliable date handling and boolean comparisons.
     */
    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end'   => 'date',
        'total_due'            => 'decimal:2',
        'is_paid'              => 'boolean',
    ];

    public function creditAccount()
    {
        return $this->belongsTo(CreditAccount::class);
    }
}