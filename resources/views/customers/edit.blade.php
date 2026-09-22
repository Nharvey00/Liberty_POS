<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Edit Customer Record</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">{{ $customer->name }}</div>
    </x-slot>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2">
                <label for="name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Client Name / Contact Person <span class="text-[#B5504B]">*</span></label>
                <input type="text" name="name" id="name" value="{{ old('name', $customer->name) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="customer_type" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Account Classification <span class="text-[#B5504B]">*</span></label>
                <select name="customer_type" id="customer_type" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="Normal" {{ old('customer_type', $customer->customer_type) == 'Normal' ? 'selected' : '' }}>Normal Customer</option>
                    <option value="Company" {{ old('customer_type', $customer->customer_type) == 'Company' ? 'selected' : '' }}>Company / Corporate (Residual Calculation)</option>
                </select>
                @error('customer_type') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="phone" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('phone') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="business_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Business Name (For Company Accounts)</label>
                <input type="text" name="business_name" id="business_name" value="{{ old('business_name', $customer->business_name) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('business_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="tin_number" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">TIN Number</label>
                <input type="text" name="tin_number" id="tin_number" value="{{ old('tin_number', $customer->tin_number) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('tin_number') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2">
                <label for="address" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Delivery Address / Location</label>
                <input type="text" name="address" id="address" value="{{ old('address', $customer->address) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('address') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('customers.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Update Record</button>
        </div>
    </form>
</x-app-layout>