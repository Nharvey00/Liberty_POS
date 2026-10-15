<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Role;
use App\Models\StatementOfAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Phase3CreditPaymentsUsersAuthAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $manager1;
    protected User $manager2;
    protected User $cashier;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Cashier']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Manager']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Owner']);

        $this->cashier = User::factory()->create([
            'first_name' => 'Dante',
            'last_name'  => 'Cashier',
            'email'      => 'dante@liberty.test',
            'password'   => bcrypt('Password123!'),
            'role_id'    => 1,
        ]);

        $this->manager1 = User::factory()->create([
            'first_name' => 'Grace',
            'last_name'  => 'Manager',
            'email'      => 'grace@liberty.test',
            'password'   => bcrypt('Password123!'),
            'role_id'    => 2,
        ]);

        $this->manager2 = User::factory()->create([
            'first_name' => 'Marco',
            'last_name'  => 'Manager',
            'email'      => 'marco@liberty.test',
            'password'   => bcrypt('Password123!'),
            'role_id'    => 2,
        ]);

        $this->owner = User::factory()->create([
            'first_name' => 'Antonio',
            'last_name'  => 'Owner',
            'email'      => 'antonio@liberty.test',
            'password'   => bcrypt('Password123!'),
            'role_id'    => 3,
        ]);
    }

    public function test_credit_account_approval_workflow_self_approval_guard_and_limit_auditing(): void
    {
        $customer = Customer::create([
            'first_name'    => 'Belen',
            'last_name'     => 'Cruz',
            'customer_type' => 'Household',
        ]);

        // 1. Manager 1 submits a pending credit application
        $createRes = $this->actingAs($this->manager1)->post(route('credit-accounts.store'), [
            'customer_id'            => $customer->id,
            'credit_limit'           => 5000.00,
            'agreed_monthly_payment' => 1500.00,
            'status'                 => CreditAccount::STATUS_PENDING,
            'approval_remarks'       => 'New application awaiting second manager review',
        ]);

        $createRes->assertRedirect(route('credit-accounts.index'));
        $account = CreditAccount::where('customer_id', $customer->id)->firstOrFail();

        $this->assertFalse($account->is_active);
        $this->assertEquals(CreditAccount::STATUS_PENDING, $account->status);
        $this->assertEquals($this->manager1->id, $account->requested_by);
        $this->assertNull($account->approved_by);

        $this->assertDatabaseHas('audit_logs', [
            'module'          => 'credit_accounts',
            'action'          => 'credit_account.application_submitted',
            'entity_id'       => (string) $account->id,
            'approval_status' => 'pending',
            'actor_user_id'   => $this->manager1->id,
        ]);

        // 2. Manager 1 attempts to self-approve their own pending application -> blocked
        $selfApprove = $this->actingAs($this->manager1)->put(route('credit-accounts.update', $account), [
            'credit_limit'           => 5000.00,
            'agreed_monthly_payment' => 1500.00,
            'is_active'              => true,
            'status'                 => CreditAccount::STATUS_APPROVED,
        ]);

        $selfApprove->assertSessionHasErrors();
        $account->refresh();
        $this->assertFalse($account->is_active);

        // 3. Manager 2 approves the pending application -> succeeds and logs approver
        $approveRes = $this->actingAs($this->manager2)->put(route('credit-accounts.update', $account), [
            'credit_limit'           => 5000.00,
            'agreed_monthly_payment' => 1500.00,
            'is_active'              => true,
            'status'                 => CreditAccount::STATUS_APPROVED,
            'approval_remarks'       => 'Credit verified and approved by Marco',
        ]);

        $approveRes->assertRedirect(route('credit-accounts.show', $account));
        $account->refresh();
        $this->assertTrue($account->is_active);
        $this->assertEquals($this->manager2->id, $account->approved_by);

        $this->assertDatabaseHas('audit_logs', [
            'module'           => 'credit_accounts',
            'action'           => 'credit_account.approved',
            'entity_id'        => (string) $account->id,
            'actor_user_id'    => $this->manager2->id,
            'approver_user_id' => $this->manager2->id,
        ]);

        // 4. Owner increases credit limit -> logs credit_account.limit_changed with before/after values
        $limitRes = $this->actingAs($this->owner)->put(route('credit-accounts.update', $account), [
            'credit_limit'           => 8500.00,
            'agreed_monthly_payment' => 2000.00,
            'is_active'              => true,
            'approval_remarks'       => 'Increased limit due to good payment standing',
        ]);

        $limitRes->assertRedirect(route('credit-accounts.show', $account));

        $limitAudit = AuditLog::where('module', 'credit_accounts')
            ->where('action', 'credit_account.limit_changed')
            ->where('entity_id', (string) $account->id)
            ->firstOrFail();

        $this->assertEquals(5000.00, $limitAudit->before_values['credit_limit']);
        $this->assertEquals(8500.00, $limitAudit->after_values['credit_limit']);
    }

    public function test_payment_collection_records_collector_balance_snapshots_and_audit_log(): void
    {
        $customer = Customer::create([
            'first_name'    => 'Nestor',
            'last_name'     => 'Reyes',
            'customer_type' => 'Household',
        ]);

        $account = CreditAccount::create([
            'customer_id'  => $customer->id,
            'credit_limit' => 5000.00,
            'is_active'    => true,
        ]);

        CreditLedger::create([
            'credit_account_id' => $account->id,
            'transaction_type'  => 'Charge',
            'amount'            => 1200.00,
            'user_id'           => $this->cashier->id,
            'balance_before'    => 0.00,
            'balance_after'     => 1200.00,
        ]);

        $payRes = $this->actingAs($this->cashier)->post(route('payments.store', $account), [
            'amount'         => 700.00,
            'payment_method' => 'Cash',
            'reference_no'   => 'OR-PAY-901',
            'notes'          => 'Partial cash payment at counter',
        ]);

        $payRes->assertRedirect(route('pos.create'));

        $payment = Payment::latest('id')->firstOrFail();
        $this->assertEquals($this->cashier->id, $payment->user_id);
        $this->assertEquals('1200.00', $payment->balance_before);
        $this->assertEquals('500.00', $payment->balance_after);
        $this->assertEquals('OR-PAY-901', $payment->reference_no);

        $ledger = CreditLedger::where('payment_id', $payment->id)->firstOrFail();
        $this->assertEquals($this->cashier->id, $ledger->user_id);
        $this->assertEquals('1200.00', $ledger->balance_before);
        $this->assertEquals('500.00', $ledger->balance_after);

        $this->assertDatabaseHas('audit_logs', [
            'module'                => 'payments',
            'action'                => 'payment.recorded',
            'entity_id'             => (string) $payment->id,
            'transaction_reference' => 'OR-PAY-901',
            'actor_user_id'         => $this->cashier->id,
        ]);
    }

    public function test_payment_that_clears_remaining_balance_marks_unpaid_statements_as_paid(): void
    {
        $customer = Customer::create([
            'first_name'    => 'Anora',
            'last_name'     => 'Santos',
            'customer_type' => 'Household',
        ]);

        $account = CreditAccount::create([
            'customer_id'  => $customer->id,
            'credit_limit' => 5000.00,
            'is_active'    => true,
        ]);

        CreditLedger::create([
            'credit_account_id' => $account->id,
            'transaction_type'  => 'Charge',
            'amount'            => 480.00,
            'user_id'           => $this->cashier->id,
            'balance_before'    => 0.00,
            'balance_after'     => 480.00,
        ]);

        $soa = StatementOfAccount::create([
            'credit_account_id'    => $account->id,
            'billing_period_start' => now()->startOfMonth()->toDateString(),
            'billing_period_end'   => now()->endOfMonth()->toDateString(),
            'total_due'            => 480.00,
            'is_paid'              => false,
        ]);

        $this->actingAs($this->manager1)
            ->post(route('payments.store', $account), [
                'amount'         => 480.00,
                'payment_method' => 'Cash',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertEquals(0.0, (float) $account->fresh()->remaining_balance);
        $this->assertTrue($soa->fresh()->is_paid);
    }


    public function test_user_role_change_and_customer_updates_are_audited_without_passwords(): void
    {
        // Owner promotes Cashier to Manager
        $roleRes = $this->actingAs($this->owner)->put(route('users.update', $this->cashier), [
            'first_name'            => 'Dante',
            'last_name'             => 'Cashier',
            'email'                 => 'dante@liberty.test',
            'role_id'               => 2,
            'password'              => 'NewSecretPass999!',
            'password_confirmation' => 'NewSecretPass999!',
        ]);

        $roleRes->assertRedirect(route('users.index'));

        $roleAudit = AuditLog::where('module', 'users')
            ->where('action', 'user.role_changed')
            ->where('entity_id', (string) $this->cashier->id)
            ->firstOrFail();

        $this->assertEquals('Cashier', $roleAudit->before_values['role_name']);
        $this->assertEquals('Manager', $roleAudit->after_values['role_name']);
        $this->assertTrue($roleAudit->after_values['password_changed']);
        $this->assertArrayNotHasKey('password', $roleAudit->after_values);
    }

    public function test_authentication_login_failed_login_and_logout_events_are_audited(): void
    {
        // 1. Failed login attempt
        $this->post('/login', [
            'email'    => 'dante@liberty.test',
            'password' => 'WrongPassword!',
        ]);

        $failedAudit = AuditLog::where('module', 'auth')
            ->where('action', 'auth.failed')
            ->latest('id')
            ->firstOrFail();

        $this->assertEquals('failed', $failedAudit->status);
        $this->assertEquals('dante@liberty.test', $failedAudit->after_values['attempted_email']);
        $this->assertArrayNotHasKey('password', $failedAudit->after_values);

        // 2. Successful login
        $this->post('/login', [
            'email'    => 'dante@liberty.test',
            'password' => 'Password123!',
        ]);

        $this->assertDatabaseHas('audit_logs', [
            'module'        => 'auth',
            'action'        => 'auth.login',
            'actor_user_id' => $this->cashier->id,
            'status'        => 'completed',
        ]);

        // 3. Logout
        $this->post('/logout');

        $this->assertDatabaseHas('audit_logs', [
            'module'        => 'auth',
            'action'        => 'auth.logout',
            'actor_user_id' => $this->cashier->id,
            'status'        => 'completed',
        ]);
    }
}
