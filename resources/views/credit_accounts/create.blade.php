<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Approve Credit Account</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Grant a customer permission to purchase on credit (utang)</div>
    </x-slot>

    <form method="POST" action="{{ route('credit-accounts.store') }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        <div class="grid grid-cols-2 gap-4 mb-5">
            {{-- Customer Selection --}}
            <div class="col-span-2">
                <label for="customer_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Select Customer <span class="text-[#B5504B]">*</span></label>
                <select name="customer_id" id="customer_id" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="">— Choose a customer —</option>
                    @foreach($customers as $customer)
                        <option value="{{ $customer->id }}" {{ old('customer_id') == $customer->id ? 'selected' : '' }}>
                            {{ $customer->name }} ({{ $customer->customer_type }})
                        </option>
                    @endforeach
                </select>
                @error('customer_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
                @if($customers->isEmpty())
                    <span class="text-[#5B6472] text-[11px] mt-1 block">All customers already have credit accounts.</span>
                @endif
            </div>

            {{-- Agreed Monthly Payment --}}
            <div class="col-span-2 md:col-span-1">
                <label for="agreed_monthly_payment" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Agreed Monthly Payment</label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#5B6472] text-[14px]">₱</span>
                    <input type="number" step="0.01" min="0" name="agreed_monthly_payment" id="agreed_monthly_payment" value="{{ old('agreed_monthly_payment') }}" placeholder="0.00" class="w-full pl-7 pr-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                </div>
                @error('agreed_monthly_payment') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('credit-accounts.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Approve Account</button>
        </div>
    </form>
</x-app-layout>
