<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'user_id',
        'total_amount',
        'payment_method',
        'discount_amount',
        'discount_type',
        'senior_id',
        'invoice_number',
        'status',
        'voided_by',
        'voided_at',
        'void_reason',
        'created_at',
    ];

    /**
     * Attribute casts for reliable type handling.
     */
    protected $casts = [
        'voided_at' => 'datetime',
    ];

    /**
     * Check if this order has been voided.
     */
    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * The manager/owner who voided this order.
     */
    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function ledger()
    {
        return $this->hasOne(CreditLedger::class);
    }
}