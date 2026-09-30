<nav :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
     class="w-[245px] shrink-0 bg-[#082A52] text-[#EAF1FA] flex flex-col px-3.5 py-5 fixed inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:sticky md:top-0 h-screen select-none">
    
    <!-- Brand Header -->
    <div class="flex items-center gap-2.5 px-2.5 pb-5 border-b border-[#14345C]">
        <div class="w-[36px] h-[36px] rounded-xl bg-gradient-to-br from-[#0B3B70] via-[#3A6B9B] to-[#5D89B0] flex items-center justify-center font-['Manrope'] font-extrabold text-[15px] text-white shrink-0 shadow-md">
            LG
        </div>
        <div>
            <div class="font-['Manrope'] font-extrabold text-[15.5px] leading-tight text-white tracking-tight">Liberty LPG</div>
            <div class="text-[11px] text-[#9FB3CC] font-medium">Sales &amp; Inventory</div>
        </div>
        <button @click="sidebarOpen = false" class="md:hidden text-[#9FB3CC] hover:text-white p-1 ml-auto rounded-lg hover:bg-[#0F4783]" aria-label="Close Sidebar">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    </div>

    <!-- Navigation Scrollable Body -->
    <div class="flex-1 overflow-y-auto pt-3.5 space-y-4 pr-1">
        
        <!-- SECTION: Overview -->
        <div>
            <div class="text-[10px] uppercase tracking-wider text-[#7C93B0] mx-3 mb-1.5 font-bold">Overview</div>
            <div class="space-y-0.5">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('dashboard') ? 'bg-gradient-to-r from-[#0F4783] to-[#1E5C9A] text-white shadow-sm' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                    <svg class="w-[18px] h-[18px] {{ request()->routeIs('dashboard') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('pos.create') }}" 
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('pos.*') ? 'bg-gradient-to-r from-[#0F4783] to-[#1E5C9A] text-white shadow-sm' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                    <svg class="w-[18px] h-[18px] {{ request()->routeIs('pos.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.6l2.6 12.6a2 2 0 0 0 2 1.6h8a2 2 0 0 0 2-1.6L21.5 7H6"/></svg>
                    <span>Point of Sale</span>
                </a>
            </div>
        </div>

        <!-- SECTION: Inventory (Role-Aware) -->
        <div>
            <div class="text-[10px] uppercase tracking-wider text-[#7C93B0] mx-3 mb-1.5 font-bold">Inventory</div>
            
            @if(Auth::user()->isCashier())
                {{-- Cashier: View-Only Inventory --}}
                <a href="{{ route('products.index') }}" 
                   class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('products.*') ? 'bg-gradient-to-r from-[#0F4783] to-[#1E5C9A] text-white shadow-sm' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                    <svg class="w-[18px] h-[18px] {{ request()->routeIs('products.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                    <span>Inventory Catalog</span>
                    <span class="ml-auto text-[10px] font-bold px-1.5 py-0.5 rounded bg-[#14345C] text-[#9FB3CC]">View</span>
                </a>
            @else
                {{-- Manager & Owner: Dropdown / Grouped Section --}}
                <div x-data="{ invOpen: {{ request()->routeIs('products.*') || request()->routeIs('stock-ins.*') || request()->routeIs('stock-outs.*') ? 'true' : 'false' }} }">
                    <button @click="invOpen = !invOpen" 
                            type="button" 
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('products.*') || request()->routeIs('stock-ins.*') || request()->routeIs('stock-outs.*') ? 'bg-[#0F4783] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-[18px] h-[18px] {{ request()->routeIs('products.*') || request()->routeIs('stock-ins.*') || request()->routeIs('stock-outs.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
                            <span>Inventory Control</span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#8EA8C7]" :class="invOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="invOpen" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="pl-4 pr-1 mt-1 space-y-0.5 border-l-2 border-[#14345C] ml-5">
                        <a href="{{ route('products.index') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('products.*') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('products.*') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Products Catalog</span>
                        </a>
                        <a href="{{ route('stock-ins.index') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('stock-ins.*') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('stock-ins.*') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Stock In History</span>
                        </a>
                        <a href="{{ route('stock-outs.index') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('stock-outs.*') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('stock-outs.*') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Stock Out History</span>
                        </a>
                    </div>
                </div>
            @endif
        </div>

        @if(Auth::user()->isManagerOrOwner())
            <!-- SECTION: Sales & Orders (Managers/Owners) -->
            <div>
                <div class="text-[10px] uppercase tracking-wider text-[#7C93B0] mx-3 mb-1.5 font-bold">Sales &amp; Orders</div>
                <div class="space-y-0.5">
                    <div x-data="{ salesOpen: {{ request()->routeIs('orders.*') ? 'true' : 'false' }} }">
                        <button @click="salesOpen = !salesOpen" 
                                type="button" 
                                class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('orders.*') ? 'bg-[#0F4783] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                            <div class="flex items-center gap-3">
                                <svg class="w-[18px] h-[18px] {{ request()->routeIs('orders.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>
                                <span>Sales History</span>
                            </div>
                            <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#8EA8C7]" :class="salesOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div x-show="salesOpen" 
                             x-transition:enter="transition ease-out duration-150"
                             x-transition:enter-start="opacity-0 -translate-y-1"
                             x-transition:enter-end="opacity-100 translate-y-0"
                             class="pl-4 pr-1 mt-1 space-y-0.5 border-l-2 border-[#14345C] ml-5">
                            <a href="{{ route('orders.index') }}" 
                               class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('orders.index') && !request()->has('status') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('orders.index') && !request()->has('status') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                                <span>All Transactions</span>
                            </a>
                            <a href="{{ route('orders.index', ['status' => 'voided']) }}" 
                               class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('orders.index') && request('status') === 'voided' ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ request('status') === 'voided' ? 'bg-[#FF6B6B]' : 'bg-[#7C93B0]' }}"></span>
                                <span>Void Audit Log</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECTION: Customer & Credit Ledger -->
            <div>
                <div class="text-[10px] uppercase tracking-wider text-[#7C93B0] mx-3 mb-1.5 font-bold">Credit &amp; Billing</div>
                <div class="space-y-0.5">
                    <a href="{{ route('customers.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('customers.*') ? 'bg-gradient-to-r from-[#0F4783] to-[#1E5C9A] text-white shadow-sm' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                        <svg class="w-[18px] h-[18px] {{ request()->routeIs('customers.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="18" cy="8.5" r="2.6"/><path d="M16.2 14.3c2.8.4 4.8 2.4 4.8 5.7"/></svg>
                        <span>Customers</span>
                    </a>
                    <a href="{{ route('credit-accounts.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('credit-accounts.*') || request()->routeIs('payments.*') ? 'bg-gradient-to-r from-[#0F4783] to-[#1E5C9A] text-white shadow-sm' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                        <svg class="w-[18px] h-[18px] {{ request()->routeIs('credit-accounts.*') || request()->routeIs('payments.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                        <span>Credit (Utang)</span>
                    </a>
                    <a href="{{ route('statements.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('statements.*') ? 'bg-gradient-to-r from-[#0F4783] to-[#1E5C9A] text-white shadow-sm' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                        <svg class="w-[18px] h-[18px] {{ request()->routeIs('statements.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                        <span>Statements</span>
                    </a>
                </div>
            </div>

            <!-- SECTION: Reports Module (Dedicated clean section restricted to Managers/Owners) -->
            <div>
                <div class="text-[10px] uppercase tracking-wider text-[#7C93B0] mx-3 mb-1.5 font-bold">Reports &amp; Analytics</div>
                <div x-data="{ repOpen: {{ request()->routeIs('reports.*') ? 'true' : 'false' }} }">
                    <button @click="repOpen = !repOpen" 
                            type="button" 
                            class="w-full flex items-center justify-between px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('reports.*') ? 'bg-[#0F4783] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                        <div class="flex items-center gap-3">
                            <svg class="w-[18px] h-[18px] {{ request()->routeIs('reports.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                            <span>Reports Hub</span>
                        </div>
                        <svg class="w-3.5 h-3.5 transition-transform duration-200 text-[#8EA8C7]" :class="repOpen ? 'rotate-180 text-white' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-show="repOpen" 
                         x-transition:enter="transition ease-out duration-150"
                         x-transition:enter-start="opacity-0 -translate-y-1"
                         x-transition:enter-end="opacity-100 translate-y-0"
                         class="pl-4 pr-1 mt-1 space-y-0.5 border-l-2 border-[#14345C] ml-5">
                        <a href="{{ route('reports.index') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('reports.index') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.index') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Overview Hub</span>
                        </a>
                        <a href="{{ route('reports.sales') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('reports.sales') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.sales') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Sales Report</span>
                        </a>
                        <a href="{{ route('reports.inventory') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('reports.inventory') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.inventory') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Inventory Report</span>
                        </a>
                        <a href="{{ route('reports.utang') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('reports.utang') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.utang') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Utang (Credit) Report</span>
                        </a>
                        <a href="{{ route('reports.discounts') }}" 
                           class="flex items-center gap-2 px-2.5 py-1.5 rounded-md text-[12px] font-medium transition-colors {{ request()->routeIs('reports.discounts') ? 'bg-[#1E5C9A] text-white font-bold' : 'text-[#A0B7D1] hover:text-white hover:bg-[#0F4783]/40' }}">
                            <span class="w-1.5 h-1.5 rounded-full {{ request()->routeIs('reports.discounts') ? 'bg-[#4AE397]' : 'bg-[#7C93B0]' }}"></span>
                            <span>Discounts Summary</span>
                        </a>
                    </div>
                </div>
            </div>
        @endif

        @if(Auth::user()->isOwner())
            <!-- SECTION: Owner Administration -->
            <div>
                <div class="text-[10px] uppercase tracking-wider text-[#7C93B0] mx-3 mb-1.5 font-bold">Administration</div>
                <div class="space-y-0.5">
                    <a href="{{ route('users.index') }}" 
                       class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13px] font-medium transition-colors {{ request()->routeIs('users.*') ? 'bg-gradient-to-r from-[#0F4783] to-[#1E5C9A] text-white shadow-sm' : 'text-[#C9D8EA] hover:bg-[#0F4783]/60 hover:text-white' }}">
                        <svg class="w-[18px] h-[18px] {{ request()->routeIs('users.*') ? 'text-white' : 'text-[#8EA8C7]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-4 3-6.5 7-6.5s7 2.5 7 6.5"/><path d="M17.5 3.5 19 5l2-2"/></svg>
                        <span>User Accounts</span>
                    </a>
                </div>
            </div>
        @endif

    </div>

    <!-- User Profile Footer Card -->
    <div class="mt-auto pt-3 border-t border-[#14345C] flex gap-2.5 items-center">
        <div class="w-[36px] h-[36px] rounded-full bg-gradient-to-br from-[#1E5C9A] to-[#0F4783] flex items-center justify-center font-bold text-white text-[13px] shrink-0 border border-[#3A6B9B] shadow-sm">
            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
        </div>
        <div class="overflow-hidden flex-1">
            <div class="text-[12.5px] font-semibold text-white truncate leading-tight">{{ Auth::user()->name ?? 'User' }}</div>
            <div class="text-[11px] text-[#9FB3CC] truncate">{{ Auth::user()->role->role_name ?? 'Staff' }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
            @csrf
            <button type="submit" 
                    class="text-[11px] text-[#9FB3CC] hover:text-white p-1 rounded hover:bg-[#0F4783] transition-colors" 
                    title="Log Out">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
            </button>
        </form>
    </div>
</nav>
