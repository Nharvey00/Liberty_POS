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
        <div class="flex flex-col md:flex-row md:items-center justify-between mb-1 gap-3">
            <!-- Sub-Tabs -->
            <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl">
                <a href="{{ route('products.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Products Catalog
                </a>
                <a href="{{ route('stock-ins.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430] transition-colors">
                    Stock In History
                </a>
                <a href="{{ route('stock-outs.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#B5504B] shadow-xs transition-colors">
                    Stock Out History
                </a>
            </div>

            <div class="flex items-center gap-2.5 flex-wrap">
                <form x-ref="form" method="GET" action="{{ route('stock-outs.index') }}" @submit.prevent="performSearch" class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[240px]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="search" x-model="query" @input.debounce.500ms="performSearch" placeholder="Search reason or product..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                    <button type="button" x-show="query.length > 0" @click="query = ''; performSearch()" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]" style="display: none;">✕</button>
                </form>
                <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#B5504B] bg-[#B5504B] text-white hover:bg-[#9a423e] whitespace-nowrap">+ Record Stock Out</a>
            </div>
        </div>

    <!-- Data Table Card -->
    <div id="table-container">
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Date Logged</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Reason</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Product</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Qty Removed</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Details / Status</th>
                        @if(Auth::user()?->isOwner())
                            <th class="text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Actions</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @forelse($stockOuts as $stockOut)
                        <tr class="hover:bg-[#F4F6F9] transition-colors {{ $stockOut->is_voided ? 'bg-red-50/40 opacity-75' : '' }}">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#1C2430]">{{ $stockOut->created_at->format('M d, Y - h:i A') }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold {{ $stockOut->reason === 'Loaned Tank (Huram)' ? 'bg-amber-100 text-amber-800' : 'bg-[#F7E9E8] text-[#B5504B]' }}">{{ $stockOut->reason }}</span>
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-medium {{ $stockOut->is_voided ? 'line-through text-gray-500' : '' }}">{{ $stockOut->product->name ?? 'N/A' }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold {{ $stockOut->is_voided ? 'line-through text-gray-400' : 'text-[#B5504B]' }}">
                                @if($stockOut->quantity_removed > 0)
                                    <div>-{{ number_format($stockOut->quantity_removed) }} <span class="text-[11px] font-normal text-[#5B6472]">Filled</span></div>
                                @endif
                                @if(($stockOut->empty_quantity_removed ?? 0) > 0)
                                    <div>-{{ number_format($stockOut->empty_quantity_removed) }} <span class="text-[11px] font-normal text-[#5B6472]">Empty Shell(s)</span></div>
                                @endif
                                @if($stockOut->quantity_removed == 0 && ($stockOut->empty_quantity_removed ?? 0) == 0)
                                    -0
                                @endif
                            </td>
                            <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF] text-[#5B6472]">
                                @if($stockOut->tankLoan && $stockOut->tankLoan->customer)
                                    <div class="font-medium text-[#1C2430]">
                                        Borrowed by: <a href="{{ route('customers.show', $stockOut->tankLoan->customer_id) }}" class="text-[#0B3B70] hover:underline">{{ $stockOut->tankLoan->customer->name }}</a>
                                    </div>
                                    @if($stockOut->tankLoan->notes)
                                        <div class="text-[11.5px] text-[#5B6472]">{{ $stockOut->tankLoan->notes }}</div>
                                    @endif
                                @else
                                    <div>{{ $stockOut->remarks ?? '—' }}</div>
                                @endif

                                @if($stockOut->is_voided)
                                    <div class="mt-1.5 flex flex-wrap items-center gap-1.5">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10.5px] font-bold bg-[#F7E9E8] text-[#B5504B] border border-[#B5504B]/30">
                                            VOIDED
                                        </span>
                                        <span class="text-[11px] text-[#B5504B] font-medium">
                                            by {{ $stockOut->voidedByUser->name ?? 'Admin Owner' }}
                                            @if($stockOut->voided_at)
                                                on {{ $stockOut->voided_at->format('M d, Y h:i A') }}
                                            @endif
                                        </span>
                                    </div>
                                    @if($stockOut->void_reason)
                                        <div class="text-[11px] text-[#5B6472] italic mt-0.5">Reason: "{{ $stockOut->void_reason }}"</div>
                                    @endif
                                @endif
                            </td>
                            @if(Auth::user()?->isOwner())
                                <td class="py-3 px-4 text-right border-b border-[#E5E9EF] whitespace-nowrap">
                                    @if(!$stockOut->is_voided)
                                        <button type="button"
                                                @click="voidModalOpen = true; voidTargetId = {{ $stockOut->id }}; voidTargetName = '{{ addslashes($stockOut->product->name ?? 'Item') }} (-{{ $stockOut->quantity_removed > 0 ? $stockOut->quantity_removed . ' Filled' : $stockOut->empty_quantity_removed . ' Empty' }})'; voidActionUrl = '{{ route('stock-outs.void', $stockOut) }}'"
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
                            <td colspan="{{ Auth::user()?->isOwner() ? 6 : 5 }}" class="py-8 text-center text-[13px] text-[#5B6472]">No stock-out records found.</td>
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
        <div x-show="voidModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="voidModalOpen = false" class="bg-white rounded-2xl border border-[#E5E9EF] shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[16px] font-bold text-[#B5504B]">Void Stock Out Record</h3>
                    <button type="button" @click="voidModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <p class="text-[13px] text-[#5B6472] mb-4">
                    You are about to void <span class="font-bold text-[#1C2430]" x-text="voidTargetName"></span>. This will restore the deducted quantity back to current inventory.
                </p>
                <form method="POST" :action="voidActionUrl">
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