<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Record Cash Payment</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Collect payment for {{ $account->customer->name }}</div>
    </x-slot>

    {{-- Account Summary --}}
    <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-5 mb-5 max-w-4xl">
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Customer</div>
                <div class="text-[14px] font-bold text-[#1C2430]">{{ $account->customer->name }}</div>
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Monthly Payment</div>
                <div class="text-[14px] font-semibold text-[#1C2430]">
                    {{ $account->agreed_monthly_payment ? '₱' . number_format($account->agreed_monthly_payment, 2) : '—' }}
                </div>
            </div>
            <div>
                <div class="text-[11px] uppercase tracking-[0.04em] text-[#5B6472] font-semibold mb-1">Outstanding Balance</div>
                <div class="text-[18px] font-extrabold {{ $remainingBalance > 0 ? 'text-[#B5504B]' : 'text-[#1E8E5A]' }}">
                    ₱{{ number_format($remainingBalance, 2) }}
                </div>
            </div>
        </div>
    </div>

    {{-- Payment Form --}}
    <form method="POST" action="{{ route('payments.store', $account) }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 max-w-4xl">
        @csrf

        <div class="grid grid-cols-2 gap-4 mb-5">
            <div class="col-span-2 md:col-span-1">
                <label for="amount" class="block text-[11.5px] font-semibold text-[#5B6472] mb-1.5">Payment Amount <span class="text-[#B5504B]">*</span></label>
                <div class="relative">
                    <span class="absolute left-3 top-1/2 -translate-y-1/2 text-[#5B6472] text-[14px]">₱</span>
                    <input type="number" step="0.01" min="0.01" name="amount" id="amount" value="{{ old('amount') }}" placeholder="0.00" required class="w-full pl-7 pr-3 py-2.5 border border-[#E5E9EF] rounded-lg text-[14px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                </div>
                @error('amount') <span class="text-[#B5504B] text-[11px] mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <div class="flex items-center gap-3 pt-5 border-t border-[#E5E9EF]">
            <a href="{{ route('credit-accounts.show', $account) }}" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9]">Cancel</a>
            <button type="submit" class="rounded-lg px-[15px] py-[10px] text-[13px] font-semibold border border-[#1E8E5A] bg-[#1E8E5A] text-white hover:bg-[#176B45]">Confirm Payment</button>
        </div>
    </form>
</x-app-layout>
