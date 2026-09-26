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

class PosController extends Controller
{
    public function create()
    {
        $products = Product::where('stock_quantity', '>', 0)->orderBy('name')->get();
        $customers = Customer::orderBy('name')->get();
        return view('pos.create', compact('products', 'customers'));
    }

    public function store(StoreOrderRequest $request)
    {
        $validated = $request->validated();
        
        // Safely resolve the customer if one was provided
        $customer = isset($validated['customer_id']) ? Customer::find($validated['customer_id']) : null;

        DB::beginTransaction();

        try {
            $totalAmount = 0;

            $order = Order::create([
                'customer_id' => $customer ? $customer->id : null,
                'user_id' => Auth::id(),
                'total_amount' => 0, 
                'payment_method' => $validated['payment_method'],
                'discount_amount' => $validated['discount_amount'] ?? 0,
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                
                // Fix #2: Prevent negative inventory
                if ($product->stock_quantity < $item['quantity']) {
                    throw new \Exception("Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}, Requested: {$item['quantity']}.");
                }

                $subtotal = 0;
                $actualConsumedKg = null;

                // Safely check customer type (only applies to Company customers returning/swapping an empty tank)
                if ($customer && $customer->customer_type === 'Company' && !is_null($product->standard_capacity_kg) && $item['is_swap']) {
                    $residual = isset($item['residual_kg']) && is_numeric($item['residual_kg']) ? (float)$item['residual_kg'] : 0;
                    $actualConsumedKg = max(0, $product->standard_capacity_kg - $residual);
                    $pricePerKg = $product->price / $product->standard_capacity_kg;
                    $subtotal = round($actualConsumedKg * $pricePerKg * $item['quantity'], 2);
                } else {
                    $subtotal = round($product->price * $item['quantity'], 2);
                }

                if ($item['is_swap']) {
                    $product->decrement('stock_quantity', $item['quantity']);
                    if (!is_null($product->new_cylinder_price)) { 
                        $product->increment('empty_quantity', $item['quantity']);
                    }
                } else {
                    if (!is_null($product->new_cylinder_price)) {
                        $subtotal += ($product->new_cylinder_price * $item['quantity']);
                    }
                    $product->decrement('stock_quantity', $item['quantity']);
                }

                $totalAmount += $subtotal;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'is_swap' => $item['is_swap'],
                    'residual_kg' => $item['residual_kg'] ?? null,
                    'actual_consumed_kg' => $actualConsumedKg,
                    'subtotal' => $subtotal,
                ]);
            }

            $finalTotal = $totalAmount - $order->discount_amount;
            $order->update(['total_amount' => max(0, $finalTotal)]);

            if ($order->payment_method === 'Credit') {
                if (!$customer) {
                    throw new \Exception("A customer must be selected to use credit.");
                }
                
                $creditAccount = CreditAccount::where('customer_id', $customer->id)->first();
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

            DB::commit();
            return redirect()->route('pos.show', $order->id)->with('success', 'Transaction completed successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors('Transaction failed: ' . $e->getMessage());
        }
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'customer', 'user']);
        return view('pos.show', compact('order'));
    }
}