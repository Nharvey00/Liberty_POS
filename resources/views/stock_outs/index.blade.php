<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Stock Out Audit Log</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Historical records of damaged tanks, leaks, and manual inventory deductions</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Toolbar -->
    <div class="flex items-center justify-between mb-5 gap-3 flex-wrap">
        <div class="flex-1 min-w-[200px] max-w-[300px] flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
            <input type="text" placeholder="Search reason or product..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#B5504B] bg-[#B5504B] text-white hover:bg-[#9a423e]">+ Record Stock Out</a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Date Logged</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Reason</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Product</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Qty Removed</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockOuts as $stockOut)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#1C2430]">{{ $stockOut->created_at->format('M d, Y - h:i A') }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F7E9E8] text-[#B5504B]">{{ $stockOut->reason }}</span>
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-medium">{{ $stockOut->product->name ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold text-[#B5504B]">-{{ number_format($stockOut->quantity_removed) }}</td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] text-[#5B6472]">{{ $stockOut->remarks ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-[13px] text-[#5B6472]">No stock-out records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-[#E5E9EF]">
            {{ $stockOuts->links() }}
        </div>
    </div>
</x-app-layout>