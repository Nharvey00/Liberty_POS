<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $fillable = ['customer_id', 'user_id', 'total_amount', 'payment_method', 'discount_amount'];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class); // The Cashier
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    public function creditLedgerEntry()
    {
        return $this->hasOne(CreditLedger::class);
    }
}