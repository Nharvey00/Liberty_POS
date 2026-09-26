<?php

namespace App\Http\Controllers;

use App\Models\CreditAccount;
use App\Models\Payment;
use App\Models\CreditLedger;
use App\Models\StatementOfAccount;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    /**
     * Show the form to record a cash payment against a credit account.
     * Route: GET /credit-accounts/{account}/payments/create
     *
     * Note: Cash payments are permitted regardless of whether is_active is true or false.
     * Suspended accounts are barred from new credit checkouts, but debt settlement is always permitted.
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
     * BUSINESS RULE: Double-entry accounting.
     * Creating a Payment MUST atomically insert a matching row into
     * credit_ledgers with transaction_type = 'Payment'.
     * Both records are created inside a DB::transaction to guarantee consistency.
     */
    public function store(StorePaymentRequest $request, CreditAccount $account)
    {
        $amount = $request->validated('amount');
        $remainingBalance = $account->remaining_balance;

        DB::transaction(function () use ($amount, $account) {
            // 1. Create the payment record
            $payment = Payment::create([
                'credit_account_id' => $account->id,
                'amount'            => $amount,
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

        // Fix #5: Automatically mark unpaid SOAs as paid if the account's remaining balance is cleared (<= 0)
        if ($account->remaining_balance <= 0) {
            StatementOfAccount::where('credit_account_id', $account->id)
                ->where('is_paid', false)
                ->update(['is_paid' => true]);
        }

        $successMessage = 'Payment of ₱' . number_format($amount, 2) . ' recorded successfully.';

        // Soft over-payment note
        if ($amount > $remainingBalance && $remainingBalance > 0) {
            $successMessage .= ' Note: This payment exceeds the outstanding balance. A credit of ₱'
                . number_format($amount - $remainingBalance, 2)
                . ' is now on the account.';
        }

        return redirect()
            ->route('credit-accounts.show', $account)
            ->with('success', $successMessage);
    }
}
