<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = ['name', 'business_name', 'tin_number', 'customer_type', 'phone', 'address'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function creditAccount()
    {
        return $this->hasOne(CreditAccount::class);
    }
}