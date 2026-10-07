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
use Symfony\Component\HttpFoundation\StreamedResponse;

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

        $query = Order::with([
            'customer' => fn($q) => $q->withTrashed(),
            'items.product' => fn($q) => $q->withTrashed(),
            'user' => fn($q) => $q->withTrashed(),
        ])
            ->whereBetween('created_at', [Carbon::parse($fromDate)->startOfDay(), Carbon::parse($toDate)->endOfDay()]);

        if ($paymentMethod && $paymentMethod !== 'all') {
            $query->whereRaw('LOWER(payment_method) = ?', [strtolower($paymentMethod)]);
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
        
        // Sums and counts strictly exclude voided orders
        $totalsQuery = (clone $query)->excludeVoided();
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

        $query = Product::withTrashed();

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
                if ($customerType === 'Coke (Residual)') {
                    $q->whereIn('customer_type', ['Coke (Residual)', 'Company']);
                } else {
                    $q->where('customer_type', $customerType);
                }
            });
        }

        if ($status !== null && $status !== 'all') {
            $query->where('is_active', $status == 'active' ? DB::raw('true') : DB::raw('false'));
        }

        $accounts = $query->get()->map(function($account) {
            $totalCharged = $account->ledgers->filter(fn($l) => strcasecmp($l->transaction_type, 'Charge') === 0)->sum('amount');
            $totalPaid = $account->ledgers->filter(fn($l) => strcasecmp($l->transaction_type, 'Payment') === 0)->sum('amount');
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
        
        $orders = Order::valid()
            ->with([
                'customer' => fn($q) => $q->withTrashed(),
                'user' => fn($q) => $q->withTrashed(),
                'items.product' => fn($q) => $q->withTrashed(),
            ])
            ->select([
                'id',
                'customer_id',
                'user_id',
                'invoice_number',
                'payment_method',
                'discount_type',
                'discount_amount',
                'discount_reference_name',
                'discount_reference_id',
                'senior_id',
                'total_amount',
                'status',
                'created_at',
            ])
            ->whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->get();

        $discountedOrders = $orders->where('discount_amount', '>', 0);
        $loanedOrders = $orders->filter(fn($o) => strcasecmp($o->payment_method, 'Credit') === 0);

        $groupedOrders = $discountedOrders->groupBy(function ($order) {
            if ($order->customer_id) {
                return 'customer_' . $order->customer_id;
            }
            if (!empty($order->discount_reference_id)) {
                return 'walkin_' . $order->discount_reference_id;
            }
            return 'walkin_order_' . $order->id;
        });

        $customersData = [];
        
        foreach ($groupedOrders as $customerOrders) {
            $firstOrder = $customerOrders->first();
            $customer = $firstOrder->customer;
            $customerId = $firstOrder->customer_id;

            $timesDiscounted = $customerOrders->count();
            $totalDiscount = (float) $customerOrders->sum('discount_amount');
            $discountTypes = $customerOrders->pluck('discount_type')->filter()->unique()->map(fn($t) => ucfirst($t))->implode(', ');
            
            $timesLoaned = $customerId ? $loanedOrders->where('customer_id', $customerId)->count() : 0;

            $refName = $customerOrders->pluck('discount_reference_name')->filter()->unique()->implode(', ') 
                ?: ($customer ? trim($customer->first_name . ' ' . $customer->last_name) : '—');
            $refId = $customerOrders->pluck('discount_reference_id')->filter()->unique()->implode(', ') 
                ?: ($customerOrders->pluck('senior_id')->filter()->unique()->implode(', ') ?: '—');

            $customerName = $customer 
                ? trim($customer->first_name . ' ' . $customer->last_name) 
                : ($customerOrders->pluck('discount_reference_name')->filter()->first() ?: 'Walk-in Customer');

            $customerType = $customer ? $customer->customer_type : 'Walk-in';

            $customersData[] = (object) [
                'customer_name' => $customerName,
                'customer_type' => $customerType,
                'discount_reference_name' => $refName !== '—' ? $refName : ($customerName !== 'Walk-in Customer' ? $customerName : '—'),
                'discount_reference_id' => $refId,
                'senior_id' => $refId,
                'times_discounted' => $timesDiscounted,
                'times_loaned' => $timesLoaned,
                'total_discount' => $totalDiscount,
                'discount_types' => $discountTypes ?: 'None',
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

    public function exportDiscounts(Request $request): StreamedResponse
    {
        $monthYear = $request->input('month_year', Carbon::now()->format('Y-m'));
        $date = Carbon::parse($monthYear . '-01');

        $orders = Order::valid()
            ->with([
                'customer' => fn($q) => $q->withTrashed(),
                'user' => fn($q) => $q->withTrashed(),
                'items.product' => fn($q) => $q->withTrashed(),
            ])
            ->select([
                'id',
                'customer_id',
                'user_id',
                'invoice_number',
                'payment_method',
                'discount_type',
                'discount_amount',
                'discount_reference_name',
                'discount_reference_id',
                'senior_id',
                'total_amount',
                'status',
                'created_at',
            ])
            ->whereBetween('created_at', [$date->copy()->startOfMonth(), $date->copy()->endOfMonth()])
            ->get();

        $discountedOrders = $orders->where('discount_amount', '>', 0);
        $loanedOrders = $orders->filter(fn($o) => strcasecmp($o->payment_method, 'Credit') === 0);

        $groupedOrders = $discountedOrders->groupBy(function ($order) {
            if ($order->customer_id) {
                return 'customer_' . $order->customer_id;
            }
            if (!empty($order->discount_reference_id)) {
                return 'walkin_' . $order->discount_reference_id;
            }
            return 'walkin_order_' . $order->id;
        });

        $filename = 'discounts_report_' . $monthYear . '_' . Carbon::now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($groupedOrders, $loanedOrders) {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, [
                'Customer Name',
                'Customer Type',
                'Reference Name',
                'ID Number',
                'Times Discounted',
                'Times Loaned',
                'Total Discount (PHP)',
                'Discount Types'
            ]);

            foreach ($groupedOrders as $customerOrders) {
                $firstOrder = $customerOrders->first();
                $customer = $firstOrder->customer;
                $customerId = $firstOrder->customer_id;

                $timesDiscounted = $customerOrders->count();
                $totalDiscount = (float) $customerOrders->sum('discount_amount');
                $discountTypes = $customerOrders->pluck('discount_type')->filter()->unique()->map(fn($t) => ucfirst($t))->implode(', ');
                $timesLoaned = $customerId ? $loanedOrders->where('customer_id', $customerId)->count() : 0;

                $refName = $customerOrders->pluck('discount_reference_name')->filter()->unique()->implode(', ') 
                    ?: ($customer ? trim($customer->first_name . ' ' . $customer->last_name) : '—');
                $refId = $customerOrders->pluck('discount_reference_id')->filter()->unique()->implode(', ') 
                    ?: ($customerOrders->pluck('senior_id')->filter()->unique()->implode(', ') ?: '—');

                $customerName = $customer 
                    ? trim($customer->first_name . ' ' . $customer->last_name) 
                    : ($customerOrders->pluck('discount_reference_name')->filter()->first() ?: 'Walk-in Customer');

                $customerType = $customer ? $customer->customer_type : 'Walk-in';

                fputcsv($handle, [
                    $customerName,
                    ucfirst($customerType),
                    $refName,
                    $refId,
                    $timesDiscounted,
                    $timesLoaned,
                    number_format($totalDiscount, 2, '.', ''),
                    $discountTypes ?: 'None'
                ]);
            }

            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Native streamed CSV export compatible with Microsoft Excel.
     */
    public function exportSales(Request $request): StreamedResponse
    {
        $fromDate = $request->input('from_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $toDate = $request->input('to_date', Carbon::now()->format('Y-m-d'));
        $paymentMethod = $request->input('payment_method');
        $customerSearch = $request->input('customer_search');
        $cashierId = $request->input('cashier_id');

        $query = Order::valid()
            ->with([
                'customer' => fn($q) => $q->withTrashed(),
                'items.product' => fn($q) => $q->withTrashed(),
                'user' => fn($q) => $q->withTrashed(),
            ])
            ->whereBetween('created_at', [
                Carbon::parse($fromDate)->startOfDay(), 
                Carbon::parse($toDate)->endOfDay()
            ]);

        if ($paymentMethod && $paymentMethod !== 'all') {
            $query->whereRaw('LOWER(payment_method) = ?', [strtolower($paymentMethod)]);
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

        $filename = 'sales_report_' . Carbon::now()->format('Y_m_d_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return new StreamedResponse(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // Write UTF-8 Byte Order Mark (BOM) so Excel opens seamlessly without encoding errors
            fwrite($handle, "\xEF\xBB\xBF");

            // Column Headers
            fputcsv($handle, [
                'Invoice #',
                'Date & Time',
                'Customer Name',
                'Business Name',
                'Cashier',
                'Payment Method',
                'Subtotal (Vatable)',
                '12% VAT',
                'Discount Amount',
                'Total Amount (PHP)',
                'Status'
            ]);

            // Chunk through database rows to ensure flat memory usage
            $query->latest('id')->chunk(250, function ($orders) use ($handle) {
                foreach ($orders as $order) {
                    $vatable = $order->total_amount / 1.12;
                    $vat = $order->total_amount - $vatable;

                    $customerName = $order->customer 
                        ? trim($order->customer->first_name . ' ' . $order->customer->last_name) 
                        : 'Walk-in Customer';
                    $businessName = $order->customer?->business_name ?? '—';

                    fputcsv($handle, [
                        $order->invoice_number,
                        $order->created_at->format('M d, Y h:i A'),
                        $customerName,
                        $businessName,
                        $order->user?->name ?? 'N/A',
                        ucfirst($order->payment_method),
                        number_format($vatable, 2, '.', ''),
                        number_format($vat, 2, '.', ''),
                        number_format($order->discount_amount, 2, '.', ''),
                        number_format($order->total_amount, 2, '.', ''),
                        ucfirst($order->status),
                    ]);
                }
            });

            fclose($handle);
        }, 200, $headers);
    }
}
