<nav class="w-[232px] shrink-0 bg-[#082A52] text-[#EAF1FA] flex flex-col px-3.5 py-5 sticky top-0 h-screen">
    
    <div class="flex items-center gap-2.5 px-2.5 pb-6">
        <div class="w-[34px] h-[34px] rounded-lg bg-gradient-to-br from-[#0B3B70] to-[#5D89B0] flex items-center justify-center font-['Manrope'] font-extrabold text-[15px] text-white shrink-0">
            LG
        </div>
        <div>
            <div class="font-['Manrope'] font-extrabold text-[15.5px] leading-tight text-white">Liberty LPG</div>
            <div class="text-[11px] text-[#9FB3CC] font-medium">Sales & Inventory</div>
        </div>
    </div>

    <div class="flex-1 overflow-y-auto">
        <div class="text-[10.5px] uppercase tracking-wider text-[#7C93B0] mx-3 mt-3.5 mb-1.5 font-semibold">Overview</div>
        <a href="{{ route('dashboard') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13.5px] font-medium mb-0.5 {{ request()->routeIs('dashboard') ? 'bg-gradient-to-br from-[#5D89B0] to-[#082A52] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783] hover:text-white' }}">
            <svg class="w-[18px] h-[18px] {{ request()->routeIs('dashboard') ? 'opacity-100' : 'opacity-85' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/></svg>
            Dashboard
        </a>

        <div class="text-[10.5px] uppercase tracking-wider text-[#7C93B0] mx-3 mt-3.5 mb-1.5 font-semibold">Operations</div>
        <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13.5px] font-medium mb-0.5 {{ request()->routeIs('products.*') || request()->routeIs('stock-ins.*') || request()->routeIs('stock-outs.*') ? 'bg-gradient-to-br from-[#5D89B0] to-[#082A52] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783] hover:text-white' }}">
            <svg class="w-[18px] h-[18px] {{ request()->routeIs('products.*') ? 'opacity-100' : 'opacity-85' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8 12 3 3 8l9 5 9-5Z"/><path d="M3 8v8l9 5 9-5V8"/><path d="M12 13v8"/></svg>
            Inventory
        </a>
        <a href="{{ route('orders.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13.5px] font-medium mb-0.5 {{ request()->routeIs('orders.*') || request()->routeIs('pos.*') ? 'bg-gradient-to-br from-[#5D89B0] to-[#082A52] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783] hover:text-white' }}">
            <svg class="w-[18px] h-[18px] {{ request()->routeIs('orders.*') ? 'opacity-100' : 'opacity-85' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="1.4"/><circle cx="18" cy="20" r="1.4"/><path d="M2.5 3h2.6l2.6 12.6a2 2 0 0 0 2 1.6h8a2 2 0 0 0 2-1.6L21.5 7H6"/></svg>
            Sales
        </a>
        <a href="{{ route('customers.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13.5px] font-medium mb-0.5 {{ request()->routeIs('customers.*') ? 'bg-gradient-to-br from-[#5D89B0] to-[#082A52] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783] hover:text-white' }}">
            <svg class="w-[18px] h-[18px] {{ request()->routeIs('customers.*') ? 'opacity-100' : 'opacity-85' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="8" r="3.2"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6"/><circle cx="18" cy="8.5" r="2.6"/><path d="M16.2 14.3c2.8.4 4.8 2.4 4.8 5.7"/></svg>
            Customers
        </a>

        <div class="text-[10.5px] uppercase tracking-wider text-[#7C93B0] mx-3 mt-3.5 mb-1.5 font-semibold">Administration</div>
        <a href="{{ route('users.index') }}" class="flex items-center gap-3 px-3 py-2 rounded-lg text-[13.5px] font-medium mb-0.5 {{ request()->routeIs('users.*') ? 'bg-gradient-to-br from-[#5D89B0] to-[#082A52] text-white' : 'text-[#C9D8EA] hover:bg-[#0F4783] hover:text-white' }}">
            <svg class="w-[18px] h-[18px] {{ request()->routeIs('users.*') ? 'opacity-100' : 'opacity-85' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="8" r="3.2"/><path d="M5 20c0-4 3-6.5 7-6.5s7 2.5 7 6.5"/><path d="M17.5 3.5 19 5l2-2"/></svg>
            User Accounts
        </a>
    </div>

    <div class="mt-auto p-3 border-t border-[#14345C] flex gap-2.5 items-center">
        <div class="w-[34px] h-[34px] rounded-full bg-[#3D6AA0] flex items-center justify-center font-bold text-white text-[13px] shrink-0">
            {{ strtoupper(substr(Auth::user()->name ?? 'U', 0, 2)) }}
        </div>
        <div class="overflow-hidden">
            <div class="text-[12.5px] font-semibold text-white truncate">{{ Auth::user()->name ?? 'User' }}</div>
            <div class="text-[11px] text-[#9FB3CC] truncate">{{ Auth::user()->role->role_name ?? 'Staff' }}</div>
        </div>
        <form method="POST" action="{{ route('logout') }}" class="ml-auto">
            @csrf
            <button type="submit" class="text-[11px] text-[#9FB3CC] bg-transparent border-none underline p-0 hover:text-white cursor-pointer">Log out</button>
        </form>
    </div>
</nav>