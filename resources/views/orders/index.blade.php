<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Transaction History &amp; Audit Log</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Log of all completed POS checkouts, credit sales, and voided orders</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif
    @if (session('error'))
        <div class="mb-4 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('error') }}
        </div>
    @endif

    <!-- Status Tabs & Action Toolbar -->
    <div x-data="liveSearch()" class="flex flex-col gap-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-1 gap-3">
        <!-- Status Tabs -->
        <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl">
            <a href="{{ route('orders.index', array_filter(['search' => $search])) }}" 
               class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold transition-colors flex items-center gap-2 {{ empty($status) ? 'bg-white text-[#0B3B70] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }}">
                <span>All Orders</span>
                <span class="text-[11px] px-2 py-0.2 rounded-full {{ empty($status) ? 'bg-[#E7EEF7] text-[#0B3B70]' : 'bg-[#E5E9EF] text-[#5B6472]' }}">{{ $totalCount }}</span>
            </a>
            <a href="{{ route('orders.index', array_filter(['status' => 'completed', 'search' => $search])) }}" 
               class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold transition-colors flex items-center gap-2 {{ $status === 'completed' ? 'bg-white text-[#1E8E5A] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }}">
                <span>Completed</span>
                <span class="text-[11px] px-2 py-0.2 rounded-full {{ $status === 'completed' ? 'bg-[#E5F5EC] text-[#1E8E5A]' : 'bg-[#E5E9EF] text-[#5B6472]' }}">{{ $completedCount }}</span>
            </a>
            <a href="{{ route('orders.index', array_filter(['status' => 'voided', 'search' => $search])) }}" 
               class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold transition-colors flex items-center gap-2 {{ $status === 'voided' ? 'bg-white text-[#B5504B] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }}">
                <span>Voided (Audit Log)</span>
                <span class="text-[11px] px-2 py-0.2 rounded-full {{ $status === 'voided' ? 'bg-[#F7E9E8] text-[#B5504B]' : 'bg-[#E5E9EF] text-[#5B6472]' }}">{{ $voidedCount }}</span>
            </a>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <form x-ref="form" method="GET" action="{{ route('orders.index') }}" @submit.prevent="performSearch" class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[240px]">
                @if($status)
                    <input type="hidden" name="status" value="{{ $status }}">
                @endif
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search receipt or customer..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                <button type="button" x-show="query.length > 0" @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
            </form>
            <a href="{{ route('pos.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ New Sale</a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div id="table-container">
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Order ID</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Date</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Customer</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Payment</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Status</th>
                        @if($status === 'voided')
                            <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Void Reason &amp; Approver</th>
                        @endif
                        <th class="whitespace-nowrap text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Total</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($orders as $order)
                        <tr class="hover:bg-[#F4F6F9] transition-colors {{ $order->isVoided() ? 'bg-[#FFF9F9]' : '' }}">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold text-[#1C2430]">
                                OR-{{ str_pad($order->id, 6, '0', STR_PAD_LEFT) }}
                                @if($order->invoice_number)
                                    <div class="text-[11px] text-[#5B6472] font-normal">{{ $order->invoice_number }}</div>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">{{ $order->created_at->format('M d, Y - h:i A') }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold">
                                {{ $order->customer->name ?? 'Walk-in Customer' }}
                                @if($order->customer && in_array($order->customer->customer_type, ['Coke (Residual)', 'Company']))
                                    <span class="ml-2 inline-block px-2 py-0.5 rounded text-[10px] font-bold bg-[#FBF0DD] text-[#B4700A] uppercase">Coke (Residual)</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($order->payment_method === 'Credit')
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FBF0DD] text-[#B4700A]">Credit Ledger</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Cash Paid</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($order->isVoided())
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F7E9E8] text-[#B5504B]">Voided</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Completed</span>
                                @endif
                            </td>
                            @if($status === 'voided')
                                <td class="py-3 px-4 text-[12px] border-b border-[#E5E9EF] text-[#5B6472]">
                                    <div class="font-medium text-[#B5504B]">{{ $order->void_reason }}</div>
                                    <div class="text-[11px] text-[#7C93B0]">By: {{ $order->voidedByUser->name ?? 'Manager' }} ({{ $order->voided_at ? \Carbon\Carbon::parse($order->voided_at)->format('M d, h:i A') : 'N/A' }})</div>
                                </td>
                            @endif
                            <td class="py-3 px-4 text-[13.5px] border-b border-[#E5E9EF] font-bold text-right {{ $order->isVoided() ? 'line-through text-[#5B6472]' : 'text-[#1C2430]' }}">
                                ₱{{ number_format($order->total_amount, 2) }}
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('orders.show', $order->id) }}" class="text-[#0B3B70] font-bold text-[12px] hover:underline">Receipt</a>
                                    @if(!$order->isVoided() && Auth::user()->isManagerOrOwner())
                                        <a href="{{ route('orders.void', $order->id) }}" class="text-[#B5504B] font-bold text-[12px] hover:underline">Void</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $status === 'voided' ? 8 : 7 }}" class="py-8 text-center text-[13px] text-[#5B6472]">
                                No transactions found matching your criteria.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $orders->links() }}
        </div>
    </div>
    </div>
    </div>
</x-app-layout>
