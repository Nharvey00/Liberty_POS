<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\Customer;
use App\Http\Requests\StoreCreditAccountRequest;
use App\Http\Requests\UpdateCreditAccountRequest;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CreditAccountController extends Controller
{
    /**
     * Display a listing of all credit accounts with live balances.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = CreditAccount::with(['customer', 'requester', 'approver'])
            ->orderByDesc('created_at');

        if ($search) {
            $query->whereHas('customer', function ($q) use ($search) {
                $q->withTrashed()->search($search);
            });
        }

        $accounts = $query->paginate(15)->withQueryString();

        return view('credit_accounts.index', compact('accounts', 'search'));
    }

    /**
     * Show the form for approving a new credit (utang) account.
     * Only customers who do NOT already have a credit account are shown.
     */
    public function create()
    {
        $customers = Customer::whereDoesntHave('creditAccount')
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        return view('credit_accounts.create', compact('customers'));
    }

    /**
     * Store a newly approved or pending credit account.
     */
    public function store(StoreCreditAccountRequest $request)
    {
        $validated = $request->validated();
        $validated['credit_limit'] = (isset($validated['credit_limit']) && $validated['credit_limit'] !== '') ? (float) $validated['credit_limit'] : null;
        $validated['agreed_monthly_payment'] = (isset($validated['agreed_monthly_payment']) && $validated['agreed_monthly_payment'] !== '') ? (float) $validated['agreed_monthly_payment'] : null;

        $requestedStatus = $validated['status'] ?? CreditAccount::STATUS_APPROVED;
        $isApproved = $requestedStatus === CreditAccount::STATUS_APPROVED;

        $validated['status']           = $requestedStatus;
        $validated['is_active']        = $isApproved;
        $validated['requested_by']     = Auth::id();
        $validated['approved_by']      = $isApproved ? Auth::id() : null;
        $validated['approved_at']      = $isApproved ? now() : null;
        $validated['approval_remarks'] = $validated['approval_remarks'] ?? null;

        $account = CreditAccount::create($validated);
        $account->load('customer');

        $referenceNo = 'CA-' . str_pad((string) $account->id, 5, '0', STR_PAD_LEFT);

        AuditTrailService::record([
            'module'                => 'credit_accounts',
            'action'                => $isApproved ? 'credit_account.approved' : 'credit_account.application_submitted',
            'entity_type'           => 'CreditAccount',
            'entity_id'             => $account->id,
            'transaction_reference' => $referenceNo,
            'status'                => $isApproved ? 'completed' : 'pending',
            'approval_status'       => $isApproved ? 'approved' : 'pending',
            'approver_user_id'      => $account->approved_by,
            'approved_at'           => $account->approved_at,
            'after_values'          => [
                'customer_id'            => $account->customer_id,
                'customer_name'          => $account->customer?->name,
                'credit_limit'           => $account->credit_limit !== null ? (float) $account->credit_limit : null,
                'agreed_monthly_payment' => $account->agreed_monthly_payment !== null ? (float) $account->agreed_monthly_payment : null,
                'status'                 => $account->status,
                'is_active'              => (bool) $account->is_active,
                'requested_by'           => $account->requested_by,
                'approved_by'            => $account->approved_by,
            ],
            'reason'                => $account->approval_remarks ?? ($isApproved ? 'Credit account approved' : 'Credit account application submitted for approval'),
        ]);

        return redirect()
            ->route('credit-accounts.index')
            ->with('success', $isApproved ? 'Credit account approved successfully.' : 'Credit account application submitted for approval.');
    }

    /**
     * Display the credit ledger for a specific account.
     * THE LEDGER: Chronological table of all Charges and Payments
     * with a dynamically calculated running balance.
     */
    public function show(CreditAccount $credit_account)
    {
        $credit_account->load([
            'customer',
            'requester',
            'approver',
            'ledgers' => function ($query) {
                $query->with(['order', 'payment', 'user'])->orderBy('created_at', 'asc')->orderBy('id', 'asc');
            },
        ]);

        // Calculate running balance for each ledger row (in-memory — no DB column needed)
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

        $creditAuditLogs = AuditLog::where('entity_type', 'CreditAccount')
            ->where('entity_id', (string) $credit_account->id)
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('credit_accounts.show', compact('credit_account', 'remainingBalance', 'creditAuditLogs'));
    }

    /**
     * Show the form for editing credit account terms.
     */
    public function edit(CreditAccount $credit_account)
    {
        $credit_account->load(['customer', 'requester', 'approver']);

        return view('credit_accounts.edit', compact('credit_account'));
    }

    /**
     * Update the credit account terms (credit limit, agreed payment, active/approval status).
     */
    public function update(UpdateCreditAccountRequest $request, CreditAccount $credit_account)
    {
        $validated = $request->validated();
        // An empty credit limit means "no limit" (null), consistent with checkout's credit check
        $newCreditLimit = array_key_exists('credit_limit', $validated)
            ? (($validated['credit_limit'] !== null && $validated['credit_limit'] !== '') ? (float) $validated['credit_limit'] : null)
            : ($credit_account->credit_limit !== null ? (float) $credit_account->credit_limit : null);
        $newMonthlyPayment = (isset($validated['agreed_monthly_payment']) && $validated['agreed_monthly_payment'] !== '') ? (float) $validated['agreed_monthly_payment'] : null;
        $newIsActive = $request->boolean('is_active');
        $newStatus = $validated['status'] ?? ($newIsActive ? CreditAccount::STATUS_APPROVED : CreditAccount::STATUS_SUSPENDED);

        // Enforce self-approval guard when transitioning from pending to approved by a non-Owner
        if ($credit_account->status === CreditAccount::STATUS_PENDING
            && $newStatus === CreditAccount::STATUS_APPROVED
            && $credit_account->requested_by === Auth::id()
            && !Auth::user()->isOwner()
        ) {
            return back()->withErrors('Self-approval is not permitted. A pending credit application must be approved by another Manager or the Owner.');
        }

        $beforeValues = [
            'credit_limit'           => $credit_account->credit_limit !== null ? (float) $credit_account->credit_limit : null,
            'agreed_monthly_payment' => $credit_account->agreed_monthly_payment !== null ? (float) $credit_account->agreed_monthly_payment : null,
            'is_active'              => (bool) $credit_account->is_active,
            'status'                 => $credit_account->status,
            'approved_by'            => $credit_account->approved_by,
        ];

        $oldCreditLimit = $credit_account->credit_limit !== null ? (float) $credit_account->credit_limit : null;
        $limitChanged = $oldCreditLimit !== $newCreditLimit;
        $wasActive    = (bool) $credit_account->is_active;

        $updatePayload = [
            'credit_limit'           => $newCreditLimit,
            'agreed_monthly_payment' => $newMonthlyPayment,
            'is_active'              => $newIsActive,
            'status'                 => $newStatus,
            'approval_remarks'       => $validated['approval_remarks'] ?? $credit_account->approval_remarks,
        ];

        if ($newIsActive && (!$wasActive || $credit_account->status === CreditAccount::STATUS_PENDING)) {
            $updatePayload['approved_by'] = Auth::id();
            $updatePayload['approved_at'] = now();
        }

        $credit_account->update($updatePayload);
        $credit_account->refresh();

        $action = 'credit_account.updated';
        if ($limitChanged) {
            $action = 'credit_account.limit_changed';
        } elseif ($wasActive && !$newIsActive) {
            $action = 'credit_account.suspended';
        } elseif (!$wasActive && $newIsActive) {
            $action = 'credit_account.approved';
        }

        $referenceNo = 'CA-' . str_pad((string) $credit_account->id, 5, '0', STR_PAD_LEFT);

        AuditTrailService::record([
            'module'                => 'credit_accounts',
            'action'                => $action,
            'entity_type'           => 'CreditAccount',
            'entity_id'             => $credit_account->id,
            'transaction_reference' => $referenceNo,
            'status'                => 'completed',
            'approval_status'       => $newIsActive ? 'approved' : ($newStatus === CreditAccount::STATUS_REJECTED ? 'rejected' : 'not_required'),
            'approver_user_id'      => Auth::id(),
            'approved_at'           => now(),
            'before_values'         => $beforeValues,
            'after_values'          => [
                'credit_limit'           => $credit_account->credit_limit !== null ? (float) $credit_account->credit_limit : null,
                'agreed_monthly_payment' => $credit_account->agreed_monthly_payment !== null ? (float) $credit_account->agreed_monthly_payment : null,
                'is_active'              => (bool) $credit_account->is_active,
                'status'                 => $credit_account->status,
                'approved_by'            => $credit_account->approved_by,
            ],
            'reason'                => $validated['approval_remarks'] ?? 'Credit account terms updated',
        ]);

        return redirect()
            ->route('credit-accounts.show', $credit_account)
            ->with('success', 'Credit account updated successfully.');
    }

    /**
     * Credit accounts cannot be deleted — only deactivated.
     * This enforces the business rule of preserving financial audit trails.
     */
    public function destroy(CreditAccount $credit_account)
    {
        abort(403, 'Credit accounts cannot be deleted. Deactivate them instead via Edit Terms.');
    }
}
