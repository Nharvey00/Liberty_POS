<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\StockIn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderVoidController extends Controller
{
    public function create(Order $order)
    {
        if (!Auth::user()->isManagerOrOwner()) {
            abort(403, 'Unauthorized action.');
        }

        if ($order->isVoided()) {
            return redirect()->route('orders.show', $order)->with('error', 'Order is already voided.');
        }

        return view('orders.void', compact('order'));
    }

    public function store(Request $request, Order $order)
    {
        if (!Auth::user()->isManagerOrOwner()) {
            abort(403, 'Unauthorized action.');
        }

        if ($order->isVoided()) {
            return redirect()->route('orders.show', $order)->with('error', 'Order is already voided.');
        }

        $validated = $request->validate([
            'void_reason' => 'required|string|min:5',
        ]);

        try {
            DB::transaction(function () use ($order, $validated) {
                $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->status === 'voided') {
                    throw new \Exception('Order is already voided.');
                }

                $lockedOrder->update([
                    'status' => 'voided',
                    'voided_by' => Auth::id(),
                    'voided_at' => now(),
                    'void_reason' => $validated['void_reason'],
                ]);

                foreach ($lockedOrder->items as $item) {
                    $product = Product::withTrashed()->lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $product->increment('stock_quantity', $item->quantity);
                        
                        if ($item->is_swap && $product->isCylinder()) {
                            $product->decrement('empty_quantity', $item->quantity);
                        }

                        StockIn::create([
                            'product_id' => $product->id,
                            'quantity_received' => $item->quantity,
                            'empty_returned_qty' => 0,
                            'remarks' => 'Stock In (Void Reversal): ' . ($lockedOrder->invoice_number ?? ('OR-' . str_pad($lockedOrder->id, 6, '0', STR_PAD_LEFT))),
                        ]);
                    }
                }

                if ($lockedOrder->payment_method === 'Credit') {
                    $chargeLedger = CreditLedger::where('order_id', $lockedOrder->id)
                        ->where('transaction_type', 'Charge')
                        ->first();

                    if ($chargeLedger) {
                        $lockedAccount = CreditAccount::lockForUpdate()->find($chargeLedger->credit_account_id);
                        CreditLedger::create([
                            'credit_account_id' => $chargeLedger->credit_account_id,
                            'transaction_type' => 'Payment',
                            'amount' => $lockedOrder->total_amount,
                            'order_id' => $lockedOrder->id,
                        ]);
                    }
                }
            });
        } catch (\Exception $e) {
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order has been successfully voided.');
    }
}
