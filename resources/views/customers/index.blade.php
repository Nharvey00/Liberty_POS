<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Customer Registry</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manage clients, company accounts, and loan balances</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex items-center justify-between mb-5 gap-3 flex-wrap">
        <div class="flex-1 min-w-[200px] max-w-[300px] flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" placeholder="Search customers..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ route('customers.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">+ Add Customer Record</a>
        </div>
    </div>

    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Name</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Type</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Phone</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Utang Balance</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($customers as $customer)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#1C2430]">{{ $customer->name }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($customer->customer_type === 'Company')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E7EEF7] text-[#0B3B70]">Company</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F4F6F9] text-[#5B6472]">Normal</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">{{ $customer->phone ?? '—' }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @php $balance = $customer->creditAccount->remaining_balance ?? 0; @endphp
                                @if($balance > 0)
                                    <span class="font-bold text-[#B5504B]">₱{{ number_format($balance, 2) }}</span>
                                @else
                                    <span class="text-[#5B6472]">₱0.00</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right">
                                <a href="{{ route('customers.show', $customer) }}" class="border-none bg-transparent text-[#0B3B70] font-bold text-[11.5px] hover:underline mr-3">View</a>
                                <a href="{{ route('customers.edit', $customer) }}" class="border-none bg-transparent text-[#5D89B0] font-bold text-[11.5px] hover:underline">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-[13px] text-[#5B6472]">No customers found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-[#E5E9EF]">
            {{ $customers->links() }}
        </div>
    </div>
</x-app-layout>