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
     *
     * Note: 'is_paid' is intentionally NOT cast to boolean. The explicit accessor/mutator
     * below stores 'true'/'false' strings for PostgreSQL compatibility; combining that with a
     * boolean cast makes Eloquent's dirty check compare (bool) 'false' === (bool) 'true'
     * (both true), which silently skips the UPDATE when marking a statement as paid.
     */
    protected $casts = [
        'billing_period_start' => 'date',
        'billing_period_end'   => 'date',
        'total_due'            => 'decimal:2',
    ];

    public function setIsPaidAttribute($value): void
    {
        $this->attributes['is_paid'] = filter_var($value, FILTER_VALIDATE_BOOLEAN) ? 'true' : 'false';
    }

    public function getIsPaidAttribute($value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    public function creditAccount()
    {
        return $this->belongsTo(CreditAccount::class);
    }
}