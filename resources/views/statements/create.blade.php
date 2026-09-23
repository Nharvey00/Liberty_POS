<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Generate Statement of Account</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Select a credit account and billing period to generate an SOA</div>
    </x-slot>

    <form method="POST" action="{{ route('statements.store') }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        <div class="grid grid-cols-2 gap-4 mb-5">
            {{-- Credit Account Selection --}}
            <div class="col-span-2">
                <label for="credit_account_id" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Credit Account <span class="text-[#B5504B]">*</span></label>
                <select name="credit_account_id" id="credit_account_id" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] bg-white focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                    <option value="">— Select credit account —</option>
                    @foreach($accounts as $account)
                        <option value="{{ $account->id }}" {{ old('credit_account_id') == $account->id ? 'selected' : '' }}>
                            {{ $account->customer->name }} — ₱{{ number_format($account->remaining_balance, 2) }} outstanding
                        </option>
                    @endforeach
                </select>
                @error('credit_account_id') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Billing Period Start --}}
            <div class="col-span-2 md:col-span-1">
                <label for="billing_period_start" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Billing Period Start <span class="text-[#B5504B]">*</span></label>
                <input type="date" name="billing_period_start" id="billing_period_start" value="{{ old('billing_period_start') }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('billing_period_start') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>

            {{-- Billing Period End --}}
            <div class="col-span-2 md:col-span-1">
                <label for="billing_period_end" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Billing Period End <span class="text-[#B5504B]">*</span></label>
                <input type="date" name="billing_period_end" id="billing_period_end" value="{{ old('billing_period_end') }}" required class="w-full px-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                @error('billing_period_end') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('statements.index') }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Generate Statement</button>
        </div>
    </form>
</x-app-layout>
