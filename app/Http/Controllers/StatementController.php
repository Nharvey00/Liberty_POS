<?php

namespace App\Http\Controllers;

use App\Models\StatementOfAccount;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Http\Requests\StoreStatementRequest;
use Illuminate\Http\Request;
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
                $q->where('name', 'like', '%' . $request->customer . '%');
            });
        }

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('is_paid', $request->status === 'paid' ? 'true' : 'false');
        }

        if ($request->filled('from')) {
            $query->where('billing_period_end', '>=', $request->from);
        }

        if ($request->filled('to')) {
            $query->where('billing_period_end', '<=', $request->to);
        }

        if ($request->filled('customer_type')) {
            $query->whereHas('creditAccount.customer', function ($q) use ($request) {
                $q->where('customer_type', $request->customer_type);
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
        
        $accounts = CreditAccount::where('is_active', 'true')->get();
        $generatedCount = 0;

        foreach ($accounts as $account) {
            $totalCharges = CreditLedger::where('credit_account_id', $account->id)
                ->where('transaction_type', 'Charge')
                ->where('created_at', '<=', $end)
                ->sum('amount');

            $totalPayments = CreditLedger::where('credit_account_id', $account->id)
                ->where('transaction_type', 'Payment')
                ->where('created_at', '<=', $end)
                ->sum('amount');

            $totalDue = max($totalCharges - $totalPayments, 0);

            if ($totalDue > 0) {
                StatementOfAccount::create([
                    'credit_account_id'    => $account->id,
                    'billing_period_start' => $validated['billing_period_start'],
                    'billing_period_end'   => $validated['billing_period_end'],
                    'total_due'            => $totalDue,
                    'is_paid'              => false, // Since it's > 0
                ]);
                
                $generatedCount++;
            }
        }

        return redirect()
            ->route('statements.index')
            ->with('success', "Batch generation complete. {$generatedCount} statements generated.");
    }

    /**
     * Show the form for generating a new statement of account.
     */
    public function create()
    {
        $accounts = CreditAccount::with('customer')
            ->where('is_active', 'true')
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

        $end = Carbon::parse($validated['billing_period_end'])->endOfDay();

        // Calculate total_due: ALL Charges − ALL Payments up to billing period end
        $totalCharges = CreditLedger::where('credit_account_id', $validated['credit_account_id'])
            ->where('transaction_type', 'Charge')
            ->where('created_at', '<=', $end)
            ->sum('amount');

        $totalPayments = CreditLedger::where('credit_account_id', $validated['credit_account_id'])
            ->where('transaction_type', 'Payment')
            ->where('created_at', '<=', $end)
            ->sum('amount');

        $totalDue = max($totalCharges - $totalPayments, 0);

        $statement = StatementOfAccount::create([
            'credit_account_id'    => $validated['credit_account_id'],
            'billing_period_start' => $validated['billing_period_start'],
            'billing_period_end'   => $validated['billing_period_end'],
            'total_due'            => $totalDue,
            'is_paid'              => $totalDue == 0, // Auto-mark as paid if balance is zero
        ]);

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

        $previousBalance = max(0, $priorCharges - $priorPayments);

        // Fetch ledger entries within the billing period for line-item display
        $ledgerEntries = CreditLedger::where('credit_account_id', $statement->credit_account_id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['order', 'payment'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('statements.show', compact('statement', 'ledgerEntries', 'previousBalance'));
    }

    /**
     * Fix #5: Toggle or update payment status of a Statement of Account.
     * Route: PATCH/PUT /statements/{statement}
     */
    public function update(Request $request, StatementOfAccount $statement)
    {
        $newStatus = $request->has('is_paid') 
            ? $request->boolean('is_paid') 
            : !$statement->is_paid;

        $statement->update(['is_paid' => $newStatus]);

        return redirect()
            ->route('statements.show', $statement)
            ->with('success', 'Statement #' . $statement->id . ' marked as ' . ($statement->is_paid ? 'Paid' : 'Unpaid') . '.');
    }

    public function destroy(StatementOfAccount $statement)
    {
        abort(403, 'Statements of Account cannot be deleted to preserve financial audit trails.');
    }
}
