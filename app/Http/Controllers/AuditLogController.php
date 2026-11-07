<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\User;
use App\Services\AuditTrailService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditLogController extends Controller
{
    /**
     * One-click category tabs for the Audit Trail (maps a tab to one or more stored modules).
     */
    public const AUDIT_CATEGORIES = [
        'sales'     => ['label' => 'Sales',              'modules' => ['sales']],
        'inventory' => ['label' => 'Inventory',          'modules' => ['inventory']],
        'products'  => ['label' => 'Products',           'modules' => ['products']],
        'credit'    => ['label' => 'Customers & Credit', 'modules' => ['credit_accounts', 'payments', 'statements', 'customers']],
        'admin'     => ['label' => 'Admin & Security',   'modules' => ['users', 'auth', 'audit_trail']],
    ];

    /**
     * One-click movement-group tabs for the Inventory Ledger.
     */
    public const MOVEMENT_GROUPS = [
        'stock_in'   => ['label' => 'Stock In',      'types' => [InventoryMovement::TYPE_STOCK_IN, InventoryMovement::TYPE_INITIAL_STOCK]],
        'sales'      => ['label' => 'Sales',         'types' => [InventoryMovement::TYPE_SALE_OUT]],
        'restocks'   => ['label' => 'Void Restocks', 'types' => [InventoryMovement::TYPE_VOID_RESTOCK, InventoryMovement::TYPE_CUSTOMER_RETURN]],
        'write_offs' => ['label' => 'Write-Offs & Adjustments', 'types' => [
            InventoryMovement::TYPE_DAMAGED_WRITEOFF,
            InventoryMovement::TYPE_EXPIRED_WRITEOFF,
            InventoryMovement::TYPE_LOST_WRITEOFF,
            InventoryMovement::TYPE_SUPPLIER_RETURN,
            InventoryMovement::TYPE_MANUAL_ADJUSTMENT,
        ]],
    ];

    /**
     * Display the centralized Audit Trail with comprehensive server-side filtering.
     */
    public function index(Request $request)
    {
        $query = $this->buildFilteredAuditQuery($request);

        $auditLogs = (clone $query)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $users = User::withTrashed()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $categories = self::AUDIT_CATEGORIES;
        $activeCategory = array_key_exists((string) $request->input('category'), $categories)
            ? $request->input('category')
            : null;

        $hasActiveFilters = $request->hasAny(['search', 'actor_user_id', 'from_date', 'to_date', 'module', 'status', 'action'])
            && collect($request->only(['search', 'actor_user_id', 'from_date', 'to_date', 'module', 'status', 'action']))->filter()->isNotEmpty();

        return view('audit_logs.index', compact(
            'auditLogs',
            'users',
            'categories',
            'activeCategory',
            'hasActiveFilters'
        ));
    }

    /**
     * Display full details and before/after value diffs for a single AuditLog event.
     */
    public function show(AuditLog $auditLog)
    {
        $auditLog->load([
            'actor'    => fn ($q) => $q->withTrashed(),
            'approver' => fn ($q) => $q->withTrashed(),
        ]);

        $relatedEvents = collect();
        if ($auditLog->correlation_id) {
            $relatedEvents = AuditLog::where('correlation_id', $auditLog->correlation_id)
                ->where('id', '!=', $auditLog->id)
                ->orderBy('id')
                ->get();
        }

        $linkedMovements = collect();
        if ($auditLog->transaction_reference) {
            $linkedMovements = InventoryMovement::with([
                'product' => fn ($q) => $q->withTrashed(),
                'user'    => fn ($q) => $q->withTrashed(),
            ])
                ->where('reference_no', $auditLog->transaction_reference)
                ->orderBy('id')
                ->get();
        }

        return view('audit_logs.show', compact('auditLog', 'relatedEvents', 'linkedMovements'));
    }

    /**
     * Export filtered audit trail records as a UTF-8 BOM CSV file and log the export action.
     */
    public function export(Request $request): StreamedResponse
    {
        $query = $this->buildFilteredAuditQuery($request);

        AuditTrailService::record([
            'module'                => 'audit_trail',
            'action'                => 'audit.exported',
            'entity_type'           => 'AuditLog',
            'transaction_reference' => 'AUDIT-EXP-' . now()->format('YmdHis'),
            'status'                => 'completed',
            'after_values'          => [
                'filters' => array_filter($request->only([
                    'from_date',
                    'to_date',
                    'start_date',
                    'end_date',
                    'actor_user_id',
                    'module',
                    'category',
                    'action',
                    'status',
                    'search',
                ])),
            ],
            'reason'                => 'Audit trail exported to CSV for compliance review',
        ]);

        $filename = 'audit_trail_' . Carbon::now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM for Microsoft Excel compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Event UUID',
                'Date & Time',
                'Module',
                'Action',
                'Actor Name',
                'Actor Role',
                'Entity Type',
                'Entity ID',
                'Transaction Reference',
                'Status',
                'Approval Status',
                'Approver',
                'Reason / Justification',
                'IP Address',
                'Previous Values (JSON)',
                'New Values (JSON)',
            ]);

            $query->orderByDesc('created_at')->orderByDesc('id')->chunk(250, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->event_uuid,
                        $log->created_at?->format('Y-m-d H:i:s'),
                        $log->module,
                        $log->action,
                        $log->actor_name_snapshot ?? $log->actor?->name ?? 'System',
                        $log->actor_role_snapshot ?? '—',
                        $log->entity_type ?? '—',
                        $log->entity_id ?? '—',
                        $log->transaction_reference ?? '—',
                        $log->status,
                        $log->approval_status,
                        $log->approver?->name ?? '—',
                        $log->reason ?? '—',
                        $log->ip_address ?? '—',
                        $log->before_values ? json_encode($log->before_values, JSON_UNESCAPED_UNICODE) : '',
                        $log->after_values ? json_encode($log->after_values, JSON_UNESCAPED_UNICODE) : '',
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Display the unified Inventory Movement Ledger with filtering by product, type, user, and date.
     */
    public function inventoryMovements(Request $request)
    {
        $fromDate     = $request->input('from_date', $request->input('start_date'));
        $toDate       = $request->input('to_date', $request->input('end_date'));
        $productId    = $request->input('product_id');
        $movementType = $request->input('movement_type');
        $userId       = $request->input('user_id');
        $search       = $request->input('search');

        $query = InventoryMovement::with([
            'product'  => fn ($q) => $q->withTrashed(),
            'user'     => fn ($q) => $q->withTrashed(),
            'approver' => fn ($q) => $q->withTrashed(),
        ]);

        if ($fromDate) {
            $query->where('created_at', '>=', Carbon::parse($fromDate)->startOfDay());
        }

        if ($toDate) {
            $query->where('created_at', '<=', Carbon::parse($toDate)->endOfDay());
        }

        if ($productId) {
            $query->where('product_id', $productId);
        }

        if ($movementType) {
            $query->where('movement_type', $movementType);
        }

        $groups = self::MOVEMENT_GROUPS;
        $activeGroup = array_key_exists((string) $request->input('group'), $groups) ? $request->input('group') : null;
        if ($activeGroup) {
            $query->whereIn('movement_type', $groups[$activeGroup]['types']);
        }

        if ($userId) {
            $query->where('user_id', $userId);
        }

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('reference_no', $like, "%{$search}%")
                  ->orWhere('reason', $like, "%{$search}%")
                  ->orWhereRaw("CAST(movement_uuid AS TEXT) {$like} ?", ["%{$search}%"])
                  ->orWhereHas('product', function ($pq) use ($search, $like) {
                      $pq->withTrashed()->where(function ($sub) use ($search, $like) {
                          $sub->where('name', $like, "%{$search}%")
                              ->orWhere('sku', $like, "%{$search}%");
                      });
                  });
            });
        }

        $movements = $query
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $products = Product::withTrashed()->orderBy('name')->get();

        $movementTypes = [
            InventoryMovement::TYPE_INITIAL_STOCK     => 'Opening Stock',
            InventoryMovement::TYPE_STOCK_IN          => 'Stock-In Delivery',
            InventoryMovement::TYPE_SALE_OUT          => 'Customer Sale',
            InventoryMovement::TYPE_VOID_RESTOCK      => 'Voided Sale Restock',
            InventoryMovement::TYPE_CUSTOMER_RETURN   => 'Customer Return',
            InventoryMovement::TYPE_DAMAGED_WRITEOFF  => 'Damaged Write-Off',
            InventoryMovement::TYPE_EXPIRED_WRITEOFF  => 'Expired Write-Off',
            InventoryMovement::TYPE_LOST_WRITEOFF     => 'Lost / Missing',
            InventoryMovement::TYPE_SUPPLIER_RETURN   => 'Supplier Return',
            InventoryMovement::TYPE_MANUAL_ADJUSTMENT => 'Manual Adjustment',
        ];

        $hasActiveFilters = collect($request->only(['search', 'product_id', 'from_date', 'to_date', 'user_id', 'movement_type']))
            ->filter()
            ->isNotEmpty();

        return view('inventory_movements.index', compact(
            'movements',
            'products',
            'movementTypes',
            'groups',
            'activeGroup',
            'hasActiveFilters'
        ));
    }

    /**
     * Build the filtered Eloquent query for AuditLog index and CSV export.
     */
    private function buildFilteredAuditQuery(Request $request): Builder
    {
        $fromDate    = $request->input('from_date', $request->input('start_date'));
        $toDate      = $request->input('to_date', $request->input('end_date'));
        $actorUserId = $request->input('actor_user_id');
        $module      = $request->input('module');
        $action      = $request->input('action');
        $status      = $request->input('status');
        $search      = $request->input('search');

        $query = AuditLog::with([
            'actor'    => fn ($q) => $q->withTrashed(),
            'approver' => fn ($q) => $q->withTrashed(),
        ]);

        if ($fromDate) {
            $query->where('created_at', '>=', Carbon::parse($fromDate)->startOfDay());
        }

        if ($toDate) {
            $query->where('created_at', '<=', Carbon::parse($toDate)->endOfDay());
        }

        if ($actorUserId) {
            $query->where('actor_user_id', $actorUserId);
        }

        if ($module) {
            $query->where('module', $module);
        }

        $category = $request->input('category');
        if ($category && array_key_exists($category, self::AUDIT_CATEGORIES)) {
            $query->whereIn('module', self::AUDIT_CATEGORIES[$category]['modules']);
        }

        if ($action) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where('action', $like, "%{$action}%");
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';

            // Allow staff to search by the readable label they see (e.g. "Price Updated").
            $matchingActions = collect(AuditLog::ACTION_LABELS)
                ->filter(fn ($label) => stripos($label, $search) !== false)
                ->keys()
                ->all();

            $query->where(function ($q) use ($search, $like, $matchingActions) {
                $q->where('transaction_reference', $like, "%{$search}%")
                  ->orWhere('actor_name_snapshot', $like, "%{$search}%")
                  ->orWhere('action', $like, "%{$search}%")
                  ->orWhere('reason', $like, "%{$search}%")
                  ->orWhereRaw("CAST(event_uuid AS TEXT) {$like} ?", ["%{$search}%"]);

                if (!empty($matchingActions)) {
                    $q->orWhereIn('action', $matchingActions);
                }

                if (is_numeric($search)) {
                    $q->orWhere('entity_id', (string) (int) $search);
                }
            });
        }

        return $query;
    }
}
