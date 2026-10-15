<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\Customer;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\InventoryMovement;
use App\Http\Requests\StoreOrderRequest;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
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
                AuditTrailService::resetCorrelationId();
                $correlationId = AuditTrailService::correlationId();
                $totalAmount = 0;
                $year = date('Y');
                $auditedItemsSummary = [];

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

                // Acquire row locks in a deterministic (ascending ID) order to prevent deadlocks
                // between concurrent checkouts that contain the same products in a different order.
                $productIds = collect($validated['items'])->pluck('product_id')->unique()->sort()->values();
                Product::whereIn('id', $productIds)->orderBy('id')->lockForUpdate()->get();

                foreach ($validated['items'] as $item) {
                    $qty = (int) $item['quantity'];
                    if ($qty < 1) {
                        throw new \Exception("Invalid quantity for product ID {$item['product_id']}.");
                    }

                    $product = Product::lockForUpdate()->findOrFail($item['product_id']);
                    
                    // Fix #2: Prevent negative inventory
                    if ($product->stock_quantity < $qty) {
                        throw new \Exception("Insufficient stock for '{$product->name}'. Available: {$product->stock_quantity}, Requested: {$qty}.");
                    }

                    $filledBefore = (int) $product->stock_quantity;
                    $emptyBefore  = (int) $product->empty_quantity;
                    $isSwap       = filter_var($item['is_swap'] ?? false, FILTER_VALIDATE_BOOLEAN);

                    $subtotal = 0;
                    $actualConsumedKg = null;
                    $effectiveUnitPrice = (float) $product->price;

                    if ($isSwap) {
                        // Refill sale: customer surrendered an empty cylinder
                        if ($customer && in_array($customer->customer_type, ['Coke (Residual)', 'Company', 'Corporate']) && !$product->isAccessory()) {
                            $residual = isset($item['residual_kg']) && is_numeric($item['residual_kg']) ? (float)$item['residual_kg'] : 0;
                            $actualConsumedKg = max(0, $product->standard_capacity_kg - $residual);
                            $pricePerKg = $product->price / $product->standard_capacity_kg;
                            $subtotal = round($actualConsumedKg * $pricePerKg * $qty, 2);
                            $effectiveUnitPrice = round($subtotal / $qty, 2);
                        } else {
                            $effectiveUnitPrice = (float) $product->price;
                            $subtotal = round($effectiveUnitPrice * $qty, 2);
                        }

                        $product->decrement('stock_quantity', $qty);
                        if (!is_null($product->standard_capacity_kg) || !is_null($product->new_cylinder_price)) { 
                            $product->increment('empty_quantity', $qty);
                        }
                    } else {
                        // New cylinder purchase: customer does NOT surrender an empty cylinder
                        // Strictly use new_cylinder_price if available (flat total cost of tank + gas); otherwise regular price
                        $effectiveUnitPrice = !is_null($product->new_cylinder_price) ? (float) $product->new_cylinder_price : (float) $product->price;
                        $subtotal = round($effectiveUnitPrice * $qty, 2);

                        $product->decrement('stock_quantity', $qty);
                    }

                    $product->refresh();
                    $filledAfter = (int) $product->stock_quantity;
                    $emptyAfter  = (int) $product->empty_quantity;

                    $totalAmount += $subtotal;

                    OrderItem::create([
                        'order_id'           => $order->id,
                        'product_id'         => $product->id,
                        'quantity'           => $qty,
                        'unit_price'         => $effectiveUnitPrice,
                        'is_swap'            => $isSwap,
                        'residual_kg'        => (isset($item['residual_kg']) && $item['residual_kg'] !== '' && is_numeric($item['residual_kg'])) ? (float)$item['residual_kg'] : null,
                        'actual_consumed_kg' => $actualConsumedKg !== null ? (float)$actualConsumedKg : null,
                        'subtotal'           => $subtotal,
                    ]);

                    InventoryLedgerService::recordMovement($product, [
                        'reference_no'      => $invoiceNumber,
                        'movement_type'     => InventoryMovement::TYPE_SALE_OUT,
                        'filled_qty_before' => $filledBefore,
                        'filled_qty_change' => $filledAfter - $filledBefore,
                        'filled_qty_after'  => $filledAfter,
                        'empty_qty_before'  => $emptyBefore,
                        'empty_qty_change'  => $emptyAfter - $emptyBefore,
                        'empty_qty_after'   => $emptyAfter,
                        'unit_cost'         => $product->cost_price,
                        'unit_price'        => $effectiveUnitPrice,
                        'source_type'       => 'Order',
                        'source_id'         => $order->id,
                        'user_id'           => Auth::id(),
                        'reason'            => 'POS Customer Sale (' . ($isSwap ? 'Refill/Swap' : 'Outright') . ')',
                    ]);

                    $auditedItemsSummary[] = [
                        'product_id'   => $product->id,
                        'product_name' => $product->name,
                        'quantity'     => $qty,
                        'unit_price'   => $effectiveUnitPrice,
                        'is_swap'      => $isSwap,
                        'subtotal'     => $subtotal,
                        'stock_before' => $filledBefore,
                        'stock_after'  => $filledAfter,
                    ];
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

                    // Improvement #3: Block charges against suspended or unapproved credit accounts
                    $normalizedStatus = strtolower((string) ($creditAccount->status ?? 'approved'));
                    if (!$creditAccount->is_active || in_array($normalizedStatus, ['pending', 'rejected', 'suspended', 'closed'], true)) {
                        throw new \Exception("This customer's credit account is currently suspended or pending approval. Reactivate or approve it before allowing credit purchases.");
                    }

                    $balanceBefore = (float) $creditAccount->remaining_balance;
                    $balanceAfter  = round($balanceBefore + (float) $order->total_amount, 2);

                    if (!is_null($creditAccount->credit_limit) && (float) $creditAccount->credit_limit > 0 && $balanceAfter > (float) $creditAccount->credit_limit) {
                        $availableCredit = max(0, (float) $creditAccount->credit_limit - $balanceBefore);
                        throw new \Exception("Credit limit exceeded. Available credit: ₱" . number_format($availableCredit, 2) . ", Order total: ₱" . number_format($order->total_amount, 2) . ".");
                    }

                    CreditLedger::create([
                        'credit_account_id' => $creditAccount->id,
                        'order_id'          => $order->id,
                        'transaction_type'  => 'Charge',
                        'amount'            => $order->total_amount,
                        'user_id'           => Auth::id(),
                        'balance_before'    => $balanceBefore,
                        'balance_after'     => $balanceAfter,
                        'notes'             => 'POS Credit Sale (' . $invoiceNumber . ')',
                    ]);
                }

                AuditTrailService::record([
                    'correlation_id'        => $correlationId,
                    'module'                => 'sales',
                    'action'                => 'sale.completed',
                    'entity_type'           => 'Order',
                    'entity_id'             => $order->id,
                    'transaction_reference' => $invoiceNumber,
                    'status'                => 'completed',
                    'after_values'          => [
                        'order_id'                => $order->id,
                        'invoice_number'          => $invoiceNumber,
                        'cashier_id'              => Auth::id(),
                        'customer_id'             => $customer?->id,
                        'customer_name'           => $customer?->name ?? 'Walk-in',
                        'payment_method'          => $order->payment_method,
                        'subtotal'                => $totalAmount,
                        'discount_type'           => $order->discount_type,
                        'discount_amount'         => $discountAmount,
                        'discount_reference_name' => $order->discount_reference_name,
                        'discount_reference_id'   => $order->discount_reference_id,
                        'total_amount'            => $finalTotal,
                        'items'                   => $auditedItemsSummary,
                    ],
                    'reason'                => 'Customer sale completed at POS terminal',
                ]);

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
