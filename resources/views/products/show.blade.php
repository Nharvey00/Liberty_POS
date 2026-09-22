<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center w-full">
            <div>
                <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">{{ $product->name }}</h1>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Inventory and Pricing Details</div>
            </div>
            <a href="{{ route('products.edit', $product) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">Edit Details</a>
        </div>
    </x-slot>

    <!-- Top Stats -->
    <div class="grid grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Filled Stock</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">{{ number_format($product->stock_quantity) }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Empty Shells</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">{{ number_format($product->empty_quantity) }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Refill Price</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">₱{{ number_format($product->price, 2) }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">New Tank Price</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">₱{{ number_format($product->new_cylinder_price, 2) }}</div>
        </div>
    </div>

    <div class="grid grid-cols-2 gap-6">
        <!-- Recent Stock Ins -->
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#E5E9EF]">
                <h3 class="text-[14.5px] font-bold text-[#1C2430]">Recent Deliveries (Stock In)</h3>
            </div>
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Date</th>
                        <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Received</th>
                        <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Remarks</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->stockIns as $in)
                        <tr>
                            <td class="py-2.5 px-4 text-[13px] border-b border-[#E5E9EF]">{{ $in->created_at->format('M d, Y') }}</td>
                            <td class="py-2.5 px-4 text-[13px] font-bold text-[#1E8E5A] border-b border-[#E5E9EF]">+{{ $in->quantity_received }}</td>
                            <td class="py-2.5 px-4 text-[12px] text-[#5B6472] border-b border-[#E5E9EF]">{{ $in->remarks ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-[12px] text-[#5B6472]">No recent deliveries.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Recent Stock Outs -->
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#E5E9EF]">
                <h3 class="text-[14.5px] font-bold text-[#1C2430]">Recent Deductions (Stock Out)</h3>
            </div>
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Date</th>
                        <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Removed</th>
                        <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->stockOuts as $out)
                        <tr>
                            <td class="py-2.5 px-4 text-[13px] border-b border-[#E5E9EF]">{{ $out->created_at->format('M d, Y') }}</td>
                            <td class="py-2.5 px-4 text-[13px] font-bold text-[#B5504B] border-b border-[#E5E9EF]">-{{ $out->quantity_removed }}</td>
                            <td class="py-2.5 px-4 text-[12px] text-[#5B6472] border-b border-[#E5E9EF]">{{ $out->reason }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-4 text-center text-[12px] text-[#5B6472]">No recent stock outs.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-app-layout>