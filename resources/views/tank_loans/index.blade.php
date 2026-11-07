<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Loaned Tanks (Huram)</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Track customer-borrowed LPG cylinders and empty shells (₱0.00 monetary charge)</div>
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

    <div x-data="{
        newLoanModalOpen: false,
        returnModalOpen: false,
        returnLoanId: null,
        returnActionUrl: '',
        returnCustomerName: '',
        returnProductName: '',
        returnMaxQty: 1,
        returnQty: 1,
        returnedAs: 'empty_shell'
    }" class="flex flex-col gap-5">

        <!-- Summary Metrics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
                <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Total Active Borrowed Tanks</div>
                <div class="font-sans text-[28px] font-bold text-amber-700">{{ number_format($totalActiveBorrowedTanks) }} <span class="text-[14px] font-semibold text-[#5B6472]">Tank(s)</span></div>
                <div class="text-[11.5px] text-[#5B6472] mt-1">Currently out with customers</div>
            </div>
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
                <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Customers with Borrowed Tanks</div>
                <div class="font-sans text-[28px] font-bold text-[#0B3B70]">{{ number_format($customersWithBorrowedTanks) }} <span class="text-[14px] font-semibold text-[#5B6472]">Customer(s)</span></div>
                <div class="text-[11.5px] text-[#5B6472] mt-1">Holding unreturned Huram cylinders</div>
            </div>
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
                <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Tanks Returned Today</div>
                <div class="font-sans text-[28px] font-bold text-[#1E8E5A]">{{ number_format($totalReturnedToday) }} <span class="text-[14px] font-semibold text-[#5B6472]">Tank(s)</span></div>
                <div class="text-[11.5px] text-[#5B6472] mt-1">Logged into Stock In History today</div>
            </div>
        </div>

        <!-- Filter & Action Toolbar -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-3">
            <form method="GET" action="{{ route('tank-loans.index') }}" class="flex items-center gap-2.5 flex-wrap">
                <div class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 min-w-[240px]">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="search" value="{{ $search }}" placeholder="Search customer or tank..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]">
                </div>
                <select name="status" onchange="this.form.submit()" class="px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13px] bg-white font-semibold text-[#1C2430]">
                    <option value="all" {{ ($statusFilter ?? 'all') === 'all' ? 'selected' : '' }}>All Loan Statuses</option>
                    <option value="active" {{ ($statusFilter ?? '') === 'active' ? 'selected' : '' }}>Active (Borrowed &amp; Partial)</option>
                    <option value="borrowed" {{ ($statusFilter ?? '') === 'borrowed' ? 'selected' : '' }}>Borrowed Only</option>
                    <option value="partially_returned" {{ ($statusFilter ?? '') === 'partially_returned' ? 'selected' : '' }}>Partially Returned</option>
                    <option value="returned" {{ ($statusFilter ?? '') === 'returned' ? 'selected' : '' }}>Fully Returned</option>
                    <option value="voided" {{ ($statusFilter ?? '') === 'voided' ? 'selected' : '' }}>Voided</option>
                </select>
                <button type="submit" class="px-3.5 py-2 rounded-lg text-[13px] font-semibold bg-white border border-[#E5E9EF] text-[#1C2430] hover:bg-gray-50">Filter</button>
                @if($search || ($statusFilter ?? 'all') !== 'all')
                    <a href="{{ route('tank-loans.index') }}" class="px-3 py-2 rounded-lg text-[12.5px] font-semibold text-[#5B6472] hover:text-[#1C2430]">Reset</a>
                @endif
            </form>

            <div class="flex items-center gap-2.5">
                <a href="{{ route('stock-outs.create') }}" class="rounded-lg px-[14px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9] whitespace-nowrap">
                    Via Stock Out
                </a>
                <button type="button" @click="newLoanModalOpen = true" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">
                    + New Loaned Tank
                </button>
            </div>
        </div>

        <!-- Loaned Tanks Table -->
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
            <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Date Borrowed</th>
                            <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Customer</th>
                            <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Tank / Item</th>
                            <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Condition Out</th>
                            <th class="text-center text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Borrowed</th>
                            <th class="text-center text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Returned</th>
                            <th class="text-center text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Remaining</th>
                            <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Status</th>
                            <th class="text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($tankLoans as $loan)
                            @php
                                $remaining = max(0, (int) $loan->quantity_borrowed - (int) $loan->quantity_returned);
                                $isVoided = $loan->status === 'voided';
                            @endphp
                            <tr class="hover:bg-[#F4F6F9] transition-colors {{ $isVoided ? 'bg-red-50/40 opacity-75' : '' }}">
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#1C2430] whitespace-nowrap">
                                    <div>{{ ($loan->borrowed_at ?? $loan->created_at)->format('M d, Y - h:i A') }}</div>
                                    <div class="text-[11px] text-[#5B6472]">by {{ $loan->createdByUser->name ?? 'Staff' }}</div>
                                </td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#1C2430]">
                                    @if($loan->customer)
                                        <a href="{{ route('tank-loans.customer', $loan->customer_id) }}" class="text-[#0B3B70] hover:underline">
                                            {{ $loan->customer->name }}
                                        </a>
                                    @else
                                        <span>Unknown Customer</span>
                                    @endif
                                    @if($loan->notes)
                                        <div class="text-[11px] font-normal text-[#5B6472]">{{ $loan->notes }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-medium {{ $isVoided ? 'line-through text-gray-500' : 'text-[#1C2430]' }}">
                                    {{ $loan->product->name ?? 'Deleted Product' }}
                                </td>
                                <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF]">
                                    @if($loan->tank_condition_out === 'filled')
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Filled Tank</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-[#E7EEF7] text-[#0B3B70]">Empty Shell</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center font-bold text-[#1C2430]">
                                    {{ number_format($loan->quantity_borrowed) }}
                                </td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center font-bold text-[#1E8E5A]">
                                    {{ number_format($loan->quantity_returned) }}
                                </td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center font-bold {{ $remaining > 0 && !$isVoided ? 'text-amber-700' : 'text-gray-400' }}">
                                    {{ $isVoided ? 0 : number_format($remaining) }}
                                </td>
                                <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF]">
                                    @if($loan->status === 'borrowed')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-amber-100 text-amber-800">Borrowed</span>
                                    @elseif($loan->status === 'partially_returned')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-blue-100 text-[#0B3B70]">Partially Returned</span>
                                    @elseif($loan->status === 'returned')
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Returned</span>
                                    @else
                                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[11px] font-bold bg-[#F7E9E8] text-[#B5504B]">Voided</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right border-b border-[#E5E9EF] whitespace-nowrap">
                                    <div class="flex items-center justify-end gap-2">
                                        @if(!$isVoided && $remaining > 0)
                                            <button type="button"
                                                    @click="returnModalOpen = true; returnLoanId = {{ $loan->id }}; returnActionUrl = '{{ route('tank-loans.return', $loan) }}'; returnCustomerName = '{{ addslashes($loan->customer->name ?? 'Customer') }}'; returnProductName = '{{ addslashes($loan->product->name ?? 'Tank') }}'; returnMaxQty = {{ $remaining }}; returnQty = {{ $remaining }}; returnedAs = 'empty_shell';"
                                                    class="px-2.5 py-1 rounded-md text-[11.5px] font-bold bg-[#1E8E5A] text-white hover:bg-[#176B45] transition-colors">
                                                Return Tank
                                            </button>
                                        @endif
                                        @if($loan->customer_id)
                                            <a href="{{ route('tank-loans.customer', $loan->customer_id) }}" class="text-[12px] font-bold text-[#0B3B70] hover:underline">
                                                Customer Log
                                            </a>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-[13px] text-[#5B6472]">No loaned tank (Huram) records found. Click "+ New Loaned Tank" above to record a borrowed tank.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
                {{ $tankLoans->links() }}
            </div>
        </div>

        <!-- Entry Point B Modal: + New Loaned Tank -->
        <div x-show="newLoanModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="newLoanModalOpen = false" class="bg-white rounded-2xl border border-[#E5E9EF] shadow-xl max-w-lg w-full p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-[16px] font-bold text-[#1C2430]">Record New Loaned Tank (Huram)</h3>
                        <p class="text-[12px] text-[#5B6472]">₱0.00 monetary charge — automatically logs Stock Out &amp; deducts inventory</p>
                    </div>
                    <button type="button" @click="newLoanModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form method="POST" action="{{ route('tank-loans.store') }}" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Borrowing Customer <span class="text-[#B5504B]">*</span></label>
                        <select name="customer_id" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                            <option value="">-- Select Customer --</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} ({{ $customer->customer_type }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">LPG Tank / Cylinder Item <span class="text-[#B5504B]">*</span></label>
                        <select name="product_id" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                            <option value="">-- Select Tank Item --</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} (Filled: {{ $product->stock_quantity }} | Empty: {{ $product->empty_quantity }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Tank Condition Out <span class="text-[#B5504B]">*</span></label>
                            <select name="tank_condition_out" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                                <option value="filled">Filled Tank</option>
                                <option value="empty_shell">Empty Cylinder Shell</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Quantity Borrowed <span class="text-[#B5504B]">*</span></label>
                            <input type="number" name="quantity_borrowed" value="1" min="1" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Notes / Remarks</label>
                        <textarea name="notes" rows="2" placeholder="Optional delivery or borrower notes..." class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-2">
                        <button type="button" @click="newLoanModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#5B6472] hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-[13px] font-bold bg-[#0B3B70] text-white hover:bg-[#082A52]">Record Loaned Tank</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Return Tank Modal (Mandatory Empty Shell vs. Filled Tank Selection) -->
        <div x-show="returnModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="returnModalOpen = false" class="bg-white rounded-2xl border border-[#E5E9EF] shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-[16px] font-bold text-[#1E8E5A]">Return Borrowed Tank (Huram)</h3>
                        <p class="text-[12px] text-[#5B6472]">Customer: <strong class="text-[#1C2430]" x-text="returnCustomerName"></strong> · <strong class="text-[#0B3B70]" x-text="returnProductName"></strong></p>
                    </div>
                    <button type="button" @click="returnModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form method="POST" :action="returnActionUrl" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">
                            Quantity Returned (Max <span x-text="returnMaxQty"></span>) <span class="text-[#B5504B]">*</span>
                        </label>
                        <input type="number" name="quantity_returned" x-model="returnQty" min="1" :max="returnMaxQty" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] font-bold focus:ring-[#1E8E5A] focus:border-[#1E8E5A]">
                    </div>

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Returned As (Physical Condition) <span class="text-[#B5504B]">*</span></label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <label class="flex flex-col p-3 border rounded-xl cursor-pointer transition-all"
                                   :class="returnedAs === 'empty_shell' ? 'border-[#0B3B70] bg-blue-50/60 ring-1 ring-[#0B3B70]' : 'border-[#E5E9EF] bg-white'">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="returned_as" value="empty_shell" x-model="returnedAs" class="text-[#0B3B70] focus:ring-[#0B3B70]">
                                    <span class="text-[13px] font-bold text-[#1C2430]">Empty Shell</span>
                                </div>
                                <span class="text-[11px] text-[#5B6472] mt-1">Adds to Empty Shell stock (Standard Return)</span>
                            </label>

                            <label class="flex flex-col p-3 border rounded-xl cursor-pointer transition-all"
                                   :class="returnedAs === 'filled' ? 'border-[#1E8E5A] bg-emerald-50/60 ring-1 ring-[#1E8E5A]' : 'border-[#E5E9EF] bg-white'">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="returned_as" value="filled" x-model="returnedAs" class="text-[#1E8E5A] focus:ring-[#1E8E5A]">
                                    <span class="text-[13px] font-bold text-[#1C2430]">Filled Tank</span>
                                </div>
                                <span class="text-[11px] text-[#5B6472] mt-1">Adds back to Filled Tank stock (Unused)</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Return Notes</label>
                        <textarea name="notes" rows="2" placeholder="Optional condition or return notes..." class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#1E8E5A] focus:border-[#1E8E5A]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-2">
                        <button type="button" @click="returnModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#5B6472] hover:bg-gray-50">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-[13px] font-bold bg-[#1E8E5A] text-white hover:bg-[#176B45]">Confirm Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
