<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function searchApi(Request $request)
    {
        $search = $request->query('query');
        
        if (!$search || strlen($search) < 2) {
            return response()->json([]);
        }

        $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
        
        $orders = Order::select('id', 'invoice_number', 'customer_id')
            ->with(['customer' => function ($q) {
                $q->select('id', 'first_name', 'last_name', 'business_name');
            }])
            ->where(function ($q) use ($search, $like) {
                $cleanSearch = preg_replace('/[^0-9]/', '', $search);
                if (!empty($cleanSearch)) {
                    $q->where('id', (int)$cleanSearch);
                }
                $q->orWhere('invoice_number', $like, "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search, $like) {
                      $cq->where('first_name', $like, "%{$search}%")
                         ->orWhere('last_name', $like, "%{$search}%")
                         ->orWhere('business_name', $like, "%{$search}%");
                  });
            })
            ->take(10)
            ->get()
            ->map(function ($o) {
                $customerName = 'Walk-in';
                if ($o->customer) {
                    $customerName = trim($o->customer->first_name . ' ' . $o->customer->last_name);
                    if ($o->customer->business_name) {
                        $customerName .= " ({$o->customer->business_name})";
                    }
                }
                
                $identifier = "OR-" . str_pad($o->id, 6, '0', STR_PAD_LEFT);
                if ($o->invoice_number) {
                    $identifier .= " | INV: {$o->invoice_number}";
                }
                
                return [
                    'id' => $o->id, 
                    'name' => "{$identifier} - {$customerName}"
                ];
            });
            
        return response()->json($orders);
    }

    public function index(Request $request)
    {
        $status = $request->query('status');
        $search = $request->query('search');

        $query = Order::with(['customer', 'items.product', 'user', 'voidedByUser'])->latest();

        if ($status === 'voided') {
            $query->where('status', 'voided');
        } elseif ($status === 'completed') {
            $query->where('status', 'completed');
        }

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $cleanSearch = preg_replace('/[^0-9]/', '', $search);
                if (!empty($cleanSearch)) {
                    $q->where('id', (int)$cleanSearch);
                }
                $q->orWhere('invoice_number', $like, "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search, $like) {
                      $cq->where('first_name', $like, "%{$search}%")
                         ->orWhere('last_name', $like, "%{$search}%")
                         ->orWhere('business_name', $like, "%{$search}%");
                  });
            });
        }

        $orders = $query->paginate(15)->withQueryString();
        $totalCount = Order::count();
        $completedCount = Order::where('status', 'completed')->count();
        $voidedCount = Order::where('status', 'voided')->count();

        return view('orders.index', compact('orders', 'status', 'search', 'totalCount', 'completedCount', 'voidedCount'));
    }

    public function show(Order $order)
    {
        $order->load(['items.product', 'customer', 'user']);
        return view('orders.show', compact('order'));
    }
}