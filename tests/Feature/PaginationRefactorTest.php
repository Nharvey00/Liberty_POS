<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\CreditAccount;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\StatementOfAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;

class PaginationRefactorTest extends TestCase
{
    use RefreshDatabase;

    protected User $cashier;
    protected User $manager;
    protected User $owner;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        Role::firstOrCreate(['id' => 1], ['role_name' => 'Cashier']);
        Role::firstOrCreate(['id' => 2], ['role_name' => 'Manager']);
        Role::firstOrCreate(['id' => 3], ['role_name' => 'Owner']);

        $this->cashier = User::factory()->create(['role_id' => 1]);
        $this->manager = User::factory()->create(['role_id' => 2]);
        $this->owner = User::factory()->create(['role_id' => 3]);

        $this->product = Product::create([
            'name' => '11kg LPG Cylinder',
            'price' => 950.00,
            'new_cylinder_price' => 2200.00,
            'standard_capacity_kg' => 11.00,
            'stock_quantity' => 500,
            'empty_quantity' => 100,
        ]);
    }

    /**
     * Requirement 1: Tailwind Pagination Configuration
     */
    public function test_tailwind_pagination_is_configured(): void
    {
        $paginator = new LengthAwarePaginator(collect(range(1, 30)), 30, 15, 1);
        $rendered = $paginator->render();
        $this->assertNotEmpty($rendered);
    }

    /**
     * Requirement 2 & 3: ProductController Pagination & View UI
     */
    public function test_product_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Product::create([
                'name' => "Extra Product {$i}",
                'price' => 100 + $i,
                'stock_quantity' => 10,
                'empty_quantity' => 0,
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('products.index', ['search' => 'Extra']));
        $response->assertOk();

        $products = $response->viewData('products');
        $this->assertInstanceOf(LengthAwarePaginator::class, $products);
        $this->assertEquals(15, $products->perPage());
        $this->assertEquals(20, $products->total());
        $this->assertCount(15, $products->items());
        $this->assertStringContainsString('search=Extra', $products->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: OrderController Pagination & View UI
     */
    public function test_order_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'user_id' => $this->cashier->id,
                'total_amount' => 500.00,
                'payment_method' => 'Cash',
                'status' => 'completed',
                'invoice_number' => 'INV-2026-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('orders.index', ['status' => 'completed']));
        $response->assertOk();

        $orders = $response->viewData('orders');
        $this->assertInstanceOf(LengthAwarePaginator::class, $orders);
        $this->assertEquals(15, $orders->perPage());
        $this->assertEquals(20, $orders->total());
        $this->assertCount(15, $orders->items());
        $this->assertStringContainsString('status=completed', $orders->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: CustomerController Pagination & View UI
     */
    public function test_customer_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Customer::create([
                'first_name' => "CustFirst{$i}",
                'last_name' => "CustLast{$i}",
                'customer_type' => 'Walk-in',
                'phone' => "0912345678{$i}",
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('customers.index', ['search' => 'CustFirst']));
        $response->assertOk();

        $customers = $response->viewData('customers');
        $this->assertInstanceOf(LengthAwarePaginator::class, $customers);
        $this->assertEquals(15, $customers->perPage());
        $this->assertEquals(20, $customers->total());
        $this->assertCount(15, $customers->items());
        $this->assertStringContainsString('search=CustFirst', $customers->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: StockInController Pagination & View UI
     */
    public function test_stock_in_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            StockIn::create([
                'product_id' => $this->product->id,
                'quantity_received' => 5,
                'empty_returned_qty' => 0,
                'remarks' => "PO-2026-{$i}",
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('stock-ins.index', ['search' => 'PO-2026']));
        $response->assertOk();

        $stockIns = $response->viewData('stockIns');
        $this->assertInstanceOf(LengthAwarePaginator::class, $stockIns);
        $this->assertEquals(15, $stockIns->perPage());
        $this->assertEquals(20, $stockIns->total());
        $this->assertCount(15, $stockIns->items());
        $this->assertStringContainsString('search=PO-2026', $stockIns->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: StockOutController Pagination & View UI
     */
    public function test_stock_out_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            StockOut::create([
                'product_id' => $this->product->id,
                'quantity_removed' => 1,
                'empty_quantity_removed' => 0,
                'reason' => 'Defect/Damage',
                'remarks' => "Defect batch {$i}",
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('stock-outs.index', ['search' => 'Defect']));
        $response->assertOk();

        $stockOuts = $response->viewData('stockOuts');
        $this->assertInstanceOf(LengthAwarePaginator::class, $stockOuts);
        $this->assertEquals(15, $stockOuts->perPage());
        $this->assertEquals(20, $stockOuts->total());
        $this->assertCount(15, $stockOuts->items());
        $this->assertStringContainsString('search=Defect', $stockOuts->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: CreditAccountController Pagination & View UI
     */
    public function test_credit_account_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            $customer = Customer::create([
                'first_name' => "AcctHolder{$i}",
                'last_name' => "Family{$i}",
                'customer_type' => 'Household',
            ]);
            CreditAccount::create([
                'customer_id' => $customer->id,
                'agreed_monthly_payment' => 500.00,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('credit-accounts.index', ['search' => 'AcctHolder']));
        $response->assertOk();

        $accounts = $response->viewData('accounts');
        $this->assertInstanceOf(LengthAwarePaginator::class, $accounts);
        $this->assertEquals(15, $accounts->perPage());
        $this->assertEquals(20, $accounts->total());
        $this->assertCount(15, $accounts->items());
        $this->assertStringContainsString('search=AcctHolder', $accounts->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: StatementController Pagination & View UI
     */
    public function test_statement_controller_pagination_and_view(): void
    {
        $customer = Customer::create([
            'first_name' => 'SOA',
            'last_name' => 'Owner',
            'customer_type' => 'Company',
        ]);
        $account = CreditAccount::create([
            'customer_id' => $customer->id,
            'is_active' => true,
        ]);

        for ($i = 1; $i <= 20; $i++) {
            StatementOfAccount::create([
                'credit_account_id' => $account->id,
                'billing_period_start' => now()->startOfMonth()->toDateString(),
                'billing_period_end' => now()->endOfMonth()->toDateString(),
                'total_due' => 1000.00,
                'is_paid' => false,
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('statements.index', ['status' => 'unpaid']));
        $response->assertOk();

        $statements = $response->viewData('statements');
        $this->assertInstanceOf(LengthAwarePaginator::class, $statements);
        $this->assertEquals(15, $statements->perPage());
        $this->assertEquals(20, $statements->total());
        $this->assertCount(15, $statements->items());
        $this->assertStringContainsString('status=unpaid', $statements->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: ReportController Sales Pagination & View UI
     */
    public function test_report_sales_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            Order::create([
                'user_id' => $this->cashier->id,
                'total_amount' => 1000.00,
                'payment_method' => 'Cash',
                'status' => 'completed',
                'invoice_number' => 'INV-REP-' . str_pad($i, 5, '0', STR_PAD_LEFT),
            ]);
        }

        $response = $this->actingAs($this->manager)->get(route('reports.sales', ['payment_method' => 'cash']));
        $response->assertOk();

        $orders = $response->viewData('orders');
        $this->assertInstanceOf(LengthAwarePaginator::class, $orders);
        $this->assertEquals(15, $orders->perPage());
        $this->assertEquals(20, $orders->total());
        $this->assertCount(15, $orders->items());
        $this->assertStringContainsString('payment_method=cash', $orders->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }

    /**
     * Requirement 2 & 3: UserController Pagination & View UI
     */
    public function test_user_controller_pagination_and_view(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            User::create([
                'first_name' => "StaffFirst{$i}",
                'last_name' => "StaffLast{$i}",
                'email' => "staff{$i}@liberty.com",
                'password' => bcrypt('password'),
                'role_id' => 1,
            ]);
        }

        $response = $this->actingAs($this->owner)->get(route('users.index', ['search' => 'StaffFirst']));
        $response->assertOk();

        $users = $response->viewData('users');
        $this->assertInstanceOf(LengthAwarePaginator::class, $users);
        $this->assertEquals(15, $users->perPage());
        $this->assertEquals(20, $users->total());
        $this->assertCount(15, $users->items());
        $this->assertStringContainsString('search=StaffFirst', $users->nextPageUrl());

        $response->assertSee('mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto', false);
    }
}
