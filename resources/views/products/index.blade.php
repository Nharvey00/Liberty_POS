<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Inventory Catalog</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manage LPG tanks, refills, and accessories</div>
    </x-slot>
    <div x-data="{ showDeleteModal: false, deleteAction: '' }">

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- Inventory Sub-Tabs & Action Toolbar -->
    <div x-data="liveSearch()" class="flex flex-col gap-4">
    <div class="flex flex-col md:flex-row md:items-center justify-between mb-1 gap-3">
        @if(Auth::user()->isManagerOrOwner())
            <!-- Sub-Tabs -->
            <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl">
                <a href="{{ route('products.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold {{ request('status') !== 'deleted' ? 'bg-white text-[#0B3B70] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }} transition-colors">
                    Products Catalog
                </a>
                <a href="{{ route('stock-ins.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock In History
                </a>
                <a href="{{ route('stock-outs.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock Out History
                </a>
                <a href="{{ route('products.index', ['status' => 'deleted']) }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold {{ request('status') === 'deleted' ? 'bg-white text-[#0B3B70] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }} transition-colors">
                    Deleted Products
                </a>
            </div>
        @else
            <div></div>
        @endif

        <div class="flex items-center gap-2.5 flex-wrap">
            <form x-ref="form" method="GET" action="{{ route('products.index') }}" @submit.prevent="performSearch" class="relative flex items-center gap-2 min-w-[240px]">
                <div class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 w-full">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search product name..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]" autocomplete="off">
                    <button type="button" x-show="query.length > 0" @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
                    @if(request('status'))
                        <input type="hidden" name="status" value="{{ request('status') }}">
                    @endif
                </div>
            </form>

            @if(Auth::user()->isManagerOrOwner())
                <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">↓ Stock Out</a>
                <a href="{{ route('stock-ins.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9]">↑ Stock In</a>
                <a href="{{ route('products.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ Add Product</a>
            @endif
        </div>
    </div>

    <!-- Data Table Card -->
    <div id="table-container">
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
                                @if($product->trashed())
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#F7E9E8] text-[#B5504B]">Deleted</span>
                                @elseif($product->stock_quantity <= 10)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FBF0DD] text-[#B4700A]">Low Stock</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Healthy</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('products.show', $product) }}" class="text-[#0B3B70] font-bold text-[12px] hover:underline">View</a>
                                    @if(Auth::user()->isManagerOrOwner())
                                        @if(!$product->trashed())
                                            <a href="{{ route('products.edit', $product) }}" class="text-[#5D89B0] font-bold text-[12px] hover:underline">Edit</a>
                                            <button type="button" @click="deleteAction = '{{ route('products.destroy', $product) }}'; showDeleteModal = true" class="px-2.5 py-1 text-[11.5px] font-semibold text-[#B5504B] bg-[#F7E9E8] hover:bg-[#F0D5D3] rounded-md transition-colors cursor-pointer border border-[#B5504B]/20">
                                                Delete
                                            </button>
                                        @else
                                            <form action="{{ route('products.restore', $product) }}" method="POST" class="m-0 p-0 inline">
                                                @csrf
                                                <button type="submit" class="px-2.5 py-1 text-[11.5px] font-semibold text-[#1E8E5A] bg-[#E5F5EC] hover:bg-[#D1EBD9] rounded-md transition-colors cursor-pointer border border-[#1E8E5A]/20">
                                                    Restore
                                                </button>
                                            </form>
                                        @endif
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
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $products->links() }}
        </div>
    </div>
    </div> <!-- Close table container -->
    </div> <!-- Close liveSearch -->

    <!-- Delete Confirmation Modal -->
    <div x-show="showDeleteModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm p-4">
        <div @click.away="showDeleteModal = false" x-show="showDeleteModal" x-transition.opacity class="bg-white rounded-2xl shadow-xl w-full max-w-sm p-6 relative">
            <div class="flex items-center justify-center w-12 h-12 mx-auto bg-[#F7E9E8] rounded-full mb-4">
                <svg class="w-6 h-6 text-[#B5504B]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-[17px] font-bold text-center text-gray-900 mb-2">Are you sure?</h3>
            <p class="text-[13px] text-center text-[#5B6472] mb-6 leading-relaxed">This action cannot be undone. This record will be safely removed from active views.</p>
            <div class="flex gap-3 justify-center">
                <button type="button" @click="showDeleteModal = false" class="flex-1 px-4 py-2.5 bg-[#F4F6F9] hover:bg-[#E5E9EF] text-[#1C2430] rounded-lg font-bold text-[13px] transition-colors">Cancel</button>
                <form :action="deleteAction" method="POST" class="flex-1 flex m-0">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2.5 bg-[#B5504B] hover:bg-[#9c423e] text-white rounded-lg font-bold text-[13px] transition-colors">Confirm Delete</button>
                </form>
            </div>
        </div>
    </div>
</div>
</x-app-layout>