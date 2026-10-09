<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CashierSalesController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\PosController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\CreditAccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\StatementController;
use App\Http\Controllers\StockInController;
use App\Http\Controllers\StockOutController;
use App\Http\Controllers\OrderVoidController;
use App\Http\Controllers\ReportController;

// Public redirect
Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated Routes (Requires login)
Route::middleware(['auth'])->group(function () {
    
    // API endpoint for customer search autocomplete
    Route::get('/api/customers/search', [CustomerController::class, 'searchApi'])->name('api.customers.search');
    Route::get('/api/products/search', [ProductController::class, 'searchApi'])->name('api.products.search');
    Route::get('/api/orders/search', [OrderController::class, 'searchApi'])->name('api.orders.search');
    
    // Profile Routes (Required by Breeze's navigation bar to prevent crashes)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Level 1, 2, 3: Basic POS Access, Dashboard, and Cash Payment Collection
    Route::middleware(['role:1,2,3'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
        
        // Cashier Sales History
        Route::get('/my-sales', [CashierSalesController::class, 'index'])->name('cashier.sales');

        // POS Checkout Routes
        Route::get('/pos', [PosController::class, 'create'])->name('pos.create');
        Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/{order}/receipt', [PosController::class, 'show'])->name('pos.show');

        // Cashiers retained access to physically receive Utang payments
        Route::get('/credit-accounts/{account}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/credit-accounts/{account}/payments', [PaymentController::class, 'store'])->name('payments.store');

        // Cashier View-Only Inventory Access (Feature 2)
        Route::get('/products', [ProductController::class, 'index'])->name('products.index');
        Route::get('/products/{product}', [ProductController::class, 'show'])->name('products.show')->whereNumber('product')->withTrashed();
    });

    // Level 2, 3: Operational Controls (Manager & Owner)
    Route::middleware(['role:2,3'])->group(function () {
        // Historical Orders & Voiding (Feature 3)
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');
        Route::get('/orders/{order}/void', [OrderVoidController::class, 'create'])->name('orders.void');
        Route::post('/orders/{order}/void', [OrderVoidController::class, 'store'])->name('orders.void.store');

        // Operational Resources
        Route::resources([
            'customers' => CustomerController::class,
            'credit-accounts' => CreditAccountController::class,
        ]);
        
        // Products (Create, Edit, Delete only - Read is in Level 1 block)
        Route::resource('products', ProductController::class)->except(['index', 'show']);
        Route::post('/products/{product}/restore', [ProductController::class, 'restore'])->name('products.restore');

        Route::resource('stock-ins', StockInController::class)->only(['index', 'create', 'store']);
        Route::resource('stock-outs', StockOutController::class)->only(['index', 'create', 'store']);
        
        // Batch Statements (Feature 5)
        Route::post('/statements/batch', [StatementController::class, 'storeBatch'])->name('statements.batch');
        Route::resource('statements', StatementController::class)->except(['edit']);

        // Reports Module (Feature 1)
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/sales', [ReportController::class, 'sales'])->name('reports.sales');
        Route::get('/reports/sales/export', [ReportController::class, 'exportSales'])->name('reports.sales.export');
        Route::get('/reports/inventory', [ReportController::class, 'inventory'])->name('reports.inventory');
        Route::get('/reports/utang', [ReportController::class, 'utang'])->name('reports.utang');
        Route::get('/reports/discounts', [ReportController::class, 'discounts'])->name('reports.discounts');
        Route::get('/reports/discounts/export', [ReportController::class, 'exportDiscounts'])->name('reports.discounts.export');
    });

    // Level 3: Owner Administration
    Route::middleware(['role:3'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

});

// Include Laravel Breeze Auth routes
require __DIR__.'/auth.php';