<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between w-full flex-wrap gap-3">
            <div>
                <div class="flex items-center gap-2.5 flex-wrap">
                    <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">
                        Loaned Tanks: {{ $customer->business_name ?? $customer->name }}
                    </h1>
                    @if($activeBorrowedCount > 0)
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[11.5px] font-bold bg-amber-100 text-amber-800 border border-amber-300">
                            Borrowed: {{ $activeBorrowedCount }} Tank(s)
                        </span>
                    @endif
                </div>
                <div class="text-[12.5px] text-[#5B6472] mt-[2px]">
                    Detailed audit log of borrowed LPG cylinders and empty shell returns for {{ $customer->name }}
                </div>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('customers.show', $customer) }}" class="rounded-lg px-[14px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">
                    Customer Profile
                </a>
                <a href="{{ route('tank-loans.index') }}" class="rounded-lg px-[14px] py-[8px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">
                    All Loaned Tanks
                </a>
            </div>
        </div>
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
        returnActionUrl: '',
        returnProductName: '',
        returnMaxQty: 1,
        returnQty: 1,
        returnedAs: 'empty_shell'
    }" class="flex flex-col gap-5">

        <!-- Customer Loan KPIs -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
                <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Currently Unreturned (Active Borrowed)</div>
                <div class="font-sans text-[26px] font-bold {{ $activeBorrowedCount > 0 ? 'text-amber-700' : 'text-[#1E8E5A]' }}">{{ number_format($activeBorrowedCount) }} <span class="text-[13px] font-semibold text-[#5B6472]">Tank(s)</span></div>
            </div>
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5">
                <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Total Tanks Ever Borrowed</div>
                <div class="font-sans text-[26px] font-bold text-[#0B3B70]">{{ number_format($totalEverBorrowed) }} <span class="text-[13px] font-semibold text-[#5B6472]">Tank(s)</span></div>
            </div>
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5 flex items-center justify-between">
                <div>
                    <div class="text-[12px] text-[#5B6472] font-semibold mb-1">Total Tanks Returned</div>
                    <div class="font-sans text-[26px] font-bold text-[#1E8E5A]">{{ number_format($totalEverReturned) }} <span class="text-[13px] font-semibold text-[#5B6472]">Tank(s)</span></div>
                </div>
                <button type="button" @click="newLoanModalOpen = true" class="rounded-lg px-[14px] py-[9px] text-[13px] font-bold bg-[#0B3B70] text-white hover:bg-[#082A52]">
                    + New Loaned Tank
                </button>
            </div>
        </div>

        <!-- Loan Records & Return History -->
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
            <div class="px-5 py-4 border-b border-[#E5E9EF] bg-[#F4F6F9]">
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900">Borrowed Tanks &amp; Return Audit Log</h3>
                <div class="text-[11.5px] text-[#5B6472]">Click "Return Tank" on any active loan to log a partial or full return</div>
            </div>
            <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
                <table class="w-full border-collapse">
                    <thead>
                        <tr>
                            <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Date Borrowed</th>
                            <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Tank / Item</th>
                            <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Condition Out</th>
                            <th class="text-center text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Borrowed</th>
                            <th class="text-center text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Returned</th>
                            <th class="text-center text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Remaining</th>
                            <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Return History / Notes</th>
                            <th class="text-left text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Status</th>
                            <th class="text-right text-[11px] uppercase text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF]">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($customer->tankLoans as $loan)
                            @php
                                $remaining = max(0, (int) $loan->quantity_borrowed - (int) $loan->quantity_returned);
                                $isVoided = $loan->status === 'voided';
                            @endphp
                            <tr class="hover:bg-[#F4F6F9] transition-colors {{ $isVoided ? 'bg-red-50/40 opacity-75' : '' }}">
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] whitespace-nowrap">
                                    <div class="font-medium text-[#1C2430]">{{ ($loan->borrowed_at ?? $loan->created_at)->format('M d, Y - h:i A') }}</div>
                                    <div class="text-[11px] text-[#5B6472]">Logged by {{ $loan->createdByUser->name ?? 'Staff' }}</div>
                                </td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-bold {{ $isVoided ? 'line-through text-gray-500' : 'text-[#1C2430]' }}">
                                    {{ $loan->product->name ?? 'Deleted Product' }}
                                </td>
                                <td class="py-3 px-4 text-[12.5px] border-b border-[#E5E9EF]">
                                    @if($loan->tank_condition_out === 'filled')
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Filled Tank</span>
                                    @else
                                        <span class="inline-block px-2 py-0.5 rounded text-[11px] font-bold bg-[#E7EEF7] text-[#0B3B70]">Empty Shell</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center font-bold">{{ $loan->quantity_borrowed }}</td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center font-bold text-[#1E8E5A]">{{ $loan->quantity_returned }}</td>
                                <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-center font-bold {{ $remaining > 0 && !$isVoided ? 'text-amber-700' : 'text-gray-400' }}">
                                    {{ $isVoided ? 0 : $remaining }}
                                </td>
                                <td class="py-3 px-4 text-[12px] border-b border-[#E5E9EF] text-[#5B6472]">
                                    @if($loan->notes)
                                        <div class="mb-1 text-[#1C2430]">Note: {{ $loan->notes }}</div>
                                    @endif
                                    @foreach($loan->returns as $ret)
                                        <div class="text-[11.5px] {{ $ret->is_voided ? 'line-through text-gray-400' : 'text-[#1E8E5A]' }}">
                                            • Returned {{ $ret->quantity_returned }}x as <strong>{{ $ret->returned_as === 'filled' ? 'Filled Tank' : 'Empty Shell' }}</strong>
                                            on {{ ($ret->returned_at ?? $ret->created_at)->format('M d, Y h:i A') }}
                                            @if($ret->is_voided)
                                                <span class="text-[#B5504B] font-bold">(Return Voided)</span>
                                            @endif
                                        </div>
                                    @endforeach
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
                                    @if(!$isVoided && $remaining > 0)
                                        <button type="button"
                                                @click="returnModalOpen = true; returnActionUrl = '{{ route('tank-loans.return', $loan) }}'; returnProductName = '{{ addslashes($loan->product->name ?? 'Tank') }}'; returnMaxQty = {{ $remaining }}; returnQty = {{ $remaining }}; returnedAs = 'empty_shell';"
                                                class="px-3 py-1.5 rounded-md text-[12px] font-bold bg-[#1E8E5A] text-white hover:bg-[#176B45] transition-colors">
                                            Return Tank
                                        </button>
                                    @else
                                        <span class="text-[11.5px] text-gray-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="py-8 text-center text-[13px] text-[#5B6472]">No loaned tanks recorded for this customer.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- + New Loaned Tank Modal for this Customer -->
        <div x-show="newLoanModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="newLoanModalOpen = false" class="bg-white rounded-2xl border border-[#E5E9EF] shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-3">
                    <h3 class="text-[16px] font-bold text-[#1C2430]">Loan Tank to {{ $customer->name }}</h3>
                    <button type="button" @click="newLoanModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>
                <form method="POST" action="{{ route('tank-loans.store') }}" class="space-y-4">
                    @csrf
                    <input type="hidden" name="customer_id" value="{{ $customer->id }}">
                    <input type="hidden" name="redirect_to_customer" value="1">

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Select Tank / Cylinder <span class="text-[#B5504B]">*</span></label>
                        <select name="product_id" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white">
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
                            <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Condition Out <span class="text-[#B5504B]">*</span></label>
                            <select name="tank_condition_out" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white">
                                <option value="filled">Filled Tank</option>
                                <option value="empty_shell">Empty Shell</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Quantity <span class="text-[#B5504B]">*</span></label>
                            <input type="number" name="quantity_borrowed" value="1" min="1" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px]">
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Notes</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13px]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-2">
                        <button type="button" @click="newLoanModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#5B6472]">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-[13px] font-bold bg-[#0B3B70] text-white hover:bg-[#082A52]">Record Loan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Return Tank Modal -->
        <div x-show="returnModalOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
            <div @click.away="returnModalOpen = false" class="bg-white rounded-2xl border border-[#E5E9EF] shadow-xl max-w-md w-full p-6">
                <div class="flex items-center justify-between mb-3">
                    <div>
                        <h3 class="text-[16px] font-bold text-[#1E8E5A]">Return Borrowed Tank</h3>
                        <p class="text-[12px] text-[#5B6472]"><strong class="text-[#0B3B70]" x-text="returnProductName"></strong></p>
                    </div>
                    <button type="button" @click="returnModalOpen = false" class="text-gray-400 hover:text-gray-600">✕</button>
                </div>

                <form method="POST" :action="returnActionUrl" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">
                            Quantity Returned (Max <span x-text="returnMaxQty"></span>) <span class="text-[#B5504B]">*</span>
                        </label>
                        <input type="number" name="quantity_returned" x-model="returnQty" min="1" :max="returnMaxQty" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] font-bold">
                    </div>

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Returned As (Physical Condition) <span class="text-[#B5504B]">*</span></label>
                        <div class="grid grid-cols-2 gap-2.5">
                            <label class="flex flex-col p-3 border rounded-xl cursor-pointer transition-all"
                                   :class="returnedAs === 'empty_shell' ? 'border-[#0B3B70] bg-blue-50/60 ring-1 ring-[#0B3B70]' : 'border-[#E5E9EF] bg-white'">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="returned_as" value="empty_shell" x-model="returnedAs" class="text-[#0B3B70]">
                                    <span class="text-[13px] font-bold text-[#1C2430]">Empty Shell</span>
                                </div>
                                <span class="text-[11px] text-[#5B6472] mt-1">Adds to Empty Shell stock</span>
                            </label>

                            <label class="flex flex-col p-3 border rounded-xl cursor-pointer transition-all"
                                   :class="returnedAs === 'filled' ? 'border-[#1E8E5A] bg-emerald-50/60 ring-1 ring-[#1E8E5A]' : 'border-[#E5E9EF] bg-white'">
                                <div class="flex items-center gap-2">
                                    <input type="radio" name="returned_as" value="filled" x-model="returnedAs" class="text-[#1E8E5A]">
                                    <span class="text-[13px] font-bold text-[#1C2430]">Filled Tank</span>
                                </div>
                                <span class="text-[11px] text-[#5B6472] mt-1">Adds back to Filled stock</span>
                            </label>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11.5px] font-bold uppercase text-[#5B6472] mb-1.5">Return Notes</label>
                        <textarea name="notes" rows="2" class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13px]"></textarea>
                    </div>

                    <div class="flex justify-end gap-2.5 pt-2">
                        <button type="button" @click="returnModalOpen = false" class="px-4 py-2 rounded-lg text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#5B6472]">Cancel</button>
                        <button type="submit" class="px-4 py-2 rounded-lg text-[13px] font-bold bg-[#1E8E5A] text-white hover:bg-[#176B45]">Confirm Return</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
