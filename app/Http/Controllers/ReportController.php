<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CreditAccount;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function sales(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $paymentMethod = $request->input('payment_method');
        $customerSearch = $request->input('customer_search');
        $cashierId = $request->input('cashier_id');

        $query = Order::with(['customer', 'user'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()]);

        if ($paymentMethod && $paymentMethod !== 'all') {
            $query->where('payment_method', $paymentMethod);
        }

        if ($customerSearch) {
            $query->whereHas('customer', function($q) use ($customerSearch) {
                $q->where('first_name', 'like', "%{$customerSearch}%")
                  ->orWhere('last_name', 'like', "%{$customerSearch}%")
                  ->orWhere('business_name', 'like', "%{$customerSearch}%");
            });
        }

        if ($cashierId) {
            $query->where('user_id', $cashierId);
        }

        $orders = $query->latest()->paginate(15)->withQueryString();
        
        $totalsQuery = clone $query;
        $totalSales = $totalsQuery->sum('total_amount');
        $totalDiscounts = $totalsQuery->sum('discount_amount');
        $orderCount = $totalsQuery->count();
        $totalVatableSale = $totalSales / 1.12;
        $totalVat = $totalSales - $totalVatableSale;

        $cashiers = User::whereIn('role_id', [1, 2, 3])->get();

        return view('reports.sales', compact(
            'orders', 'fromDate', 'toDate', 'paymentMethod', 'customerSearch', 
            'cashierId', 'totalSales', 'totalVat', 'totalDiscounts', 'orderCount', 'cashiers'
        ));
    }

    public function inventory(Request $request)
    {
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->endOfMonth()->format('Y-m-d'));
        $productSearch = $request->input('product_search');

        $query = Product::query();

        if ($productSearch) {
            $query->where('name', 'like', "%{$productSearch}%");
        }

        $products = $query->get()->map(function($product) use ($fromDate, $toDate) {
            $sold = DB::table('order_items')
                ->join('orders', 'order_items.order_id', '=', 'orders.id')
                ->where('order_items.product_id', $product->id)
                ->where('orders.status', 'completed')
                ->whereBetween('orders.created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
                ->sum('order_items.quantity');
                
            $stockIns = DB::table('stock_ins')
                ->where('product_id', $product->id)
                ->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
                ->sum('quantity_received');

            $stockOuts = DB::table('stock_outs')
                ->where('product_id', $product->id)
                ->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()])
                ->sum('quantity_removed');

            $product->period_sold = $sold;
            $product->period_in = $stockIns;
            $product->period_out = $stockOuts;
            return $product;
        });

        return view('reports.inventory', compact('products', 'fromDate', 'toDate', 'productSearch'));
    }

    public function utang(Request $request)
    {
        $customerSearch = $request->input('customer_search');
        $customerType = $request->input('customer_type');
        $status = $request->input('status');

        $query = CreditAccount::with('customer', 'ledgers');

        if ($customerSearch) {
            $query->whereHas('customer', function($q) use ($customerSearch) {
                $q->where('first_name', 'like', "%{$customerSearch}%")
                  ->orWhere('last_name', 'like', "%{$customerSearch}%")
                  ->orWhere('business_name', 'like', "%{$customerSearch}%");
            });
        }

        if ($customerType && $customerType !== 'all') {
            $query->whereHas('customer', function($q) use ($customerType) {
                $q->where('customer_type', $customerType);
            });
        }

        if ($status !== null && $status !== 'all') {
            $query->where('is_active', $status == 'active' ? 'true' : 'false');
        }

        $accounts = $query->get()->map(function($account) {
            $totalCharged = $account->ledgers->where('transaction_type', 'charge')->sum('amount');
            $totalPaid = $account->ledgers->where('transaction_type', 'payment')->sum('amount');
            $balance = $totalCharged - $totalPaid;
            
            $lastActivity = $account->ledgers->max('created_at');

            $account->total_charged = $totalCharged;
            $account->total_paid = $totalPaid;
            $account->balance = $balance;
            $account->last_activity = $lastActivity;
            
            return $account;
        })->filter(function($account) {
            return $account->balance > 0 || $account->total_charged > 0;
        });

        $grandTotalCharged = $accounts->sum('total_charged');
        $grandTotalPaid = $accounts->sum('total_paid');
        $grandTotalBalance = $accounts->sum('balance');

        return view('reports.utang', compact(
            'accounts', 'customerSearch', 'customerType', 'status',
            'grandTotalCharged', 'grandTotalPaid', 'grandTotalBalance'
        ));
    }

    public function discounts(Request $request)
    {
        $monthYear = $request->input('month_year', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($monthYear . '-01');
        
        $orders = Order::with('customer')
            ->where('status', 'completed')
            ->whereNotNull('customer_id')
            ->whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->get();

        $discountedOrders = $orders->where('discount_amount', '>', 0);
        $loanedOrders = $orders->where('payment_method', 'credit');

        $customersData = [];
        
        foreach ($discountedOrders->groupBy('customer_id') as $customerId => $customerOrders) {
            $customer = $customerOrders->first()->customer;
            $timesDiscounted = $customerOrders->count();
            $totalDiscount = $customerOrders->sum('discount_amount');
            $discountTypes = $customerOrders->pluck('discount_type')->filter()->unique()->implode(', ');
            
            $timesLoaned = $loanedOrders->where('customer_id', $customerId)->count();

            $customersData[] = (object) [
                'customer_name' => $customer->first_name . ' ' . $customer->last_name,
                'customer_type' => $customer->customer_type,
                'senior_id' => $customerOrders->pluck('senior_id')->filter()->first(),
                'times_discounted' => $timesDiscounted,
                'times_loaned' => $timesLoaned,
                'total_discount' => $totalDiscount,
                'discount_types' => $discountTypes,
            ];
        }

        $totalTimesDiscounted = collect($customersData)->sum('times_discounted');
        $totalTimesLoaned = collect($customersData)->sum('times_loaned');
        $grandTotalDiscount = collect($customersData)->sum('total_discount');

        return view('reports.discounts', compact(
            'customersData', 'monthYear',
            'totalTimesDiscounted', 'totalTimesLoaned', 'grandTotalDiscount'
        ));
    }
}
