<?php

namespace App\Http\Controllers;

use App\Models\CreditAccount;
use App\Models\Payment;
use App\Models\CreditLedger;
use App\Models\StatementOfAccount;
use App\Http\Requests\StorePaymentRequest;
use App\Services\AuditTrailService;
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
        $validated = $request->validated();
        $amount = (float) $validated['amount'];
        $paymentMethod = $validated['payment_method'] ?? 'Cash';
        $notes = $validated['notes'] ?? null;
        $inputReferenceNo = $validated['reference_no'] ?? null;
        $priorRemainingBalance = 0;

        DB::transaction(function () use ($amount, $paymentMethod, $notes, $inputReferenceNo, $account, &$priorRemainingBalance) {
            AuditTrailService::resetCorrelationId();
            $correlationId = AuditTrailService::correlationId();

            $lockedAccount = CreditAccount::lockForUpdate()->findOrFail($account->id);
            $priorRemainingBalance = (float) $lockedAccount->remaining_balance;
            $balanceAfter = round($priorRemainingBalance - $amount, 2);

            $referenceNo = !empty($inputReferenceNo) ? trim($inputReferenceNo) : null;

            // 1. Create the payment record with collector user_id and balance snapshots
            $payment = Payment::create([
                'credit_account_id' => $lockedAccount->id,
                'user_id'           => Auth::id(),
                'amount'            => $amount,
                'payment_method'    => $paymentMethod,
                'reference_no'      => $referenceNo,
                'notes'             => $notes,
                'balance_before'    => $priorRemainingBalance,
                'balance_after'     => $balanceAfter,
            ]);

            // Auto-number from the committed row ID so concurrent requests can never collide
            if ($referenceNo === null) {
                $referenceNo = 'PAY-' . date('Ymd') . '-' . str_pad((string) $payment->id, 4, '0', STR_PAD_LEFT);
                $payment->update(['reference_no' => $referenceNo]);
            }

            // 2. Create the corresponding credit ledger entry (double-entry)
            CreditLedger::create([
                'credit_account_id' => $lockedAccount->id,
                'transaction_type'  => 'Payment',
                'amount'            => $payment->amount,
                'order_id'          => null,
                'payment_id'        => $payment->id,
                'user_id'           => Auth::id(),
                'balance_before'    => $priorRemainingBalance,
                'balance_after'     => $balanceAfter,
                'notes'             => $notes ?? ('Payment collected (' . $referenceNo . ')'),
            ]);

            // Fix #5: Automatically mark unpaid SOAs as paid if the account's remaining balance is cleared (<= 0)
            if ($lockedAccount->remaining_balance <= 0) {
                StatementOfAccount::where('credit_account_id', $lockedAccount->id)
                    ->where(function ($q) {
                        if (DB::connection()->getDriverName() === 'pgsql') {
                            $q->where('is_paid', DB::raw('false'));
                        } else {
                            $q->where('is_paid', 'false')
                              ->orWhere('is_paid', false)
                              ->orWhere('is_paid', 0);
                        }
                    })
                    ->get()
                    ->each(function ($soa) {
                        $soa->is_paid = true;
                        $soa->save();
                    });
            }

            AuditTrailService::record([
                'correlation_id'        => $correlationId,
                'module'                => 'payments',
                'action'                => 'payment.recorded',
                'entity_type'           => 'Payment',
                'entity_id'             => $payment->id,
                'transaction_reference' => $referenceNo,
                'status'                => 'completed',
                'before_values'         => [
                    'credit_account_id' => $lockedAccount->id,
                    'remaining_balance' => $priorRemainingBalance,
                ],
                'after_values'          => [
                    'credit_account_id' => $lockedAccount->id,
                    'payment_id'        => $payment->id,
                    'amount'            => $amount,
                    'payment_method'    => $paymentMethod,
                    'remaining_balance' => $balanceAfter,
                    'received_by'       => Auth::id(),
                ],
                'reason'                => $notes ?? 'Customer credit payment recorded',
            ]);
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
