<nav :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
     class="w-[260px] shrink-0 bg-gradient-to-b from-[#050A15] to-[#0D1D3A] text-white flex flex-col px-4 py-6 fixed inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:sticky md:top-0 h-screen select-none border-r border-[#0D1D3A]">
    
    <!-- Brand Header -->
    <div class="flex items-center gap-3.5 px-3 pb-8">
        <div class="w-[42px] h-[42px] rounded-[10px] bg-[#245CA6] flex items-center justify-center font-sans font-extrabold text-[15px] text-white shrink-0 shadow-sm">
            LG
        </div>
        <div>
            <div class="font-sans font-bold text-[16px] leading-tight text-white">Liberty LPG</div>
            <div class="text-[12px] text-[#7E96B8] mt-[2px] font-medium tracking-wide">Sales &amp; Inventory</div>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden text-[#7E96B8] hover:text-white p-1 ml-auto rounded-lg hover:bg-[#152441]" aria-label="Close Sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Navigation Scrollable Body -->
    <div class="flex-1 overflow-y-auto space-y-5 pr-1 custom-scrollbar">
        
        <!-- SECTION: Overview -->
        <div>
            <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-2 font-bold">Overview</div>
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <!-- SECTION: Operations / Checkout -->
        <div>
            <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-2 font-bold mt-2">Operations</div>
            <div class="space-y-1">
                <!-- POS Checkout (All roles: Cashier, Manager, Owner) -->
                <a href="{{ route('pos.create') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('pos.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('pos.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.5"/><circle cx="19" cy="20" r="1.5"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span>Point of Sale</span>
                </a>

                @if(Auth::user()->isManagerOrOwner())
                    <!-- Historical Orders (Restricted to Manager/Owner) -->
                    <a href="{{ route('orders.index') }}" 
                       class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('orders.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('orders.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <span>Sales History</span>
                    </a>
                    
                    <!-- Customer Management (Restricted to Manager/Owner) -->
                    <a href="{{ route('customers.index') }}" 
                       class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('customers.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('customers.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                        <span>Customers</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- SECTION: Inventory -->
        <div>
            <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-2 font-bold mt-2">Inventory</div>
            <div class="space-y-1">
                @if(!Auth::user()->isManagerOrOwner())
                    <!-- Cashier View-Only Catalog -->
                    <a href="{{ route('products.index') }}" 
                       class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('products.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('products.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                        <span>Products Catalog</span>
                    </a>
                @else
                    <!-- Manager/Owner Inventory Hub with Dropdown -->
                    <div x-data="{ invOpen: {{ request()->routeIs('products.*', 'stock-ins.*', 'stock-outs.*') ? 'true' : 'false' }} }">
                        <button @click="invOpen = !invOpen" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('products.*', 'stock-ins.*', 'stock-outs.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                            <div class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 {{ request()->routeIs('products.*', 'stock-ins.*', 'stock-outs.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                                <span>Inventory Hub</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200 text-[#88A6D3]" :class="invOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="invOpen" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="pl-4 pr-1 mt-1 space-y-0.5 border-l-2 border-[#1E3B68] ml-6">
                            <a href="{{ route('products.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('products.*') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('products.*') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Products</span>
                            </a>
                            <a href="{{ route('stock-ins.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('stock-ins.*') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('stock-ins.*') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Stock In History</span>
                            </a>
                            <a href="{{ route('stock-outs.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('stock-outs.*') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('stock-outs.*') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Stock Out History</span>
                            </a>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        @if(Auth::user()->isManagerOrOwner())
            <!-- SECTION: Credit & Receivables -->
            <div>
                <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-2 font-bold mt-2">Credit &amp; Receivables</div>
                <div class="space-y-1">
                    <a href="{{ route('credit-accounts.index') }}" 
                       class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('credit-accounts.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('credit-accounts.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>Credit Accounts (Utang)</span>
                    </a>
                    
                    <a href="{{ route('statements.index') }}" 
                       class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('statements.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('statements.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <span>Statements of Account</span>
                    </a>
                </div>
            </div>

            <!-- SECTION: Reports Module -->
            <div>
                <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-2 font-bold mt-2">Reports &amp; Analytics</div>
                <div class="space-y-1">
                    <div x-data="{ repOpen: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
                        <button @click="repOpen = !repOpen" 
                                type="button" 
                                class="w-full flex items-center justify-between px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('reports.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                            <div class="flex items-center gap-3.5">
                                <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                                <span>Reports Hub</span>
                            </div>
                            <svg class="w-4 h-4 transition-transform duration-200 text-[#88A6D3]" :class="repOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="repOpen" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="pl-4 pr-1 mt-1 space-y-0.5 border-l-2 border-[#1E3B68] ml-6">
                            <a href="{{ route('reports.index') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('reports.index') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.index') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Overview Hub</span>
                            </a>
                            <a href="{{ route('reports.sales') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('reports.sales') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.sales') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Sales Report</span>
                            </a>
                            <a href="{{ route('reports.inventory') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('reports.inventory') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Inventory Report</span>
                            </a>
                            <a href="{{ route('reports.utang') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('reports.utang') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.utang') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Utang (Credit) Report</span>
                            </a>
                            <a href="{{ route('reports.discounts') }}" 
                               class="flex items-center gap-2.5 px-3 py-1.5 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('reports.discounts') ? 'bg-[#1D4E89] text-white font-bold' : 'text-[#88A6D3] hover:text-white hover:bg-[#152441]' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.discounts') ? 'bg-[#38BDF8]' : 'bg-[#546E9C]' }}"></span>
                                <span>Discounts Summary</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        @if(Auth::user()->isOwner())
            <!-- SECTION: Administration (Owner Only) -->
            <div>
                <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-2 font-bold mt-2">Administration</div>
                <div class="space-y-1">
                    <a href="{{ route('users.index') }}" 
                       class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('users.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                        <svg class="w-5 h-5 {{ request()->routeIs('users.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                        <span>User Accounts</span>
                    </a>
                </div>
            </div>
        @endif

    </div>

    <!-- User Profile Footer Card -->
    <div class="mt-auto pt-5 border-t border-[#1C335A] flex gap-3 items-center px-1">
        <div class="w-[38px] h-[38px] rounded-full bg-[#245CA6] flex items-center justify-center font-bold text-white text-[14px] shrink-0 shadow-sm">
            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
        </div>
        <div class="overflow-hidden flex-1">
            <div class="text-[13px] font-bold text-white truncate leading-tight">{{ Auth::user()->name ?? 'User' }}</div>
            <div class="text-[11px] text-[#7E96B8] truncate mt-[1px]">{{ Auth::user()->role->role_name ?? 'Staff' }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
            @csrf
            <button type="submit" 
                    class="text-[13px] text-[#88A6D3] hover:text-white transition-colors underline" 
                    title="Log Out">
                Log out
            </button>
        </form>
    </div>
</nav>
