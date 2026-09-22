<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\Customer;
use App\Models\CreditAccount;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();

        $todaySalesAmount = Order::whereDate('created_at', $today)->sum('total_amount');
        $todayTransactions = Order::whereDate('created_at', $today)->count();
        
        $lowStockProducts = Product::where('stock_quantity', '<=', 10)->get();
        
        $totalCustomers = Customer::count();
        $activeCreditAccounts = CreditAccount::where('is_active', true)->count();

        return view('dashboard', compact(
            'todaySalesAmount', 
            'todayTransactions', 
            'lowStockProducts', 
            'totalCustomers',
            'activeCreditAccounts'
        ));
    }
}