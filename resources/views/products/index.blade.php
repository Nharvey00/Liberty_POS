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

    <!-- Inventory Sub-Tabs & Action Toolbar -->
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-5 gap-3">
        @if(Auth::user()->isManagerOrOwner())
            <!-- Sub-Tabs -->
            <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl">
                <a href="{{ route('products.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs transition-colors">
                    Products Catalog
                </a>
                <a href="{{ route('stock-ins.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock In History
                </a>
                <a href="{{ route('stock-outs.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock Out History
                </a>
            </div>
        @else
            <div></div>
        @endif

        <div class="flex items-center gap-2.5 flex-wrap">
            <form method="GET" action="{{ route('products.index') }}" class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[240px]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <input type="text" name="search" value="{{ $search ?? '' }}" placeholder="Search product name..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                @if(!empty($search))
                    <a href="{{ route('products.index') }}" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]">✕</a>
                @endif
            </form>
            @if(Auth::user()->isManagerOrOwner())
                <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">↓ Stock Out</a>
                <a href="{{ route('stock-ins.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">↑ Stock In</a>
                <a href="{{ route('products.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ Add Product</a>
            @endif
        </div>
    </div>

    <!-- Data Table Card -->
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Product Name</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Refill Price</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Capacity (kg)</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Filled Stock</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Empty Shells</th>
                        <th class="whitespace-nowrap text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Status</th>
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
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('products.show', $product) }}" class="text-[#0B3B70] font-bold text-[12px] hover:underline">View</a>
                                    @if(Auth::user()->isManagerOrOwner())
                                        <a href="{{ route('products.edit', $product) }}" class="text-[#5D89B0] font-bold text-[12px] hover:underline">Edit</a>
                                    @endif
                                </div>
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