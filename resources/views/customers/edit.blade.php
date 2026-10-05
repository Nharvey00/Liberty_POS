<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Edit Customer Record</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">{{ $customer->name }}</div>
    </x-slot>

    <form method="POST" action="{{ route('customers.update', $customer) }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2 grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_100px] gap-3">
                <div>
                    <label for="first_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">First Name <span class="text-[#B5504B]">*</span></label>
                    <input type="text" name="first_name" id="first_name" value="{{ old('first_name', $customer->first_name) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('first_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="middle_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Middle Name</label>
                    <input type="text" name="middle_name" id="middle_name" value="{{ old('middle_name', $customer->middle_name) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('middle_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="last_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Last Name <span class="text-[#B5504B]">*</span></label>
                    <input type="text" name="last_name" id="last_name" value="{{ old('last_name', $customer->last_name) }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('last_name') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="suffix" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Suffix</label>
                    <input type="text" name="suffix" id="suffix" placeholder="Jr., III" value="{{ old('suffix', $customer->suffix) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    @error('suffix') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="customer_type" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Account Classification <span class="text-[#B5504B]">*</span></label>
                <select name="customer_type" id="customer_type" required class="w-full pl-3 pr-10 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="Tertiary" {{ old('customer_type', $customer->customer_type) == 'Tertiary' ? 'selected' : '' }}>Tertiary</option>
                    <option value="Household" {{ old('customer_type', $customer->customer_type) == 'Household' ? 'selected' : '' }}>Household</option>
                    <option value="MRO" {{ old('customer_type', $customer->customer_type) == 'MRO' ? 'selected' : '' }}>MRO</option>
                    <option value="Service Station" {{ old('customer_type', $customer->customer_type) == 'Service Station' ? 'selected' : '' }}>Service Station</option>
                    <option value="Commercial" {{ old('customer_type', $customer->customer_type) == 'Commercial' ? 'selected' : '' }}>Commercial</option>
                    <option value="Main Store" {{ old('customer_type', $customer->customer_type) == 'Main Store' ? 'selected' : '' }}>Main Store</option>
                    <option value="Coke (Residual)" {{ in_array(old('customer_type', $customer->customer_type), ['Coke (Residual)', 'Company']) ? 'selected' : '' }}>Coke (Residual)</option>
                </select>
                @error('customer_type') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="phone" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Phone Number</label>
                <input type="text" name="phone" id="phone" value="{{ old('phone', $customer->phone) }}" class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('phone') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="col-span-2 md:col-span-1">
                <label for="business_name" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Business Name (For Coke / Corporate Accounts)</label>
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

        <div class="flex items-center justify-end gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('customers.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Update Record</button>
        </div>
    </form>
</x-app-layout>