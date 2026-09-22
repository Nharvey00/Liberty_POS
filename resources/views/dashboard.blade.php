<x-app-layout>
    <x-slot name="header">
        <h1 class="font-['Manrope'] text-[19px] font-extrabold m-0">Dashboard</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Real-time view across all branches</div>
    </x-slot>

    <!-- KPI Row -->
    <div class="grid grid-cols-4 gap-4 mb-5">
        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-[18px]">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-2">Today's Sales</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">₱{{ number_format($todaySalesAmount, 2) }}</div>
            <span class="text-[11.5px] font-semibold mt-1.5 inline-block px-2 py-0.5 rounded-full {{ $todaySalesAmount > 0 ? 'text-[#1E8E5A] bg-[#E5F5EC]' : 'text-[#5B6472] bg-[#F4F6F9]' }}">
                {{ $todayTransactions }} Transactions
            </span>
        </div>

        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-[18px]">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-2">Total Customers</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">{{ number_format($totalCustomers) }}</div>
            <span class="text-[11.5px] font-semibold mt-1.5 inline-block px-2 py-0.5 rounded-full text-[#1E8E5A] bg-[#E5F5EC]">
                Active Client Base
            </span>
        </div>

        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-[18px]">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-2">Low Stock Alerts</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">{{ $lowStockProducts->count() }}</div>
            <span class="text-[11.5px] font-semibold mt-1.5 inline-block px-2 py-0.5 rounded-full {{ $lowStockProducts->count() > 0 ? 'text-[#B5504B] bg-[#F7E9E8]' : 'text-[#1E8E5A] bg-[#E5F5EC]' }}">
                {{ $lowStockProducts->count() > 0 ? 'Requires attention' : 'Inventory healthy' }}
            </span>
        </div>

        <div class="bg-white border border-[#E5E9EF] rounded-[16px] p-[18px]">
            <div class="text-[12px] text-[#5B6472] font-semibold mb-2">Active Credit Accounts</div>
            <div class="font-['Manrope'] text-[24px] font-extrabold">{{ number_format($activeCreditAccounts) }}</div>
            <span class="text-[11.5px] font-semibold mt-1.5 inline-block px-2 py-0.5 rounded-full text-[#B4700A] bg-[#FBF0DD]">
                Utang ledger active
            </span>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-[2fr_1fr_1fr] gap-4 mb-5">
        <a href="{{ route('pos.create') }}" class="relative overflow-hidden rounded-[16px] border-none text-left flex flex-col justify-center gap-2.5 cursor-pointer text-white bg-gradient-to-br from-[#0B3B70] to-[#4A7096] px-7 py-7 hover:shadow-lg transition-shadow">
            <div class="w-[52px] h-[52px] rounded-xl flex items-center justify-center shrink-0 bg-white/20 relative z-10">
                <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.6l2.6 12.6a2 2 0 0 0 2 1.6h8a2 2 0 0 0 2-1.6L21.5 7H6"/></svg>
            </div>
            <div>
                <div class="font-['Manrope'] text-[19px] font-extrabold relative z-10">+ New POS Sale</div>
                <div class="text-[12.5px] opacity-85 relative z-10">Walk-in or delivery checkout</div>
            </div>
        </a>

        <a href="{{ route('stock-ins.create') }}" class="relative overflow-hidden rounded-[16px] border-none text-left flex flex-col justify-center gap-2.5 cursor-pointer text-white bg-gradient-to-br from-[#0B3B70] to-[#4A7096] px-5 py-5 hover:shadow-lg transition-shadow">
            <div class="w-[46px] h-[46px] rounded-xl flex items-center justify-center shrink-0 bg-white/20 relative z-10">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
            </div>
            <div>
                <div class="font-['Manrope'] text-[15px] font-extrabold relative z-10">Stock In</div>
                <div class="text-[12.5px] opacity-85 relative z-10">Log supplier delivery</div>
            </div>
        </a>

        <a href="{{ route('stock-outs.create') }}" class="relative overflow-hidden rounded-[16px] border-none text-left flex flex-col justify-center gap-2.5 cursor-pointer text-white bg-gradient-to-br from-[#0B3B70] to-[#4A7096] px-5 py-5 hover:shadow-lg transition-shadow">
            <div class="w-[46px] h-[46px] rounded-xl flex items-center justify-center shrink-0 bg-white/20 relative z-10">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
            </div>
            <div>
                <div class="font-['Manrope'] text-[15px] font-extrabold relative z-10">Stock Out</div>
                <div class="text-[12.5px] opacity-85 relative z-10">Manual deduction</div>
            </div>
        </a>
    </div>

</x-app-layout>