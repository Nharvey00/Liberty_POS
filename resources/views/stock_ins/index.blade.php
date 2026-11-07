<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Stock In Audit Log</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Historical records of all incoming supplier deliveries and restocks</div>
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
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-1 gap-3">
            <!-- Sub-Tabs -->
            <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl flex-wrap">
                <a href="{{ route('products.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Products Catalog
                </a>
                <a href="{{ route('stock-ins.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs transition-colors">
                    Stock In History
                </a>
                <a href="{{ route('stock-outs.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock Out History
                </a>
                <a href="{{ route('inventory-movements.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Inventory Ledger
                </a>
            </div>
            <div class="flex items-center gap-2.5 flex-wrap">
                <form x-ref="form" method="GET" action="{{ route('stock-ins.index') }}" @submit.prevent="performSearch" class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[240px]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search reference or product..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                    <button type="button" x-show="query.length > 0" @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
                </form>
                <a href="{{ route('stock-ins.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ Record Stock In</a>
            </div>
        </div>

    <!-- Data Table Card -->
    <div id="table-container">
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Date Received</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Reference / PO</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Product</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Qty Received</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Stock (Before → After)</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Unit Cost</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Recorded By</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Remarks / Status</th>
                        @if(Auth::user()?->isOwner())
                            <th class="text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockIns as $stockIn)
                        @php
                            $emptyReceived = max((int) ($stockIn->empty_received_qty ?? 0), (int) ($stockIn->empty_shells_received ?? 0));
                        @endphp
                        <tr class="hover:bg-[#F4F6F9] transition-colors {{ $stockIn->is_voided ? 'bg-red-50/40 opacity-75' : '' }}">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#1C2430] whitespace-nowrap">{{ $stockIn->created_at->format('M d, Y - h:i A') }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#0B3B70]">
                                @if($stockIn->tankLoanReturn)
                                    <span class="inline-block px-2 py-0.5 rounded bg-blue-50 text-[#0B3B70] text-[11px] font-bold">HURAM-RETURN</span>
                                    @if($stockIn->reference_no)
                                        <div class="text-[11px] font-normal text-[#5B6472]">{{ $stockIn->reference_no }}</div>
                                    @endif
                                @else
                                    {{ $stockIn->reference_no ?? 'PO-DELIVERY' }}
                                    @if($stockIn->supplier_name)
                                        <div class="text-[11px] font-normal text-[#5B6472]">{{ $stockIn->supplier_name }}</div>
                                    @endif
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-medium {{ $stockIn->is_voided ? 'line-through text-gray-500' : '' }}">{{ $stockIn->product->name ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold {{ $stockIn->is_voided ? 'line-through text-gray-400' : 'text-[#1E8E5A]' }}">
                                @if($stockIn->quantity_received > 0)
                                    <div>+{{ number_format($stockIn->quantity_received) }} <span class="text-[11px] font-normal text-[#5B6472]">Filled</span></div>
                                @endif
                                @if($emptyReceived > 0)
                                    <div class="{{ $stockIn->is_voided ? 'line-through text-gray-400' : 'text-[#0B3B70]' }}">+{{ number_format($emptyReceived) }} <span class="text-[11px] font-normal text-[#5B6472]">Empty Shell(s)</span></div>
                                @endif
                                @if($stockIn->empty_returned_qty > 0)
                                    <div class="text-[11px] font-semibold text-[#B4700A]">-{{ number_format($stockIn->empty_returned_qty) }} Empty Returned</div>
                                @endif
                                @if($stockIn->quantity_received == 0 && $emptyReceived == 0 && ($stockIn->empty_returned_qty ?? 0) == 0)
                                    +0
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] text-[#5B6472]">
                                @if(!is_null($stockIn->filled_qty_before) && !is_null($stockIn->filled_qty_after))
                                    <div>Filled: <span class="font-medium text-[#1C2430]">{{ number_format($stockIn->filled_qty_before) }} → {{ number_format($stockIn->filled_qty_after) }}</span></div>
                                    @if(!is_null($stockIn->empty_qty_before) && !is_null($stockIn->empty_qty_after) && ($stockIn->empty_qty_before != $stockIn->empty_qty_after || $emptyReceived > 0))
                                        <div class="text-[11.5px]">Empty: <span class="font-medium text-[#1C2430]">{{ number_format($stockIn->empty_qty_before) }} → {{ number_format($stockIn->empty_qty_after) }}</span></div>
                                    @endif
                                @else
                                    —
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">₱{{ number_format($stockIn->unit_cost ?? 0, 2) }}</td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] font-medium text-[#1C2430]">{{ $stockIn->user->name ?? 'System' }}</td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] text-[#5B6472]">
                                <div>{{ $stockIn->remarks ?? '—' }}</div>
                                @if($stockIn->is_voided)
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-[#F7E9E8] text-[#B5504B] border border-[#B5504B]/30">
                                            VOIDED
                                        </span>
                                        <span class="text-[11px] text-[#B5504B] font-medium">
                                            by {{ $stockIn->voidedByUser->name ?? 'Admin Owner' }}
                                            @if($stockIn->voided_at)
                                                on {{ $stockIn->voided_at->format('M d, Y h:i A') }}
                                            @endif
                                        </span>
                                    </div>
                                    @if($stockIn->void_reason)
                                        <div class="text-[11px] text-[#5B6472] italic mt-0.5">Reason: "{{ $stockIn->void_reason }}"</div>
                                    @endif
                                @endif
                            </td>
                            @if(Auth::user()?->isOwner())
                                <td class="py-3 px-4 text-right border-b border-[#E5E9EF] whitespace-nowrap">
                                    @if(!$stockIn->is_voided)
                                        <button type="button"
                                                @click="voidModalOpen = true; voidTargetId = {{ $stockIn->id }}; voidTargetName = '{{ addslashes($stockIn->product->name ?? 'Item') }} (+{{ $stockIn->quantity_received > 0 ? $stockIn->quantity_received . ' Filled' : $emptyReceived . ' Empty' }})'; voidActionUrl = '{{ route('stock-ins.void', $stockIn) }}'"
                                                class="px-2.5 py-1 rounded-md text-[11.5px] font-bold bg-[#F7E9E8] text-[#B5504B] hover:bg-[#B5504B] hover:text-white transition-colors">
                                            Void
                                        </button>
                                    @else
                                        <span class="text-[11px] text-gray-400 font-semibold">Voided</span>
                                    @endif
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ Auth::user()?->isOwner() ? 9 : 8 }}" class="py-8 text-center text-[13px] text-[#5B6472]">No stock-in records found. Use the record button above to log a delivery.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $stockIns->links() }}
        </div>
    </div>
    </div>

    @if(Auth::user()?->isOwner())
        <!-- Admin Owner Void Confirmation Modal -->
        <div x-show="voidModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="voidModalOpen = false" class="bg-white rounded-2xl border border-[#E5E9EF] shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[16px] font-bold text-[#B5504B]">Void Stock In Record</h3>
                    <button type="button" @click="voidModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <p class="text-[13px] text-[#5B6472] mb-4">
                    You are about to void <span class="font-bold text-[#1C2430]" x-text="voidTargetName"></span>. This will subtract the received quantity from current inventory.
                </p>
                <form method="POST" :action="voidActionUrl">
                    @csrf
                    <div class="mb-4">
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Reason for Voiding <span class="text-[#B5504B]">*</span></label>
                        <textarea name="void_reason" rows="3" required minlength="5" placeholder="Enter reason for voiding this Stock In log (min 5 characters)..." class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#B5504B] focus:border-[#B5504B]"></textarea>
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