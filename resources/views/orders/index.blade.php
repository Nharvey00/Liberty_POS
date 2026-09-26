<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Transaction History</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Log of all completed POS checkouts and credit sales</div>
    </x-slot>

    <div class="flex items-center justify-between mb-5 mt-4 gap-3 flex-wrap">
        <div class="flex-1 min-w-[200px] max-w-[300px] flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" placeholder="Search receipt no..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ route('pos.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">+ New Sale</a>
        </div>
    </div>

    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Order ID</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Date</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Customer</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Payment</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Total</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold text-[#1C2430]">OR-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">{{ $order->created_at->format('M d, Y - h:i A') }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold">
                                {{ $order->customer->name ?? 'Walk-in Customer' }}
                                @if($order->customer && $order->customer->customer_type === 'Company')
                                    <span class="ml-2 inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-[#FBF0DD] text-[#B4700A] uppercase">Company</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($order->payment_method === 'Credit')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FBF0DD] text-[#B4700A]">Credit Ledger</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Cash Paid</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13.5px] border-b border-[#E5E9EF] font-bold text-[#1C2430]">₱{{ number_format($order->total_amount, 2) }}</td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right">
                                <a href="{{ route('orders.show', $order->id) }}" class="text-[#5D89B0] font-bold text-[11.5px] hover:underline">View Receipt</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[13px] text-[#5B6472]">No transactions recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-[#E5E9EF]">
            {{ $orders->links() }}
        </div>
    </div>
</x-app-layout>