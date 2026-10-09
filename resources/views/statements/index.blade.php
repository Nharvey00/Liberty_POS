<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Statements of Account</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Generated billing statements for credit customers</div>
    </x-slot>

    @if (session('success'))
        <div class="mb-4 bg-[#E5F5EC] border border-[#1E8E5A] text-[#1E8E5A] px-4 py-3 rounded-lg text-[13px] font-semibold">
            {{ session('success') }}
        </div>
    @endif

    <div class="mb-5 flex flex-col xl:flex-row xl:items-center justify-between gap-4">
        <form method="GET" action="{{ route('statements.index') }}" x-data="customerAutocomplete()" class="flex-1 flex flex-wrap items-center gap-3">
            <div class="relative min-w-[200px]">
                <div class="flex items-center gap-2 bg-white border border-[#E5E9EF] rounded-lg px-3 py-2">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="#5B6472" stroke-width="2"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/></svg>
                    <input type="text" name="customer" x-model="query" @input.debounce.300ms="fetchSuggestions" @keydown.down.prevent="highlightNext()" @keydown.up.prevent="highlightPrev()" @keydown.enter.prevent="selectHighlighted()" @focus="open = true" @click.away="open = false" placeholder="Search customer..." class="border-none outline-none font-inherit w-full bg-transparent p-0 focus:ring-0 text-[13px]" autocomplete="off">
                    @if(request('customer'))
                        <a href="{{ route('statements.index') }}" class="text-[#5B6472] hover:text-[#1C2430] text-[11px]">✕</a>
                    @endif
                </div>
                <div x-show="open && suggestions.length > 0" class="absolute top-full left-0 z-10 w-full bg-white mt-1 border border-[#E5E9EF] rounded-lg shadow-lg max-h-60 overflow-y-auto" style="display: none;">
                    <template x-for="(suggestion, index) in suggestions" :key="suggestion.id">
                        <div @click="selectSuggestion(suggestion)" @mouseenter="highlightedIndex = index" :class="{'bg-[#F4F6F9]': highlightedIndex === index}" class="px-3 py-2 cursor-pointer text-[13px] text-[#1C2430] border-b border-[#E5E9EF] last:border-b-0">
                            <span x-text="suggestion.name"></span>
                        </div>
                    </template>
                </div>
            </div>
            
            <select name="status" class="pl-3 pr-10 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <option value="all" {{ request('status') === 'all' ? 'selected' : '' }}>All Status</option>
                <option value="paid" {{ request('status') === 'paid' ? 'selected' : '' }}>Paid</option>
                <option value="unpaid" {{ request('status') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
            </select>
            
            <select name="customer_type" class="pl-3 pr-10 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <option value="">All Types</option>
                <option value="Individual" {{ request('customer_type') === 'Individual' ? 'selected' : '' }}>Individual</option>
                <option value="Business" {{ request('customer_type') === 'Business' ? 'selected' : '' }}>Business</option>
            </select>
            
            <div class="flex items-center gap-2">
                <input type="date" name="from" value="{{ request('from') }}" class="px-3 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
                <span class="text-[13px] text-[#5B6472]">to</span>
                <input type="date" name="to" value="{{ request('to') }}" class="px-3 py-2 bg-white border border-[#E5E9EF] rounded-lg text-[13px] focus:ring-[#0B3B70] focus:border-[#0B3B70]">
            </div>
            
            <button type="submit" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52]">Filter</button>
            <a href="{{ route('statements.index') }}" class="text-[13px] text-[#0B3B70] hover:underline">Clear</a>
        </form>

        <script>
            function customerAutocomplete() {
                return {
                    query: '{{ request('customer') ?? '' }}',
                    suggestions: [],
                    open: false,
                    highlightedIndex: -1,
                    
                    async fetchSuggestions() {
                        if (this.query.length < 2) {
                            this.suggestions = [];
                            this.open = false;
                            return;
                        }
                        try {
                            const response = await fetch(`/api/customers/search?query=${encodeURIComponent(this.query)}`);
                            this.suggestions = await response.json();
                            this.open = this.suggestions.length > 0;
                            this.highlightedIndex = -1;
                        } catch (e) {
                            console.error(e);
                        }
                    },
                    highlightNext() {
                        if (this.highlightedIndex < this.suggestions.length - 1) this.highlightedIndex++;
                    },
                    highlightPrev() {
                        if (this.highlightedIndex > 0) this.highlightedIndex--;
                    },
                    selectHighlighted() {
                        if (this.highlightedIndex >= 0 && this.highlightedIndex < this.suggestions.length) {
                            this.selectSuggestion(this.suggestions[this.highlightedIndex]);
                        } else {
                            this.$el.closest('form').submit();
                        }
                    },
                    selectSuggestion(suggestion) {
                        this.query = suggestion.name;
                        this.open = false;
                        this.$nextTick(() => {
                            this.$el.closest('form').submit();
                        });
                    }
                }
            }
        </script>
        
        <div class="flex gap-2.5 flex-wrap items-center">
            <a href="{{ route('statements.create') }}" class="rounded-lg px-[15px] py-[8px] text-[13px] font-semibold border border-[#0B3B70] bg-[#0B3B70] text-white hover:bg-[#082A52] whitespace-nowrap">+ Generate SOA</a>
        </div>
    </div>

    <div class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
        <div class="overflow-x-auto -mx-4 md:mx-0 px-4 md:px-0">
            <table class="w-full border-collapse">
                <thead>
                    <tr>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">SOA #</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Customer</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Billing Period</th>
                        <th class="text-right text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Total Due</th>
                        <th class="text-left text-[11.5px] uppercase tracking-[0.02em] text-[#5B6472] font-semibold py-3 px-4 border-b border-[#E5E9EF] whitespace-nowrap">Status</th>
                        <th class="border-b border-[#E5E9EF]"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($statements as $soa)
                        <tr class="hover:bg-[#F4F6F9] transition-colors">
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] font-semibold text-[#1C2430]">#{{ $soa->id }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#1C2430]">{{ $soa->creditAccount->customer->name }}</td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-[#5B6472]">
                                {{ \Carbon\Carbon::parse($soa->billing_period_start)->format('M d') }} — {{ \Carbon\Carbon::parse($soa->billing_period_end)->format('M d, Y') }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF] text-right font-bold {{ $soa->total_due > 0 ? 'text-[#B5504B]' : 'text-[#1E8E5A]' }}">
                                ₱{{ number_format($soa->total_due, 2) }}
                            </td>
                            <td class="py-3 px-4 text-[13px] border-b border-[#E5E9EF]">
                                @if($soa->is_paid)
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#E5F5EC] text-[#1E8E5A]">Paid</span>
                                @else
                                    <span class="inline-block px-2.5 py-1 rounded-full text-[11px] font-bold bg-[#FDECEA] text-[#B5504B]">Unpaid</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 border-b border-[#E5E9EF] text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-3">
                                    <a href="{{ route('statements.show', $soa) }}" class="text-[#0B3B70] font-bold text-[12px] hover:underline">View / Print</a>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-[13px] text-[#5B6472]">No statements generated yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4 px-4 py-3 bg-white border-t border-gray-200 sm:px-6 print:hidden overflow-x-auto">
            {{ $statements->links() }}
        </div>
    </div>
</x-app-layout>
