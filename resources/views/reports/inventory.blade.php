<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div>
                <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Inventory Report</h1>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px] print:hidden">Stock turnover, period receipts, deductions, and sales volume</div>
            </div>
            <div class="flex items-center justify-end print:hidden shrink-0">
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
        <a href="{{ route('reports.sales') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Sales Report</a>
        <a href="{{ route('reports.inventory') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs">Inventory Report</a>
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
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Product Search</label>
                        <input type="text" name="product_search" value="{{ $productSearch }}" placeholder="Product name..." class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full border border-[#E5E9EF] bg-white text-[#0B3B70] px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50 h-[42px]">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-[#E5E9EF]">
                            <tr>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Product</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Price</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Current Stock</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Empty Shells</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Stock In (Period)</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Stock Out (Period)</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Sold (Period)</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E9EF]">
                            @forelse($products as $product)
                                <tr class="hover:bg-gray-50 transition-colors {{ $product->stock_quantity <= 5 ? 'bg-red-50' : '' }}">
                                    <td class="px-6 py-4 text-[13px] font-semibold text-gray-900">{{ $product->name }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-right">₱{{ number_format($product->price, 2) }}</td>
                                    <td class="px-6 py-4 text-[13px] font-bold {{ $product->stock_quantity <= 5 ? 'text-[#B5504B]' : 'text-gray-900' }} text-right">
                                        {{ $product->stock_quantity }}
                                    </td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-right">{{ $product->empty_quantity ?? 0 }}</td>
                                    <td class="px-6 py-4 text-[13px] text-[#1E8E5A] text-center">+{{ $product->period_in }}</td>
                                    <td class="px-6 py-4 text-[13px] text-[#B5504B] text-center">-{{ $product->period_out }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-center">{{ $product->period_sold }}</td>
                                    <td class="px-6 py-4 text-[13px] text-center">
                                        @if($product->stock_quantity <= 5)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F7E9E8] text-[#B5504B]">
                                                Low Stock
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#E5F5EC] text-[#1E8E5A]">
                                                In Stock
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-[13px] text-gray-500">No products found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
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
