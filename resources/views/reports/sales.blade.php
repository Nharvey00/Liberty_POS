<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div>
                <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Sales Report</h1>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px] print:hidden">Comprehensive sales revenue, 12% VAT breakdown, and discounts analysis</div>
                <div class="hidden print:block text-xs text-gray-600 mt-1">
                    Liberty POS &bull; Period: {{ \Carbon\Carbon::parse($fromDate)->format('M d, Y') }} to {{ \Carbon\Carbon::parse($toDate)->format('M d, Y') }} &bull; Printed: {{ now()->format('M d, Y h:i A') }}
                </div>
            </div>
            <div class="flex items-center justify-end gap-2.5 print:hidden shrink-0">
                <a href="{{ route('reports.sales.export', request()->query()) }}" class="inline-flex items-center gap-2 bg-[#1E8E5A] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#166E46] shadow-xs cursor-pointer transition-colors no-underline">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span>Export to Excel</span>
                </a>
                <button onclick="window.print()" class="inline-flex items-center gap-2 bg-[#0B3B70] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#0a2f5a] shadow-xs cursor-pointer transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    <span>Print Report</span>
                </button>
            </div>
        </div>
    </x-slot>

    <!-- Sub-Navigation Tabs -->
    <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl mb-6 flex-wrap print:hidden">
        <a href="{{ route('reports.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Overview Hub</a>
        <a href="{{ route('reports.sales') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs">Sales Report</a>
        <a href="{{ route('reports.inventory') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Inventory Report</a>
        <a href="{{ route('reports.utang') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Utang (Credit) Report</a>
        <a href="{{ route('reports.discounts') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Discounts Summary</a>
    </div>

    <div>

            <!-- Filters -->
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 mb-8 print:hidden">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">From Date</label>
                        <input type="date" name="from_date" value="{{ $fromDate }}" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">To Date</label>
                        <input type="date" name="to_date" value="{{ $toDate }}" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Payment Method</label>
                        <select name="payment_method" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                            <option value="all" {{ $paymentMethod == 'all' ? 'selected' : '' }}>All</option>
                            <option value="cash" {{ $paymentMethod == 'cash' ? 'selected' : '' }}>Cash</option>
                            <option value="credit" {{ $paymentMethod == 'credit' ? 'selected' : '' }}>Credit</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Cashier</label>
                        <select name="cashier_id" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                            <option value="">All Cashiers</option>
                            @foreach($cashiers as $c)
                                <option value="{{ $c->id }}" {{ $cashierId == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="md:col-span-3">
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Customer Search</label>
                        <input type="text" name="customer_search" value="{{ $customerSearch }}" placeholder="Name or business..." class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full border border-[#E5E9EF] bg-white text-[#0B3B70] px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50 h-[42px]">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- KPIs -->
            <div class="grid grid-cols-1 md:grid-cols-4 print:grid-cols-4 gap-6 print:gap-3 mb-8 print:mb-4">
                <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 print:p-2.5 print:rounded-lg">
                    <p class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Total Sales</p>
                    <p class="font-sans text-2xl font-bold text-gray-900 mt-2 print:mt-1 print:text-lg">₱{{ number_format($totalSales, 2) }}</p>
                </div>
                <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 print:p-2.5 print:rounded-lg">
                    <p class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Total VAT (12%)</p>
                    <p class="font-sans text-2xl font-bold text-gray-900 mt-2 print:mt-1 print:text-lg">₱{{ number_format($totalVat, 2) }}</p>
                </div>
                <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 print:p-2.5 print:rounded-lg">
                    <p class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Total Discounts</p>
                    <p class="font-sans text-2xl font-bold text-[#B5504B] mt-2 print:mt-1 print:text-lg">₱{{ number_format($totalDiscounts, 2) }}</p>
                </div>
                <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 print:p-2.5 print:rounded-lg">
                    <p class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Transactions</p>
                    <p class="font-sans text-2xl font-bold text-gray-900 mt-2 print:mt-1 print:text-lg">{{ $orderCount }}</p>
                </div>
            </div>

            <!-- Table -->
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden print:border-none print:overflow-visible">
                <div class="overflow-x-auto print:overflow-visible">
                    <table class="w-full text-left print:text-[11px]">
                        <thead class="bg-gray-50 border-b border-[#E5E9EF]">
                            <tr>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Date</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Invoice #</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Customer</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Cashier</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Method</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Subtotal</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">VAT</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Discount</th>
                                <th class="px-6 py-4 print:px-2 print:py-1.5 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E9EF]">
                            @forelse($orders as $order)
                                @php
                                    $vatable = $order->total_amount / 1.12;
                                    $vat = $order->total_amount - $vatable;
                                @endphp
                                <tr class="hover:bg-gray-50 transition-colors {{ $order->isVoided() ? 'bg-[#FFF8F8] opacity-75' : '' }}">
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] text-gray-900 whitespace-nowrap">{{ $order->created_at->format('M d, Y h:i A') }}</td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] text-gray-900">
                                        {{ $order->invoice_number }}
                                        @if($order->isVoided())
                                            <span class="ml-1.5 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-bold bg-[#F7E9E8] text-[#B5504B]">VOIDED</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] text-gray-900">
                                        {{ $order->customer ? $order->customer->first_name . ' ' . $order->customer->last_name : 'Walk-in' }}
                                    </td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] text-gray-900">{{ $order->user ? $order->user->name : '-' }}</td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px]">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $order->payment_method == 'cash' ? 'bg-[#E5F5EC] text-[#1E8E5A]' : 'bg-[#FBF0DD] text-[#B4700A]' }}">
                                            {{ ucfirst($order->payment_method) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] text-right {{ $order->isVoided() ? 'line-through text-gray-400' : 'text-gray-900' }}">₱{{ number_format($vatable, 2) }}</td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] text-right {{ $order->isVoided() ? 'line-through text-gray-400' : 'text-gray-900' }}">₱{{ number_format($vat, 2) }}</td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] text-right {{ $order->isVoided() ? 'line-through text-gray-400' : 'text-[#B5504B]' }}">₱{{ number_format($order->discount_amount, 2) }}</td>
                                    <td class="px-6 py-4 print:px-2 print:py-1.5 text-[13px] font-bold text-right {{ $order->isVoided() ? 'line-through text-gray-400' : 'text-gray-900' }}">₱{{ number_format($order->total_amount, 2) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="9" class="px-6 py-8 print:px-2 print:py-3 text-center text-[13px] text-gray-500">No sales records found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 border-t border-[#E5E9EF] print:hidden">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>

    <style>
        @media print {
            nav, aside, header button, .print\:hidden, .no-print, button, form, input, select {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 10pt !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            table {
                width: 100% !important;
                border-collapse: collapse !important;
                border: 1.5px solid #000000 !important;
                margin-top: 15px !important;
            }
            th, td {
                border: 1px solid #000000 !important;
                color: #000000 !important;
                padding: 6px 8px !important;
            }
            thead th {
                background-color: #f0f0f0 !important;
                font-weight: 700 !important;
                border-bottom: 2px solid #000000 !important;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .bg-white, .border, .rounded-\[16px\], .rounded-lg, .shadow-sm, .shadow-xs {
                box-shadow: none !important;
                border-radius: 0 !important;
                border-color: #000000 !important;
            }
        }
    </style>
</x-app-layout>
