<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between w-full gap-3">
            <div>
                <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Audit Trail</h1>
                <p class="text-[13px] text-[#5B6472] mt-[2px]">Who did what, and when, across sales, inventory, credit, and staff accounts.</p>
            </div>
            <div class="flex items-center gap-2.5">
                <a href="{{ route('inventory-movements.index') }}" class="rounded-lg px-4 py-2 text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9] transition-colors">
                    Inventory Ledger
                </a>
                <a href="{{ route('audit-logs.export', request()->query()) }}" class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] transition-colors">
                    <svg class="w-4 h-4" aria-hidden="true" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <span>Export CSV</span>
                </a>
            </div>
        </div>
    </x-slot>

    @php
        // Preserve every active filter except the category when switching tabs.
        $baseQuery = request()->except(['category', 'page']);
    @endphp

    <div x-data="liveSearch()" class="flex flex-col gap-4">
        {{-- Category Tabs --}}
        <nav aria-label="Activity categories" class="flex items-center gap-1 bg-[#E5E9EF]/60 p-1 rounded-xl overflow-x-auto self-start max-w-full">
            <a href="{{ route('audit-logs.index', $baseQuery) }}"
               @if(!$activeCategory) aria-current="page" @endif
               class="px-3.5 py-1.5 rounded-lg text-[13px] font-semibold whitespace-nowrap transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0B3B70] {{ !$activeCategory ? 'bg-white text-[#0B3B70] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }}">
                All Activity
            </a>
            @foreach($categories as $key => $category)
                <a href="{{ route('audit-logs.index', array_merge($baseQuery, ['category' => $key])) }}"
                   @if($activeCategory === $key) aria-current="page" @endif
                   class="px-3.5 py-1.5 rounded-lg text-[13px] font-semibold whitespace-nowrap transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0B3B70] {{ $activeCategory === $key ? 'bg-white text-[#0B3B70] shadow-xs' : 'text-[#5B6472] hover:text-[#1C2430]' }}">
                    {{ $category['label'] }}
                </a>
            @endforeach
        </nav>

        {{-- Compact Filter Toolbar --}}
        <form x-ref="form" method="GET" action="{{ route('audit-logs.index') }}" role="search" class="flex flex-wrap items-center gap-2.5">
            @if($activeCategory)
                <input type="hidden" name="category" value="{{ $activeCategory }}">
            @endif

            <div class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2 flex-1 min-w-[240px] max-w-[380px] focus-within:border-[#0B3B70]">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                <label for="audit-search" class="sr-only">Search activity</label>
                <input id="audit-search" type="text" name="search" x-model="query" @input.debounce.500ms="performSearch"
                       placeholder="Search reference, staff, or activity..."
                       class="border-none outline-none w-full bg-transparent p-0 focus:ring-0 text-[13px] text-[#1C2430]">
            </div>

            <label for="audit-staff" class="sr-only">Staff member</label>
            <select id="audit-staff" name="actor_user_id" onchange="this.form.submit()"
                    class="py-2 pl-3 pr-9 border border-[#E5E9EF] rounded-lg text-[13px] text-[#1C2430] bg-white focus:border-[#0B3B70] focus:ring-[#0B3B70]">
                <option value="">All staff</option>
                @foreach($users as $user)
                    <option value="{{ $user->id }}" {{ (string) request('actor_user_id') === (string) $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                @endforeach
            </select>

            <div class="flex items-center gap-1.5">
                <label for="audit-from" class="text-[13px] text-[#5B6472]">From</label>
                <input id="audit-from" type="date" name="from_date" value="{{ request('from_date') }}" onchange="this.form.submit()"
                       class="py-2 px-3 border border-[#E5E9EF] rounded-lg text-[13px] text-[#1C2430] focus:border-[#0B3B70] focus:ring-[#0B3B70]">
                <label for="audit-to" class="text-[13px] text-[#5B6472]">to</label>
                <input id="audit-to" type="date" name="to_date" value="{{ request('to_date') }}" onchange="this.form.submit()"
                       class="py-2 px-3 border border-[#E5E9EF] rounded-lg text-[13px] text-[#1C2430] focus:border-[#0B3B70] focus:ring-[#0B3B70]">
            </div>

            @if($hasActiveFilters)
                <a href="{{ route('audit-logs.index', array_filter(['category' => $activeCategory])) }}" class="text-[13px] font-semibold text-[#0B3B70] hover:underline px-1">
                    Clear filters
                </a>
            @endif
        </form>

        {{-- Activity Table --}}
        <div id="table-container">
            <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <caption class="sr-only">Audit trail activity, newest first</caption>
                        <thead>
                            <tr class="bg-[#F8FAFC]">
                                <th scope="col" class="whitespace-nowrap text-left text-[12px] font-semibold text-[#5B6472] py-3 px-5 border-b border-[#E5E9EF]">Date &amp; Time</th>
                                <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-5 border-b border-[#E5E9EF]">Activity</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[12px] font-semibold text-[#5B6472] py-3 px-5 border-b border-[#E5E9EF]">Performed By</th>
                                <th scope="col" class="whitespace-nowrap text-left text-[12px] font-semibold text-[#5B6472] py-3 px-5 border-b border-[#E5E9EF]">Reference &amp; Status</th>
                                <th scope="col" class="border-b border-[#E5E9EF] py-3 px-5"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($auditLogs as $log)
                                <tr class="hover:bg-[#F8FAFC] transition-colors">
                                    <td class="py-3.5 px-5 border-b border-[#EEF1F5] whitespace-nowrap align-top">
                                        <div class="text-[13px] font-semibold text-[#1C2430]">{{ $log->created_at?->format('M d, Y') }}</div>
                                        <div class="text-[12px] text-[#5B6472]">{{ $log->created_at?->format('h:i A') }}</div>
                                    </td>
                                    <td class="py-3.5 px-5 border-b border-[#EEF1F5] align-top">
                                        <div class="text-[13.5px] font-semibold text-[#1C2430]">{{ $log->action_label }}</div>
                                        @if($log->reason)
                                            <div class="text-[12.5px] text-[#5B6472] mt-0.5 max-w-[420px] line-clamp-1" title="{{ $log->reason }}">{{ $log->reason }}</div>
                                        @endif
                                    </td>
                                    <td class="py-3.5 px-5 border-b border-[#EEF1F5] whitespace-nowrap align-top">
                                        <div class="text-[13px] font-semibold text-[#1C2430]">{{ $log->actor_name_snapshot ?? $log->actor?->name ?? 'System' }}</div>
                                        <div class="text-[12px] text-[#5B6472]">{{ $log->actor_role_snapshot ?? '—' }}</div>
                                    </td>
                                    <td class="py-3.5 px-5 border-b border-[#EEF1F5] whitespace-nowrap align-top">
                                        <div class="text-[13px] font-medium text-[#1C2430]">{{ $log->transaction_reference ?? '—' }}</div>
                                        <div class="text-[12px] font-semibold {{ $log->status_color_class }}">{{ $log->status_label }}</div>
                                    </td>
                                    <td class="py-3.5 px-5 border-b border-[#EEF1F5] text-right whitespace-nowrap align-top">
                                        <a href="{{ route('audit-logs.show', $log) }}"
                                           class="text-[13px] font-semibold text-[#0B3B70] hover:underline focus:outline-none focus-visible:ring-2 focus-visible:ring-[#0B3B70] rounded">
                                            View details<span class="sr-only">: {{ $log->action_label }} on {{ $log->created_at?->format('M d, Y h:i A') }}</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="py-12 text-center">
                                        <div class="text-[14px] font-semibold text-[#1C2430]">No activity found</div>
                                        <div class="text-[13px] text-[#5B6472] mt-1">Try a different category, staff member, or date range.</div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($auditLogs->hasPages())
                    <div class="px-5 py-4 border-t border-[#E5E9EF]">
                        {{ $auditLogs->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
