<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Record Stock Out (Deduction)</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manually deduct inventory due to damages, leaks, or corrections</div>
    </x-slot>

    <form method="POST" action="{{ route('stock-outs.store') }}" 
          x-data="{
              products: {{ Js::from($products) }},
              selectedProductId: '{{ old('product_id', '') }}',
              get selectedProduct() {
                  return this.products.find(p => p.id == this.selectedProductId) || null;
              },
              get isAccessory() {
                  if (!this.selectedProduct) return false;
                  return this.selectedProduct.is_accessory === true || this.selectedProduct.standard_capacity_kg === null;
              }
          }"
          class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2">
                <label for="product_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Select Product / Item <span class="text-[#B5504B]">*</span></label>
                <select name="product_id" id="product_id" x-model="selectedProductId" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
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
                <select name="reason" id="reason" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="Damaged / leaking" {{ old('reason') == 'Damaged / leaking' ? 'selected' : '' }}>Damaged / Leaking Tank</option>
                    <option value="Inventory count correction" {{ old('reason') == 'Inventory count correction' ? 'selected' : '' }}>Inventory Count Correction</option>
                    <option value="Other" {{ old('reason') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('reason') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div :class="isAccessory ? 'col-span-2' : 'col-span-1'">
                <label for="quantity_removed" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">
                    <span x-text="isAccessory ? 'Units Deducted' : 'Filled Tanks Deducted'">Filled Tanks Deducted</span>
                    <span class="text-[#B5504B]">*</span>
                </label>
                <input type="number" name="quantity_removed" id="quantity_removed" value="{{ old('quantity_removed', 0) }}" min="0" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                @error('quantity_removed') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div x-show="!isAccessory" x-cloak class="col-span-1">
                <label for="empty_quantity_removed" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Empty Shells Deducted <span class="text-[#B5504B]">*</span></label>
                <input type="number" name="empty_quantity_removed" id="empty_quantity_removed" 
                       :disabled="isAccessory" 
                       :value="isAccessory ? 0 : '{{ old('empty_quantity_removed', 0) }}'" 
                       min="0" :required="!isAccessory" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                @error('empty_quantity_removed') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <template x-if="isAccessory">
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