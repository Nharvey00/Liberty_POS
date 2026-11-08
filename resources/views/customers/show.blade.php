<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full flex-wrap gap-3">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">
                        {{ $customer->business_name ?? $customer->name }}
                    </h1>
                    @php $borrowedCount = $customer->borrowed_tanks_count; @endphp
                    @if($borrowedCount > 0)
                        <a href="{{ route('tank-loans.customer', $customer) }}" class="inline-flex items-center px-2.5 py-1 rounded-full text-[11.5px] font-bold bg-amber-100 text-amber-800 border border-amber-300 hover:bg-amber-200 transition-colors">
                            Borrowed: {{ $borrowedCount }} Tank(s)
                        </a>
                    @endif
                </div>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px]">
                    {{ $customer->customer_type }} Account 
                    @if($customer->business_name)
                        · Contact: {{ $customer->name }}
                    @endif
                    @if($customer->default_discount_type && $customer->default_discount_type !== 'none')
                        · Default Discount:
                        <span class="font-semibold text-[#0B3B70]">
                            {{ ucfirst($customer->default_discount_type) }}
                            ({{ in_array($customer->default_discount_type, ['senior', 'pwd']) ? number_format($customer->default_discount_percentage ?? 0, 2) . '%' : '₱' . number_format($customer->default_discount_percentage ?? 0, 2) }})
                        </span>
                    @endif
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5 flex-wrap">
                <form method="GET" action="{{ route('customers.show', $customer) }}" class="flex items-center gap-2">
                    <input type="hidden" name="audit_month" value="{{ $auditMonth ?? 'all' }}">
                    <input type="hidden" name="payment_method" value="{{ $auditPaymentMethod ?? 'all' }}">
                    <input type="month" name="month" value="{{ $currentMonth }}" onchange="this.form.submit()" class="px-3 py-1.5 border border-[#E5E9EF] rounded-lg text-[13px] bg-white font-semibold text-[#0B3B70]">
                </form>
                <a href="{{ route('customers.index') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Back</a>
                @if(Auth::user()->isManagerOrOwner())
                    <a href="{{ route('tank-loans.customer', $customer) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-amber-500 bg-amber-50 text-amber-900 hover:bg-amber-100">Loaned Tanks ({{ $borrowedCount }})</a>
                    @if($customer->creditAccount)
                        <a href="{{ route('credit-accounts.show', $customer->creditAccount) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#1E8E5A] bg-[#1E8E5A] text-white hover:bg-[#176B45]">View Ledger &amp; Payments</a>
                    @else
                        <a href="{{ route('credit-accounts.create') }}?customer_id={{ $customer->id }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">+ Approve Credit</a>
                    @endif
                @endif
                <a href="{{ route('customers.edit', $customer) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">Edit Profile</a>
            </div>
        </div>
    </x-slot>

    <!-- Top Profile Stats -->
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Outstanding Utang Balance</div>
            @php $custBalance = $customer->creditAccount->remaining_balance ?? 0; @endphp
            @if($custBalance > 0)
                <div class="font-sans text-[22px] font-bold text-[#B5504B]">₱{{ number_format($custBalance, 2) }}</div>
            @elseif($custBalance < 0)
                <div class="font-sans text-[18px] font-bold text-[#1E8E5A]">Advance: ₱{{ number_format(abs($custBalance), 2) }}</div>
            @else
                <div class="font-sans text-[22px] font-bold text-[#1E8E5A]">₱0.00</div>
            @endif
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Active Loaned Tanks (Huram)</div>
            <div class="flex items-center justify-between">
                <div class="font-sans text-[22px] font-bold {{ $borrowedCount > 0 ? 'text-amber-700' : 'text-gray-900' }}">{{ $borrowedCount }} Tank(s)</div>
                <a href="{{ route('tank-loans.customer', $customer) }}" class="text-[11.5px] font-bold text-[#0B3B70] hover:underline">Manage →</a>
            </div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Phone Number</div>
            <div class="font-sans text-[17px] font-bold text-gray-900 truncate">{{ $customer->phone ?? 'Not provided' }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">TIN Number</div>
            <div class="font-sans text-[17px] font-bold text-gray-900 truncate">{{ $customer->tin_number ?? 'Not provided' }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5 col-span-2 md:col-span-1">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Delivery Address</div>
            <div class="font-sans text-[14px] font-bold text-gray-900 truncate">{{ $customer->address ?? 'Not provided' }}</div>
        </div>
    </div>

    <!-- DIGITAL LOGSHEET: Monthly Volume Matrix -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden mb-6 shadow-sm">
        <div class="px-5 py-4 border-b border-[#E5E9EF] flex justify-between items-center bg-[#F4F6F9]">
            <div>
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900">Monthly Purchase Volume Matrix</h3>
                <div class="text-[11.5px] text-[#5B6472]">Digitized daily tracking grid for {{ \Carbon\Carbon::parse($currentMonth)->format('F Y') }} (excludes voided sales)</div>
            </div>
            <div class="text-[12.5px] font-semibold text-[#0B3B70]">
                Total Volume: {{ array_sum($dailyVolumes) }} tanks
            </div>
        </div>

        <div class="p-5 overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse text-center min-w-[700px]">
                <thead>
                    <tr class="bg-[#1C2430] text-white">
                        <th class="whitespace-nowrap py-2.5 px-3 text-[11.5px] font-semibold uppercase tracking-wider text-left rounded-l-lg">Metric</th>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            <th class="whitespace-nowrap py-2.5 px-1 text-[11px] font-semibold border-l border-[#2E3A4E]">{{ $day }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-[#E5E9EF] hover:bg-[#F4F6F9]">
                        <td class="py-3 px-3 text-[13px] font-bold text-[#1C2430] text-left bg-white">Volume Bought</td>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            <td class="py-3 px-1 text-[12.5px] border-l border-[#E5E9EF]">
                                @if($dailyVolumes[$day] > 0)
                                    <span class="inline-block w-6 h-6 leading-6 rounded-md bg-[#B4700A] text-white font-bold shadow-xs">
                                        {{ $dailyVolumes[$day] }}
                                    </span>
                                @else
                                    <span class="text-[#A0AEC0] font-light">—</span>
                                @endif
                            </td>
                        @endfor
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- PHASE 3A: Monthly Summary Breakdown (Non-Voided Sales) -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden mb-6 shadow-sm">
        <div class="px-5 py-4 border-b border-[#E5E9EF] flex flex-col md:flex-row md:items-center justify-between gap-2 bg-[#F4F6F9]">
            <div>
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900">Monthly Purchase &amp; Discount Summary Breakdown</h3>
                <div class="text-[11.5px] text-[#5B6472]">Grouped by Month/Year — strictly excludes voided POS transactions</div>
            </div>
            <div class="flex items-center gap-3 flex-wrap text-[12px]">
                <span class="px-3 py-1 rounded-full bg-white border border-[#E5E9EF] font-semibold text-[#1C2430]">
                    Lifetime Items/Tanks: <strong class="text-[#0B3B70]">{{ number_format($lifetimeSummary['total_qty'] ?? 0) }}</strong>
                </span>
                <span class="px-3 py-1 rounded-full bg-white border border-[#E5E9EF] font-semibold text-[#1C2430]">
                    Lifetime Discounts Given: <strong class="text-[#B4700A]">₱{{ number_format($lifetimeSummary['total_discount'] ?? 0, 2) }}</strong>
                </span>
                <span class="px-3 py-1 rounded-full bg-white border border-[#E5E9EF] font-semibold text-[#1C2430]">
                    Lifetime Net Spend: <strong class="text-[#1E8E5A]">₱{{ number_format($lifetimeSummary['net_spend'] ?? 0, 2) }}</strong>
                </span>
            </div>
        </div>

        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-gray-50/70">
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Month / Year</th>
                        <th class="whitespace-nowrap text-center text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Valid Orders</th>
                        <th class="whitespace-nowrap text-center text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Items / Tanks Bought (Pila Nakuha)</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Gross Purchase Amount</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Total Discounts Given (Pilay Na-Discount)</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Net Spend</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Filter Audit</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($monthlyBreakdown ?? [] as $row)
                        <tr class="hover:bg-[#F4F6F9] transition-colors {{ ($auditMonth ?? 'all') === $row['month_key'] ? 'bg-blue-50/40' : '' }}">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold text-[#1C2430]">{{ $row['month_label'] }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center font-medium text-[#5B6472]">{{ number_format($row['orders_count']) }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center">
                                <span class="inline-block px-2.5 py-0.5 rounded-full text-[12px] font-bold bg-[#E7EEF7] text-[#0B3B70]">
                                    {{ number_format($row['total_qty']) }} pcs
                                </span>
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-right font-medium text-[#1C2430]">₱{{ number_format($row['gross_amount'], 2) }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-right font-bold text-[#B4700A]">
                                @if($row['total_discount'] > 0)
                                    -₱{{ number_format($row['total_discount'], 2) }}
                                @else
                                    <span class="text-gray-400 font-normal">₱0.00</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-right font-bold text-[#1E8E5A]">₱{{ number_format($row['net_spend'], 2) }}</td>
                            <td class="py-3 px-4 text-[12px] border-b border-[#E5E9EF] text-right">
                                <a href="{{ route('customers.show', ['customer' => $customer->id, 'month' => $row['month_key'], 'audit_month' => $row['month_key'], 'payment_method' => $auditPaymentMethod ?? 'all']) }}" class="text-[#0B3B70] font-bold hover:underline">
                                    Filter Month
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-6 text-center text-[12.5px] text-[#5B6472]">No non-voided purchases recorded for this customer yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- PHASE 3B: Itemized Purchase Audit Log & Transaction History -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="px-5 py-4 border-b border-[#E5E9EF] flex flex-col md:flex-row md:items-center justify-between gap-3">
            <div>
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900">Itemized Purchase Audit Log (Transaction History)</h3>
                <div class="text-[11.5px] text-[#5B6472]">Detailed log of exact dates, items/quantities purchased, discounts applied, payment/utang status, and staff member</div>
            </div>

            <form method="GET" action="{{ route('customers.show', $customer) }}" class="flex items-center gap-2 flex-wrap">
                <input type="hidden" name="month" value="{{ $currentMonth }}">
                <select name="audit_month" onchange="this.form.submit()" class="px-3 py-1.5 border border-[#E5E9EF] rounded-lg text-[12.5px] bg-white font-semibold text-[#1C2430]">
                    <option value="all" {{ ($auditMonth ?? 'all') === 'all' ? 'selected' : '' }}>All Months</option>
                    @foreach($monthlyBreakdown ?? [] as $mRow)
                        <option value="{{ $mRow['month_key'] }}" {{ ($auditMonth ?? 'all') === $mRow['month_key'] ? 'selected' : '' }}>
                            {{ $mRow['month_label'] }}
                        </option>
                    @endforeach
                </select>
                <select name="payment_method" onchange="this.form.submit()" class="px-3 py-1.5 border border-[#E5E9EF] rounded-lg text-[12.5px] bg-white font-semibold text-[#1C2430]">
                    <option value="all" {{ ($auditPaymentMethod ?? 'all') === 'all' ? 'selected' : '' }}>All Payment Terms</option>
                    <option value="Cash" {{ ($auditPaymentMethod ?? '') === 'Cash' ? 'selected' : '' }}>Cash</option>
                    <option value="Credit (Utang)" {{ ($auditPaymentMethod ?? '') === 'Credit (Utang)' ? 'selected' : '' }}>Credit (Utang)</option>
                    <option value="GCash" {{ ($auditPaymentMethod ?? '') === 'GCash' ? 'selected' : '' }}>GCash</option>
                    <option value="Bank Transfer" {{ ($auditPaymentMethod ?? '') === 'Bank Transfer' ? 'selected' : '' }}>Bank Transfer</option>
                </select>
                @if(($auditMonth ?? 'all') !== 'all' || ($auditPaymentMethod ?? 'all') !== 'all')
                    <a href="{{ route('customers.show', ['customer' => $customer->id, 'month' => $currentMonth]) }}" class="px-2.5 py-1.5 rounded-lg text-[12px] font-semibold bg-gray-100 text-[#5B6472] hover:text-[#1C2430]">Reset</a>
                @endif
            </form>
        </div>
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Order / Ref #</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Date &amp; Time (Kanus-a Nagkuha)</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Items &amp; Qty Bought (Unsa Nga Item Napalit)</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Discount Applied</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Total Amount</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Payment / Utang Status</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Recorded By (Staff)</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Status</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($auditOrders ?? $customer->orders ?? [] as $order)
                        @php
                            $isVoided = method_exists($order, 'isVoided') && $order->isVoided();
                            $discAmount = (float) ($order->discount_amount ?? 0);
                            $grossOrderAmount = (float) $order->total_amount + $discAmount;
                        @endphp
                        <tr class="hover:bg-[#F4F6F9] transition-colors {{ $isVoided ? 'bg-red-50/40 opacity-75' : '' }}">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold {{ $isVoided ? 'line-through text-gray-500' : 'text-[#1C2430]' }}">
                                {{ $order->invoice_number ?? ('OR-' . str_pad($order->id, 6, '0', STR_PAD_LEFT)) }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] whitespace-nowrap">{{ $order->created_at->format('M d, Y - h:i A') }}</td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF]">
                                @forelse($order->items ?? [] as $item)
                                    <div class="flex items-center gap-1.5 {{ $isVoided ? 'line-through text-gray-500' : 'text-[#1C2430]' }}">
                                        <span class="font-bold text-[#0B3B70]">{{ $item->quantity }}x</span>
                                        <span class="font-medium">{{ $item->product?->name ?? 'Deleted Product' }}</span>
                                    </div>
                                @empty
                                    <span class="text-gray-400">—</span>
                                @endforelse
                            </td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                @if($discAmount > 0)
                                    <div class="font-bold text-[#B4700A]">-₱{{ number_format($discAmount, 2) }}</div>
                                    <div class="text-[11px] text-[#5B6472]">
                                        {{ ucfirst($order->discount_type ?? 'Discount') }}
                                        @if(in_array($order->discount_type, ['senior', 'pwd']) && $grossOrderAmount > 0)
                                            ({{ round(($discAmount / $grossOrderAmount) * 100, 1) }}%)
                                        @else
                                            (Fixed ₱)
                                        @endif
                                    </div>
                                @else
                                    <span class="text-gray-400">₱0.00</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] font-bold border-b border-[#E5E9EF] text-right whitespace-nowrap {{ $isVoided ? 'line-through text-gray-400' : 'text-[#1C2430]' }}">
                                ₱{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF]">
                                <span class="inline-block px-2 py-0.5 rounded text-[11.5px] font-semibold {{ ($order->payment_method ?? 'Cash') === 'Credit (Utang)' ? 'bg-amber-100 text-amber-800' : 'bg-gray-100 text-[#1C2430]' }}">
                                    {{ $order->payment_method ?? 'Cash' }}
                                </span>
                            </td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] font-medium text-[#1C2430]">
                                {{ $order->user?->name ?? 'System / Staff' }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($isVoided)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F7E9E8] text-[#B5504B]">Voided</span>
                                @else
                                    <span class="text-[12.5px] font-semibold text-[#1E8E5A]">Completed</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <a href="{{ route('orders.show', $order->id) }}" class="text-[#0B3B70] font-bold text-[11.5px] hover:underline">View Receipt</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-6 text-center text-[12.5px] text-[#5B6472]">No transactions recorded for this client during this month.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>