<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Record New Loaned Tank (Huram)</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Log a customer-borrowed LPG tank or empty shell (₱0.00 monetary charge)</div>
    </x-slot>

    <form method="POST" action="{{ route('tank-loans.store') }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-3xl">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
            <div class="md:col-span-2">
                <label for="customer_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Borrowing Customer <span class="text-[#B5504B]">*</span></label>
                <select name="customer_id" id="customer_id" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="">-- Select Customer --</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id', $selectedCustomerId ?? '') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} ({{ $customer->customer_type }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="product_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Select Tank / Cylinder Item <span class="text-[#B5504B]">*</span></label>
                <select name="product_id" id="product_id" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="">-- Choose LPG Cylinder --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}" {{ old('product_id') == $product->id ? 'selected' : '' }}>
                            {{ $product->name }} (Filled: {{ $product->stock_quantity }} | Empty: {{ $product->empty_quantity }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="tank_condition_out" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Tank Condition Borrowed <span class="text-[#B5504B]">*</span></label>
                <select name="tank_condition_out" id="tank_condition_out" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="filled" {{ old('tank_condition_out', 'filled') === 'filled' ? 'selected' : '' }}>Filled Tank (Deducts Filled Stock)</option>
                    <option value="empty_shell" {{ old('tank_condition_out') === 'empty_shell' ? 'selected' : '' }}>Empty Cylinder Shell (Deducts Empty Stock)</option>
                </select>
                @error('tank_condition_out') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="quantity_borrowed" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Quantity Borrowed <span class="text-[#B5504B]">*</span></label>
                <input type="number" name="quantity_borrowed" id="quantity_borrowed" value="{{ old('quantity_borrowed', 1) }}" min="1" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('quantity_borrowed') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="notes" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Notes / Remarks</label>
                <textarea name="notes" id="notes" rows="3" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="Optional notes...">{{ old('notes') }}</textarea>
                @error('notes') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('tank-loans.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Record Loaned Tank</button>
        </div>
    </form>
</x-app-layout>
