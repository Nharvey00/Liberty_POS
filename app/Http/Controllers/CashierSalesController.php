<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashierSalesController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::with(['customer', 'items.product'])
            ->where('user_id', Auth::id())
            ->latest();

        $search = $request->query('search');
        if ($search) {
            $like = \DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
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

        $todaySales = Order::where('user_id', Auth::id())
            ->valid()
            ->whereDate('created_at', \Carbon\Carbon::today())
            ->sum('total_amount');

        return view('cashier.sales', compact('orders', 'search', 'todaySales'));
    }
}
