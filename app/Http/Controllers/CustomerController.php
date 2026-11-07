<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Services\AuditTrailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with eager-loaded credit balances and active tank loans.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $relations = ['creditAccount'];
        if (Schema::hasTable('tank_loans')) {
            $relations[] = 'activeTankLoans';
        }

        $query = Customer::with($relations)
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($search) {
            $query->search($search);
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('customers.index', compact('customers', 'search'));
    }

    /**
     * Show the form for creating a new customer.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created customer in the database.
     */
    public function store(StoreCustomerRequest $request)
    {
        $customer = Customer::create($request->validated());

        AuditTrailService::record([
            'module'                => 'customers',
            'action'                => 'customer.created',
            'entity_type'           => 'Customer',
            'entity_id'             => $customer->id,
            'transaction_reference' => 'CUS-' . str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT),
            'status'                => 'completed',
            'after_values'          => [
                'name'                        => $customer->name,
                'business_name'               => $customer->business_name,
                'customer_type'               => $customer->customer_type,
                'phone'                       => $customer->phone,
                'default_discount_type'       => $customer->default_discount_type,
                'default_discount_percentage' => $customer->default_discount_percentage,
            ],
            'reason'                => 'New customer profile created',
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer record created successfully.');
    }

    /**
     * Display the specified customer profile, daily volume matrix, monthly summary breakdown, and itemized purchase audit log.
     */
    public function show(Customer $customer, Request $request)
    {
        $currentMonth = $request->input('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($currentMonth)->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonth)->endOfMonth();

        $relations = [
            'orders' => function ($query) use ($startOfMonth, $endOfMonth) {
                $query->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                      ->with(['items.product', 'user'])
                      ->orderBy('created_at', 'desc');
            },
            'creditAccount',
        ];

        if (Schema::hasTable('tank_loans')) {
            $relations[] = 'activeTankLoans.product';
        }

        $customer->load($relations);

        $daysInMonth = $endOfMonth->daysInMonth;
        $dailyVolumes = array_fill(1, $daysInMonth, 0);

        foreach ($customer->orders as $order) {
            if ($order->isVoided()) {
                continue;
            }
            $day = $order->created_at->day;
            $qty = $order->items->sum('quantity');
            $dailyVolumes[$day] += ($qty > 0 ? (int)$qty : 1);
        }

        // Phase 3A: Monthly Summary Breakdown (Strictly excluding voided POS sales)
        $validOrders = Order::valid()
            ->where('customer_id', $customer->id)
            ->with('items')
            ->orderBy('created_at', 'desc')
            ->get();

        $monthlyBreakdown = $validOrders
            ->groupBy(fn ($order) => $order->created_at->format('Y-m'))
            ->map(function ($monthOrders, $monthKey) {
                $totalQty = 0;
                $grossAmount = 0.0;
                $totalDiscount = 0.0;
                $netSpend = 0.0;

                foreach ($monthOrders as $order) {
                    $qty = (int) $order->items->sum('quantity');
                    $totalQty += $qty;
                    $net = (float) $order->total_amount;
                    $disc = (float) ($order->discount_amount ?? 0);
                    $grossAmount += ($net + $disc);
                    $totalDiscount += $disc;
                    $netSpend += $net;
                }

                return [
                    'month_key' => $monthKey,
                    'month_label' => Carbon::createFromFormat('Y-m', $monthKey)->format('F Y'),
                    'orders_count' => $monthOrders->count(),
                    'total_qty' => $totalQty,
                    'gross_amount' => round($grossAmount, 2),
                    'total_discount' => round($totalDiscount, 2),
                    'net_spend' => round($netSpend, 2),
                ];
            })
            ->values();

        $lifetimeSummary = [
            'orders_count' => $monthlyBreakdown->sum('orders_count'),
            'total_qty' => $monthlyBreakdown->sum('total_qty'),
            'gross_amount' => round($monthlyBreakdown->sum('gross_amount'), 2),
            'total_discount' => round($monthlyBreakdown->sum('total_discount'), 2),
            'net_spend' => round($monthlyBreakdown->sum('net_spend'), 2),
        ];

        // Phase 3B: Itemized Purchase Audit Log (Filterable)
        $auditMonth = $request->input('audit_month', 'all');
        $auditPaymentMethod = $request->input('payment_method', 'all');

        $auditQuery = Order::where('customer_id', $customer->id)
            ->with(['items.product', 'user', 'voidedByUser'])
            ->orderBy('created_at', 'desc');

        if ($auditMonth !== 'all' && preg_match('/^\d{4}-\d{2}$/', $auditMonth)) {
            $mStart = Carbon::parse($auditMonth)->startOfMonth();
            $mEnd = Carbon::parse($auditMonth)->endOfMonth();
            $auditQuery->whereBetween('created_at', [$mStart, $mEnd]);
        }

        if ($auditPaymentMethod !== 'all' && $auditPaymentMethod !== '') {
            $auditQuery->where('payment_method', $auditPaymentMethod);
        }

        $auditOrders = $auditQuery->get();

        return view('customers.show', compact(
            'customer',
            'dailyVolumes',
            'currentMonth',
            'daysInMonth',
            'monthlyBreakdown',
            'lifetimeSummary',
            'auditOrders',
            'auditMonth',
            'auditPaymentMethod'
        ));
    }

    /**
     * Show the form for editing the specified customer.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified customer in the database.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $beforeValues = [
            'name'                        => $customer->name,
            'business_name'               => $customer->business_name,
            'customer_type'               => $customer->customer_type,
            'phone'                       => $customer->phone,
            'default_discount_type'       => $customer->default_discount_type,
            'default_discount_percentage' => $customer->default_discount_percentage,
        ];

        $customer->update($request->validated());
        $customer->refresh();

        AuditTrailService::record([
            'module'                => 'customers',
            'action'                => 'customer.updated',
            'entity_type'           => 'Customer',
            'entity_id'             => $customer->id,
            'transaction_reference' => 'CUS-' . str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT),
            'status'                => 'completed',
            'before_values'         => $beforeValues,
            'after_values'          => [
                'name'                        => $customer->name,
                'business_name'               => $customer->business_name,
                'customer_type'               => $customer->customer_type,
                'phone'                       => $customer->phone,
                'default_discount_type'       => $customer->default_discount_type,
                'default_discount_percentage' => $customer->default_discount_percentage,
            ],
            'reason'                => 'Customer profile details updated',
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer record updated successfully.');
    }

    /**
     * Remove the specified customer from the database.
     * Guard: Cannot delete customer if they have an active credit balance or existing transaction history.
     */
    public function destroy(Customer $customer)
    {
        if (!auth()->user()->isManagerOrOwner()) {
            abort(403, 'Unauthorized action.');
        }

        $customerName = $customer->name;
        $customer->delete();

        AuditTrailService::record([
            'module'                => 'customers',
            'action'                => 'customer.archived',
            'entity_type'           => 'Customer',
            'entity_id'             => $customer->id,
            'transaction_reference' => 'CUS-' . str_pad((string) $customer->id, 5, '0', STR_PAD_LEFT),
            'status'                => 'completed',
            'before_values'         => ['name' => $customerName, 'status' => 'active'],
            'after_values'          => ['name' => $customerName, 'status' => 'archived'],
            'reason'                => 'Customer profile archived (soft-deleted)',
        ]);

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer record deleted successfully.');
    }

    /**
     * API Endpoint for customer autocomplete/search.
     */
    public function searchApi(Request $request)
    {
        $search = $request->query('query');
        
        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $customers = Customer::select('id', 'first_name', 'last_name', 'business_name')
            ->search($search)
            ->take(10)
            ->get()
            ->map(function ($c) {
                $name = trim($c->first_name . ' ' . $c->last_name);
                if ($c->business_name) {
                    $name .= " ({$c->business_name})";
                }
                return ['id' => $c->id, 'name' => $name, 'value' => $name];
            });

        return response()->json($customers);
    }
}