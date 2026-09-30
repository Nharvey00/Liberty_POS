<x-app-layout>
    <div class="py-12 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8 print:mb-4">
                <div>
                    <h2 class="font-['Manrope'] text-[19px] font-extrabold text-gray-800">
                        Discounts Report
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 print:hidden">Monthly summary of discounts provided.</p>
                </div>
                <div class="print:hidden">
                    <button onclick="window.print()" class="bg-[#0B3B70] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#0a2f5a]">
                        Print Report
                    </button>
                </div>
            </div>

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
