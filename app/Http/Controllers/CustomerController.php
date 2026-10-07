<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with eager-loaded credit balances.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $query = Customer::with('creditAccount')
            ->orderBy('first_name')
            ->orderBy('last_name');

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->where('first_name', $like, "%{$search}%")
                  ->orWhere('last_name', $like, "%{$search}%")
                  ->orWhere('business_name', $like, "%{$search}%")
                  ->orWhere('phone', $like, "%{$search}%");
            });
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
        Customer::create($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer record created successfully.');
    }

    /**
     * Display the specified customer profile and daily volume matrix.
     */
    public function show(Customer $customer, Request $request)
    {
        $currentMonth = $request->input('month', now()->format('Y-m'));
        $startOfMonth = Carbon::parse($currentMonth)->startOfMonth();
        $endOfMonth = Carbon::parse($currentMonth)->endOfMonth();

        // Load related monthly orders and items along with the credit account
        $customer->load(['orders' => function ($query) use ($startOfMonth, $endOfMonth) {
            $query->whereBetween('created_at', [$startOfMonth, $endOfMonth])
                  ->with('items')
                  ->orderBy('created_at', 'desc');
        }, 'creditAccount']);

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

        return view('customers.show', compact('customer', 'dailyVolumes', 'currentMonth', 'daysInMonth'));
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
        $customer->update($request->validated());

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

        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer record deleted successfully.');
    }
}