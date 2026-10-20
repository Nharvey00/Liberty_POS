<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Add New Product</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Register a new item to the inventory catalog</div>
    </x-slot>

    <form method="POST" action="{{ route('products.store') }}" data-submit-once class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        <!-- UI Helper: Product Type Toggle -->
        <div class="mb-6 pb-5 border-b border-[#E5E9EF]">
            <label class="block text-[12px] font-bold text-[#1C2430] mb-3">Product Category</label>
            <div class="flex gap-4">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="ui_product_type" value="lpg" class="peer hidden" @checked(old('ui_product_type', 'lpg') === 'lpg') onchange="toggleFields()">
                    <div class="border border-[#E5E9EF] rounded-lg p-4 text-center peer-checked:border-[#0B3B70] peer-checked:bg-[#E7EEF7] peer-checked:text-[#0B3B70] transition-colors">
                        <div class="font-bold text-[13.5px]">LPG Cylinder Tank</div>
                        <div class="text-[11.5px] text-[#5B6472] mt-1 peer-checked:text-[#0B3B70]">Tracks filled stock and empty shell inventory.</div>
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="ui_product_type" value="accessory" class="peer hidden" @checked(old('ui_product_type') === 'accessory') onchange="toggleFields()">
                    <div class="border border-[#E5E9EF] rounded-lg p-4 text-center peer-checked:border-[#0B3B70] peer-checked:bg-[#E7EEF7] peer-checked:text-[#0B3B70] transition-colors">
                        <div class="font-bold text-[13.5px]">Accessory or Part</div>
                        <div class="text-[11.5px] text-[#5B6472] mt-1 peer-checked:text-[#0B3B70]">For items like regulators, hoses, and clamps.</div>
                    </div>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5">
            <!-- Product Name -->
            <div class="col-span-2 md:col-span-1">
                <label for="name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Product Name <span class="text-[#B5504B]">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name') }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="e.g. 11kg LPG Cylinder">
                @error('name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Standard Price -->
            <div class="col-span-2 md:col-span-1">
                <label for="price" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Standard Selling Price (₱) <span class="text-[#B5504B]">*</span></label>
                <input type="number" step="0.01" name="price" id="price" value="{{ old('price') }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <div class="text-[11px] text-[#5B6472] mt-1">The regular price of the item or gas refill.</div>
                @error('price') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Starting Filled Tank Quantity -->
            <div id="filled-stock-wrapper" class="col-span-2 md:col-span-1">
                <label for="stock_quantity" id="stock_quantity_label" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Starting Filled Tank Quantity</label>
                <input type="number" name="stock_quantity" id="stock_quantity" value="{{ old('stock_quantity', 0) }}" min="0" oninput="updateTotalTanks()" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <div id="stock_quantity_hint" class="text-[11px] text-[#5B6472] mt-1">Number of gas-filled tanks currently on hand.</div>
                @error('stock_quantity') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Starting Empty Shells (Side-by-side with Filled Tank Quantity) -->
            <div id="empty-stock-wrapper" class="col-span-2 md:col-span-1">
                <label for="empty_quantity" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Starting Empty Shells</label>
                <input type="number" name="empty_quantity" id="empty_quantity" value="{{ old('empty_quantity', 0) }}" min="0" oninput="updateTotalTanks()" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <div class="text-[11px] text-[#5B6472] mt-1">Number of empty metal shells currently on hand.</div>
                @error('empty_quantity') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Live Total Metal Tanks Tally -->
            <div id="total-tanks-banner" class="col-span-2 bg-[#EBF4FF] border border-[#C6DDF7] rounded-lg px-4 py-2.5 flex items-center justify-between text-[12.5px] text-[#0B3B70]">
                <span class="font-medium">Total Physical Metal Tanks in Store (Filled + Empty):</span>
                <span id="total-tanks-count" class="font-bold text-[14px]">0</span>
            </div>
        </div>

        <!-- LPG Specific Fields Container -->
        <div id="lpg-specific-fields" class="bg-[#F4F6F9] border border-[#E5E9EF] p-5 rounded-xl mb-5">
            <h4 class="font-sans text-[12.5px] font-bold text-gray-900 mb-4">Cylinder Specifications</h4>
            <div class="grid grid-cols-2 gap-4">
                <!-- New Cylinder Price -->
                <div>
                    <label for="new_cylinder_price" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">New Tank Price (₱)</label>
                    <input type="number" step="0.01" name="new_cylinder_price" id="new_cylinder_price" value="{{ old('new_cylinder_price') }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <div class="text-[11px] text-[#5B6472] mt-1">Price applied when a customer does not have an empty shell to swap.</div>
                    @error('new_cylinder_price') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Standard Capacity -->
                <div>
                    <label for="standard_capacity_kg" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Standard Capacity (kg)</label>
                    <input type="number" step="0.01" name="standard_capacity_kg" id="standard_capacity_kg" value="{{ old('standard_capacity_kg') }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]" placeholder="e.g. 11, 22, 50">
                    <div class="text-[11px] text-[#5B6472] mt-1">The designated net weight of the gas.</div>
                    @error('standard_capacity_kg') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('products.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Save Product</button>
        </div>
    </form>

    <script>
        function updateTotalTanks() {
            const filled = parseInt(document.getElementById('stock_quantity').value || '0', 10) || 0;
            const empty = parseInt(document.getElementById('empty_quantity').value || '0', 10) || 0;
            const totalEl = document.getElementById('total-tanks-count');
            if (totalEl) {
                totalEl.textContent = (filled + empty).toLocaleString();
            }
        }

        function toggleFields() {
            const isLpg = document.querySelector('input[name="ui_product_type"][value="lpg"]').checked;
            const lpgContainer = document.getElementById('lpg-specific-fields');
            const newCylinderInput = document.getElementById('new_cylinder_price');
            const capacityInput = document.getElementById('standard_capacity_kg');
            const emptyQuantityInput = document.getElementById('empty_quantity');
            const emptyStockWrapper = document.getElementById('empty-stock-wrapper');
            const filledStockWrapper = document.getElementById('filled-stock-wrapper');
            const stockQuantityLabel = document.getElementById('stock_quantity_label');
            const stockQuantityHint = document.getElementById('stock_quantity_hint');
            const totalTanksBanner = document.getElementById('total-tanks-banner');

            if (isLpg) {
                lpgContainer.style.display = 'block';
                emptyStockWrapper.style.display = 'block';
                totalTanksBanner.style.display = 'flex';
                filledStockWrapper.className = 'col-span-2 md:col-span-1';
                stockQuantityLabel.textContent = 'Starting Filled Tank Quantity';
                stockQuantityHint.textContent = 'Number of gas-filled tanks currently on hand.';
            } else {
                lpgContainer.style.display = 'none';
                emptyStockWrapper.style.display = 'none';
                totalTanksBanner.style.display = 'none';
                filledStockWrapper.className = 'col-span-2';
                stockQuantityLabel.textContent = 'Starting Stock Quantity';
                stockQuantityHint.textContent = 'Number of accessory units currently on hand.';
                newCylinderInput.value = '';
                capacityInput.value = '';
                emptyQuantityInput.value = 0;
            }
            updateTotalTanks();
        }
        document.addEventListener('DOMContentLoaded', toggleFields);
    </script>
</x-app-layout>