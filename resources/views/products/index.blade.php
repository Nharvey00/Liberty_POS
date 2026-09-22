<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Inventory Catalog</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manage LPG tanks, refills, and accessories</div>
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
            <input type="text" placeholder="Search product..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
        </div>
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">↓ Stock Out</a>
            <a href="{{ route('stock-ins.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">↑ Stock In</a>
            <a href="{{ route('products.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">+ Add Product</a>
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Product Name</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Refill Price</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Capacity (kg)</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Filled Stock</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Empty Shells</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Status</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#1C2430]">{{ $product->name }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">₱{{ number_format($product->price, 2) }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">{{ $product->standard_capacity_kg ? $product->standard_capacity_kg . ' kg' : '—' }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold">{{ number_format($product->stock_quantity) }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">{{ number_format($product->empty_quantity) }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($product->stock_quantity <= 10)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FBF0DD] text-[#B4700A]">Low Stock</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Healthy</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right">
                                <a href="{{ route('products.show', $product) }}" class="border-none bg-transparent text-[#0B3B70] font-bold text-[11.5px] hover:underline mr-3">View</a>
                                <a href="{{ route('products.edit', $product) }}" class="border-none bg-transparent text-[#5D89B0] font-bold text-[11.5px] hover:underline">Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-8 text-center text-[13px] text-[#5B6472]">No products found. Add your first item to get started.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="px-4 py-3 border-t border-[#E5E9EF]">
            {{ $products->links() }}
        </div>
    </div>
</x-app-layout>