<?php

use Illuminate\Support\Facades\Route;
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

// Public redirect or welcome page
Route::get('/', function () {
    return redirect()->route('login');
});

// Authenticated Routes (Requires login)
Route::middleware(['auth'])->group(function () {

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // POS / Cashier Counter (Custom routes for checkout workflow)
    Route::get('/pos', [PosController::class, 'index'])->name('pos.index');
    Route::post('/pos/checkout', [PosController::class, 'store'])->name('pos.checkout');

    // Standard Resource Routes (CRUD)
    Route::resources([
        'users' => UserController::class,
        'customers' => CustomerController::class,
        'products' => ProductController::class,
        'orders' => OrderController::class,
        'credit-accounts' => CreditAccountController::class,
        'payments' => PaymentController::class,
        'statements' => StatementController::class,
        'stock-ins' => StockInController::class,
        'stock-outs' => StockOutController::class,
    ]);

});

// Include Laravel Breeze / Jetstream Auth routes if installed
require __DIR__.'/auth.php';