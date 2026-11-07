<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Record Stock Out (Deduction)</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manually deduct inventory due to damages, leaks, or corrections</div>
    </x-slot>

    <form method="POST" action="{{ route('stock-outs.store') }}" 
          x-data="{
              products: {{ Js::from($products) }},
              selectedProductId: '{{ old('product_id', '') }}',
              reason: '{{ old('reason', 'Damaged / leaking') }}',
              tankConditionOut: '{{ old('tank_condition_out', 'filled') }}',
              get selectedProduct() {
                  return this.products.find(p => p.id == this.selectedProductId) || null;
              },
              get isAccessory() {
                  if (!this.selectedProduct) return false;
                  return this.selectedProduct.is_accessory === true || this.selectedProduct.standard_capacity_kg === null;
              },
              get isHuram() {
                  return this.reason === 'Loaned Tank (Huram)';
              }
          }"
          class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2">
                <label for="product_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Select Product / Item <span class="text-[#B5504B]">*</span></label>
                <select name="product_id" id="product_id" x-model="selectedProductId" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="">-- Choose inventory item --</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">
                            {{ $product->name }} (Filled: {{ $product->stock_quantity }} | Empty: {{ $product->empty_quantity }})
                        </option>
                    @endforeach
                </select>
                @error('product_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label for="reason" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Reason for Deduction <span class="text-[#B5504B]">*</span></label>
                <select name="reason" id="reason" x-model="reason" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="Damaged / leaking">Damaged / Leaking Tank</option>
                    <option value="Loaned Tank (Huram)">Loaned Tank (Huram) — Customer Borrowed (₱0)</option>
                    <option value="Inventory count correction">Inventory Count Correction</option>
                    <option value="Other">Other</option>
                </select>
                @error('reason') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Entry Point A: Loaned Tank (Huram) Fields -->
            <template x-if="isHuram">
                <div class="col-span-2 grid grid-cols-1 md:grid-cols-3 gap-4 p-4 bg-amber-50/60 border border-amber-200 rounded-xl">
                    <div class="md:col-span-3 flex items-center justify-between">
                        <span class="text-[12px] font-bold text-amber-800 uppercase tracking-wider">Loaned Tank (Huram) — ₱0.00 Monetary Charge</span>
                        <span class="text-[11px] text-amber-700">Automatically syncs with Loaned Tanks & Customer Profile</span>
                    </div>
                    <div class="md:col-span-1">
                        <label for="customer_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Borrowing Customer <span class="text-[#B5504B]">*</span></label>
                        <select name="customer_id" id="customer_id" required class="w-full pl-3 pr-8 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                            <option value="">-- Select Customer --</option>
                            @foreach($customers ?? [] as $customer)
                                <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                                    {{ $customer->name }} ({{ $customer->customer_type }})
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label for="tank_condition_out" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Tank Condition Borrowed <span class="text-[#B5504B]">*</span></label>
                        <select name="tank_condition_out" id="tank_condition_out" x-model="tankConditionOut" required class="w-full pl-3 pr-8 py-2.5 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                            <option value="filled">Filled Tank (Deducts Filled Stock)</option>
                            <option value="empty_shell">Empty Cylinder Shell (Deducts Empty Stock)</option>
                        </select>
                        @error('tank_condition_out') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="md:col-span-1">
                        <label for="loan_quantity" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Quantity Borrowed <span class="text-[#B5504B]">*</span></label>
                        <input type="number" name="loan_quantity" id="loan_quantity" value="{{ old('loan_quantity', 1) }}" min="1" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                        @error('loan_quantity') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        @error('quantity_removed') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                        @error('empty_quantity_removed') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </template>

            <!-- Standard Stock Out Quantity Fields -->
            <div x-show="!isHuram" :class="isAccessory ? 'col-span-2' : 'col-span-1'">
                <label for="quantity_removed" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">
                    <span x-text="isAccessory ? 'Units Deducted' : 'Filled Tanks Deducted'">Filled Tanks Deducted</span>
                    <span class="text-[#B5504B]">*</span>
                </label>
                <input type="number" name="quantity_removed" id="quantity_removed" :disabled="isHuram" value="{{ old('quantity_removed', 0) }}" min="0" :required="!isHuram" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                @error('quantity_removed') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div x-show="!isAccessory && !isHuram" x-cloak class="col-span-1">
                <label for="empty_quantity_removed" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Empty Shells Deducted <span class="text-[#B5504B]">*</span></label>
                <input type="number" name="empty_quantity_removed" id="empty_quantity_removed" 
                       :disabled="isAccessory || isHuram" 
                       :value="isAccessory ? 0 : '{{ old('empty_quantity_removed', 0) }}'" 
                       min="0" :required="!isAccessory && !isHuram" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                @error('empty_quantity_removed') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <template x-if="isAccessory && !isHuram">
                <div class="col-span-2 p-3 bg-[#F4F6F9] border border-[#E5E9EF] rounded-lg text-[12px] text-[#5B6472] flex items-center gap-2">
                    <span class="font-semibold text-[#B5504B]">ℹ️ Accessory Item:</span> Empty shell deduction is not applicable for non-cylinder products.
                </div>
            </template>

            <div class="col-span-2">
                <label for="remarks" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Remarks / Details</label>
                <textarea name="remarks" id="remarks" rows="3" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">{{ old('remarks') }}</textarea>
                @error('remarks') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('stock-outs.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#B5504B] bg-[#B5504B] text-white hover:bg-[#9a423e]">Confirm Stock Out</button>
        </div>
    </form>
</x-app-layout>