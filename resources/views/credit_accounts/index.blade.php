<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Credit Accounts (Utang)</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manage approved credit lines and track outstanding balances</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <div x-data="liveSearch()" class="flex flex-col gap-4">
    <div class="flex items-center justify-between mb-1 gap-3 flex-wrap">
        <form x-ref="form" method="GET" action="{{ route('credit-accounts.index') }}" @submit.prevent="performSearch" class="flex-1 min-w-[200px] max-w-[300px] flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search accounts..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
            <button type="button" x-show="query.length > 0" @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
        </form>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ route('credit-accounts.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">+ Approve Credit Account</a>
        </div>
    </div>

    <div id="table-container">
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Customer</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Agreed Monthly</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Balance</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Status</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($accounts as $account)
                        @php $balance = $account->remaining_balance; @endphp
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#1C2430]">{{ $account->customer->name }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">
                                {{ $account->agreed_monthly_payment ? '₱' . number_format($account->agreed_monthly_payment, 2) : '—' }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($balance > 0)
                                    <span class="font-bold text-[#B5504B]">₱{{ number_format($balance, 2) }}</span>
                                @elseif($balance < 0)
                                    <span class="font-bold text-[#1E8E5A]" title="Customer has an advance deposit/credit">Advance: ₱{{ number_format(abs($balance), 2) }}</span>
                                @else
                                    <span class="text-[#1E8E5A] font-semibold">₱0.00</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($account->is_active)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Active</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F4F6F9] text-[#5B6472]">Inactive</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('credit-accounts.show', $account) }}" class="text-[#0B3B70] font-bold text-[12px] hover:underline">Ledger</a>
                                    <a href="{{ route('payments.create', $account) }}" class="text-[#1E8E5A] font-bold text-[12px] hover:underline">Record Payment</a>
                                    <a href="{{ route('credit-accounts.edit', $account) }}" class="text-[#5D89B0] font-bold text-[12px] hover:underline">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-[13px] text-[#5B6472]">No credit accounts found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $accounts->links() }}
        </div>
    </div>
    </div>
    </div>
</x-app-layout>
