<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4 print:hidden">
            <div>
                <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Utang (Credit) Report</h1>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Outstanding debt balances, credit limit tracking, and collections audit</div>
            </div>
            <div class="flex items-center justify-end shrink-0">
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
        <a href="{{ route('reports.utang') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs">Utang (Credit) Report</a>
        <a href="{{ route('reports.discounts') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Discounts Summary</a>
    </div>

    <div class="print:p-10 print:w-full print:max-w-none print:bg-white print:m-0 print:shadow-none">
        <!-- Print Document Header -->
        <div class="hidden print:block mb-6">
            <h1 class="font-sans text-2xl font-bold text-gray-900 m-0">Utang (Credit) Report</h1>
            <div class="text-xs text-gray-600 mt-1">
                Liberty POS &bull; Utang Ledger Balance Audit &bull; Printed: {{ now()->format('M d, Y h:i A') }}
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
                    <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Customer Classification</label>
                    <select name="customer_type" class="w-full pl-3 pr-10 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                        <option value="all" {{ $customerType == 'all' ? 'selected' : '' }}>All Classifications</option>
                        <option value="Tertiary" {{ $customerType == 'Tertiary' ? 'selected' : '' }}>Tertiary</option>
                        <option value="Household" {{ $customerType == 'Household' ? 'selected' : '' }}>Household</option>
                        <option value="MRO" {{ $customerType == 'MRO' ? 'selected' : '' }}>MRO</option>
                        <option value="Service Station" {{ $customerType == 'Service Station' ? 'selected' : '' }}>Service Station</option>
                        <option value="Commercial" {{ $customerType == 'Commercial' ? 'selected' : '' }}>Commercial</option>
                        <option value="Main Store" {{ $customerType == 'Main Store' ? 'selected' : '' }}>Main Store</option>
                        <option value="Coke (Residual)" {{ $customerType == 'Coke (Residual)' ? 'selected' : '' }}>Coke (Residual)</option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Account Status</label>
                    <select name="status" class="w-full pl-3 pr-10 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
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

        <!-- KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 print:grid-cols-3 gap-6 print:gap-3 mb-8 print:mb-4">
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 print:p-2.5 print:rounded-lg print:border print:border-gray-800">
                <p class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Total Charged</p>
                <p class="font-sans text-2xl font-bold text-gray-900 mt-2 print:mt-1 print:text-lg">₱{{ number_format($grandTotalCharged, 2) }}</p>
            </div>
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 print:p-2.5 print:rounded-lg print:border print:border-gray-800">
                <p class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Total Collected (Paid)</p>
                <p class="font-sans text-2xl font-bold text-gray-900 mt-2 print:mt-1 print:text-lg">₱{{ number_format($grandTotalPaid, 2) }}</p>
            </div>
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 print:p-2.5 print:rounded-lg print:border print:border-gray-800">
                <p class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Outstanding Balance</p>
                <p class="font-sans text-2xl font-bold text-[#B5504B] mt-2 print:mt-1 print:text-lg">₱{{ number_format($grandTotalBalance, 2) }}</p>
            </div>
        </div>

        <!-- Table -->
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden print:border-none print:overflow-visible">
            <div class="overflow-x-auto print:overflow-visible">
                <table class="w-full text-left print:text-[11px] print:border print:border-gray-800">
                    <thead class="bg-gray-50 border-b border-[#E5E9EF] print:border-b print:border-gray-800">
                        <tr class="print:border-b print:border-gray-400">
                            <th class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Customer</th>
                            <th class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Type</th>
                            <th class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Total Charged</th>
                            <th class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Total Paid</th>
                            <th class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Balance</th>
                            <th class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Last Activity</th>
                            <th class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-[#E5E9EF]">
                        @forelse($accounts as $account)
                            <tr class="hover:bg-gray-50 transition-colors print:border-b print:border-gray-400">
                                <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] font-semibold text-gray-900">
                                    {{ $account->customer->first_name }} {{ $account->customer->last_name }}
                                    @if($account->customer->business_name)
                                        <span class="block text-xs text-gray-500 font-normal">{{ $account->customer->business_name }}</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-gray-900">{{ ucfirst($account->customer->customer_type) }}</td>
                                <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-gray-900 text-right">₱{{ number_format($account->total_charged, 2) }}</td>
                                <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-gray-900 text-right">₱{{ number_format($account->total_paid, 2) }}</td>
                                <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] font-bold text-[#B5504B] text-right">₱{{ number_format($account->balance, 2) }}</td>
                                <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-gray-900">
                                    {{ $account->last_activity ? \Carbon\Carbon::parse($account->last_activity)->format('M d, Y') : '-' }}
                                </td>
                                <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-center">
                                    @if($account->is_active)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#E5F5EC] text-[#1E8E5A]">Active</span>
                                    @else
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F7E9E8] text-[#B5504B]">Inactive</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr class="print:border-b print:border-gray-400">
                                <td colspan="7" class="px-6 py-8 print:px-2 print:py-3 print:border-b print:border-gray-400 text-center text-[13px] text-gray-500">No credit accounts found matching criteria.</td>
                            </tr>
                        @endforelse
                    </tbody>
                    <tfoot class="bg-gray-50 border-t border-[#E5E9EF] font-bold">
                        <tr class="print:border-b print:border-gray-400">
                            <td colspan="2" class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-gray-900 text-right">GRAND TOTALS:</td>
                            <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-gray-900 text-right">₱{{ number_format($grandTotalCharged, 2) }}</td>
                            <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-gray-900 text-right">₱{{ number_format($grandTotalPaid, 2) }}</td>
                            <td class="px-6 py-4 print:px-2 print:py-1.5 print:border-b print:border-gray-400 text-[13px] text-[#B5504B] text-right">₱{{ number_format($grandTotalBalance, 2) }}</td>
                            <td colspan="2" class="print:border-b print:border-gray-400"></td>
                        </tr>
                    </tfoot>
                </table>
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
            }
            tr {
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
        }
    </style>
</x-app-layout>
