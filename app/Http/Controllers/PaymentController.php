<?php

namespace App\Http\Controllers;

use App\Models\CreditAccount;
use App\Models\Payment;
use App\Models\CreditLedger;
use App\Models\StatementOfAccount;
use App\Http\Requests\StorePaymentRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        $priorRemainingBalance = 0;

        DB::transaction(function () use ($amount, $account, &$priorRemainingBalance) {
            $lockedAccount = CreditAccount::lockForUpdate()->findOrFail($account->id);
            $priorRemainingBalance = $lockedAccount->remaining_balance;

            // 1. Create the payment record
            $payment = Payment::create([
                'credit_account_id' => $lockedAccount->id,
                'amount'            => $amount,
            ]);

            // 2. Create the corresponding credit ledger entry (double-entry)
            CreditLedger::create([
                'credit_account_id' => $lockedAccount->id,
                'transaction_type'  => 'Payment',
                'amount'            => $payment->amount,
                'order_id'          => null,
                'payment_id'        => $payment->id,
            ]);

            // Fix #5: Automatically mark unpaid SOAs as paid if the account's remaining balance is cleared (<= 0)
            if ($lockedAccount->remaining_balance <= 0) {
                StatementOfAccount::where('credit_account_id', $lockedAccount->id)
                    ->where(function ($q) {
                        $q->where('is_paid', 'false')
                          ->orWhere('is_paid', false);
                    })
                    ->get()
                    ->each(function ($soa) {
                        $soa->is_paid = true;
                        $soa->save();
                    });
            }
        });

        $successMessage = 'Payment of ₱' . number_format($amount, 2) . ' recorded successfully.';

        // Soft over-payment note
        if ($amount > $priorRemainingBalance && $priorRemainingBalance > 0) {
            $successMessage .= ' Note: This payment exceeds the outstanding balance. A credit of ₱'
                . number_format($amount - $priorRemainingBalance, 2)
                . ' is now on the account.';
        }

        if (Auth::user()->isManagerOrOwner()) {
            return redirect()
                ->route('credit-accounts.show', $account)
                ->with('success', $successMessage);
        }

        return redirect()
            ->route('pos.create')
            ->with('success', $successMessage);
    }
}
