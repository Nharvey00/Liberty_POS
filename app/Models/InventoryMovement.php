<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class InventoryMovement extends Model
{
    use HasFactory;

    public const TYPE_SALE_OUT          = 'sale_out';
    public const TYPE_VOID_RESTOCK      = 'void_restock';
    public const TYPE_CUSTOMER_RETURN   = 'customer_return';
    public const TYPE_STOCK_IN          = 'stock_in';
    public const TYPE_SUPPLIER_RETURN   = 'supplier_return';
    public const TYPE_DAMAGED_WRITEOFF  = 'damaged_writeoff';
    public const TYPE_EXPIRED_WRITEOFF  = 'expired_writeoff';
    public const TYPE_LOST_WRITEOFF     = 'lost_writeoff';
    public const TYPE_MANUAL_ADJUSTMENT = 'manual_adjustment';
    public const TYPE_INITIAL_STOCK     = 'initial_stock';

    protected $fillable = [
        'movement_uuid',
        'reference_no',
        'product_id',
        'product_name_snapshot',
        'movement_type',
        'filled_qty_before',
        'filled_qty_change',
        'filled_qty_after',
        'empty_qty_before',
        'empty_qty_change',
        'empty_qty_after',
        'unit_cost',
        'unit_price',
        'source_type',
        'source_id',
        'user_id',
        'approved_by',
        'reason',
        'status',
    ];

    protected $casts = [
        'filled_qty_before' => 'integer',
        'filled_qty_change' => 'integer',
        'filled_qty_after'  => 'integer',
        'empty_qty_before'  => 'integer',
        'empty_qty_change'  => 'integer',
        'empty_qty_after'   => 'integer',
        'unit_cost'         => 'decimal:2',
        'unit_price'        => 'decimal:2',
    ];

    /**
     * Enforce strict append-only immutability on inventory movement ledger records.
     */
    protected static function booted(): void
    {
        static::creating(function (InventoryMovement $movement) {
            if (empty($movement->movement_uuid)) {
                $movement->movement_uuid = (string) Str::uuid();
            }
        });

        static::updating(function () {
            throw new LogicException('Inventory movement ledger records are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new LogicException('Inventory movement ledger records are immutable and cannot be deleted.');
        });
    }

    public function product()
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function approver()
    {
        return $this->belongsTo(User::class, 'approved_by')->withTrashed();
    }

    /**
     * Human-readable movement type label.
     */
    public function getMovementLabelAttribute(): string
    {
        return match ($this->movement_type) {
            self::TYPE_SALE_OUT          => 'Customer Sale (Out)',
            self::TYPE_VOID_RESTOCK      => 'Voided Sale Restock (In)',
            self::TYPE_STOCK_IN          => 'Supplier Delivery (Stock-In)',
            self::TYPE_SUPPLIER_RETURN   => 'Supplier Return',
            self::TYPE_DAMAGED_WRITEOFF  => 'Damaged Stock Write-Off',
            self::TYPE_EXPIRED_WRITEOFF  => 'Expired Stock Write-Off',
            self::TYPE_LOST_WRITEOFF     => 'Lost / Shrinkage Write-Off',
            self::TYPE_MANUAL_ADJUSTMENT => 'Manual Inventory Adjustment',
            self::TYPE_INITIAL_STOCK     => 'Initial Stock Registration',
            default                      => ucwords(str_replace('_', ' ', (string) $this->movement_type)),
        };
    }
}
