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
        $activeCreditAccounts = CreditAccount::where('is_active', 'true')->count();

        // --- CHART DATA GENERATION ---

        // Weekly (Last 7 Days)
        $weeklyLabels = [];
        $weeklyData = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::today()->subDays($i);
            $weeklyLabels[] = $date->format('D'); // Mon, Tue, etc.
            $weeklyData[] = Order::whereDate('created_at', $date)->sum('total_amount');
        }
        $weeklyTotal = array_sum($weeklyData);
        $weeklyAvg = $weeklyTotal / 7;
        $weeklyBest = empty(array_filter($weeklyData)) ? 0 : max($weeklyData);
        $weeklyBestDay = 'N/A';
        if ($weeklyBest > 0) {
            $weeklyBestDay = $weeklyLabels[array_search($weeklyBest, $weeklyData)];
        }

        // Monthly (Last 6 Months)
        $monthlyLabels = [];
        $monthlyData = [];
        for ($i = 5; $i >= 0; $i--) {
            $date = Carbon::today()->startOfMonth()->subMonths($i);
            $monthlyLabels[] = $date->format('M Y'); // Jan 2024, etc.
            $monthlyData[] = Order::whereYear('created_at', $date->year)
                                  ->whereMonth('created_at', $date->month)
                                  ->sum('total_amount');
        }
        $monthlyTotal = array_sum($monthlyData);
        $monthlyAvg = $monthlyTotal / 6;
        $monthlyBest = empty(array_filter($monthlyData)) ? 0 : max($monthlyData);
        $monthlyBestDay = 'N/A';
        if ($monthlyBest > 0) {
            $monthlyBestDay = $monthlyLabels[array_search($monthlyBest, $monthlyData)];
        }

        // Quarterly (Last 4 Quarters)
        $quarterlyLabels = [];
        $quarterlyData = [];
        for ($i = 3; $i >= 0; $i--) {
            $date = Carbon::today()->firstOfQuarter()->subQuarters($i);
            $quarterlyLabels[] = 'Q' . $date->quarter . ' ' . $date->format('y');
            $quarterlyData[] = Order::whereBetween('created_at', [
                $date->copy()->startOfQuarter(),
                $date->copy()->endOfQuarter()
            ])->sum('total_amount');
        }
        $quarterlyTotal = array_sum($quarterlyData);
        $quarterlyAvg = $quarterlyTotal / 4;
        $quarterlyBest = empty(array_filter($quarterlyData)) ? 0 : max($quarterlyData);
        $quarterlyBestDay = 'N/A';
        if ($quarterlyBest > 0) {
            $quarterlyBestDay = $quarterlyLabels[array_search($quarterlyBest, $quarterlyData)];
        }

        $chartData = [
            'weekly' => [
                'labels' => $weeklyLabels,
                'data' => $weeklyData,
                'total' => $weeklyTotal,
                'avg' => $weeklyAvg,
                'best' => $weeklyBest,
                'bestLabel' => $weeklyBestDay
            ],
            'monthly' => [
                'labels' => $monthlyLabels,
                'data' => $monthlyData,
                'total' => $monthlyTotal,
                'avg' => $monthlyAvg,
                'best' => $monthlyBest,
                'bestLabel' => $monthlyBestDay
            ],
            'quarterly' => [
                'labels' => $quarterlyLabels,
                'data' => $quarterlyData,
                'total' => $quarterlyTotal,
                'avg' => $quarterlyAvg,
                'best' => $quarterlyBest,
                'bestLabel' => $quarterlyBestDay
            ]
        ];

        return view('dashboard', compact(
            'todaySalesAmount', 
            'todayTransactions', 
            'lowStockProducts', 
            'totalCustomers',
            'activeCreditAccounts',
            'chartData'
        ));
    }
}
