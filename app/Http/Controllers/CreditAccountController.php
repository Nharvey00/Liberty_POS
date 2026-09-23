<?php

namespace App\Http\Controllers;

use App\Models\CreditAccount;
use App\Models\Customer;
use App\Http\Requests\StoreCreditAccountRequest;
use App\Http\Requests\UpdateCreditAccountRequest;

class CreditAccountController extends Controller
{
    /**
     * Display a listing of all credit accounts with live balances.
     */
    public function index()
    {
        $accounts = CreditAccount::with('customer')
            ->orderByDesc('created_at')
            ->paginate(15);

        return view('credit_accounts.index', compact('accounts'));
    }

    /**
     * Show the form for approving a new credit (utang) account.
     * Only customers who do NOT already have a credit account are shown.
     */
    public function create()
    {
        $customers = Customer::whereDoesntHave('creditAccount')
            ->orderBy('name')
            ->get();

        return view('credit_accounts.create', compact('customers'));
    }

    /**
     * Store a newly approved credit account.
     */
    public function store(StoreCreditAccountRequest $request)
    {
        CreditAccount::create($request->validated());

        return redirect()
            ->route('credit-accounts.index')
            ->with('success', 'Credit account approved successfully.');
    }

    /**
     * Display the credit ledger for a specific account.
     * THE LEDGER: Chronological table of all Charges and Payments
     * with a dynamically calculated running balance.
     */
    public function show(CreditAccount $credit_account)
    {
        $credit_account->load(['customer', 'ledgers' => function ($query) {
            $query->with(['order', 'payment'])->orderBy('created_at', 'asc');
        }]);

        // Calculate running balance for each ledger row
        $runningBalance = 0;
        foreach ($credit_account->ledgers as $entry) {
            if ($entry->transaction_type === 'Charge') {
                $runningBalance += $entry->amount;
            } else {
                $runningBalance -= $entry->amount;
            }
            $entry->running_balance = $runningBalance;
        }

        $remainingBalance = $credit_account->remaining_balance;

        return view('credit_accounts.show', compact('credit_account', 'remainingBalance'));
    }

    /**
     * Show the form for editing credit account terms.
     */
    public function edit(CreditAccount $credit_account)
    {
        $credit_account->load('customer');

        return view('credit_accounts.edit', compact('credit_account'));
    }

    /**
     * Update the credit account terms (agreed payment, active status).
     */
    public function update(UpdateCreditAccountRequest $request, CreditAccount $credit_account)
    {
        $credit_account->update($request->validated());

        return redirect()
            ->route('credit-accounts.index')
            ->with('success', 'Credit account updated successfully.');
    }

    /**
     * Remove is not implemented — credit accounts should be deactivated, not deleted.
     */
    public function destroy(CreditAccount $credit_account)
    {
        abort(403, 'Credit accounts cannot be deleted. Deactivate them instead.');
    }
}
