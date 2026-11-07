<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full gap-4">
            <div>
                <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">{{ $product->name }}</h1>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Inventory and Pricing Details</div>
            </div>
            <div class="flex items-center justify-end gap-3">
                <a href="{{ route('products.index') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Back to Catalog</a>
                @if(Auth::user()->isManagerOrOwner())
                    <a href="{{ route('products.edit', $product) }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Edit Details</a>
                @endif
            </div>
        </div>
    </x-slot>

    <!-- Top Stats -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Filled Stock</div>
            <div class="font-sans text-[24px] font-bold text-gray-900">{{ number_format($product->stock_quantity) }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Empty Shells</div>
            <div class="font-sans text-[24px] font-bold text-gray-900">{{ number_format($product->empty_quantity) }}</div>
            @if(!$product->isAccessory())
                <div class="text-[11px] font-semibold text-[#0B3B70] mt-1">Total Tanks: {{ number_format($product->stock_quantity + $product->empty_quantity) }}</div>
            @endif
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Refill Price</div>
            <div class="font-sans text-[24px] font-bold text-gray-900">₱{{ number_format($product->price, 2) }}</div>
        </div>
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-1">New Tank Price</div>
            <div class="font-sans text-[24px] font-bold text-gray-900">{{ $product->new_cylinder_price !== null ? '₱' . number_format($product->new_cylinder_price, 2) : '—' }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Recent Stock Ins -->
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#E5E9EF]">
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900">Recent Deliveries (Stock In)</h3>
            </div>
            <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Date</th>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Received</th>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($product->stockIns as $in)
                            <tr>
                                <td class="py-2.5 px-4 text-[13px] border-b border-[#E5E9EF]">{{ $in->created_at->format('M d, Y') }}</td>
                                <td class="py-2.5 px-4 text-[13px] border-b border-[#E5E9EF]">
                                    @if(($in->quantity_received ?? 0) > 0 || ($in->empty_received_qty ?? 0) == 0)
                                        <span class="font-bold text-[#1E8E5A]">+{{ $in->quantity_received }} Filled</span>
                                    @endif
                                    @if(($in->empty_received_qty ?? 0) > 0)
                                        <span class="font-bold text-[#0B3B70] ml-1">+{{ $in->empty_received_qty }} Empty</span>
                                    @endif
                                </td>
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
        </div>

        <!-- Recent Stock Outs -->
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#E5E9EF]">
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900">Recent Deductions (Stock Out)</h3>
            </div>
            <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Date</th>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Removed</th>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2 px-4 border-b border-[#E5E9EF]">Reason</th>
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
    </div>

    {{-- Unified Inventory Movement Ledger for this Product --}}
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden mt-6">
        <div class="px-5 py-4 border-b border-[#E5E9EF] flex items-center justify-between">
            <h3 class="font-sans text-[14.5px] font-bold text-gray-900 m-0">Unified Inventory Movement Ledger</h3>
            @if(Auth::user()->isManagerOrOwner())
                <a href="{{ route('inventory-movements.index', ['product_id' => $product->id]) }}" class="text-[12.5px] font-semibold text-[#0B3B70] hover:underline">
                    View Full Product Ledger &rarr;
                </a>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Date &amp; Time</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Reference</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Movement Type</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Filled Stock (Before &rarr; Change &rarr; After)</th>
                        <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Empty Shells (Before &rarr; Change &rarr; After)</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Recorded By</th>
                        <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Reason</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($product->inventoryMovements ?? [] as $mv)
                        <tr>
                            <td class="py-2.5 px-4 text-[12.5px] text-[#5B6472] border-b border-[#E5E9EF] whitespace-nowrap">{{ $mv->created_at?->format('M d, Y h:i A') }}</td>
                            <td class="py-2.5 px-4 text-[12.5px] font-mono font-semibold text-[#0B3B70] border-b border-[#E5E9EF] whitespace-nowrap">{{ $mv->reference_no ?? '—' }}</td>
                            <td class="py-2.5 px-4 text-[12.5px] font-bold {{ $mv->filled_qty_change >= 0 ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }} border-b border-[#E5E9EF] whitespace-nowrap">
                                {{ ucfirst(str_replace('_', ' ', $mv->movement_type)) }}
                            </td>
                            <td class="py-2.5 px-4 text-[12.5px] text-right font-mono border-b border-[#E5E9EF] whitespace-nowrap">
                                {{ $mv->filled_qty_before }} &rarr;
                                <span class="font-bold {{ $mv->filled_qty_change >= 0 ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }}">
                                    {{ $mv->filled_qty_change >= 0 ? '+' . $mv->filled_qty_change : $mv->filled_qty_change }}
                                </span>
                                &rarr; <span class="font-bold text-[#1C2430]">{{ $mv->filled_qty_after }}</span>
                            </td>
                            <td class="py-2.5 px-4 text-[12.5px] text-right font-mono border-b border-[#E5E9EF] whitespace-nowrap">
                                {{ $mv->empty_qty_before }} &rarr;
                                <span class="font-bold {{ $mv->empty_qty_change >= 0 ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }}">
                                    {{ $mv->empty_qty_change >= 0 ? '+' . $mv->empty_qty_change : $mv->empty_qty_change }}
                                </span>
                                &rarr; <span class="font-bold text-[#1C2430]">{{ $mv->empty_qty_after }}</span>
                            </td>
                            <td class="py-2.5 px-4 text-[12.5px] font-medium text-[#1C2430] border-b border-[#E5E9EF] whitespace-nowrap">{{ $mv->user?->name ?? 'System' }}</td>
                            <td class="py-2.5 px-4 text-[12px] text-[#5B6472] border-b border-[#E5E9EF]">{{ $mv->reason ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-4 text-center text-[12px] text-[#5B6472]">No inventory movements recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Product Price & Catalog Change Audit History (Manager / Owner) --}}
    @if(Auth::user()->isManagerOrOwner())
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden mt-6">
            <div class="px-5 py-4 border-b border-[#E5E9EF]">
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900 m-0">Product Pricing &amp; Catalog Audit History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Date &amp; Time</th>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Action</th>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Actor &amp; Role</th>
                            <th class="whitespace-nowrap text-left text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Reason</th>
                            <th class="whitespace-nowrap text-right text-[11px] uppercase text-[#5B6472] font-semibold py-2.5 px-4 border-b border-[#E5E9EF]">Details</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($productAuditLogs ?? [] as $log)
                            <tr>
                                <td class="py-2.5 px-4 text-[12.5px] text-[#5B6472] border-b border-[#E5E9EF] whitespace-nowrap">{{ $log->created_at?->format('M d, Y h:i A') }}</td>
                                <td class="py-2.5 px-4 text-[13px] font-semibold text-[#1C2430] border-b border-[#E5E9EF] whitespace-nowrap">{{ $log->action_label }}</td>
                                <td class="py-2.5 px-4 text-[12.5px] text-[#1C2430] border-b border-[#E5E9EF] whitespace-nowrap">
                                    <span class="font-bold">{{ $log->actor_name_snapshot ?? 'System' }}</span>
                                    <span class="text-[#5B6472]">({{ $log->actor_role_snapshot ?? '—' }})</span>
                                </td>
                                <td class="py-2.5 px-4 text-[12px] text-[#5B6472] border-b border-[#E5E9EF]">{{ $log->reason ?? '—' }}</td>
                                <td class="py-2.5 px-4 text-right border-b border-[#E5E9EF] whitespace-nowrap">
                                    <a href="{{ route('audit-logs.show', $log) }}" class="text-[12.5px] font-semibold text-[#0B3B70] hover:underline">View details</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="py-4 text-center text-[12px] text-[#5B6472]">No catalog audit events recorded for this product.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</x-app-layout>