<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Statement of Account #{{ $statement->id }}</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Billing statement for {{ $statement->creditAccount->customer->name }}</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold print:hidden">
            {{ session('success') }}
        </div>
    @endif

    {{-- Print Button --}}
    <div class="flex gap-2.5 mb-5 print:hidden">
        <button onclick="window.print()" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">🖨 Print Statement</button>
        <a href="{{ route('statements.index') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#5B6472] hover:bg-[#F4F6F9]">← Back to List</a>
    </div>

    {{-- Printable SOA --}}
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-8 max-w-4xl print:border-none print:shadow-none print:p-0 print:max-w-full">
        {{-- Company Header --}}
        <div class="text-center mb-6 pb-4 border-b-2 border-[#0B3B70]">
            <h2 class="font-['Manrope'] text-[22px] font-extrabold text-[#0B3B70] m-0">LIBERTY LPG</h2>
            <div class="text-[12px] text-[#5B6472] mt-1">LPG Distributor — Davao City</div>
            <div class="text-[15px] font-bold text-[#1C2430] mt-3 uppercase tracking-wider">Statement of Account</div>
        </div>

        {{-- Customer & Billing Info --}}
        <div class="grid grid-cols-2 gap-6 mb-6">
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Billed To</div>
                <div class="text-[14px] font-bold text-[#1C2430]">{{ $statement->creditAccount->customer->name }}</div>
                @if($statement->creditAccount->customer->address)
                    <div class="text-[12px] text-[#5B6472] mt-0.5">{{ $statement->creditAccount->customer->address }}</div>
                @endif
                @if($statement->creditAccount->customer->phone)
                    <div class="text-[12px] text-[#5B6472] mt-0.5">{{ $statement->creditAccount->customer->phone }}</div>
                @endif
            </div>
            <div class="text-right">
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">SOA Number</div>
                <div class="text-[14px] font-bold text-[#1C2430]">#{{ $statement->id }}</div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mt-3 mb-1">Billing Period</div>
                <div class="text-[13px] text-[#1C2430]">
                    {{ \Carbon\Carbon::parse($statement->billing_period_start)->format('M d, Y') }} — {{ \Carbon\Carbon::parse($statement->billing_period_end)->format('M d, Y') }}
                </div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mt-3 mb-1">Date Issued</div>
                <div class="text-[13px] text-[#1C2430]">{{ $statement->created_at->format('M d, Y') }}</div>
            </div>
        </div>

        {{-- Line Items Table --}}
        <div class="overflow-x-auto mb-6">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="bg-[#F4F6F9]">
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-2.5 px-3 border border-[#E5E9EF]">Date</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-2.5 px-3 border border-[#E5E9EF]">Type</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-2.5 px-3 border border-[#E5E9EF]">Reference</th>
                        <th class="text-right text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-2.5 px-3 border border-[#E5E9EF]">Charges</th>
                        <th class="text-right text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-2.5 px-3 border border-[#E5E9EF]">Payments</th>
                    </tr>
                </thead>
                <tbody>
                    @php $totalCharges = 0; $totalPayments = 0; @endphp
                    @forelse($ledgerEntries as $entry)
                        @php
                            if ($entry->transaction_type === 'Charge') {
                                $totalCharges += $entry->amount;
                            } else {
                                $totalPayments += $entry->amount;
                            }
                        @endphp
                        <tr>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-[#5B6472]">{{ $entry->created_at->format('M d, Y') }}</td>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-[#1C2430] font-semibold">{{ $entry->transaction_type }}</td>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-[#5B6472]">
                                @if($entry->order_id)
                                    Order #{{ $entry->order_id }}
                                @elseif($entry->payment_id)
                                    Payment #{{ $entry->payment_id }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-right {{ $entry->transaction_type === 'Charge' ? 'text-[#B5504B] font-semibold' : '' }}">
                                {{ $entry->transaction_type === 'Charge' ? '₱' . number_format($entry->amount, 2) : '' }}
                            </td>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-right {{ $entry->transaction_type === 'Payment' ? 'text-[#1E8E5A] font-semibold' : '' }}">
                                {{ $entry->transaction_type === 'Payment' ? '₱' . number_format($entry->amount, 2) : '' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-6 text-center text-[12px] text-[#5B6472] border border-[#E5E9EF]">No transactions in this billing period.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-[#F4F6F9]">
                        <td colspan="3" class="py-2.5 px-3 text-[12px] font-bold text-[#1C2430] border border-[#E5E9EF] text-right">Subtotals</td>
                        <td class="py-2.5 px-3 text-[12px] font-bold text-[#B5504B] border border-[#E5E9EF] text-right">₱{{ number_format($totalCharges, 2) }}</td>
                        <td class="py-2.5 px-3 text-[12px] font-bold text-[#1E8E5A] border border-[#E5E9EF] text-right">₱{{ number_format($totalPayments, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Total Due Box --}}
        <div class="flex justify-end">
            <div class="border-2 {{ $statement->is_paid ? 'border-[#1E8E5A]' : 'border-[#B5504B]' }} rounded-lg p-4 min-w-[220px] text-right">
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Total Amount Due</div>
                <div class="text-[24px] font-extrabold {{ $statement->is_paid ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }}">
                    ₱{{ number_format($statement->total_due, 2) }}
                </div>
                @if($statement->is_paid)
                    <div class="text-[12px] font-bold text-[#1E8E5A] mt-1 uppercase">✓ Paid</div>
                @else
                    <div class="text-[12px] font-bold text-[#B5504B] mt-1 uppercase">Unpaid</div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 pt-4 border-t border-[#E5E9EF] text-center text-[11px] text-[#5B6472]">
            This is a system-generated statement. For questions, contact Liberty LPG management.
        </div>
    </div>
</x-app-layout>
