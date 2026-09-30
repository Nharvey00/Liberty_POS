<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div>
                <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Discounts Summary Report</h1>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px] print:hidden">Monthly analysis of customer discounts, Senior Citizen IDs, and loan frequencies</div>
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
        <a href="{{ route('reports.inventory') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Inventory Report</a>
        <a href="{{ route('reports.utang') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Utang (Credit) Report</a>
        <a href="{{ route('reports.discounts') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs">Discounts Summary</a>
    </div>

    <div>

            <!-- Filters -->
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 mb-8 print:hidden">
                <form method="GET" class="flex gap-4 items-end">
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Month / Year</label>
                        <input type="month" name="month_year" value="{{ $monthYear }}" class="w-48 border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div>
                        <button type="submit" class="border border-[#E5E9EF] bg-white text-[#0B3B70] px-6 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50 h-[42px]">
                            View Month
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
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Customer Name</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Customer Type</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Senior ID</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Times Discounted</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Times Loaned</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Total Discount</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Discount Types</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E9EF]">
                            @forelse($customersData as $data)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-[13px] font-semibold text-gray-900">{{ $data->customer_name }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900">{{ ucfirst($data->customer_type) }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900">{{ $data->senior_id ?? '-' }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-center">{{ $data->times_discounted }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-center">{{ $data->times_loaned }}</td>
                                    <td class="px-6 py-4 text-[13px] font-bold text-[#B5504B] text-right">₱{{ number_format($data->total_discount, 2) }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900">
                                        {{ $data->discount_types }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-[13px] text-gray-500">No discounts given in this month.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-[#E5E9EF] font-bold">
                            <tr>
                                <td colspan="3" class="px-6 py-4 text-[13px] text-gray-900 text-right">TOTALS:</td>
                                <td class="px-6 py-4 text-[13px] text-gray-900 text-center">{{ $totalTimesDiscounted }}</td>
                                <td class="px-6 py-4 text-[13px] text-gray-900 text-center">{{ $totalTimesLoaned }}</td>
                                <td class="px-6 py-4 text-[13px] text-[#B5504B] text-right">₱{{ number_format($grandTotalDiscount, 2) }}</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
