<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">My Sales History</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">View your processed transactions</div>
    </x-slot>

    <div x-data="liveSearch()" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 mb-8">
        <div class="flex justify-between items-center mb-6">
            <form x-ref="form" method="GET" action="{{ route('cashier.sales') }}" @submit.prevent="performSearch" class="relative w-full max-w-[300px]">
                <div class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search OR#, Invoice, Customer..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]" autocomplete="off">
                    <button type="button" x-show="query.length > 0" @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
                </div>
            </form>
            <div class="text-right">
                <div class="text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Your Sales Today</div>
                <div class="text-[20px] font-bold text-[#1E8E5A]">₱{{ number_format($todaySales, 2) }}</div>
            </div>
        </div>

        <div id="table-container">

        <div class="overflow-x-auto -mx-6 md:mx-0">
            <table class="w-full text-left border-collapse min-w-[700px]">
                <thead>
                    <tr class="bg-[#F4F6F9] border-y border-[#E5E9EF]">
                        <th class="py-3 px-4 text-[11.5px] font-semibold text-[#5B6472] uppercase tracking-[0.02em] whitespace-nowrap">Order Ref</th>
                        <th class="py-3 px-4 text-[11.5px] font-semibold text-[#5B6472] uppercase tracking-[0.02em] whitespace-nowrap">Date / Time</th>
                        <th class="py-3 px-4 text-[11.5px] font-semibold text-[#5B6472] uppercase tracking-[0.02em]">Customer</th>
                        <th class="py-3 px-4 text-[11.5px] font-semibold text-[#5B6472] uppercase tracking-[0.02em]">Items</th>
                        <th class="py-3 px-4 text-[11.5px] font-semibold text-[#5B6472] uppercase tracking-[0.02em] whitespace-nowrap">Total</th>
                        <th class="py-3 px-4 text-[11.5px] font-semibold text-[#5B6472] uppercase tracking-[0.02em] whitespace-nowrap">Method</th>
                    </tr>
                </thead>
                <tbody class="text-[#1C2430]">
                    @forelse($orders as $order)
                        <tr class="hover:bg-[#F4F6F9] transition-colors group">
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-[13px]">
                                <div class="font-bold text-[#0B3B70]">OR-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}</div>
                                @if($order->invoice_number)
                                    <div class="text-[11px] text-[#5B6472] mt-0.5">INV: {{ $order->invoice_number }}</div>
                                @endif
                                @if($order->isVoided())
                                    <span class="inline-block mt-1 px-2 py-0.5 rounded text-[10px] font-bold bg-[#F7E9E8] text-[#B5504B]">VOIDED</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-[13px] whitespace-nowrap">
                                <div class="font-semibold">{{ $order->created_at->format('M d, Y') }}</div>
                                <div class="text-[#5B6472] text-[11.5px] mt-0.5">{{ $order->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-[13px]">
                                @if($order->customer)
                                    <div class="font-bold">{{ $order->customer->name }}</div>
                                    @if($order->customer->business_name)
                                        <div class="text-[#0B3B70] text-[11.5px] font-medium mt-0.5">{{ $order->customer->business_name }}</div>
                                    @endif
                                @else
                                    <span class="text-[#5B6472] italic">Walk-in</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-[12.5px] text-[#5B6472]">
                                <ul class="list-disc pl-4 space-y-0.5">
                                    @foreach($order->items as $item)
                                        <li>{{ $item->quantity }}x {{ $item->product->name }}</li>
                                    @endforeach
                                </ul>
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-[13px] font-bold {{ $order->isVoided() ? 'line-through text-[#B5504B]' : '' }}">
                                ₱{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-[13px]">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[11px] font-bold {{ strtolower($order->payment_method) === 'cash' ? 'bg-[#E5F5EC] text-[#1E8E5A]' : 'bg-[#FBF0DD] text-[#B4700A]' }}">
                                    {{ ucfirst($order->payment_method) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[#5B6472] text-[13px]">
                                You have not processed any sales yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <div class="mt-5">
            {{ $orders->links() }}
        </div>
    </div>
    </div>
</x-app-layout>

