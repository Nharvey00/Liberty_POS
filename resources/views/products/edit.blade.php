<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Edit Product</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">{{ $product->name }}</div>
    </x-slot>

    <form method="POST" action="{{ route('products.update', $product) }}" data-submit-once class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf
        @method('PUT')

        <!-- UI Helper: Product Type Toggle -->
        <div class="mb-6 pb-5 border-b border-[#E5E9EF]">
            <label class="block text-[12px] font-bold text-[#1C2430] mb-3">Product Category</label>
            <div class="flex gap-4">
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="ui_product_type" value="lpg" class="peer hidden" @checked(old('ui_product_type', $product->isAccessory() ? 'accessory' : 'lpg') === 'lpg') onchange="toggleEditFields()">
                    <div class="border border-[#E5E9EF] rounded-lg p-4 text-center peer-checked:border-[#0B3B70] peer-checked:bg-[#E7EEF7] peer-checked:text-[#0B3B70] transition-colors">
                        <div class="font-bold text-[13.5px]">LPG Cylinder Tank</div>
                        <div class="text-[11.5px] text-[#5B6472] mt-1 peer-checked:text-[#0B3B70]">Tracks filled stock and empty shell inventory.</div>
                    </div>
                </label>
                <label class="flex-1 cursor-pointer">
                    <input type="radio" name="ui_product_type" value="accessory" class="peer hidden" @checked(old('ui_product_type', $product->isAccessory() ? 'accessory' : 'lpg') === 'accessory') onchange="toggleEditFields()">
                    <div class="border border-[#E5E9EF] rounded-lg p-4 text-center peer-checked:border-[#0B3B70] peer-checked:bg-[#E7EEF7] peer-checked:text-[#0B3B70] transition-colors">
                        <div class="font-bold text-[13.5px]">Accessory or Part</div>
                        <div class="text-[11.5px] text-[#5B6472] mt-1 peer-checked:text-[#0B3B70]">For items like regulators, hoses, and clamps.</div>
                    </div>
                </label>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4 mb-5">
            <!-- Product Name -->
            <div class="col-span-2">
                <label for="name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Product Name <span class="text-[#B5504B]">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $product->name) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <!-- Standard Price -->
            <div class="col-span-2 md:col-span-1">
                <label for="price" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Standard Selling Price (₱) <span class="text-[#B5504B]">*</span></label>
                <input type="number" step="0.01" name="price" id="price" value="{{ old('price', $product->price) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('price') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <!-- LPG Specific Fields Container -->
        <div id="lpg-edit-fields" class="bg-[#F4F6F9] border border-[#E5E9EF] p-5 rounded-xl mb-5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="font-sans text-[12.5px] font-bold text-gray-900">Cylinder Specifications</h4>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <!-- New Cylinder Price -->
                <div>
                    <label for="new_cylinder_price" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">New Tank Price (₱)</label>
                    <input type="number" step="0.01" name="new_cylinder_price" id="new_cylinder_price" value="{{ old('new_cylinder_price', $product->new_cylinder_price) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <div class="text-[11px] text-[#5B6472] mt-1">Price applied when a customer does not have an empty shell to swap.</div>
                    @error('new_cylinder_price') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>

                <!-- Standard Capacity -->
                <div>
                    <label for="standard_capacity_kg" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Standard Capacity (kg) <span class="text-[#B5504B]">*</span></label>
                    <input type="number" step="0.01" name="standard_capacity_kg" id="standard_capacity_kg" value="{{ old('standard_capacity_kg', $product->standard_capacity_kg) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <div class="text-[11px] text-[#5B6472] mt-1">The designated net weight of the gas.</div>
                    @error('standard_capacity_kg') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-[#FBF0DD] border border-[#B4700A] p-4 rounded-lg mb-6">
            <h4 class="font-sans text-[12.5px] font-bold text-[#B4700A] mb-1">Inventory Control Active</h4>
            <p class="text-[12px] text-[#B4700A] opacity-90">Current Stock: <strong>{{ $product->stock_quantity }}</strong> @if(!$product->isAccessory()) | Empty Shells: <strong>{{ $product->empty_quantity }}</strong> @endif</p>
            <p class="text-[11.5px] text-[#B4700A] opacity-80 mt-1">To ensure audit integrity, stock quantities cannot be manually edited here. Please use the <a href="{{ route('stock-ins.create') }}" class="underline font-bold">Stock In</a> or <a href="{{ route('stock-outs.create') }}" class="underline font-bold">Stock Out</a> modules to adjust inventory.</p>
        </div>

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('products.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Update Product Info</button>
        </div>
    </form>

    <script>
        function toggleEditFields() {
            const isLpg = document.querySelector('input[name="ui_product_type"][value="lpg"]').checked;
            const lpgContainer = document.getElementById('lpg-edit-fields');
            const newCylinderInput = document.getElementById('new_cylinder_price');
            const capacityInput = document.getElementById('standard_capacity_kg');

            if (isLpg) {
                lpgContainer.style.display = 'block';
                capacityInput.required = true;
            } else {
                lpgContainer.style.display = 'none';
                newCylinderInput.value = '';
                capacityInput.value = '';
                capacityInput.required = false;
            }
        }
        document.addEventListener('DOMContentLoaded', toggleEditFields);
    </script>
</x-app-layout>