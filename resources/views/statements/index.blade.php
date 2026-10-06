<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Statements of Account</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Generated billing statements for credit customers</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <form method="GET" action="{{ route('statements.index') }}" class="flex-1 flex flex-wrap items-center gap-3">
            <div class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[200px]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="customer" value="{{ request('customer') }}" placeholder="Search customer..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
            </div>
            
            <select name="status" class="pl-3 pr-10 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Status</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>
            
            <select name="customer_type" class="pl-3 pr-10 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <option value="">All Types</option>
                <option value="Individual" {{ request('customer_type') === 'Individual' ? 'selected' : '' }}>Individual</option>
                <option value="Business" {{ request('customer_type') === 'Business' ? 'selected' : '' }}>Business</option>
            </select>
            
            <div class="flex items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}" class="px-3 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <span class="text-[13px] text-[#5B6472]">to</span>
                <input type="date" name="to" value="{{ request('to') }}" class="px-3 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
            </div>
            
            <button type="submit" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Filter</button>
            <a href="{{ route('statements.index') }}" class="text-[13px] text-[#0B3B70] hover:underline">Clear</a>
        </form>
        
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ route('statements.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ Generate SOA</a>
        </div>
    </div>

    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">SOA #</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Customer</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Billing Period</th>
                        <th class="text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Total Due</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Status</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statements as $soa)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#1C2430]">#{{ $soa->id }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#1C2430]">{{ $soa->creditAccount->customer->name }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">
                                {{ \Carbon\Carbon::parse($soa->billing_period_start)->format('M d') }} — {{ \Carbon\Carbon::parse($soa->billing_period_end)->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-right font-bold {{ $soa->total_due > 0 ? 'text-[#B5504B]' : 'text-[#1E8E5A]' }}">
                                ₱{{ number_format($soa->total_due, 2) }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($soa->is_paid)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Paid</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FDECEA] text-[#B5504B]">Unpaid</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('statements.show', $soa) }}" class="text-[#0B3B70] font-bold text-[12px] hover:underline">View / Print</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[13px] text-[#5B6472]">No statements generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $statements->links() }}
        </div>
    </div>
</x-app-layout>
