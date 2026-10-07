<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Http\Requests\StoreOrderRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class PosController extends Controller
{
    public function create()
    {
        $products = Product::where('stock_quantity', '>', 0)->orderBy('name')->get();
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        return view('pos.create', compact('products', 'customers'));
    }

    public function store(StoreOrderRequest $request)
    {
        $userId = Auth::id() ?? 'guest';
        $lock = Cache::lock("checkout_lock_user_{$userId}", 5);

        if (! $lock->get()) {
            abort(429, 'A checkout is already being processed. Please wait.');
        }

        try {
            $validated = $request->validated();
            
            // Defensive guard against empty items payload
            if (empty($validated['items']) || !is_array($validated['items'])) {
                return back()->withErrors('Transaction failed: Cart cannot be empty.');
            }

            // Safely resolve the customer if one was provided
            $customer = isset($validated['customer_id']) ? Customer::find($validated['customer_id']) : null;

            $order = DB::transaction(function () use ($validated, $customer) {
                $totalAmount = 0;
                $year = date('Y');

                $order = Order::create([
                    'customer_id' => $customer ? $customer->id : null,
                    'user_id' => Auth::id(),
                    'total_amount' => 0, 
                    'payment_method' => $validated['payment_method'],
                    'discount_amount' => (isset($validated['discount_amount']) && $validated['discount_amount'] !== '') ? (float)$validated['discount_amount'] : 0,
                    'discount_type' => (!empty($validated['discount_type']) && $validated['discount_type'] !== 'none') ? $validated['discount_type'] : null,
                    'discount_reference_name' => !empty($validated['discount_reference_name']) ? $validated['discount_reference_name'] : null,
                    'discount_reference_id' => !empty($validated['discount_reference_id']) ? $validated['discount_reference_id'] : null,
                    'senior_id' => !empty($validated['discount_reference_id']) ? $validated['discount_reference_id'] : (!empty($validated['senior_id']) ? $validated['senior_id'] : null),
                ]);

                $invoiceNumber = 'INV-' . $year . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);
                if (Order::where('invoice_number', $invoiceNumber)->where('id', '!=', $order->id)->exists()) {
                    $invoiceNumber .= '-' . strtoupper(\Illuminate\Support\Str::random(4));
                }
                $order->update(['invoice_number' => $invoiceNumber]);

                foreach ($validated['items'] as $item) {
                    if ((int)$item['quantity'] < 1) {
                        throw new \Exception("Invalid quantity for product ID {$item['product_id']}.");
                    }

                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    
                    // Fix #2: Prevent negative inventory
                    if ($product->stock_quantity < $item['quantity']) {
                        throw new \Exception("Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}, Requested: {$item['quantity']}.");
                    }

                    $subtotal = 0;
                    $actualConsumedKg = null;

                    if ($item['is_swap']) {
                        // Refill sale: customer surrendered an empty cylinder
                        if ($customer && in_array($customer->customer_type, ['Coke (Residual)', 'Company']) && !is_null($product->standard_capacity_kg)) {
                            $residual = isset($item['residual_kg']) && is_numeric($item['residual_kg']) ? (float)$item['residual_kg'] : 0;
                            $actualConsumedKg = max(0, $product->standard_capacity_kg - $residual);
                            $pricePerKg = $product->price / $product->standard_capacity_kg;
                            $subtotal = round($actualConsumedKg * $pricePerKg * $item['quantity'], 2);
                        } else {
                            $subtotal = round($product->price * $item['quantity'], 2);
                        }

                        $product->decrement('stock_quantity', $item['quantity']);
                        if (!is_null($product->standard_capacity_kg) || !is_null($product->new_cylinder_price)) { 
                            $product->increment('empty_quantity', $item['quantity']);
                        }
                    } else {
                        // New cylinder purchase: customer does NOT surrender an empty cylinder
                        // Strictly use new_cylinder_price if available (flat total cost of tank + gas); otherwise regular price
                        $unitPrice = !is_null($product->new_cylinder_price) ? $product->new_cylinder_price : $product->price;
                        $subtotal = round($unitPrice * $item['quantity'], 2);

                        $product->decrement('stock_quantity', $item['quantity']);
                    }

                    $totalAmount += $subtotal;

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $product->id,
                        'quantity' => (int)$item['quantity'],
                        'is_swap' => filter_var($item['is_swap'] ?? false, FILTER_VALIDATE_BOOLEAN),
                        'residual_kg' => (isset($item['residual_kg']) && $item['residual_kg'] !== '' && is_numeric($item['residual_kg'])) ? (float)$item['residual_kg'] : null,
                        'actual_consumed_kg' => $actualConsumedKg !== null ? (float)$actualConsumedKg : null,
                        'subtotal' => $subtotal,
                    ]);
                }

                // Mathematically cap discount to order subtotal
                $requestedDiscount = (float)($order->discount_amount ?? 0);
                $discountAmount = min($requestedDiscount, $totalAmount);
                $finalTotal = max(0, $totalAmount - $discountAmount);
                $order->update([
                    'discount_amount' => $discountAmount,
                    'total_amount' => $finalTotal,
                ]);

                if ($order->payment_method === 'Credit') {
                    if (!$customer) {
                        throw new \Exception("A customer must be selected to use credit.");
                    }
                    
                    $creditAccount = CreditAccount::lockForUpdate()->where('customer_id', $customer->id)->first();
                    if (!$creditAccount) {
                        throw new \Exception("Customer does not have an active credit account.");
                    }

                    // Improvement #3: Block charges against suspended credit accounts
                    if (!$creditAccount->is_active) {
                        throw new \Exception("This customer's credit account is currently suspended. Reactivate it before allowing credit purchases.");
                    }

                    CreditLedger::create([
                        'credit_account_id' => $creditAccount->id,
                        'order_id'          => $order->id,
                        'transaction_type'  => 'Charge',
                        'amount'            => $order->total_amount,
                    ]);
                }

                return $order;
            });

            return redirect()->route('pos.show', $order->id)->with('success', 'Transaction completed successfully.');

        } catch (\Exception $e) {
            return back()->withErrors('Transaction failed: ' . $e->getMessage());
        } finally {
            optional($lock)->release();
        }
    }

    public function show(Order $order)
    {
        if (!Auth::user()->isManagerOrOwner() && $order->user_id !== Auth::id()) {
            abort(403, 'Unauthorized action.');
        }

        $order->load(['items.product', 'customer', 'user']);
        return view('pos.show', compact('order'));
    }
}
