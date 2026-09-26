<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Credit Ledger</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Transaction history for {{ $credit_account->customer->name }}</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    {{-- Account Summary Card --}}
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5 mb-5 max-w-4xl">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Customer</div>
                <div class="text-[14px] font-bold text-[#1C2430]">{{ $credit_account->customer->name }}</div>
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Monthly Payment</div>
                <div class="text-[14px] font-semibold text-[#1C2430]">
                    {{ $credit_account->agreed_monthly_payment ? '₱' . number_format($credit_account->agreed_monthly_payment, 2) : '—' }}
                </div>
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Status</div>
                <div>
                    @if($credit_account->is_active)
                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Active</span>
                    @else
                        <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FDECEA] text-[#B5504B]">Suspended</span>
                    @endif
                </div>
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Outstanding Balance</div>
                <div class="text-[18px] font-extrabold {{ $remainingBalance > 0 ? 'text-[#B5504B]' : 'text-[#1E8E5A]' }}">
                    ₱{{ number_format($remainingBalance, 2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Action Buttons --}}
    <div class="flex gap-2.5 mb-5 flex-wrap max-w-4xl">
        <a href="{{ route('payments.create', $credit_account) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#1E8E5A] bg-[#1E8E5A] text-white hover:bg-[#176B45]">₱ Record Payment</a>
        <a href="{{ route('credit-accounts.edit', $credit_account) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Edit Terms</a>
        <a href="{{ route('credit-accounts.index') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#5B6472] hover:bg-[#F4F6F9]">← Back to List</a>
    </div>

    {{-- Ledger Table --}}
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden max-w-4xl">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Date</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Type</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Reference</th>
                        <th class="text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Amount</th>
                        <th class="text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Running Balance</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($credit_account->ledgers as $entry)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">
                                {{ $entry->created_at->format('M d, Y — h:i A') }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($entry->transaction_type === 'Charge')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FDECEA] text-[#B5504B]">Charge</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Payment</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">
                                @if($entry->order_id)
                                    <a href="{{ route('orders.show', $entry->order_id) }}" class="text-[#0B3B70] hover:underline font-semibold">Order OR-{{ str_pad($entry->order_id, 6, '0', STR_PAD_LEFT) }}</a>
                                @elseif($entry->payment_id)
                                    Payment #{{ $entry->payment_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-right font-semibold {{ $entry->transaction_type === 'Charge' ? 'text-[#B5504B]' : 'text-[#1E8E5A]' }}">
                                {{ $entry->transaction_type === 'Charge' ? '+' : '−' }}₱{{ number_format($entry->amount, 2) }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-right font-bold text-[#1C2430]">
                                ₱{{ number_format($entry->running_balance, 2) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-[13px] text-[#5B6472]">No transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
                @if($credit_account->ledgers->isNotEmpty())
                    <tfoot>
                        <tr class="bg-[#F4F6F9]">
                            <td colspan="3" class="py-3 px-4 text-[13px] font-bold text-[#1C2430]">Current Outstanding Balance</td>
                            <td class="py-3 px-4"></td>
                            <td class="py-3 px-4 text-right text-[16px] font-extrabold {{ $remainingBalance > 0 ? 'text-[#B5504B]' : 'text-[#1E8E5A]' }}">
                                ₱{{ number_format($remainingBalance, 2) }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</x-app-layout>
