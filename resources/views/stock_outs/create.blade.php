<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Record Stock Out (Deduction)</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Manually deduct inventory due to damages, leaks, or corrections</div>
    </x-slot>

    @php
        $initialItems = old('items', [
            [
                'product_id' => old('product_id', ''),
                'quantity_removed' => old('quantity_removed', 0),
                'empty_quantity_removed' => old('empty_quantity_removed', 0),
            ]
        ]);
    @endphp

    <form method="POST" action="{{ route('stock-outs.store') }}" data-submit-once
          x-data="{
              products: {{ Js::from($products) }},
              items: {{ Js::from(array_values($initialItems)) }},
              addItem() {
                  this.items.push({
                      product_id: '',
                      quantity_removed: 0,
                      empty_quantity_removed: 0
                  });
              },
              removeItem(index) {
                  if (this.items.length > 1) {
                      this.items.splice(index, 1);
                  }
              },
              getProduct(productId) {
                  return this.products.find(p => p.id == productId) || null;
              },
              isAccessory(productId) {
                  const p = this.getProduct(productId);
                  if (!p) return false;
                  return p.is_accessory === true || p.standard_capacity_kg === null;
              }
          }"
          class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        @if ($errors->any())
            <div class="mb-5 p-3.5 bg-[#FCEAE8] border border-[#B5504B]/40 rounded-lg text-[12.5px] text-[#B5504B]">
                <div class="font-bold mb-1">Please correct the following errors:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Items Section Header -->
        <div class="flex items-center justify-between mb-3">
            <div>
                <h2 class="text-[14px] font-bold text-[#1C2430] m-0">Items to Deduct</h2>
                <p class="text-[11.5px] text-[#5B6472] m-0">Add one or more products to deduct in this batch</p>
            </div>
            <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-[12.5px] font-semibold border border-[#B5504B] bg-[#FCEAE8]/50 text-[#B5504B] hover:bg-[#B5504B] hover:text-white transition-colors">
                <span>+ Add Another Item</span>
            </button>
        </div>

        <!-- Dynamic Item Cards -->
        <div class="space-y-4 mb-6">
            <template x-for="(item, index) in items" :key="index">
                <div class="p-4 bg-[#F8FAFC] border border-[#E5E9EF] rounded-xl relative">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-[#E5E9EF]">
                        <span class="text-[12px] font-bold uppercase tracking-wider text-[#B5504B]" x-text="'Item #' + (index + 1)"></span>
                        <button type="button" x-show="items.length > 1" @click="removeItem(index)" class="text-[11.5px] font-semibold text-[#B5504B] hover:underline">
                            Remove Item
                        </button>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div class="md:col-span-2">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Select Product / Item <span class="text-[#B5504B]">*</span></label>
                            <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" required class="w-full pl-3 pr-10 py-2 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                                <option value="">-- Choose inventory item --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} (Filled: {{ $product->stock_quantity }} | Empty: {{ $product->empty_quantity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div :class="isAccessory(item.product_id) ? 'md:col-span-2' : 'md:col-span-1'">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">
                                <span x-text="isAccessory(item.product_id) ? 'Units Deducted' : 'Filled Tanks Deducted'">Filled Tanks Deducted</span>
                                <span class="text-[#B5504B]">*</span>
                            </label>
                            <input type="number" :name="'items[' + index + '][quantity_removed]'" x-model.number="item.quantity_removed" min="0" :max="getProduct(item.product_id) ? getProduct(item.product_id).stock_quantity : null" required class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                            <p x-show="getProduct(item.product_id)" class="text-[11px] text-[#5B6472] mt-1">
                                Available Filled: <strong x-text="getProduct(item.product_id) ? getProduct(item.product_id).stock_quantity : 0"></strong>
                            </p>
                        </div>

                        <div x-show="!isAccessory(item.product_id)" x-cloak class="md:col-span-1">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Empty Shells Deducted <span class="text-[#B5504B]">*</span></label>
                            <input type="number" :name="'items[' + index + '][empty_quantity_removed]'"
                                   x-model.number="item.empty_quantity_removed"
                                   :disabled="isAccessory(item.product_id)"
                                   :max="getProduct(item.product_id) ? getProduct(item.product_id).empty_quantity : null"
                                   min="0" :required="!isAccessory(item.product_id)" class="w-full px-3 py-2 border border-[#E5E9EF] rounded-lg text-[13.5px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                            <p x-show="getProduct(item.product_id) && !isAccessory(item.product_id)" class="text-[11px] text-[#5B6472] mt-1">
                                Available Empty: <strong x-text="getProduct(item.product_id) ? getProduct(item.product_id).empty_quantity : 0"></strong>
                            </p>
                        </div>

                        <!-- Per-Item Resulting Stock Preview -->
                        <template x-if="getProduct(item.product_id) && !isAccessory(item.product_id)">
                            <div class="md:col-span-2 p-2.5 bg-white border border-[#E5E9EF] rounded-lg text-[12px] text-[#1C2430] flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <span class="font-semibold text-[#B5504B]">Remaining Stock:</span>
                                    Filled: <strong x-text="getProduct(item.product_id).stock_quantity"></strong> &rarr; <strong class="text-[#B5504B]" x-text="Math.max(0, getProduct(item.product_id).stock_quantity - (Number(item.quantity_removed) || 0))"></strong>
                                    <span class="mx-1.5 text-[#E5E9EF]">|</span>
                                    Empty: <strong x-text="getProduct(item.product_id).empty_quantity"></strong> &rarr; <strong class="text-[#0B3B70]" x-text="Math.max(0, getProduct(item.product_id).empty_quantity - (Number(item.empty_quantity_removed) || 0))"></strong>
                                </div>
                                <div class="text-[11.5px] font-semibold bg-[#F4F6F9] px-2.5 py-1 rounded-md border border-[#E5E9EF]">
                                    Total Physical Metal Tanks: <strong class="text-[#0B3B70]" x-text="(getProduct(item.product_id).stock_quantity + getProduct(item.product_id).empty_quantity) + ' → ' + Math.max(0, (getProduct(item.product_id).stock_quantity - (Number(item.quantity_removed) || 0)) + (getProduct(item.product_id).empty_quantity - (Number(item.empty_quantity_removed) || 0)))"></strong>
                                </div>
                            </div>
                        </template>

                        <template x-if="isAccessory(item.product_id)">
                            <div class="md:col-span-2 p-2.5 bg-white border border-[#E5E9EF] rounded-lg text-[12px] text-[#5B6472] flex items-center gap-2">
                                <span class="font-semibold text-[#B5504B]">ℹ️ Accessory Item:</span> Empty shell deduction is not applicable for non-cylinder products.
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Shared Deduction Details -->
        <div class="pt-4 border-t border-[#E5E9EF] grid grid-cols-1 md:grid-cols-2 gap-4 mb-5">
            <div class="md:col-span-2">
                <h2 class="text-[14px] font-bold text-[#1C2430] m-0">Batch Deduction Reason</h2>
                <p class="text-[11.5px] text-[#5B6472] m-0">Applies to all items in this Stock Out entry</p>
            </div>

            <div class="md:col-span-2">
                <label for="reason" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Reason for Deduction <span class="text-[#B5504B]">*</span></label>
                <select name="reason" id="reason" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="Damaged / leaking" {{ old('reason') == 'Damaged / leaking' ? 'selected' : '' }}>Damaged / Leaking Tank</option>
                    <option value="Inventory count correction" {{ old('reason') == 'Inventory count correction' ? 'selected' : '' }}>Inventory Count Correction</option>
                    <option value="Other" {{ old('reason') == 'Other' ? 'selected' : '' }}>Other</option>
                </select>
                @error('reason') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="md:col-span-2">
                <label for="remarks" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Remarks / Details</label>
                <textarea name="remarks" id="remarks" rows="2" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">{{ old('remarks') }}</textarea>
                @error('remarks') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-between pt-5 border-t border-[#E5E9EF]">
            <button type="button" @click="addItem()" class="rounded-lg px-3.5 py-2 text-[12.5px] font-semibold border border-[#E5E9EF] bg-[#F8FAFC] text-[#B5504B] hover:bg-[#FCEAE8]/50">
                + Add Another Item
            </button>
            <div class="flex items-center gap-3">
                <a href="{{ route('stock-outs.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
                <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#B5504B] bg-[#B5504B] text-white hover:bg-[#9a423e]">Confirm Stock Out</button>
            </div>
        </div>
    </form>
</x-app-layout>