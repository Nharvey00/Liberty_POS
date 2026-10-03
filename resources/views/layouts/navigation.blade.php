<nav :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full md:translate-x-0'"
     class="w-[260px] shrink-0 bg-gradient-to-b from-[#050A15] to-[#0D1D3A] text-white flex flex-col px-4 py-6 fixed inset-y-0 left-0 z-50 transition-transform duration-300 ease-in-out md:static md:translate-x-0 md:sticky md:top-0 h-screen select-none border-r border-[#0D1D3A]">
    
    <!-- Brand Header -->
    <div class="flex items-center gap-3.5 px-3 pb-8">
        <div class="w-[42px] h-[42px] rounded-[10px] bg-[#245CA6] flex items-center justify-center font-['Inter'] font-extrabold text-[15px] text-white shrink-0 shadow-sm">
            LG
        </div>
        <div>
            <div class="font-['Inter'] font-bold text-[16px] leading-tight text-white">Liberty LPG</div>
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
            <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-3 font-bold">Overview</div>
            <div class="space-y-1">
                <a href="{{ route('dashboard') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('dashboard') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('dashboard') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
                    <span>Dashboard</span>
                </a>
            </div>
        </div>

        <!-- SECTION: Operations -->
        <div>
            <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-3 font-bold mt-2">Operations</div>
            <div class="space-y-1">
                <a href="{{ route('products.index') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('products.*', 'stock-ins.*', 'stock-outs.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('products.*', 'stock-ins.*', 'stock-outs.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
                    <span>Inventory</span>
                </a>
                
                <a href="{{ route('orders.index') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('orders.*', 'pos.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('orders.*', 'pos.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
                    <span>Sales</span>
                </a>
                
                <a href="{{ route('customers.index') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('customers.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('customers.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span>Customers</span>
                </a>
            </div>
        </div>

        @if(Auth::user()->isManagerOrOwner())
        <!-- SECTION: Insights -->
        <div>
            <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-3 font-bold mt-2">Insights</div>
            <div class="space-y-1">
                <a href="{{ route('reports.index') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('reports.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('reports.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 3v18h18"/><path d="m19 9-5 5-4-4-3 3"/></svg>
                    <span>Reports</span>
                </a>
                
                <a href="{{ route('credit-accounts.index') }}" 
                   class="flex items-center gap-3.5 px-4 py-2.5 rounded-[12px] text-[14px] font-semibold transition-colors {{ request()->routeIs('credit-accounts.*', 'statements.*') ? 'bg-[#2E66AD] text-white shadow-sm' : 'text-[#88A6D3] hover:bg-[#152441] hover:text-white' }}">
                    <svg class="w-5 h-5 {{ request()->routeIs('credit-accounts.*', 'statements.*') ? 'text-white' : 'text-[#88A6D3]' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="4" width="20" height="16" rx="2" ry="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg>
                    <span>Income &amp; Expenses</span>
                </a>
            </div>
        </div>
        @endif

        @if(Auth::user()->isOwner())
        <!-- SECTION: Administration -->
        <div>
            <div class="text-[11px] uppercase tracking-widest text-[#546E9C] mx-3 mb-3 font-bold mt-2">Administration</div>
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
