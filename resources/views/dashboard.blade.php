<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between w-full">
            <div>
                <h1 class="font-['Inter'] text-[24px] font-bold text-[#0F1D3A] m-0">Dashboard</h1>
                <div class="text-[13px] text-[#5B6B88] mt-1">Real-time store &amp; inventory overview</div>
            </div>
            
            @if(Auth::user()->isManagerOrOwner())
                <div class="flex items-center gap-3 mt-4 md:mt-0">
                    <a href="{{ route('reports.sales.export') }}" class="bg-white border border-[#E5E7EB] rounded-lg px-4 py-2 text-[13px] font-semibold text-[#0F1D3A] shadow-sm hover:bg-gray-50 transition-colors inline-flex items-center gap-2 no-underline">
                        <svg class="w-4 h-4 text-[#1A8A4F]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <span>Sync to Excel</span>
                    </a>
                </div>
            @endif
        </div>
    </x-slot>

    @if(session('success'))
        <div class="mb-5 bg-[#E2F4EA] border border-[#1A8A4F] text-[#1A8A4F] px-4 py-3 rounded-xl text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <!-- KPI Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-5">
        <div class="bg-white border border-[#E5E7EB] rounded-[14px] p-5 shadow-sm">
            <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Today's Sales</div>
            <div class="flex items-end justify-between mt-1">
                <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">₱{{ number_format($todaySalesAmount, 2) }}</div>
                <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $todaySalesAmount > 0 ? 'bg-[#E2F4EA] text-[#1A8A4F]' : 'bg-gray-100 text-[#5B6B88]' }} flex items-center gap-1">
                    {{ $todayTransactions }} Transactions
                </div>
            </div>
        </div>

        <div class="bg-white border border-[#E5E7EB] rounded-[14px] p-5 shadow-sm">
            <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Low Stock Alerts</div>
            <div class="flex items-end justify-between mt-1">
                <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">{{ $lowStockProducts->count() }}</div>
                <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full {{ $lowStockProducts->count() > 0 ? 'bg-[#FBE5E2] text-[#C0392B]' : 'bg-[#E2F4EA] text-[#1A8A4F]' }} flex items-center gap-1">
                    {{ $lowStockProducts->count() > 0 ? 'Requires attention' : 'Inventory healthy' }}
                </div>
            </div>
        </div>

        <div class="bg-white border border-[#E5E7EB] rounded-[14px] p-5 shadow-sm">
            <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Total Customers</div>
            <div class="flex items-end justify-between mt-1">
                <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">{{ number_format($totalCustomers) }}</div>
                <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#E2F4EA] text-[#1A8A4F] flex items-center gap-1">
                    Active Client Base
                </div>
            </div>
        </div>

        @if(Auth::user()->isManagerOrOwner())
            <div class="bg-white border border-[#E5E7EB] rounded-[14px] p-5 shadow-sm">
                <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Active Credit Accounts</div>
                <div class="flex items-end justify-between mt-1">
                    <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">{{ number_format($activeCreditAccounts) }}</div>
                    <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#FBF0DD] text-[#B4700A] flex items-center gap-1">
                        Utang ledger active
                    </div>
                </div>
            </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
        <a href="{{ route('pos.create') }}" class="{{ Auth::user()->isManagerOrOwner() ? 'md:col-span-2' : 'md:col-span-4' }} relative overflow-hidden rounded-[16px] text-left flex flex-col justify-end p-6 cursor-pointer text-white bg-gradient-to-br from-[#0A1128] to-[#0D2A5C] border border-[#0A1730] shadow-md hover:shadow-lg transition-shadow min-h-[160px] no-underline">
            <div class="w-[42px] h-[42px] rounded-xl flex items-center justify-center bg-white/10 text-[#8FB0E6] mb-auto">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="2"/><circle cx="20" cy="20" r="2"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <div>
                <div class="font-['Inter'] text-[20px] font-bold">+ New Sale</div>
                <div class="text-[13px] text-[#9FB6DE] mt-1 font-medium">Walk-in or delivery — record it in seconds</div>
            </div>
        </a>

        @if(Auth::user()->isManagerOrOwner())
            <a href="{{ route('stock-ins.create') }}" class="relative overflow-hidden rounded-[16px] text-left flex flex-col justify-end p-6 cursor-pointer text-white bg-gradient-to-br from-[#0A1128] to-[#0D2A5C] border border-[#0A1730] shadow-md hover:shadow-lg transition-shadow min-h-[160px] no-underline">
                <div class="w-[42px] h-[42px] rounded-xl flex items-center justify-center bg-white/10 text-[#8FB0E6] mb-auto">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                </div>
                <div>
                    <div class="font-['Inter'] text-[18px] font-bold">Stock In</div>
                    <div class="text-[13px] text-[#9FB6DE] mt-1 font-medium">Restock inventory</div>
                </div>
            </a>

            <a href="{{ route('stock-outs.create') }}" class="relative overflow-hidden rounded-[16px] text-left flex flex-col justify-end p-6 cursor-pointer text-white bg-gradient-to-br from-[#0A1128] to-[#0D2A5C] border border-[#0A1730] shadow-md hover:shadow-lg transition-shadow min-h-[160px] no-underline">
                <div class="w-[42px] h-[42px] rounded-xl flex items-center justify-center bg-white/10 text-[#8FB0E6] mb-auto">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12l7 7 7-7"/></svg>
                </div>
                <div>
                    <div class="font-['Inter'] text-[18px] font-bold">Stock Out</div>
                    <div class="text-[13px] text-[#9FB6DE] mt-1 font-medium">Manual deduction</div>
                </div>
            </a>
        @endif
    </div>

    <!-- Chart Area -->
    <div class="bg-white border border-[#E5E7EB] rounded-[16px] p-6 shadow-sm mb-5">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h3 class="font-['Inter'] text-[18px] font-bold text-[#0F1D3A] m-0">Sales</h3>
                <div class="text-[13px] text-[#5B6B88] mt-1">Last 7 days · Sep 25 – Oct 1</div>
            </div>
            <div class="flex bg-[#EAF0F9] p-1 rounded-xl">
                <button class="px-4 py-1.5 rounded-lg text-[13px] font-semibold bg-[#0D2A5C] text-white shadow-sm">Weekly</button>
                <button class="px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A]">Monthly</button>
                <button class="px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A]">Quarterly</button>
            </div>
        </div>

        <div class="flex gap-10 mb-8 mt-6">
            <div>
                <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Total sales</div>
                <div class="font-['Inter'] text-[20px] font-bold text-[#0F1D3A]">₱330,050</div>
            </div>
            <div>
                <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Daily average</div>
                <div class="font-['Inter'] text-[20px] font-bold text-[#0F1D3A]">₱47,150</div>
            </div>
            <div>
                <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Best day · Sun</div>
                <div class="font-['Inter'] text-[20px] font-bold text-[#0F1D3A]">₱61,200</div>
            </div>
        </div>

        <div class="h-[250px] flex items-end justify-between gap-2 px-2 relative border-b border-[#E5E7EB]">
            <!-- Y-Axis Labels -->
            <div class="absolute -left-2 top-0 h-full w-full pointer-events-none flex flex-col justify-between text-[11px] text-[#5B6B88] font-medium pb-[30px]">
                <div class="border-b border-dashed border-[#E5E7EB] w-full flex items-center"><span class="bg-white pr-2 absolute -left-10">₱80k</span></div>
                <div class="border-b border-dashed border-[#E5E7EB] w-full flex items-center"><span class="bg-white pr-2 absolute -left-10">₱60k</span></div>
                <div class="border-b border-dashed border-[#E5E7EB] w-full flex items-center"><span class="bg-white pr-2 absolute -left-10">₱40k</span></div>
                <div class="border-b border-dashed border-[#E5E7EB] w-full flex items-center"><span class="bg-white pr-2 absolute -left-10">₱20k</span></div>
            </div>

            <!-- Bars -->
            <div class="w-full flex justify-between h-[220px] items-end pl-8 relative z-10">
                <div class="w-[8%] flex flex-col items-center">
                    <span class="text-[11.5px] font-bold text-[#0F1D3A] mb-2">₱44.8k</span>
                    <div class="w-full bg-[#4B7FC0] rounded-t-lg h-[55%] hover:brightness-110 transition-all cursor-pointer"></div>
                    <span class="text-[12.5px] text-[#5B6B88] mt-3">Fri</span>
                </div>
                <div class="w-[8%] flex flex-col items-center">
                    <span class="text-[11.5px] font-bold text-[#0F1D3A] mb-2">₱52.3k</span>
                    <div class="w-full bg-[#4B7FC0] rounded-t-lg h-[65%] hover:brightness-110 transition-all cursor-pointer"></div>
                    <span class="text-[12.5px] text-[#5B6B88] mt-3">Sat</span>
                </div>
                <div class="w-[8%] flex flex-col items-center">
                    <span class="text-[11.5px] font-bold text-[#0F1D3A] mb-2">₱61.2k</span>
                    <div class="w-full bg-[#4B7FC0] rounded-t-lg h-[76%] hover:brightness-110 transition-all cursor-pointer"></div>
                    <span class="text-[12.5px] text-[#5B6B88] mt-3">Sun</span>
                </div>
                <div class="w-[8%] flex flex-col items-center">
                    <span class="text-[11.5px] font-bold text-[#0F1D3A] mb-2">₱38.9k</span>
                    <div class="w-full bg-[#4B7FC0] rounded-t-lg h-[48%] hover:brightness-110 transition-all cursor-pointer"></div>
                    <span class="text-[12.5px] text-[#5B6B88] mt-3">Mon</span>
                </div>
                <div class="w-[8%] flex flex-col items-center">
                    <span class="text-[11.5px] font-bold text-[#0F1D3A] mb-2">₱41.5k</span>
                    <div class="w-full bg-[#4B7FC0] rounded-t-lg h-[52%] hover:brightness-110 transition-all cursor-pointer"></div>
                    <span class="text-[12.5px] text-[#5B6B88] mt-3">Tue</span>
                </div>
                <div class="w-[8%] flex flex-col items-center">
                    <span class="text-[11.5px] font-bold text-[#0F1D3A] mb-2">₱43.1k</span>
                    <div class="w-full bg-[#4B7FC0] rounded-t-lg h-[54%] hover:brightness-110 transition-all cursor-pointer"></div>
                    <span class="text-[12.5px] text-[#5B6B88] mt-3">Wed</span>
                </div>
                <div class="w-[8%] flex flex-col items-center">
                    <span class="text-[11.5px] font-bold text-[#0F1D3A] mb-2">₱48.3k</span>
                    <div class="w-full bg-[#0D2A5C] rounded-t-lg h-[60%] hover:brightness-110 transition-all cursor-pointer"></div>
                    <span class="text-[12.5px] font-bold text-[#0F1D3A] mt-3">Thu</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
