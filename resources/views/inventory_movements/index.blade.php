<x-app-layout>
    <x-slot name="header">
        <div>
            <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Inventory Ledger</h1>
            <p class="text-[12.5px] text-[#5B6472] mt-[2px]">Every stock change, who recorded it, and the resulting balance.</p>
        </div>
    </x-slot>

    @php
        $baseQuery = request()->except(['group', 'page']);
    @endphp

    <div x-data="liveSearch()" class="flex flex-col gap-4">
        {{-- Inventory Hub 5-Tab Top Pill Bar --}}
        <div class="flex flex-wrap items-center justify-between mb-1 gap-2 md:gap-4">
            <div class="flex flex-wrap gap-2 md:gap-4 items-center bg-[#E5E9EF]/60 p-1 rounded-xl">
                <a href="{{ route('products.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Products Catalog
                </a>
                <a href="{{ route('stock-ins.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock In History
                </a>
                <a href="{{ route('stock-outs.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock Out History
                </a>
                <a href="{{ route('inventory-movements.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap bg-white text-[#0B3B70] shadow-xs transition-colors">
                    Inventory Ledger
                </a>
                @if(auth()->user()?->isOwner())
                    <a href="{{ route('products.index', ['status' => 'deleted']) }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                        Deleted Products
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#0B3B70] hover:bg-[#F4F6F9] whitespace-nowrap">↓ Stock Out</a>
                <a href="{{ route('stock-ins.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ Record Stock In</a>
            </div>
        </div>

        {{-- Movement Group Tabs --}}
        <nav aria-label="Movement types" class="flex items-center gap-1 bg-[#E5E9EF]/60 p-1 rounded-xl overflow-x-auto self-start max-w-full">
            <a href="{{ route('inventory-movements.index', $baseQuery) }}"
               @if(!$activeGroup) aria-current="page" @endif
               class="px-3.5 py-1.5 rounded-lg text-[13px] font-semibold whitespace-nowrap transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0B3B70] {{ !$activeGroup ? 'bg-white text-[#0B3B70] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }}">
                All Movements
            </a>
            @foreach($groups as $key => $group)
                <a href="{{ route('inventory-movements.index', array_merge($baseQuery, ['group' => $key])) }}"
                   @if($activeGroup === $key) aria-current="page" @endif
                   class="px-3.5 py-1.5 rounded-lg text-[13px] font-semibold whitespace-nowrap transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0B3B70] {{ $activeGroup === $key ? 'bg-white text-[#0B3B70] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }}">
                    {{ $group['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Compact Filter Toolbar --}}
        <form x-ref="form" method="GET" action="{{ route('inventory-movements.index') }}" role="search" class="flex flex-wrap items-center gap-2.5">
            @if($activeGroup)
                <input type="hidden" name="group" value="{{ $activeGroup }}">
            @endif

            <div class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 flex-1 min-w-[240px] max-w-[380px] focus-within:border-[#0B3B70]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <label for="ledger-search" class="sr-only">Search movements</label>
                <input id="ledger-search" type="text" name="search" x-model="query" @input.debounce.500ms="performSearch"
                       placeholder="Search reference, product, or reason..."
                       class="border-none outline-none w-full bg-transparent p-0 focus:ring-0 text-[13px] text-[#1C2430]">
            </div>

            <label for="ledger-product" class="sr-only">Product</label>
            <select id="ledger-product" name="product_id" onchange="this.form.submit()"
                    class="py-2 pl-3 pr-9 border border-[#E5E9EF] rounded-lg text-[13px] text-[#1C2430] bg-white focus:border-[#0B3B70] focus:ring-[#0B3B70]">
                <option value="">All products</option>
                @foreach($products as $prod)
                    <option value="{{ $prod->id }}" {{ (string) request('product_id') === (string) $prod->id ? 'selected' : '' }}>{{ $prod->name }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-1.5">
                <label for="ledger-from" class="text-[13px] text-[#5B6472]">From</label>
                <input id="ledger-from" type="date" name="from_date" value="{{ request('from_date') }}" onchange="this.form.submit()"
                       class="py-2 px-3 border border-[#E5E9EF] rounded-lg text-[13px] text-[#1C2430] focus:border-[#0B3B70] focus:ring-[#0B3B70]">
                <label for="ledger-to" class="text-[13px] text-[#5B6472]">to</label>
                <input id="ledger-to" type="date" name="to_date" value="{{ request('to_date') }}" onchange="this.form.submit()"
                       class="py-2 px-3 border border-[#E5E9EF] rounded-lg text-[13px] text-[#1C2430] focus:border-[#0B3B70] focus:ring-[#0B3B70]">
            </div>

            @if($hasActiveFilters)
                <a href="{{ route('inventory-movements.index', array_filter(['group' => $activeGroup])) }}" class="text-[13px] font-semibold text-[#0B3B70] hover:underline px-1">
                    Clear filters
                </a>
            @endif
        </form>

        {{-- Movements Table --}}
        <div id="table-container">
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <caption class="sr-only">Inventory movements, newest first</caption>
                        <thead>
                            <tr class="bg-[#F8FAFC]">
                                <th scope="col" class="whitespace-nowrap text-left text-[11px] uppercase tracking-[0.02em] font-semibold text-[#5B6472] py-3 px-3 border-b border-[#E5E9EF]">Date &amp; Time</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[11px] uppercase tracking-[0.02em] font-semibold text-[#5B6472] py-3 px-3 border-b border-[#E5E9EF]">Reference</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[11px] uppercase tracking-[0.02em] font-semibold text-[#5B6472] py-3 px-3 border-b border-[#E5E9EF]">Product</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[11px] uppercase tracking-[0.02em] font-semibold text-[#5B6472] py-3 px-2.5 border-b border-[#E5E9EF]">Qty Change</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[11px] uppercase tracking-[0.02em] font-semibold text-[#5B6472] py-3 px-2.5 border-b border-[#E5E9EF]">Stock (Before → After)</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[11px] uppercase tracking-[0.02em] font-semibold text-[#5B6472] py-3 px-3 border-b border-[#E5E9EF]">Details / Notes</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[11px] uppercase tracking-[0.02em] font-semibold text-[#5B6472] py-3 px-3 border-b border-[#E5E9EF]">Recorded By</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($movements as $mv)
                                @php
                                    $typeLabel = $movementTypes[$mv->movement_type] ?? \Illuminate\Support\Str::headline($mv->movement_type);
                                    $isPositiveType = in_array($mv->movement_type, ['stock_in', 'initial_stock', 'void_restock', 'return_in'], true);
                                    $isNegativeType = in_array($mv->movement_type, ['damaged_writeoff', 'expired_writeoff', 'lost_writeoff', 'supplier_return'], true);
                                @endphp
                                <tr class="hover:bg-[#F8FAFC] transition-colors">
                                    <td class="py-3 px-3 border-b border-[#EEF1F5] whitespace-nowrap align-top">
                                        <div class="text-sm font-medium text-[#1C2430]">{{ $mv->created_at?->format('M d, Y') }}</div>
                                        <div class="text-xs text-gray-500">{{ $mv->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td class="py-3 px-3 border-b border-[#EEF1F5] whitespace-nowrap align-top">
                                        <div class="text-sm font-semibold text-[#0B3B70] mb-1 truncate max-w-[140px]">
                                            {{ $mv->reference_no ?? '—' }}
                                        </div>
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ $isPositiveType ? 'bg-[#E5F5EC] text-[#1E8E5A]' : ($isNegativeType ? 'bg-[#F7E9E8] text-[#B5504B]' : 'bg-blue-50 text-[#0B3B70]') }}">
                                            {{ $typeLabel }}
                                        </span>
                                    </td>
                                    <td class="py-3 px-3 border-b border-[#EEF1F5] align-top max-w-[170px]">
                                        @if($mv->product)
                                            <a href="{{ route('products.show', $mv->product_id) }}" class="block text-sm font-medium text-[#1C2430] hover:text-[#0B3B70] hover:underline truncate" title="{{ $mv->product->name }}">{{ $mv->product->name }}</a>
                                        @else
                                            <span class="block text-sm font-medium text-[#1C2430] truncate">Unknown product</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-2.5 border-b border-[#EEF1F5] whitespace-nowrap align-top">
                                        <div class="flex flex-col gap-0.5">
                                            @if($mv->filled_qty_change != 0)
                                                <span class="text-xs font-bold {{ $mv->filled_qty_change > 0 ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }}">
                                                    {{ sprintf('%+d', $mv->filled_qty_change) }} Filled
                                                </span>
                                            @endif
                                            @if($mv->empty_qty_change != 0)
                                                <span class="text-xs font-semibold {{ $mv->empty_qty_change > 0 ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }}">
                                                    {{ sprintf('%+d', $mv->empty_qty_change) }} Empty
                                                </span>
                                            @endif
                                            @if($mv->filled_qty_change == 0 && $mv->empty_qty_change == 0)
                                                <span class="text-xs text-gray-400">No change</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3 px-2.5 border-b border-[#EEF1F5] whitespace-nowrap align-top text-xs text-[#5B6472]">
                                        <div>Filled: <span class="font-medium text-[#1C2430]">{{ number_format($mv->filled_qty_before) }} → {{ number_format($mv->filled_qty_after) }}</span></div>
                                        <div class="text-gray-500">Empty: <span class="font-medium text-[#1C2430]">{{ number_format($mv->empty_qty_before) }} → {{ number_format($mv->empty_qty_after) }}</span></div>
                                    </td>
                                    <td class="py-3 px-3 border-b border-[#EEF1F5] align-top max-w-[200px] whitespace-normal">
                                        @if($mv->reason)
                                            <div class="line-clamp-2 text-xs text-gray-500 whitespace-normal" title="{{ $mv->reason }}">{{ $mv->reason }}</div>
                                        @else
                                            <span class="text-xs text-gray-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-3 border-b border-[#EEF1F5] whitespace-nowrap align-top text-xs font-medium text-[#1C2430]">
                                        {{ $mv->user?->name ?? 'System' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="py-12 text-center">
                                        <div class="text-[14px] font-semibold text-[#1C2430]">No stock movements found</div>
                                        <div class="text-[13px] text-[#5B6472] mt-1">Try a different movement type, product, or date range.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($movements->hasPages())
                    <div class="px-5 py-4 border-t border-[#E5E9EF]">
                        {{ $movements->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
