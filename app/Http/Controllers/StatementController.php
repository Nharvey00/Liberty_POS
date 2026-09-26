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
    public function index()
    {
        $statements = StatementOfAccount::with('creditAccount.customer')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('statements.index', compact('statements'));
    }

    /**
     * Show the form for generating a new statement of account.
     */
    public function create()
    {
        $accounts = CreditAccount::with('customer')
            ->where('is_active', true)
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
}
