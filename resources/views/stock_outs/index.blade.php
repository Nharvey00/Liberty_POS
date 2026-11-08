<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Stock Out Audit Log</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Historical records of damaged tanks, leaks, and manual inventory deductions</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    @if ($errors->any())
        <div class="mb-4 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px] font-semibold">
            <ul class="list-disc pl-5 m-0 space-y-1">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Inventory Sub-Tabs & Action Toolbar -->
    <div x-data="{ ...liveSearch(), voidModalOpen: false, voidTargetId: null, voidTargetName: '', voidActionUrl: '' }" class="flex flex-col gap-4">
        <div class="flex flex-wrap items-center justify-between mb-1 gap-2 md:gap-4">
            <!-- Sub-Tabs -->
            <div class="flex flex-wrap gap-2 md:gap-4 items-center bg-[#E5E9EF]/60 p-1 rounded-xl">
                <a href="{{ route('products.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Products Catalog
                </a>
                <a href="{{ route('stock-ins.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock In History
                </a>
                <a href="{{ route('stock-outs.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap bg-white text-[#B5504B] shadow-xs transition-colors">
                    Stock Out History
                </a>
                <a href="{{ route('inventory-movements.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Inventory Ledger
                </a>
                @if(auth()->user()?->isOwner())
                    <a href="{{ route('products.index', ['status' => 'deleted']) }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold whitespace-nowrap text-[#5B6472] hover:text-[#1C2430] transition-colors">
                        Deleted Products
                    </a>
                @endif
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <form x-ref="form" method="GET" action="{{ route('stock-outs.index') }}" @submit.prevent="performSearch" class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[240px]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search reason or product..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                    <button type="button" x-show="query.length > 0" x-cloak @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
                </form>
                <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#B5504B] bg-[#B5504B] text-white hover:bg-[#9a423e] whitespace-nowrap">+ Record Stock Out</a>
            </div>
        </div>

    <!-- Data Table Card -->
    <div id="table-container">
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">Date Logged</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">Reference</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">Product</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-2.5 border-b border-[#E5E9EF] whitespace-nowrap">Qty Removed</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-2.5 border-b border-[#E5E9EF] whitespace-nowrap">Stock (Before → After)</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">Details / Notes</th>
                        <th class="text-left text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">Recorded By</th>
                        @if(Auth::user()?->isOwner())
                            <th class="text-right text-[11px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockOuts as $stockOut)
                        @php
                            $shortReason = $stockOut->short_reason;
                            $isHuramBadge = in_array($shortReason, ['Loaned Tank (Huram)', 'Loaned (Huram)'], true) || str_contains(strtolower($shortReason), 'huram');
                            $isSupplierBadge = str_contains(strtolower($shortReason), 'supplier');
                        @endphp
                        <tr class="hover:bg-[#F4F6F9] transition-colors {{ $stockOut->is_voided ? 'bg-red-50/40 opacity-75' : '' }}">
                            <td class="py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">
                                <div class="text-sm font-medium text-[#1C2430]">{{ $stockOut->created_at->format('M d, Y') }}</div>
                                <div class="text-xs text-gray-500">{{ $stockOut->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap">
                                <div class="text-sm font-semibold text-[#0B3B70] mb-1 truncate max-w-[140px]">
                                    {{ $stockOut->reference_no ?? 'ADJ-' . str_pad($stockOut->id, 4, '0', STR_PAD_LEFT) }}
                                </div>
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap {{ $isHuramBadge ? 'bg-amber-100 text-amber-800' : ($isSupplierBadge ? 'bg-blue-50 text-[#0B3B70]' : 'bg-[#F7E9E8] text-[#B5504B]') }}">
                                    {{ $shortReason }}
                                </span>
                            </td>
                            <td class="py-3 px-3 border-b border-[#E5E9EF] max-w-[170px]">
                                <div class="text-sm font-medium truncate {{ $stockOut->is_voided ? 'line-through text-gray-500' : 'text-[#1C2430]' }}" title="{{ $stockOut->product?->name ?? 'N/A' }}">
                                    {{ $stockOut->product?->name ?? 'N/A' }}
                                </div>
                            </td>
                            <td class="py-3 px-2.5 border-b border-[#E5E9EF] whitespace-nowrap">
                                <div class="flex flex-col gap-0.5">
                                    @if(($stockOut->quantity_removed ?? 0) > 0)
                                        <span class="text-xs font-bold {{ $stockOut->is_voided ? 'line-through text-gray-400' : 'text-[#B5504B]' }}">
                                            -{{ number_format($stockOut->quantity_removed) }} Filled
                                        </span>
                                    @endif
                                    @if(($stockOut->empty_quantity_removed ?? 0) > 0)
                                        <span class="text-xs font-semibold {{ $stockOut->is_voided ? 'line-through text-gray-400' : 'text-[#5B6472]' }}">
                                            -{{ number_format($stockOut->empty_quantity_removed) }} Empty
                                        </span>
                                    @elseif(($stockOut->quantity_removed ?? 0) == 0)
                                        <span class="text-xs text-gray-400">0</span>
                                    @endif
                                </div>
                            </td>
                            <td class="py-3 px-2.5 border-b border-[#E5E9EF] whitespace-nowrap text-xs text-[#5B6472]">
                                @if(!is_null($stockOut->filled_qty_before) && !is_null($stockOut->filled_qty_after))
                                    <div>Filled: <span class="font-medium text-[#1C2430]">{{ number_format($stockOut->filled_qty_before) }} → {{ number_format($stockOut->filled_qty_after) }}</span></div>
                                    @if(!is_null($stockOut->empty_qty_before) && !is_null($stockOut->empty_qty_after))
                                        <div class="text-gray-500">Empty: <span class="font-medium text-[#1C2430]">{{ number_format($stockOut->empty_qty_before) }} → {{ number_format($stockOut->empty_qty_after) }}</span></div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-3 border-b border-[#E5E9EF] max-w-[200px] whitespace-normal">
                                @if($stockOut->tankLoan && $stockOut->tankLoan->customer)
                                    <div class="text-xs font-medium text-[#1C2430] whitespace-normal">
                                        Borrowed by: <a href="{{ route('customers.show', $stockOut->tankLoan->customer_id) }}" class="text-[#0B3B70] hover:underline">{{ $stockOut->tankLoan->customer?->name }}</a>
                                    </div>
                                @endif
                                @if($stockOut->notes)
                                    <div class="line-clamp-2 text-xs text-gray-500 whitespace-normal" title="{{ $stockOut->notes }}">
                                        {{ $stockOut->notes }}
                                    </div>
                                @elseif(!($stockOut->tankLoan && $stockOut->tankLoan->customer) && !$stockOut->is_voided)
                                    <div class="text-xs text-gray-400">—</div>
                                @endif

                                @if($stockOut->is_voided)
                                    <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium whitespace-nowrap bg-[#F7E9E8] text-[#B5504B] border border-[#B5504B]/30">
                                            VOIDED
                                        </span>
                                        <span class="text-xs text-[#B5504B] font-medium whitespace-normal">
                                            by {{ $stockOut->voidedByUser?->name ?? 'Admin Owner' }}
                                        </span>
                                    </div>
                                    @if($stockOut->void_reason)
                                        <div class="line-clamp-2 text-xs text-gray-500 italic mt-0.5 whitespace-normal" title="{{ $stockOut->void_reason }}">Reason: "{{ $stockOut->void_reason }}"</div>
                                    @endif
                                @endif
                            </td>
                            <td class="py-3 px-3 border-b border-[#E5E9EF] whitespace-nowrap text-xs font-medium text-[#1C2430]">
                                {{ $stockOut->user?->name ?? 'System' }}
                            </td>
                            @if(Auth::user()?->isOwner())
                                <td class="py-3 px-3 text-right border-b border-[#E5E9EF] whitespace-nowrap">
                                    @if(!$stockOut->is_voided)
                                        <button type="button"
                                                @click="voidModalOpen = true; voidTargetId = {{ $stockOut->id }}; voidTargetName = '{{ addslashes($stockOut->product?->name ?? 'Item') }} (-{{ $stockOut->quantity_removed > 0 ? $stockOut->quantity_removed . ' Filled' : $stockOut->empty_quantity_removed . ' Empty' }})'; voidActionUrl = '{{ route('stock-outs.void', $stockOut) }}'"
                                                class="px-2.5 py-1 rounded-md text-xs font-bold bg-[#F7E9E8] text-[#B5504B] hover:bg-[#B5504B] hover:text-white transition-colors">
                                            Void
                                        </button>
                                    @else
                                        <span class="text-xs text-gray-400 font-semibold">Voided</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ Auth::user()?->isOwner() ? 8 : 7 }}" class="py-8 text-center text-[13px] text-[#5B6472]">No stock-out records found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $stockOuts->links() }}
        </div>
    </div>
    </div>

    @if(Auth::user()?->isOwner())
        <!-- Admin Owner Void Confirmation Modal -->
        <div x-show="voidModalOpen" x-cloak style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="voidModalOpen = false" class="bg-white rounded-2xl border border-[#E5E9EF] shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[16px] font-bold text-[#B5504B]">Void Stock Out Record</h3>
                    <button type="button" @click="voidModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <p class="text-[13px] text-[#5B6472] mb-4">
                    You are about to void <span class="font-bold text-[#1C2430]" x-text="voidTargetName"></span>. This will restore the deducted quantity back to current inventory.
                </p>
                <form method="POST" :action="voidActionUrl" data-submit-once>
                    @csrf
                    <div class="mb-4">
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Reason for Voiding <span class="text-[#B5504B]">*</span></label>
                        <textarea name="void_reason" rows="3" required minlength="5" placeholder="Enter reason for voiding this Stock Out log (min 5 characters)..." class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#B5504B] focus:border-[#B5504B]"></textarea>
                    </div>
                    <div class="flex justify-end gap-2.5">
                        <button type="button" @click="voidModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#5B6472] hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-[13px] font-bold bg-[#B5504B] text-white hover:bg-[#9a423e]">Confirm Void</button>
                    </div>
                </form>
            </div>
        </div>
    @endif
    </div>
</x-app-layout>