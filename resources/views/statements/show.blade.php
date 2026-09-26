<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0 print:hidden">Statement of Account #{{ $statement->id }}</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px] print:hidden">Billing statement for {{ $statement->creditAccount->customer->name }}</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold print:hidden">
            {{ session('success') }}
        </div>
    @endif

    {{-- Action Buttons --}}
    <div class="flex gap-2.5 mb-5 print:hidden items-center">
        <button onclick="window.print()" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">🖨 Print Statement</button>
        
        {{-- Fix #5: Manual toggle for paid status --}}
        <form method="POST" action="{{ route('statements.update', $statement) }}" class="inline">
            @csrf
            @method('PATCH')
            <input type="hidden" name="is_paid" value="{{ $statement->is_paid ? '0' : '1' }}">
            <button type="submit" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border {{ $statement->is_paid ? 'border-[#B5504B] text-[#B5504B] hover:bg-[#FDECEA]' : 'border-[#1E8E5A] text-[#1E8E5A] hover:bg-[#E5F5EC]' }} bg-white">
                {{ $statement->is_paid ? 'Mark as Unpaid' : '✓ Mark as Paid' }}
            </button>
        </form>

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
                @if($statement->creditAccount->customer->business_name)
                    <div class="text-[12.5px] font-semibold text-[#0B3B70] mt-0.5">{{ $statement->creditAccount->customer->business_name }}</div>
                @endif
                @if($statement->creditAccount->customer->address)
                    <div class="text-[12px] text-[#5B6472] mt-0.5">{{ $statement->creditAccount->customer->address }}</div>
                @endif
                @if($statement->creditAccount->customer->phone)
                    <div class="text-[12px] text-[#5B6472] mt-0.5">{{ $statement->creditAccount->customer->phone }}</div>
                @endif
                @if($statement->creditAccount->customer->tin_number)
                    <div class="text-[12px] text-[#5B6472] mt-0.5">TIN: {{ $statement->creditAccount->customer->tin_number }}</div>
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
                    {{-- Fix #5: Balance Brought Forward row so line items mathematically reconcile with total due --}}
                    <tr class="bg-[#F8FAFC]">
                        <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] font-semibold text-[#5B6472]">
                            Prior to {{ \Carbon\Carbon::parse($statement->billing_period_start)->format('M d, Y') }}
                        </td>
                        <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] font-bold text-[#0B3B70]" colspan="2">
                            Balance Brought Forward / Previous Balance
                        </td>
                        <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-right font-bold text-[#B5504B]">
                            {{ $previousBalance > 0 ? '₱' . number_format($previousBalance, 2) : '₱0.00' }}
                        </td>
                        <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-right text-[#5B6472]">—</td>
                    </tr>

                    @php $periodCharges = 0; $periodPayments = 0; @endphp
                    @forelse($ledgerEntries as $entry)
                        @php
                            if ($entry->transaction_type === 'Charge') {
                                $periodCharges += $entry->amount;
                            } else {
                                $periodPayments += $entry->amount;
                            }
                        @endphp
                        <tr>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-[#5B6472]">{{ $entry->created_at->format('M d, Y') }}</td>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-[#1C2430] font-semibold">{{ $entry->transaction_type }}</td>
                            <td class="py-2.5 px-3 text-[12px] border border-[#E5E9EF] text-[#5B6472]">
                                @if($entry->order_id)
                                    Order OR-{{ str_pad($entry->order_id, 6, '0', STR_PAD_LEFT) }}
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
                            <td colspan="5" class="py-4 text-center text-[12px] text-[#5B6472] border border-[#E5E9EF] italic">No new transactions during this billing period window.</td>
                        </tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr class="bg-[#F4F6F9]">
                        <td colspan="3" class="py-2.5 px-3 text-[12px] font-bold text-[#1C2430] border border-[#E5E9EF] text-right">Period Subtotals</td>
                        <td class="py-2.5 px-3 text-[12px] font-bold text-[#B5504B] border border-[#E5E9EF] text-right">₱{{ number_format($periodCharges, 2) }}</td>
                        <td class="py-2.5 px-3 text-[12px] font-bold text-[#1E8E5A] border border-[#E5E9EF] text-right">₱{{ number_format($periodPayments, 2) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>

        {{-- Total Due Box with Calculation Breakdown --}}
        <div class="flex justify-between items-end">
            <div class="text-[12px] text-[#5B6472] space-y-1">
                <div><strong>Previous Balance:</strong> ₱{{ number_format($previousBalance, 2) }}</div>
                <div><strong>Current Period Charges:</strong> +₱{{ number_format($periodCharges, 2) }}</div>
                <div><strong>Current Period Payments:</strong> −₱{{ number_format($periodPayments, 2) }}</div>
            </div>

            <div class="border-2 {{ $statement->is_paid ? 'border-[#1E8E5A]' : 'border-[#B5504B]' }} rounded-lg p-4 min-w-[240px] text-right bg-white shadow-xs">
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Total Outstanding Balance</div>
                <div class="text-[24px] font-extrabold {{ $statement->is_paid ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }}">
                    ₱{{ number_format($statement->total_due, 2) }}
                </div>
                <div class="text-[10px] text-[#5B6472] mt-1">Reconciled cumulative balance</div>
                @if($statement->is_paid)
                    <div class="text-[12px] font-bold text-[#1E8E5A] mt-1.5 uppercase tracking-wider">✓ Paid</div>
                @else
                    <div class="text-[12px] font-bold text-[#B5504B] mt-1.5 uppercase tracking-wider">Unpaid</div>
                @endif
            </div>
        </div>

        {{-- Footer --}}
        <div class="mt-8 pt-4 border-t border-[#E5E9EF] text-center text-[11px] text-[#5B6472]">
            This is a system-generated statement. For questions, contact Liberty LPG management.
        </div>
    </div>

    <style>
        @media print {
            body { background: white; margin: 0; padding: 0; }
            .print\:hidden { display: none !important; }
            .print\:border-none { border: none !important; }
            .print\:shadow-none { box-shadow: none !important; }
            .print\:p-0 { padding: 0 !important; }
            .print\:max-w-full { max-width: 100% !important; }
        }
    </style>
</x-app-layout>
