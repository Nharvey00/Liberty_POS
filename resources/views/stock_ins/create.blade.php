<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Record Stock In (Delivery)</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Log supplier shipments to safely increment inventory stock</div>
    </x-slot>

    @php
        $initialStockInItems = old('items', [[
            'product_id'         => old('product_id', ''),
            'quantity_received'  => (int) old('quantity_received', 0),
            'empty_received_qty' => (int) old('empty_received_qty', 0),
            'empty_returned_qty' => (int) old('empty_returned_qty', 0),
            'unit_cost'          => old('unit_cost', ''),
        ]]);
    @endphp

    <form method="POST" action="{{ route('stock-ins.store') }}" data-submit-once
          x-data="{
              products: {{ Js::from($products) }},
              items: {{ Js::from($initialStockInItems) }},
              addItem() {
                  this.items.push({
                      product_id: '',
                      quantity_received: 0,
                      empty_received_qty: 0,
                      empty_returned_qty: 0,
                      unit_cost: ''
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
              },
              newFilledQty(item) {
                  const p = this.getProduct(item.product_id);
                  const base = p ? Number(p.stock_quantity || 0) : 0;
                  return base + (Number(item.quantity_received) || 0);
              },
              newEmptyQty(item) {
                  if (this.isAccessory(item.product_id)) return 0;
                  const p = this.getProduct(item.product_id);
                  const base = p ? Number(p.empty_quantity || 0) : 0;
                  return Math.max(0, base + (Number(item.empty_received_qty) || 0) - (Number(item.empty_returned_qty) || 0));
              },
              totalPhysicalTanks(item) {
                  return this.newFilledQty(item) + this.newEmptyQty(item);
              }
          }"
          class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        @if ($errors->any())
            <div class="mb-5 bg-[#F7E9E8] border border-[#B5504B] text-[#B5504B] px-4 py-3 rounded-lg text-[13px]">
                <div class="font-bold mb-1">Please check the form for errors:</div>
                <ul class="list-disc list-inside space-y-0.5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Items to Stock In -->
        <div class="flex items-center justify-between mb-3">
            <div>
                <h3 class="font-sans text-[14.5px] font-bold text-gray-900 m-0">Items to Stock In</h3>
                <p class="text-[12px] text-[#5B6472] mt-0.5">Add one or multiple products received in this delivery or inventory batch</p>
            </div>
            <button type="button" @click="addItem()" class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-2 text-[12.5px] font-semibold border border-[#0B3B70] bg-[#EBF4FF] text-[#0B3B70] hover:bg-[#dbeafe] transition-colors cursor-pointer">
                <span>+ Add Another Item</span>
            </button>
        </div>

        <div class="space-y-4 mb-6">
            <template x-for="(item, index) in items" :key="index">
                <div class="bg-[#F8FAFC] border border-[#E5E9EF] rounded-xl p-4">
                    <div class="flex items-center justify-between mb-3 pb-2 border-b border-[#E5E9EF]">
                        <span class="text-[12.5px] font-bold text-[#0B3B70]" x-text="'Item #' + (index + 1)"></span>
                        <button type="button" x-show="items.length > 1" @click="removeItem(index)" class="text-[12px] font-semibold text-[#B5504B] hover:underline cursor-pointer">
                            Remove Item
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <!-- Select Product -->
                        <div class="col-span-2">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Select Product / Item <span class="text-[#B5504B]">*</span></label>
                            <select :name="'items[' + index + '][product_id]'" x-model="item.product_id" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                                <option value="">-- Choose inventory item --</option>
                                @foreach($products as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }} (Filled: {{ $product->stock_quantity }} | Empty: {{ $product->empty_quantity }} | Total Tanks: {{ $product->stock_quantity + $product->empty_quantity }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Filled Tanks Received -->
                        <div :class="isAccessory(item.product_id) ? 'col-span-2 md:col-span-1' : 'col-span-2 md:col-span-1'">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">
                                <span x-text="isAccessory(item.product_id) ? 'Units Received' : 'Filled Tanks Received'">Filled Tanks Received</span>
                            </label>
                            <input type="number" :name="'items[' + index + '][quantity_received]'" x-model.number="item.quantity_received" min="0" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                            <p x-show="getProduct(item.product_id)" class="text-[11px] text-[#5B6472] mt-1">
                                Current filled on hand: <span x-text="getProduct(item.product_id) ? getProduct(item.product_id).stock_quantity : 0"></span>
                            </p>
                        </div>

                        <!-- Empty Tanks / Shells Received (Side-by-side with Filled Tanks Received) -->
                        <div x-show="!isAccessory(item.product_id)" x-cloak class="col-span-2 md:col-span-1">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Empty Tanks / Shells Received</label>
                            <input type="number" :name="'items[' + index + '][empty_received_qty]'"
                                   x-model.number="item.empty_received_qty"
                                   :disabled="isAccessory(item.product_id)"
                                   min="0" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                            <p class="text-[11px] text-[#5B6472] mt-1">
                                Current empty on hand: <span x-text="getProduct(item.product_id) ? getProduct(item.product_id).empty_quantity : 0"></span>
                            </p>
                        </div>

                        <!-- Empty Shells Returned to Supplier (Optional) -->
                        <div x-show="!isAccessory(item.product_id)" x-cloak class="col-span-2 md:col-span-1">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Empty Shells Returned to Supplier (Optional)</label>
                            <input type="number" :name="'items[' + index + '][empty_returned_qty]'"
                                   x-model.number="item.empty_returned_qty"
                                   :disabled="isAccessory(item.product_id)"
                                   :max="getProduct(item.product_id) ? (Number(getProduct(item.product_id).empty_quantity || 0) + Number(item.empty_received_qty || 0)) : null"
                                   min="0" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0">
                            <p class="text-[11px] text-[#5B6472] mt-1">
                                Only fill if returning empties to supplier truck.
                            </p>
                        </div>

                        <!-- Unit Cost per Item -->
                        <div class="col-span-2 md:col-span-1">
                            <label class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Unit Cost (₱)</label>
                            <input type="number" step="0.01" min="0" :name="'items[' + index + '][unit_cost]'" x-model="item.unit_cost" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="0.00">
                        </div>

                        <!-- Live Total Physical Metal Tanks Preview -->
                        <div x-show="getProduct(item.product_id) && !isAccessory(item.product_id)" x-cloak class="col-span-2 bg-[#EBF4FF] border border-[#C6DDF7] rounded-lg px-4 py-2 flex flex-wrap items-center justify-between gap-2 text-[12px] text-[#0B3B70]">
                            <div>
                                <span class="font-medium">Resulting Stock:</span>
                                <span class="ml-1.5">Filled: <strong x-text="newFilledQty(item)"></strong></span>
                                <span class="mx-1">&bull;</span>
                                <span>Empty: <strong x-text="newEmptyQty(item)"></strong></span>
                            </div>
                            <div>
                                Total Physical Metal Tanks: <strong class="text-[13.5px]" x-text="totalPhysicalTanks(item)"></strong>
                            </div>
                        </div>

                        <template x-if="isAccessory(item.product_id)">
                            <div class="col-span-2 p-2.5 bg-white border border-[#E5E9EF] rounded-lg text-[12px] text-[#5B6472] flex items-center gap-2">
                                <span class="font-semibold text-[#0B3B70]">ℹ️ Accessory Item:</span> Empty shell tracking is not applicable for non-cylinder products.
                            </div>
                        </template>
                    </div>
                </div>
            </template>
        </div>

        <!-- Shared Delivery / Batch Details -->
        <div class="grid grid-cols-2 gap-4 mb-5 pt-4 border-t border-[#E5E9EF]">
            <div class="col-span-2 md:col-span-1">
                <label for="reference_no" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Delivery Reference / PO No.</label>
                <input type="text" name="reference_no" id="reference_no" value="{{ old('reference_no') }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="Auto-generated if left blank">
                @error('reference_no') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="supplier_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Supplier Name or Source</label>
                <input type="text" name="supplier_name" id="supplier_name" value="{{ old('supplier_name') }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="e.g. Petron / Isla LPG, or Store Paper Inventory">
                @error('supplier_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label for="remarks" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Remarks / Delivery Notes</label>
                <textarea name="remarks" id="remarks" rows="3" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">{{ old('remarks') }}</textarea>
                @error('remarks') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('stock-ins.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Confirm Stock In</button>
        </div>
    </form>
</x-app-layout>