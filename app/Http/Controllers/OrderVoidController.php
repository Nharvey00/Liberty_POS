<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\CreditLedger;
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

        DB::transaction(function () use ($order, $validated) {
            $order->update([
                'status' => 'voided',
                'voided_by' => Auth::id(),
                'voided_at' => now(),
                'void_reason' => $validated['void_reason'],
            ]);

            foreach ($order->items as $item) {
                $product = $item->product;
                if ($product) {
                    $product->increment('stock_quantity', $item->quantity);
                    
                    if ($item->is_swap && $product->standard_capacity_kg !== null) {
                        $product->decrement('empty_quantity', $item->quantity);
                    }
                }
            }

            if ($order->payment_method === 'Credit') {
                $chargeLedger = CreditLedger::where('order_id', $order->id)
                    ->where('transaction_type', 'Charge')
                    ->first();

                if ($chargeLedger) {
                    CreditLedger::create([
                        'credit_account_id' => $chargeLedger->credit_account_id,
                        'transaction_type' => 'Payment',
                        'amount' => $order->total_amount,
                        'order_id' => $order->id,
                    ]);
                }
            }
        });

        return redirect()->route('orders.show', $order)->with('success', 'Order has been successfully voided.');
    }
}
