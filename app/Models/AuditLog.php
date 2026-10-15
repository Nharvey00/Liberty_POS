<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use LogicException;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_uuid',
        'correlation_id',
        'actor_user_id',
        'actor_name_snapshot',
        'actor_role_snapshot',
        'module',
        'action',
        'entity_type',
        'entity_id',
        'transaction_reference',
        'status',
        'approval_status',
        'approver_user_id',
        'approver_name_snapshot',
        'approved_at',
        'before_values',
        'after_values',
        'reason',
        'ip_address',
        'user_agent',
        'source_channel',
    ];

    protected $casts = [
        'before_values' => 'array',
        'after_values'  => 'array',
        'approved_at'   => 'datetime',
    ];

    /**
     * Enforce strict append-only immutability on audit trail records.
     */
    protected static function booted(): void
    {
        static::creating(function (AuditLog $log) {
            if (empty($log->event_uuid)) {
                $log->event_uuid = (string) Str::uuid();
            }
        });

        static::updating(function () {
            throw new LogicException('Audit trail records are immutable and cannot be modified.');
        });

        static::deleting(function () {
            throw new LogicException('Audit trail records are immutable and cannot be deleted.');
        });
    }

    /**
     * The authenticated staff member who performed the action.
     */
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }

    /**
     * The manager or owner who approved the action, if applicable.
     */
    public function approver()
    {
        return $this->belongsTo(User::class, 'approver_user_id')->withTrashed();
    }

    /*
    |--------------------------------------------------------------------------
    | Presentation Helpers (read-only; never alter stored audit data)
    |--------------------------------------------------------------------------
    */

    /** Human-readable labels for stored action codes. */
    public const ACTION_LABELS = [
        'sale.completed'                       => 'Sale Completed',
        'sale.voided'                          => 'Sale Voided',
        'stock.in'                             => 'Stock-In Recorded',
        'stock.out'                            => 'Stock-Out Recorded',
        'product.created'                      => 'Product Registered',
        'product.updated'                      => 'Product Details Updated',
        'product.price_changed'                => 'Product Price Updated',
        'product.archived'                     => 'Product Archived',
        'product.restored'                     => 'Product Restored',
        'credit_account.application_submitted' => 'Credit Application Submitted',
        'credit_account.approved'              => 'Credit Account Approved',
        'credit_account.limit_changed'         => 'Credit Limit Updated',
        'credit_account.suspended'             => 'Credit Account Suspended',
        'credit_account.rejected'              => 'Credit Application Rejected',
        'credit_account.updated'               => 'Credit Terms Updated',
        'payment.recorded'                     => 'Payment Collected',
        'statement.generated'                  => 'Statement of Account Generated',
        'statement.marked_paid'                => 'Statement Marked as Paid',
        'statement.marked_unpaid'              => 'Statement Marked as Unpaid',
        'customer.created'                     => 'Customer Added',
        'customer.updated'                     => 'Customer Profile Updated',
        'customer.archived'                    => 'Customer Archived',
        'user.created'                         => 'Staff Account Created',
        'user.updated'                         => 'Staff Account Updated',
        'user.role_changed'                    => 'Staff Role Updated',
        'user.archived'                        => 'Staff Account Archived',
        'auth.login'                           => 'Signed In',
        'auth.logout'                          => 'Signed Out',
        'auth.failed'                          => 'Failed Sign-In Attempt',
        'auth.password_reset'                  => 'Password Reset',
        'audit.exported'                       => 'Audit Trail Exported',
    ];

    /** Human-readable labels for stored module keys. */
    public const MODULE_LABELS = [
        'sales'           => 'Sales',
        'inventory'       => 'Inventory',
        'products'        => 'Products',
        'credit_accounts' => 'Credit Accounts',
        'payments'        => 'Payments',
        'statements'      => 'Statements of Account',
        'customers'       => 'Customers',
        'users'           => 'Staff Accounts',
        'auth'            => 'Sign-In & Security',
        'audit_trail'     => 'Audit Compliance',
    ];

    /** Friendly labels for recorded field keys (fallback: Str::headline). */
    private const FIELD_LABELS = [
        'price'                  => 'Refill Price',
        'new_cylinder_price'     => 'New Cylinder Price',
        'cost_price'             => 'Cost Price',
        'stock_quantity'         => 'Filled Stock',
        'empty_quantity'         => 'Empty Shells',
        'reorder_level'          => 'Reorder Level',
        'standard_capacity_kg'   => 'Standard Capacity (kg)',
        'sku'                    => 'SKU',
        'credit_limit'           => 'Credit Limit',
        'agreed_monthly_payment' => 'Agreed Monthly Payment',
        'is_active'              => 'Active',
        'remaining_balance'      => 'Outstanding Balance',
        'role_name'              => 'Role',
        'original_cashier_id'    => 'Original Cashier (User ID)',
        'approved_by'            => 'Approved By (User ID)',
        'requested_by'           => 'Requested By (User ID)',
        'received_by'            => 'Received By (User ID)',
        'is_paid'                => 'Paid',
        'total_due'              => 'Total Due',
        'billing_period_start'   => 'Billing Period Start',
        'billing_period_end'     => 'Billing Period End',
    ];

    /** Field keys rendered as Philippine Peso amounts. */
    private const MONEY_FIELDS = [
        'price', 'new_cylinder_price', 'cost_price', 'credit_limit', 'agreed_monthly_payment',
        'remaining_balance', 'amount', 'total_amount', 'discount_amount', 'unit_price',
        'unit_cost', 'subtotal', 'balance_before', 'balance_after', 'total_due',
    ];

    /** Internal foreign keys that add no value next to human-readable names. */
    private const HIDDEN_FIELDS = [
        'id', 'customer_id', 'product_id', 'credit_account_id', 'payment_id', 'order_id',
    ];

    public function getActionLabelAttribute(): string
    {
        return self::ACTION_LABELS[$this->action] ?? Str::headline(str_replace('.', ' ', (string) $this->action));
    }

    public function getModuleLabelAttribute(): string
    {
        return self::MODULE_LABELS[$this->module] ?? Str::headline((string) $this->module);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'pending' => 'Pending Approval',
            default   => Str::headline((string) ($this->status ?: 'completed')),
        };
    }

    /** Semantic text colour for non-interactive, text-only status indicators. */
    public function getStatusColorClassAttribute(): string
    {
        return match ($this->status) {
            'completed'                    => 'text-[#1E8E5A]',
            'pending'                      => 'text-[#B4700A]',
            'voided', 'failed', 'rejected' => 'text-[#B5504B]',
            default                        => 'text-[#5B6472]',
        };
    }

    public static function formatFieldLabel(string $key): string
    {
        return self::FIELD_LABELS[$key] ?? Str::headline($key);
    }

    public static function formatFieldValue(string $key, mixed $value): string
    {
        if ($value === null || $value === '') {
            return '—';
        }

        if (is_bool($value)) {
            return $value ? 'Yes' : 'No';
        }

        if (is_array($value)) {
            return self::formatArrayValue($value);
        }

        if (in_array($key, self::MONEY_FIELDS, true) && is_numeric($value)) {
            return '₱' . number_format((float) $value, 2);
        }

        return (string) $value;
    }

    /**
     * Build display rows for the "Changes Recorded" table.
     *
     * @return array<int, array{label: string, before: string, after: string, changed: bool}>
     */
    public function changeRows(): array
    {
        $before = is_array($this->before_values) ? $this->before_values : [];
        $after  = is_array($this->after_values) ? $this->after_values : [];
        $keys   = array_values(array_unique(array_merge(array_keys($before), array_keys($after))));

        $rows = [];
        foreach ($keys as $key) {
            if (in_array($key, self::HIDDEN_FIELDS, true)) {
                continue;
            }

            $oldDisplay = array_key_exists($key, $before) ? self::formatFieldValue($key, $before[$key]) : '—';
            $newDisplay = array_key_exists($key, $after) ? self::formatFieldValue($key, $after[$key]) : '—';

            $rows[] = [
                'label'   => self::formatFieldLabel($key),
                'before'  => $oldDisplay,
                'after'   => $newDisplay,
                'changed' => array_key_exists($key, $before) && array_key_exists($key, $after) && $oldDisplay !== $newDisplay,
            ];
        }

        return $rows;
    }

    /** True when the event only captured resulting values (e.g. a creation event). */
    public function hasPreviousValues(): bool
    {
        return is_array($this->before_values) && count($this->before_values) > 0;
    }

    private static function formatArrayValue(array $value): string
    {
        if ($value === []) {
            return '—';
        }

        $lines = [];
        foreach ($value as $itemKey => $item) {
            if (is_array($item)) {
                $parts = [];
                foreach ($item as $k => $v) {
                    if (in_array($k, self::HIDDEN_FIELDS, true) || is_array($v)) {
                        continue;
                    }
                    $parts[] = self::formatFieldLabel((string) $k) . ': ' . self::formatFieldValue((string) $k, $v);
                }
                $lines[] = implode(' · ', $parts);
            } else {
                $lines[] = is_int($itemKey)
                    ? self::formatFieldValue('', $item)
                    : self::formatFieldLabel((string) $itemKey) . ': ' . self::formatFieldValue((string) $itemKey, $item);
            }
        }

        return implode("\n", $lines);
    }
}
