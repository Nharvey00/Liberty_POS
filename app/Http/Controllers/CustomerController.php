<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use Illuminate\Http\Request;
use Carbon\Carbon;

class CustomerController extends Controller
{
    /**
     * Display a listing of customers with eager-loaded credit balances.
     */
    public function index()
    {
        $customers = Customer::with('creditAccount')
            ->orderBy('name')
            ->paginate(15);

        return view('customers.index', compact('customers'));
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
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer record deleted successfully.');
    }
}