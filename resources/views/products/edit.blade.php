<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Edit Product</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">{{ $product->name }}</div>
    </x-slot>

    <form method="POST" action="{{ route('products.update', $product) }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf
        @method('PUT')

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
        <div class="bg-[#F4F6F9] border border-[#E5E9EF] p-5 rounded-xl mb-5">
            <div class="flex items-center justify-between mb-4">
                <h4 class="text-[12.5px] font-bold text-[#1C2430]">Cylinder Specifications</h4>
                <span class="text-[11px] text-[#5B6472]">Leave blank if this is an accessory.</span>
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
                    <label for="standard_capacity_kg" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Standard Capacity (kg)</label>
                    <input type="number" step="0.01" name="standard_capacity_kg" id="standard_capacity_kg" value="{{ old('standard_capacity_kg', $product->standard_capacity_kg) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <div class="text-[11px] text-[#5B6472] mt-1">The designated net weight of the gas.</div>
                    @error('standard_capacity_kg') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>
        </div>

        <div class="bg-[#FBF0DD] border border-[#B4700A] p-4 rounded-lg mb-6">
            <h4 class="text-[12.5px] font-bold text-[#B4700A] mb-1">Inventory Control Active</h4>
            <p class="text-[12px] text-[#B4700A] opacity-90">Current Stock: <strong>{{ $product->stock_quantity }}</strong> | Empty Shells: <strong>{{ $product->empty_quantity }}</strong></p>
            <p class="text-[11.5px] text-[#B4700A] opacity-80 mt-1">To ensure audit integrity, stock quantities cannot be manually edited here. Please use the <a href="{{ route('stock-ins.create') }}" class="underline font-bold">Stock In</a> or <a href="{{ route('stock-outs.create') }}" class="underline font-bold">Stock Out</a> modules to adjust inventory.</p>
        </div>

        <div class="flex items-center gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('products.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Update Product Info</button>
        </div>
    </form>
</x-app-layout>