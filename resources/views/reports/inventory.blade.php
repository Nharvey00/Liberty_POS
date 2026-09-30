<x-app-layout>
    <div class="py-12 print:py-0">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8 print:mb-4">
                <div>
                    <h2 class="font-['Manrope'] text-[19px] font-extrabold text-gray-800">
                        Inventory Report
                    </h2>
                    <p class="text-sm text-gray-500 mt-1 print:hidden">Track product stock levels and movements.</p>
                </div>
                <div class="print:hidden">
                    <button onclick="window.print()" class="bg-[#0B3B70] text-white px-4 py-2 rounded-lg text-sm font-semibold hover:bg-[#0a2f5a]">
                        Print Report
                    </button>
                </div>
            </div>

            <!-- Filters -->
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 mb-8 print:hidden">
                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">From Date</label>
                        <input type="date" name="from_date" value="{{ $fromDate }}" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">To Date</label>
                        <input type="date" name="to_date" value="{{ $toDate }}" class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div>
                        <label class="block text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold mb-2">Product Search</label>
                        <input type="text" name="product_search" value="{{ $productSearch }}" placeholder="Product name..." class="w-full border-[#E5E9EF] rounded-lg text-[13px]">
                    </div>
                    <div class="flex items-end">
                        <button type="submit" class="w-full border border-[#E5E9EF] bg-white text-[#0B3B70] px-4 py-2 rounded-lg text-sm font-semibold hover:bg-gray-50 h-[42px]">
                            Filter
                        </button>
                    </div>
                </form>
            </div>

            <!-- Table -->
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left">
                        <thead class="bg-gray-50 border-b border-[#E5E9EF]">
                            <tr>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold">Product</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Price</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Current Stock</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-right">Empty Shells</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Stock In (Period)</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Stock Out (Period)</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Sold (Period)</th>
                                <th class="px-6 py-4 text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-[#E5E9EF]">
                            @forelse($products as $product)
                                <tr class="hover:bg-gray-50 transition-colors {{ $product->stock_quantity <= 5 ? 'bg-red-50' : '' }}">
                                    <td class="px-6 py-4 text-[13px] font-semibold text-gray-900">{{ $product->name }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-right">₱{{ number_format($product->price, 2) }}</td>
                                    <td class="px-6 py-4 text-[13px] font-bold {{ $product->stock_quantity <= 5 ? 'text-[#B5504B]' : 'text-gray-900' }} text-right">
                                        {{ $product->stock_quantity }}
                                    </td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-right">{{ $product->empty_quantity ?? 0 }}</td>
                                    <td class="px-6 py-4 text-[13px] text-[#1E8E5A] text-center">+{{ $product->period_in }}</td>
                                    <td class="px-6 py-4 text-[13px] text-[#B5504B] text-center">-{{ $product->period_out }}</td>
                                    <td class="px-6 py-4 text-[13px] text-gray-900 text-center">{{ $product->period_sold }}</td>
                                    <td class="px-6 py-4 text-[13px] text-center">
                                        @if($product->stock_quantity <= 5)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#F7E9E8] text-[#B5504B]">
                                                Low Stock
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-[#E5F5EC] text-[#1E8E5A]">
                                                In Stock
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="px-6 py-8 text-center text-[13px] text-gray-500">No products found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
