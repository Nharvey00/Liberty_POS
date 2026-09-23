<?php

namespace App\Http\Controllers;

use App\Models\CreditAccount;
use App\Models\Payment;
use App\Models\CreditLedger;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Show the form to record a cash payment against a credit account.
     * Route: GET /credit-accounts/{account}/payments/create
     */
    public function create(CreditAccount $account)
    {
        $account->load('customer');
        $remainingBalance = $account->remaining_balance;

        return view('payments.create', compact('account', 'remainingBalance'));
    }

    /**
     * Store a new payment and its corresponding credit ledger entry.
     * Route: POST /credit-accounts/{account}/payments
     *
     * BUSINESS RULE: Double-entry. Creating a Payment MUST atomically
     * insert a matching row into credit_ledgers with transaction_type = 'Payment'.
     */
    public function store(StorePaymentRequest $request, CreditAccount $account)
    {
        DB::transaction(function () use ($request, $account) {
            // 1. Create the payment record
            $payment = Payment::create([
                'credit_account_id' => $account->id,
                'amount'            => $request->validated('amount'),
            ]);

            // 2. Create the corresponding credit ledger entry (double-entry)
            CreditLedger::create([
                'credit_account_id' => $account->id,
                'transaction_type'  => 'Payment',
                'amount'            => $payment->amount,
                'order_id'          => null,
                'payment_id'        => $payment->id,
            ]);
        });

        return redirect()
            ->route('credit-accounts.show', $account)
            ->with('success', 'Payment of ₱' . number_format($request->validated('amount'), 2) . ' recorded successfully.');
    }
}
