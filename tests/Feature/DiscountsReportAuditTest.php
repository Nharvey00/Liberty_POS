<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountsReportAuditTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected User $cashier;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['role_name' => 'Level 1']);
        Role::firstOrCreate(['role_name' => 'Level 2']);
        Role::firstOrCreate(['role_name' => 'Level 3']);

        $this->manager = User::factory()->create([
            'first_name' => 'Manager',
            'last_name' => 'Admin',
            'role_id' => 2,
        ]);

        $this->cashier = User::factory()->create([
            'first_name' => 'Test',
            'last_name' => 'Cashier',
            'role_id' => 1,
        ]);

        $this->product = Product::create([
            'name' => '11kg LPG Refill',
            'price' => 1000.00,
            'stock_quantity' => 50,
        ]);
    }

    public function test_discounts_report_displays_reference_name_and_id_for_walkin_and_registered_customers()
    {
        $currentMonth = Carbon::now()->format('Y-m');

        // 1. Registered Customer with Senior Discount
        $registeredCustomer = Customer::create([
            'first_name' => 'Maria',
            'last_name' => 'Clara',
            'customer_type' => 'Retail',
        ]);

        Order::create([
            'invoice_number' => 'INV-DISC-001',
            'customer_id' => $registeredCustomer->id,
            'user_id' => $this->cashier->id,
            'payment_method' => 'Cash',
            'discount_type' => 'senior',
            'discount_amount' => 120.00,
            'discount_reference_name' => 'Maria Clara Senior',
            'discount_reference_id' => 'OSCA-99881',
            'total_amount' => 880.00,
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        // 2. Walk-in Customer with PWD Discount (customer_id is NULL)
        Order::create([
            'invoice_number' => 'INV-DISC-002',
            'customer_id' => null,
            'user_id' => $this->cashier->id,
            'payment_method' => 'Cash',
            'discount_type' => 'pwd',
            'discount_amount' => 80.00,
            'discount_reference_name' => 'Juan Dela Cruz PWD',
            'discount_reference_id' => 'PWD-44552',
            'total_amount' => 920.00,
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->manager)->get(route('reports.discounts', ['month_year' => $currentMonth]));

        $response->assertOk();
        $response->assertSee('Discounts Summary Report');
        $response->assertSee('Reference Name');
        $response->assertSee('ID Number');

        // Verify Registered Customer reference details
        $response->assertSee('Maria Clara');
        $response->assertSee('Maria Clara Senior');
        $response->assertSee('OSCA-99881');
        $response->assertSee('120.00');

        // Verify Walk-in Customer reference details
        $response->assertSee('Juan Dela Cruz PWD');
        $response->assertSee('PWD-44552');
        $response->assertSee('80.00');

        // Verify Total Discounts aggregation (120 + 80 = 200.00)
        $response->assertSee('200.00');
    }

    public function test_discounts_report_does_not_crash_when_customer_or_user_is_soft_deleted()
    {
        $currentMonth = Carbon::now()->format('Y-m');

        // Soft-delete employee / cashier
        $softDeletedCashier = User::factory()->create([
            'first_name' => 'Former',
            'last_name' => 'Cashier',
            'role_id' => 1,
        ]);

        // Soft-delete customer
        $softDeletedCustomer = Customer::create([
            'first_name' => 'Deleted',
            'last_name' => 'Client',
            'customer_type' => 'Retail',
        ]);

        $order = Order::create([
            'invoice_number' => 'INV-DISC-003',
            'customer_id' => $softDeletedCustomer->id,
            'user_id' => $softDeletedCashier->id,
            'payment_method' => 'Cash',
            'discount_type' => 'senior',
            'discount_amount' => 50.00,
            'discount_reference_name' => 'Deleted Client Senior',
            'discount_reference_id' => 'SNR-0001',
            'total_amount' => 950.00,
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        // Soft delete the cashier and the customer
        $softDeletedCashier->delete();
        $softDeletedCustomer->delete();

        $this->assertSoftDeleted('users', ['id' => $softDeletedCashier->id]);
        $this->assertSoftDeleted('customers', ['id' => $softDeletedCustomer->id]);

        $response = $this->actingAs($this->manager)->get(route('reports.discounts', ['month_year' => $currentMonth]));

        $response->assertOk();
        $response->assertSee('Deleted Client');
        $response->assertSee('Deleted Client Senior');
        $response->assertSee('SNR-0001');
        $response->assertSee('50.00');
    }

    public function test_discounts_report_export_to_csv_works()
    {
        $currentMonth = Carbon::now()->format('Y-m');

        Order::create([
            'invoice_number' => 'INV-DISC-004',
            'customer_id' => null,
            'user_id' => $this->cashier->id,
            'payment_method' => 'Cash',
            'discount_type' => 'senior',
            'discount_amount' => 150.00,
            'discount_reference_name' => 'Walkin Lola',
            'discount_reference_id' => 'SNR-7788',
            'total_amount' => 850.00,
            'status' => 'completed',
            'created_at' => Carbon::now(),
        ]);

        $response = $this->actingAs($this->manager)->get(route('reports.discounts.export', ['month_year' => $currentMonth]));

        $response->assertOk();
        $this->assertTrue(str_contains($response->headers->get('content-type'), 'text/csv'));
        $content = $response->streamedContent();

        $this->assertStringContainsString('Reference Name', $content);
        $this->assertStringContainsString('ID Number', $content);
        $this->assertStringContainsString('Walkin Lola', $content);
        $this->assertStringContainsString('SNR-7788', $content);
        $this->assertStringContainsString('150.00', $content);
    }
}
