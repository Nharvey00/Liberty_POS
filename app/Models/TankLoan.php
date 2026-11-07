<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TankLoan extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'product_id',
        'stock_out_id',
        'tank_condition_out',
        'quantity_borrowed',
        'quantity_returned',
        'status',
        'notes',
        'created_by',
        'borrowed_at',
        'returned_at',
        'voided_at',
        'voided_by',
        'void_reason',
    ];

    protected $casts = [
        'quantity_borrowed' => 'integer',
        'quantity_returned' => 'integer',
        'borrowed_at'       => 'datetime',
        'returned_at'       => 'datetime',
        'voided_at'         => 'datetime',
    ];

    protected $appends = [
        'remaining_quantity',
    ];

    public function getRemainingQuantityAttribute(): int
    {
        if ($this->status === 'voided') {
            return 0;
        }
        return max(0, (int) $this->quantity_borrowed - (int) $this->quantity_returned);
    }

    public function isVoided(): bool
    {
        return $this->status === 'voided';
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['borrowed', 'partially_returned'])
                     ->whereColumn('quantity_borrowed', '>', 'quantity_returned');
    }

    public function scopeExcludeVoided($query)
    {
        return $query->where('status', '!=', 'voided');
    }

    public function scopeValid($query)
    {
        return $query->where('status', '!=', 'voided');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class)->withTrashed();
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function stockOut()
    {
        return $this->belongsTo(StockOut::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function createdByUser()
    {
        return $this->belongsTo(User::class, 'created_by')->withTrashed();
    }

    public function voidedByUser()
    {
        return $this->belongsTo(User::class, 'voided_by')->withTrashed();
    }

    public function returns()
    {
        return $this->hasMany(TankLoanReturn::class)->latest('returned_at');
    }
}
