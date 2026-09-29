<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\DashboardController;
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

// Public redirect
Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated Routes (Requires login)
Route::middleware(['auth'])->group(function () {
    
    // Profile Routes (Required by Breeze's navigation bar to prevent crashes)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Level 1, 2, 3: Basic POS Access, Dashboard, and Cash Payment Collection
    Route::middleware(['role:1,2,3'])->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // POS Checkout Routes
        Route::get('/pos', [PosController::class, 'create'])->name('pos.create');
        Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.store');
        Route::get('/pos/{order}/receipt', [PosController::class, 'show'])->name('pos.show');

        // Cashiers retained access to physically receive Utang payments
        Route::get('/credit-accounts/{account}/payments/create', [PaymentController::class, 'create'])->name('payments.create');
        Route::post('/credit-accounts/{account}/payments', [PaymentController::class, 'store'])->name('payments.store');
    });

    // Level 2, 3: Operational Controls (Manager & Owner)
    Route::middleware(['role:2,3'])->group(function () {
        // Historical Orders (View Only)
        Route::get('/orders', [OrderController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderController::class, 'show'])->name('orders.show');

        // Operational Resources
        Route::resources([
            'customers' => CustomerController::class,
            'products' => ProductController::class,
            'credit-accounts' => CreditAccountController::class,
        ]);
        Route::resource('stock-ins', StockInController::class)->only(['index', 'create', 'store']);
        Route::resource('stock-outs', StockOutController::class)->only(['index', 'create', 'store']);
        Route::resource('statements', StatementController::class)->except(['edit']);
    });

    // Level 3: Owner Administration
    Route::middleware(['role:3'])->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

});

// Include Laravel Breeze Auth routes
require __DIR__.'/auth.php';