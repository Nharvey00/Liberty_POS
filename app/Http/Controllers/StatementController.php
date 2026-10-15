<?php

namespace App\Http\Controllers;

use App\Models\StatementOfAccount;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Http\Requests\StoreStatementRequest;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StatementController extends Controller
{
    /**
     * Display a listing of all generated statements of account.
     */
    public function index(Request $request)
    {
        $query = StatementOfAccount::with('creditAccount.customer')
            ->orderByDesc('created_at');

        if ($request->filled('customer')) {
            $query->whereHas('creditAccount.customer', function ($q) use ($request) {
                $q->search($request->customer);
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $val = $request->status === 'paid';
            $query->where(function ($q) use ($val) {
                if (DB::connection()->getDriverName() === 'pgsql') {
                    $q->where('is_paid', $val ? DB::raw('true') : DB::raw('false'));
                } else {
                    $q->where('is_paid', $val ? 'true' : 'false')
                      ->orWhere('is_paid', $val);
                }
            });
        }

        if ($request->filled('from')) {
            $query->where('billing_period_end', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('billing_period_end', '<=', $request->to);
        }

        if ($request->filled('customer_type')) {
            $query->whereHas('creditAccount.customer', function ($q) use ($request) {
                if ($request->customer_type === 'Coke (Residual)') {
                    $q->whereIn('customer_type', ['Coke (Residual)', 'Company', 'Corporate']);
                } else {
                    $q->where('customer_type', $request->customer_type);
                }
            });
        }

        $statements = $query->paginate(15)->withQueryString();

        return view('statements.index', compact('statements'));
    }

    /**
     * Batch generate statements for all active accounts.
     */
    public function storeBatch(Request $request)
    {
        $validated = $request->validate([
            'billing_period_start' => 'required|date',
            'billing_period_end'   => 'required|date|after_or_equal:billing_period_start',
        ]);

        $end = Carbon::parse($validated['billing_period_end'])->endOfDay();

        [$generatedCount, $skippedCount] = DB::transaction(function () use ($validated, $end) {
            $generated = 0;
            $skipped   = 0;

            foreach (CreditAccount::active()->get() as $account) {
                // Never issue a second statement for the same account and billing period
                if ($this->statementExistsFor($account->id, $validated['billing_period_start'], $validated['billing_period_end'])) {
                    $skipped++;
                    continue;
                }

                $totalDue = $this->outstandingBalanceAsOf($account->id, $end);

                if ($totalDue > 0) {
                    $statement = StatementOfAccount::create([
                        'credit_account_id'    => $account->id,
                        'billing_period_start' => $validated['billing_period_start'],
                        'billing_period_end'   => $validated['billing_period_end'],
                        'total_due'            => $totalDue,
                        'is_paid'              => false, // Since it's > 0
                    ]);

                    $this->recordStatementGenerated($statement, 'Statement generated in batch run');
                    $generated++;
                }
            }

            return [$generated, $skipped];
        });

        $message = "Batch generation complete. {$generatedCount} statements generated.";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} account(s) already had a statement for this period and were skipped.";
        }

        return redirect()
            ->route('statements.index')
            ->with('success', $message);
    }

    /**
     * Show the form for generating a new statement of account.
     */
    public function create()
    {
        $accounts = CreditAccount::with('customer')
            ->active()
            ->orderBy('created_at')
            ->get();

        return view('statements.create', compact('accounts'));
    }

    /**
     * Generate a new statement of account.
     *
     * BUG FIX:
     * The `total_due` on a Statement of Account reflects the true cumulative balance
     * as of the billing period end (<= $end).
     */
    public function store(StoreStatementRequest $request)
    {
        $validated = $request->validated();

        // Prevent duplicate statements for the same account and billing period
        $existing = StatementOfAccount::where('credit_account_id', $validated['credit_account_id'])
            ->whereDate('billing_period_start', $validated['billing_period_start'])
            ->whereDate('billing_period_end', $validated['billing_period_end'])
            ->first();

        if ($existing) {
            return redirect()
                ->route('statements.show', $existing)
                ->with('success', 'A statement for this account and billing period already exists. Showing the existing statement.');
        }

        $end = Carbon::parse($validated['billing_period_end'])->endOfDay();

        // Calculate total_due: ALL Charges − ALL Payments up to billing period end
        $totalDue = $this->outstandingBalanceAsOf($validated['credit_account_id'], $end);

        $statement = DB::transaction(function () use ($validated, $totalDue) {
            $statement = StatementOfAccount::create([
                'credit_account_id'    => $validated['credit_account_id'],
                'billing_period_start' => $validated['billing_period_start'],
                'billing_period_end'   => $validated['billing_period_end'],
                'total_due'            => $totalDue,
                'is_paid'              => (bool)($totalDue == 0), // Auto-mark as paid if balance is zero
            ]);

            $this->recordStatementGenerated($statement, 'Statement of Account generated');

            return $statement;
        });

        return redirect()
            ->route('statements.show', $statement)
            ->with('success', 'Statement of Account generated. Total outstanding balance: ₱' . number_format($totalDue, 2));
    }

    /**
     * Display a printable statement of account with line items and previous balance.
     */
    public function show(StatementOfAccount $statement)
    {
        $statement->load('creditAccount.customer');

        $start = Carbon::parse($statement->billing_period_start)->startOfDay();
        $end   = Carbon::parse($statement->billing_period_end)->endOfDay();

        // Fix #5: Calculate previous balance brought forward prior to billing_period_start
        $priorCharges = CreditLedger::where('credit_account_id', $statement->credit_account_id)
            ->where('transaction_type', 'Charge')
            ->where('created_at', '<', $start)
            ->sum('amount');

        $priorPayments = CreditLedger::where('credit_account_id', $statement->credit_account_id)
            ->where('transaction_type', 'Payment')
            ->where('created_at', '<', $start)
            ->sum('amount');

        $previousBalance = $priorCharges - $priorPayments;

        // Fetch ledger entries within the billing period for line-item display
        $ledgerEntries = CreditLedger::where('credit_account_id', $statement->credit_account_id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['order', 'payment'])
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->get();

        return view('statements.show', compact('statement', 'ledgerEntries', 'previousBalance'));
    }

    /**
     * Fix #5: Toggle or update payment status of a Statement of Account.
     * Route: PATCH/PUT /statements/{statement}
     */
    public function update(Request $request, StatementOfAccount $statement)
    {
        $wasPaid = (bool) $statement->is_paid;
        $newStatus = $request->has('is_paid') 
            ? $request->boolean('is_paid') 
            : !$wasPaid;

        $statement->update(['is_paid' => (bool)$newStatus]);
        $statement->refresh();

        if ($wasPaid !== (bool) $statement->is_paid) {
            AuditTrailService::record([
                'module'                => 'statements',
                'action'                => $statement->is_paid ? 'statement.marked_paid' : 'statement.marked_unpaid',
                'entity_type'           => 'StatementOfAccount',
                'entity_id'             => $statement->id,
                'transaction_reference' => $this->statementReference($statement),
                'status'                => 'completed',
                'before_values'         => ['is_paid' => $wasPaid],
                'after_values'          => ['is_paid' => (bool) $statement->is_paid],
                'reason'                => 'Statement payment status updated manually',
            ]);
        }

        return redirect()
            ->route('statements.show', $statement)
            ->with('success', 'Statement #' . $statement->id . ' marked as ' . ($statement->is_paid ? 'Paid' : 'Unpaid') . '.');
    }

    public function destroy(StatementOfAccount $statement)
    {
        abort(403, 'Statements of Account cannot be deleted to preserve financial audit trails.');
    }

    /**
     * Cumulative outstanding balance (all charges minus all payments) as of the given moment.
     */
    private function outstandingBalanceAsOf(int|string $creditAccountId, Carbon $end): float
    {
        $totalCharges = CreditLedger::where('credit_account_id', $creditAccountId)
            ->where('transaction_type', 'Charge')
            ->where('created_at', '<=', $end)
            ->sum('amount');

        $totalPayments = CreditLedger::where('credit_account_id', $creditAccountId)
            ->where('transaction_type', 'Payment')
            ->where('created_at', '<=', $end)
            ->sum('amount');

        return (float) max($totalCharges - $totalPayments, 0);
    }

    private function statementExistsFor(int|string $creditAccountId, string $periodStart, string $periodEnd): bool
    {
        return StatementOfAccount::where('credit_account_id', $creditAccountId)
            ->whereDate('billing_period_start', $periodStart)
            ->whereDate('billing_period_end', $periodEnd)
            ->exists();
    }

    private function statementReference(StatementOfAccount $statement): string
    {
        return 'SOA-' . str_pad((string) $statement->id, 5, '0', STR_PAD_LEFT);
    }

    private function recordStatementGenerated(StatementOfAccount $statement, string $reason): void
    {
        AuditTrailService::record([
            'module'                => 'statements',
            'action'                => 'statement.generated',
            'entity_type'           => 'StatementOfAccount',
            'entity_id'             => $statement->id,
            'transaction_reference' => $this->statementReference($statement),
            'status'                => 'completed',
            'after_values'          => [
                'credit_account_id'    => $statement->credit_account_id,
                'billing_period_start' => Carbon::parse($statement->billing_period_start)->toDateString(),
                'billing_period_end'   => Carbon::parse($statement->billing_period_end)->toDateString(),
                'total_due'            => (float) $statement->total_due,
                'is_paid'              => (bool) $statement->is_paid,
            ],
            'reason'                => $reason,
        ]);
    }
}
