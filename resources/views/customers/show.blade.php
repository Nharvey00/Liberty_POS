<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <div>
                <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">
                    {{ $customer->business_name ?? $customer->name }}
                </h1>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px]">
                    {{ $customer->customer_type }} Account 
                    @if($customer->business_name)
                        · Contact: {{ $customer->name }}
                    @endif
                </div>
            </div>
            <div class="flex gap-2 items-center">
                <form method="GET" action="{{ route('customers.show', $customer) }}" class="flex items-center gap-2">
                    <input type="month" name="month" value="{{ $currentMonth }}" onchange="this.form.submit()" class="px-3 py-1.5 border border-[#E5E9EF] rounded-lg text-[13px] bg-white font-semibold text-[#0B3B70]">
                </form>
                <a href="{{ route('customers.edit', $customer) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">Edit Profile</a>
            </div>
        </div>
    </x-slot>

    <!-- Top Profile Stats -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Outstanding Utang Balance</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold text-[#B5504B]">₱{{ number_format($customer->creditAccount->remaining_balance ?? 0, 2) }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Phone Number</div>
            <div class="font-['Manrope'] text-[18px] font-extrabold text-[#1C2430] truncate">{{ $customer->phone ?? 'Not provided' }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">TIN Number</div>
            <div class="font-['Manrope'] text-[18px] font-bold text-[#1C2430] truncate">{{ $customer->tin_number ?? 'Not provided' }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Delivery Address</div>
            <div class="font-['Manrope'] text-[15px] font-bold text-[#1C2430] truncate">{{ $customer->address ?? 'Not provided' }}</div>
        </div>
    </div>

    <!-- DIGITAL LOGSHEET: Monthly Volume Matrix -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden mb-6 shadow-sm">
        <div class="px-5 py-4 border-b border-[#E5E9EF] flex justify-between items-center bg-[#F4F6F9]">
            <div>
                <h3 class="text-[14.5px] font-bold text-[#1C2430]">Monthly Purchase Volume Matrix</h3>
                <div class="text-[11.5px] text-[#5B6472]">Digitized daily tracking grid for {{ \Carbon\Carbon::parse($currentMonth)->format('F Y') }}</div>
            </div>
            <div class="text-[12px] font-semibold text-[#0B3B70] bg-[#E7EEF7] px-3 py-1 rounded-full border border-[#D6E2F0]">
                Total Volume: {{ array_sum($dailyVolumes) }} tanks
            </div>
        </div>

        <div class="p-5 overflow-x-auto">
            <table class="w-full border-collapse text-center min-w-[700px]">
                <thead>
                    <tr class="bg-[#1C2430] text-white">
                        <th class="py-2.5 px-3 text-[11.5px] font-semibold uppercase tracking-wider text-left rounded-l-lg">Metric</th>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            <th class="py-2.5 px-1 text-[11px] font-semibold border-l border-[#2E3A4E]">{{ $day }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    <tr class="border-b border-[#E5E9EF] hover:bg-[#F4F6F9]">
                        <td class="py-3 px-3 text-[13px] font-bold text-[#1C2430] text-left bg-white">Volume Bought</td>
                        @for ($day = 1; $day <= $daysInMonth; $day++)
                            <td class="py-3 px-1 text-[12.5px] border-l border-[#E5E9EF]">
                                @if($dailyVolumes[$day] > 0)
                                    <span class="inline-block w-6 h-6 leading-6 rounded-md bg-[#B4700A] text-white font-bold shadow-xs">
                                        {{ $dailyVolumes[$day] }}
                                    </span>
                                @else
                                    <span class="text-[#A0AEC0] font-light">—</span>
                                @endif
                            </td>
                        @endfor
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Orders & Credit History -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="px-5 py-4 border-b border-[#E5E9EF] flex justify-between items-center">
            <h3 class="text-[14.5px] font-bold text-[#1C2430]">Transaction History</h3>
            <span class="text-[11.5px] text-[#5B6472]">Connected to Developer 2's Utang Module</span>
        </div>
        <table class="w-full border-collapse">
            <thead>
                <tr>
                    <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Date &amp; Time</th>
                    <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Payment Terms</th>
                    <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Total Amount</th>
                    <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($customer->orders ?? [] as $order)
                    <tr>
                        <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">{{ $order->created_at->format('M d, Y - h:i A') }}</td>
                        <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">{{ $order->payment_method ?? 'Cash' }}</td>
                        <td class="py-3 px-4 text-[13px] font-bold border-b border-[#E5E9EF]">₱{{ number_format($order->total_amount, 2) }}</td>
                        <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                            <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Recorded</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="4" class="py-6 text-center text-[12.5px] text-[#5B6472]">No transactions recorded for this client during this month.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-app-layout>