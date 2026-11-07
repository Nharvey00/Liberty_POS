<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Product;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\TankLoan;
use App\Models\TankLoanReturn;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TankLoanController extends Controller
{
    /**
     * Display the Loaned Tanks (Huram) hub with summary metrics and filterable records.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $statusFilter = $request->query('status', 'all');

        // Summary KPIs
        $activeLoansQuery = TankLoan::whereIn('status', ['borrowed', 'partially_returned'])
            ->whereColumn('quantity_borrowed', '>', 'quantity_returned');

        $totalActiveBorrowedTanks = (int) (clone $activeLoansQuery)
            ->selectRaw('COALESCE(SUM(quantity_borrowed - quantity_returned), 0) as total_active')
            ->value('total_active');

        $customersWithBorrowedTanks = (int) (clone $activeLoansQuery)
            ->distinct('customer_id')
            ->count('customer_id');

        $totalReturnedToday = (int) TankLoanReturn::valid()
            ->whereDate('returned_at', Carbon::today())
            ->sum('quantity_returned');

        // Main Loaned Tanks Query
        $query = TankLoan::with(['customer', 'product', 'createdByUser', 'stockOut', 'returns'])
            ->orderByRaw("CASE WHEN status IN ('borrowed', 'partially_returned') THEN 0 WHEN status = 'returned' THEN 1 ELSE 2 END")
            ->orderBy('borrowed_at', 'desc')
            ->orderBy('id', 'desc');

        if ($statusFilter === 'active') {
            $query->whereIn('status', ['borrowed', 'partially_returned']);
        } elseif (in_array($statusFilter, ['borrowed', 'partially_returned', 'returned', 'voided'], true)) {
            $query->where('status', $statusFilter);
        }

        if ($search) {
            $like = DB::connection()->getDriverName() === 'pgsql' ? 'ilike' : 'like';
            $query->where(function ($q) use ($search, $like) {
                $q->whereHas('customer', function ($cq) use ($search) {
                    $cq->search($search);
                })->orWhereHas('product', function ($pq) use ($search, $like) {
                    $pq->where('name', $like, "%{$search}%");
                })->orWhere('notes', $like, "%{$search}%");
            });
        }

        $tankLoans = $query->paginate(15)->withQueryString();

        // Cylinder products and customers for the "+ New Loaned Tank" modal/form
        $products = Product::orderBy('name')->get()->reject(fn ($p) => $p->isAccessory())->values();
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();

        return view('tank_loans.index', compact(
            'tankLoans',
            'search',
            'statusFilter',
            'totalActiveBorrowedTanks',
            'customersWithBorrowedTanks',
            'totalReturnedToday',
            'products',
            'customers'
        ));
    }

    /**
     * Show the standalone form for recording a new Loaned Tank (Huram).
     */
    public function create(Request $request)
    {
        $products = Product::orderBy('name')->get()->reject(fn ($p) => $p->isAccessory())->values();
        $customers = Customer::orderBy('first_name')->orderBy('last_name')->get();
        $selectedCustomerId = $request->query('customer_id');

        return view('tank_loans.create', compact('products', 'customers', 'selectedCustomerId'));
    }

    /**
     * Store a newly recorded Loaned Tank (Entry Point B) in an atomic transaction.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'product_id' => 'required|exists:products,id',
            'tank_condition_out' => 'required|in:filled,empty_shell',
            'quantity_borrowed' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:500',
            'redirect_to_customer' => 'nullable|boolean',
        ]);

        DB::transaction(function () use ($validated) {
            $product = Product::lockForUpdate()->findOrFail($validated['product_id']);

            if ($product->isAccessory()) {
                throw ValidationException::withMessages([
                    'product_id' => 'Only LPG cylinder/tank products can be loaned out as Huram tanks.',
                ]);
            }

            $qty = (int) $validated['quantity_borrowed'];
            $conditionOut = $validated['tank_condition_out'];

            $quantityRemoved = $conditionOut === 'filled' ? $qty : 0;
            $emptyQuantityRemoved = $conditionOut === 'empty_shell' ? $qty : 0;

            if ($quantityRemoved > 0 && $product->stock_quantity < $quantityRemoved) {
                throw ValidationException::withMessages([
                    'quantity_borrowed' => "Insufficient filled stock for '{$product->name}'. Available filled stock: {$product->stock_quantity}.",
                ]);
            }

            if ($emptyQuantityRemoved > 0 && $product->empty_quantity < $emptyQuantityRemoved) {
                throw ValidationException::withMessages([
                    'quantity_borrowed' => "Insufficient empty cylinder shells for '{$product->name}'. Available empty shells: {$product->empty_quantity}.",
                ]);
            }

            // 1. Create Stock Out audit entry
            $stockOut = StockOut::create([
                'product_id' => $product->id,
                'quantity_removed' => $quantityRemoved,
                'empty_quantity_removed' => $emptyQuantityRemoved,
                'reason' => 'Loaned Tank (Huram)',
            ]);

            // 2. Deduct from inventory
            $product->stock_quantity -= $quantityRemoved;
            $product->empty_quantity -= $emptyQuantityRemoved;
            $product->save();

            // 3. Create linked TankLoan record (₱0.00 monetary charge)
            TankLoan::create([
                'customer_id' => $validated['customer_id'],
                'product_id' => $product->id,
                'stock_out_id' => $stockOut->id,
                'tank_condition_out' => $conditionOut,
                'quantity_borrowed' => $qty,
                'quantity_returned' => 0,
                'status' => 'borrowed',
                'notes' => $validated['notes'] ?? null,
                'created_by' => Auth::id(),
                'borrowed_at' => now(),
            ]);
        });

        if ($request->boolean('redirect_to_customer')) {
            return redirect()
                ->route('tank-loans.customer', $validated['customer_id'])
                ->with('success', 'Loaned tank (Huram) recorded and inventory deducted.');
        }

        return redirect()
            ->route('tank-loans.index')
            ->with('success', 'Loaned tank (Huram) recorded and inventory deducted.');
    }

    /**
     * Show detailed Loaned Tanks (Huram) history and return controls for a specific customer.
     */
    public function showCustomer(Customer $customer)
    {
        $customer->load([
            'tankLoans' => function ($query) {
                $query->with([
                    'product',
                    'createdByUser',
                    'stockOut',
                    'voidedByUser',
                    'returns' => function ($rq) {
                        $rq->with(['receivedByUser', 'stockIn', 'voidedByUser'])->orderBy('returned_at', 'desc');
                    },
                ])
                ->orderByRaw("CASE WHEN status IN ('borrowed', 'partially_returned') THEN 0 WHEN status = 'returned' THEN 1 ELSE 2 END")
                ->orderBy('borrowed_at', 'desc');
            },
            'creditAccount',
        ]);

        $nonVoidedLoans = $customer->tankLoans->where('status', '!=', 'voided');
        $activeBorrowedCount = (int) $nonVoidedLoans->sum(fn ($l) => max(0, (int) $l->quantity_borrowed - (int) $l->quantity_returned));
        $totalEverBorrowed = (int) $nonVoidedLoans->sum('quantity_borrowed');
        $totalEverReturned = (int) $nonVoidedLoans->sum('quantity_returned');

        $products = Product::orderBy('name')->get()->reject(fn ($p) => $p->isAccessory())->values();

        return view('tank_loans.customer', compact(
            'customer',
            'activeBorrowedCount',
            'totalEverBorrowed',
            'totalEverReturned',
            'products'
        ));
    }

    /**
     * Record a partial or full return of a customer's borrowed tank (Huram).
     */
    public function returnTank(Request $request, TankLoan $tankLoan)
    {
        $validated = $request->validate([
            'quantity_returned' => 'required|integer|min:1',
            'returned_as' => 'required|in:empty_shell,filled',
            'notes' => 'nullable|string|max:500',
        ]);

        DB::transaction(function () use ($tankLoan, $validated) {
            $lockedLoan = TankLoan::with('customer')->lockForUpdate()->findOrFail($tankLoan->id);

            if ($lockedLoan->status === 'voided') {
                throw ValidationException::withMessages([
                    'quantity_returned' => 'Cannot return tanks for a voided loan record.',
                ]);
            }

            $remaining = max(0, (int) $lockedLoan->quantity_borrowed - (int) $lockedLoan->quantity_returned);
            $qtyToReturn = (int) $validated['quantity_returned'];

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    'quantity_returned' => 'All borrowed tanks for this loan record have already been returned.',
                ]);
            }

            if ($qtyToReturn > $remaining) {
                throw ValidationException::withMessages([
                    'quantity_returned' => "Cannot return {$qtyToReturn} tank(s). Only {$remaining} tank(s) remain unreturned on this loan.",
                ]);
            }

            $product = Product::withTrashed()->lockForUpdate()->findOrFail($lockedLoan->product_id);
            $returnedAs = $validated['returned_as'];

            $qtyReceivedFilled = $returnedAs === 'filled' ? $qtyToReturn : 0;
            $emptyShellsReceived = $returnedAs === 'empty_shell' ? $qtyToReturn : 0;

            // 1. Increment inventory based on Returned As condition
            $product->stock_quantity += $qtyReceivedFilled;
            $product->empty_quantity += $emptyShellsReceived;
            $product->save();

            // 2. Log Stock In entry for audit trail
            $conditionLabel = $returnedAs === 'filled' ? 'Filled Tank' : 'Empty Shell';
            $customerName = $lockedLoan->customer->name ?? 'Customer';
            $remarks = "Huram Tank Return ({$conditionLabel}) - {$customerName}";
            if (!empty($validated['notes'])) {
                $remarks .= " | {$validated['notes']}";
            }

            $stockIn = StockIn::create([
                'product_id' => $product->id,
                'quantity_received' => $qtyReceivedFilled,
                'empty_returned_qty' => 0,
                'empty_shells_received' => $emptyShellsReceived,
                'remarks' => $remarks,
            ]);

            // 3. Create TankLoanReturn audit record
            TankLoanReturn::create([
                'tank_loan_id' => $lockedLoan->id,
                'stock_in_id' => $stockIn->id,
                'returned_as' => $returnedAs,
                'quantity_returned' => $qtyToReturn,
                'notes' => $validated['notes'] ?? null,
                'received_by' => Auth::id(),
                'returned_at' => now(),
            ]);

            // 4. Update parent TankLoan status and returned count
            $lockedLoan->quantity_returned = (int) $lockedLoan->quantity_returned + $qtyToReturn;
            if ($lockedLoan->quantity_returned >= (int) $lockedLoan->quantity_borrowed) {
                $lockedLoan->status = 'returned';
                $lockedLoan->returned_at = now();
            } else {
                $lockedLoan->status = 'partially_returned';
            }
            $lockedLoan->save();
        });

        return back()->with('success', 'Returned tank(s) recorded and inventory updated.');
    }
}
