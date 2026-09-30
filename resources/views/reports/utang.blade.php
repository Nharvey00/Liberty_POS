<x-app-layout>
    <div class="py-12 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8 print:mb-4">
                <div>
                    <h2 class="font-['Manrope'] text-[19px] font-extrabold text-gray-800">
                        Utang (Credit) Report
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 print:hidden">Monitor outstanding credit balances across all customers.</p>
                </div>
                <div class="print:hidden">
                    <button onclick="window.print()" class="bg-[#0B3B70] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#0a2f5a]">
                        Print Report
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 mb-8 print:hidden">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div class="md:col-span-2">
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Customer Search</label>
                        <input type="text" name="customer_search" value="{{ $customerSearch }}" placeholder="Name or business..." class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Customer Type</label>
                        <select name="customer_type" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                            <option value="all" {{ $customerType == 'all' ? 'selected' : '' }}>All</option>
                            <option value="retail" {{ $customerType == 'retail' ? 'selected' : '' }}>Retail</option>
                            <option value="wholesale" {{ $customerType == 'wholesale' ? 'selected' : '' }}>Wholesale</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Account Status</label>
                        <select name="status" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                            <option value="all" {{ $status == 'all' ? 'selected' : '' }}>All</option>
                            <option value="active" {{ $status == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ $status == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="md:col-span-4 flex justify-end">
                        <button type="submit" class="border border-[#E5E9EF] bg-white text-[#0B3B70] px-6 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50 h-[42px]">
                            Apply Filters
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
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Customer</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Type</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Total Charged</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Total Paid</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Balance</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Last Activity</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E9EF]">
                            @forelse($accounts as $account)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 text-[13px] font-semibold text-gray-900">
                                        {{ $account->customer->first_name }} {{ $account->customer->last_name }}
                                        @if($account->customer->business_name)
                                            <span class="block text-xs text-gray-500 font-normal">{{ $account->customer->business_name }}</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900">{{ ucfirst($account->customer->customer_type) }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-right">₱{{ number_format($account->total_charged, 2) }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-right">₱{{ number_format($account->total_paid, 2) }}</td>
                                    <td class="px-6 py-4 text-[13px] font-bold text-[#B5504B] text-right">₱{{ number_format($account->balance, 2) }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900">
                                        {{ $account->last_activity ? \Carbon\Carbon::parse($account->last_activity)->format('M d, Y') : '-' }}
                                    </td>
                                    <td class="px-6 py-4 text-[13px] text-center">
                                        @if($account->is_active)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#E5F5EC] text-[#1E8E5A]">Active</span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F7E9E8] text-[#B5504B]">Inactive</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-8 text-center text-[13px] text-gray-500">No credit accounts found matching criteria.</td>
                                </tr>
                            @endforelse
                        </tbody>
                        <tfoot class="bg-gray-50 border-t border-[#E5E9EF] font-bold">
                            <tr>
                                <td colspan="2" class="px-6 py-4 text-[13px] text-gray-900 text-right">GRAND TOTALS:</td>
                                <td class="px-6 py-4 text-[13px] text-gray-900 text-right">₱{{ number_format($grandTotalCharged, 2) }}</td>
                                <td class="px-6 py-4 text-[13px] text-gray-900 text-right">₱{{ number_format($grandTotalPaid, 2) }}</td>
                                <td class="px-6 py-4 text-[13px] text-[#B5504B] text-right">₱{{ number_format($grandTotalBalance, 2) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
