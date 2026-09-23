<?php

namespace App\Http\Controllers;

use App\Models\StatementOfAccount;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Http\Requests\StoreStatementRequest;
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
     * Calculates total_due by summing ledger entries within the billing period.
     */
    public function store(StoreStatementRequest $request)
    {
        $validated = $request->validated();

        $start = Carbon::parse($validated['billing_period_start'])->startOfDay();
        $end   = Carbon::parse($validated['billing_period_end'])->endOfDay();

        // Calculate total_due from the ledger: Charges − Payments in the period
        $totalCharges = CreditLedger::where('credit_account_id', $validated['credit_account_id'])
            ->where('transaction_type', 'Charge')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $totalPayments = CreditLedger::where('credit_account_id', $validated['credit_account_id'])
            ->where('transaction_type', 'Payment')
            ->whereBetween('created_at', [$start, $end])
            ->sum('amount');

        $totalDue = max($totalCharges - $totalPayments, 0);

        $statement = StatementOfAccount::create([
            'credit_account_id'    => $validated['credit_account_id'],
            'billing_period_start' => $validated['billing_period_start'],
            'billing_period_end'   => $validated['billing_period_end'],
            'total_due'            => $totalDue,
            'is_paid'              => false,
        ]);

        return redirect()
            ->route('statements.show', $statement)
            ->with('success', 'Statement of Account generated. Total due: ₱' . number_format($totalDue, 2));
    }

    /**
     * Display a printable statement of account with line items from the ledger.
     */
    public function show(StatementOfAccount $statement)
    {
        $statement->load('creditAccount.customer');

        $start = Carbon::parse($statement->billing_period_start)->startOfDay();
        $end   = Carbon::parse($statement->billing_period_end)->endOfDay();

        // Fetch ledger entries within the billing period for this account
        $ledgerEntries = CreditLedger::where('credit_account_id', $statement->credit_account_id)
            ->whereBetween('created_at', [$start, $end])
            ->with(['order', 'payment'])
            ->orderBy('created_at', 'asc')
            ->get();

        return view('statements.show', compact('statement', 'ledgerEntries'));
    }
}
