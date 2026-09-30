<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Stock In Audit Log</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Historical records of all incoming supplier deliveries and restocks</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Inventory Sub-Tabs & Action Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-5 gap-3">
        <!-- Sub-Tabs -->
        <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl">
            <a href="{{ route('products.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                Products Catalog
            </a>
            <a href="{{ route('stock-ins.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs transition-colors">
                Stock In History
            </a>
            <a href="{{ route('stock-outs.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                Stock Out History
            </a>
        </div>

        <div class="flex items-center gap-2.5 flex-wrap">
            <form method="GET" action="{{ route('stock-ins.index') }}" class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[240px]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search reference or product..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                @if(!empty($search))
                    <a href="{{ route('stock-ins.index') }}" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]">✕</a>
                @endif
            </form>
            <a href="{{ route('stock-ins.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ Record Stock In</a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Date Received</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Reference / PO</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Product</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Qty Received</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Unit Cost</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockIns as $stockIn)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#1C2430]">{{ $stockIn->created_at->format('M d, Y - h:i A') }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#0B3B70]">{{ $stockIn->reference_no ?? 'PO-DELIVERY' }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-medium">{{ $stockIn->product->name ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold text-[#1E8E5A]">+{{ number_format($stockIn->quantity_received) }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">₱{{ number_format($stockIn->unit_cost ?? 0, 2) }}</td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] text-[#5B6472]">{{ $stockIn->remarks ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[13px] text-[#5B6472]">No stock-in records found. Use the record button above to log a delivery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-[#E5E9EF]">
            {{ $stockIns->links() }}
        </div>
    </div>
</x-app-layout>