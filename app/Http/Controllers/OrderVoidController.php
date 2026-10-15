<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\CreditAccount;
use App\Models\CreditLedger;
use App\Models\InventoryMovement;
use App\Models\StockIn;
use App\Models\StatementOfAccount;
use App\Services\AuditTrailService;
use App\Services\InventoryLedgerService;
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
                AuditTrailService::resetCorrelationId();
                $correlationId = AuditTrailService::correlationId();

                $lockedOrder = Order::lockForUpdate()->findOrFail($order->id);

                if ($lockedOrder->status === 'voided') {
                    throw new \Exception('Order is already voided.');
                }

                $beforeStatus      = $lockedOrder->status;
                $originalCashierId = $lockedOrder->user_id;
                $voidTimestamp     = now();
                $referenceNo       = $lockedOrder->invoice_number ?? ('OR-' . str_pad($lockedOrder->id, 6, '0', STR_PAD_LEFT));
                $restockedItems    = [];

                // Preserve original cashier (user_id) untouched while recording void authorizer
                $lockedOrder->update([
                    'status'      => 'voided',
                    'voided_by'   => Auth::id(),
                    'voided_at'   => $voidTimestamp,
                    'void_reason' => $validated['void_reason'],
                ]);

                foreach ($lockedOrder->items as $item) {
                    $product = Product::withTrashed()->lockForUpdate()->find($item->product_id);
                    if ($product) {
                        $filledBefore = (int) $product->stock_quantity;
                        $emptyBefore  = (int) $product->empty_quantity;

                        $product->increment('stock_quantity', $item->quantity);
                        
                        if ($item->is_swap && $product->isCylinder()) {
                            // Shells may already have been returned to the supplier; never go negative
                            $emptyToReverse = min((int) $product->empty_quantity, (int) $item->quantity);
                            if ($emptyToReverse > 0) {
                                $product->decrement('empty_quantity', $emptyToReverse);
                            }
                        }

                        $product->refresh();
                        $filledAfter = (int) $product->stock_quantity;
                        $emptyAfter  = (int) $product->empty_quantity;

                        StockIn::create([
                            'product_id'         => $product->id,
                            'user_id'            => Auth::id(),
                            'approved_by'        => Auth::id(),
                            'reference_no'       => 'VOID-' . $referenceNo,
                            'quantity_received'  => $item->quantity,
                            'empty_returned_qty' => 0,
                            'unit_cost'          => $product->cost_price,
                            'filled_qty_before'  => $filledBefore,
                            'filled_qty_after'   => $filledAfter,
                            'empty_qty_before'   => $emptyBefore,
                            'empty_qty_after'    => $emptyAfter,
                            'remarks'            => 'Stock In (Void Reversal): ' . $referenceNo,
                        ]);

                        InventoryLedgerService::recordMovement($product, [
                            'reference_no'      => $referenceNo,
                            'movement_type'     => InventoryMovement::TYPE_VOID_RESTOCK,
                            'filled_qty_before' => $filledBefore,
                            'filled_qty_change' => $filledAfter - $filledBefore,
                            'filled_qty_after'  => $filledAfter,
                            'empty_qty_before'  => $emptyBefore,
                            'empty_qty_change'  => $emptyAfter - $emptyBefore,
                            'empty_qty_after'   => $emptyAfter,
                            'unit_cost'         => $product->cost_price,
                            'unit_price'        => $item->unit_price ?? $product->price,
                            'source_type'       => 'Order',
                            'source_id'         => $lockedOrder->id,
                            'user_id'           => Auth::id(),
                            'approved_by'       => Auth::id(),
                            'reason'            => 'Void Reversal: ' . $validated['void_reason'],
                        ]);

                        $restockedItems[] = [
                            'product_id'   => $product->id,
                            'product_name' => $product->name,
                            'quantity'     => (int) $item->quantity,
                            'stock_before' => $filledBefore,
                            'stock_after'  => $filledAfter,
                        ];
                    }
                }

                if ($lockedOrder->payment_method === 'Credit') {
                    $chargeLedger = CreditLedger::where('order_id', $lockedOrder->id)
                        ->where('transaction_type', 'Charge')
                        ->first();

                    if ($chargeLedger) {
                        $lockedAccount = CreditAccount::lockForUpdate()->find($chargeLedger->credit_account_id);
                        $balanceBefore = $lockedAccount ? (float) $lockedAccount->remaining_balance : 0.0;
                        $balanceAfter  = round($balanceBefore - (float) $lockedOrder->total_amount, 2);

                        CreditLedger::create([
                            'credit_account_id' => $chargeLedger->credit_account_id,
                            'transaction_type'  => 'Payment',
                            'amount'            => $lockedOrder->total_amount,
                            'order_id'          => $lockedOrder->id,
                            'user_id'           => Auth::id(),
                            'balance_before'    => $balanceBefore,
                            'balance_after'     => $balanceAfter,
                            'notes'             => 'Void Reversal (' . $referenceNo . '): ' . $validated['void_reason'],
                        ]);

                        // Same rule as PaymentController: a cleared balance settles open statements
                        if ($balanceAfter <= 0) {
                            StatementOfAccount::where('credit_account_id', $chargeLedger->credit_account_id)
                                ->where(function ($q) {
                                    if (DB::connection()->getDriverName() === 'pgsql') {
                                        $q->where('is_paid', DB::raw('false'));
                                    } else {
                                        $q->where('is_paid', 'false')
                                          ->orWhere('is_paid', false)
                                          ->orWhere('is_paid', 0);
                                    }
                                })
                                ->get()
                                ->each(function ($soa) {
                                    $soa->is_paid = true;
                                    $soa->save();
                                });
                        }
                    }
                }

                AuditTrailService::record([
                    'correlation_id'        => $correlationId,
                    'module'                => 'sales',
                    'action'                => 'sale.voided',
                    'entity_type'           => 'Order',
                    'entity_id'             => $lockedOrder->id,
                    'transaction_reference' => $referenceNo,
                    'status'                => 'voided',
                    'approval_status'       => 'approved',
                    'approver_user_id'      => Auth::id(),
                    'approved_at'           => $voidTimestamp,
                    'before_values'         => [
                        'status'              => $beforeStatus,
                        'original_cashier_id' => $originalCashierId,
                        'total_amount'        => (float) $lockedOrder->total_amount,
                    ],
                    'after_values'          => [
                        'status'              => 'voided',
                        'original_cashier_id' => $lockedOrder->user_id,
                        'voided_by'           => Auth::id(),
                        'voided_at'           => $voidTimestamp->toIso8601String(),
                        'restocked_items'     => $restockedItems,
                    ],
                    'reason'                => $validated['void_reason'],
                ]);
            });
        } catch (\Exception $e) {
            return redirect()->route('orders.show', $order)->with('error', $e->getMessage());
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order has been successfully voided.');
    }
}
