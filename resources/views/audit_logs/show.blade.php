<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between w-full gap-3">
            <div>
                <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">{{ $auditLog->action_label }}</h1>
                <p class="text-[13px] text-[#5B6472] mt-[2px]">{{ $auditLog->module_label }} &middot; {{ $auditLog->created_at?->format('M d, Y \a\t h:i A') }}</p>
            </div>
            <a href="{{ route('audit-logs.index') }}" class="rounded-lg px-4 py-2 text-[13px] font-semibold border border-[#E5E9EF] bg-white text-[#1C2430] hover:bg-[#F4F6F9] transition-colors self-start sm:self-auto">
                &larr; Back to Audit Trail
            </a>
        </div>
    </x-slot>

    <div class="max-w-5xl space-y-6">
        {{-- Summary --}}
        <section aria-labelledby="summary-heading" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6">
            <h2 id="summary-heading" class="sr-only">Summary</h2>
            <dl class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                <div>
                    <dt class="text-[12px] font-semibold text-[#5B6472] mb-1">Performed By</dt>
                    <dd class="text-[14px] font-semibold text-[#1C2430]">{{ $auditLog->actor_name_snapshot ?? $auditLog->actor?->name ?? 'System' }}</dd>
                    <dd class="text-[13px] text-[#5B6472]">{{ $auditLog->actor_role_snapshot ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[12px] font-semibold text-[#5B6472] mb-1">Date &amp; Time</dt>
                    <dd class="text-[14px] font-semibold text-[#1C2430]">{{ $auditLog->created_at?->format('M d, Y') }}</dd>
                    <dd class="text-[13px] text-[#5B6472]">{{ $auditLog->created_at?->format('h:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-[12px] font-semibold text-[#5B6472] mb-1">Reference</dt>
                    <dd class="text-[14px] font-semibold text-[#1C2430] break-all">{{ $auditLog->transaction_reference ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="text-[12px] font-semibold text-[#5B6472] mb-1">Outcome</dt>
                    <dd class="text-[14px] font-semibold {{ $auditLog->status_color_class }}">{{ $auditLog->status_label }}</dd>
                    @if($auditLog->approval_status && $auditLog->approval_status !== 'not_required')
                        <dd class="text-[13px] text-[#5B6472]">
                            {{ \Illuminate\Support\Str::headline($auditLog->approval_status) }}@if($auditLog->approver) by {{ $auditLog->approver->name }}@endif
                        </dd>
                    @endif
                </div>
            </dl>

            @if($auditLog->reason)
                <div class="mt-6 pt-5 border-t border-[#EEF1F5]">
                    <div class="text-[12px] font-semibold text-[#5B6472] mb-1">Reason / Remarks</div>
                    <p class="text-[14px] text-[#1C2430] leading-relaxed">{{ $auditLog->reason }}</p>
                </div>
            @endif
        </section>

        {{-- Changes Recorded --}}
        @php $rows = $auditLog->changeRows(); @endphp
        <section aria-labelledby="changes-heading" class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
            <div class="px-6 py-4 border-b border-[#E5E9EF]">
                <h2 id="changes-heading" class="font-sans text-[15px] font-bold text-gray-900 m-0">
                    {{ $auditLog->hasPreviousValues() ? 'Changes Recorded' : 'Recorded Details' }}
                </h2>
            </div>

            @if(count($rows) > 0)
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#F8FAFC]">
                                <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-6 border-b border-[#E5E9EF] w-1/3">Field</th>
                                @if($auditLog->hasPreviousValues())
                                    <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-6 border-b border-[#E5E9EF]">Before</th>
                                    <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-6 border-b border-[#E5E9EF]">After</th>
                                @else
                                    <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-6 border-b border-[#E5E9EF]">Value</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($rows as $row)
                                <tr>
                                    <th scope="row" class="text-left py-3 px-6 text-[13px] font-semibold text-[#1C2430] border-b border-[#EEF1F5] align-top">
                                        {{ $row['label'] }}
                                    </th>
                                    @if($auditLog->hasPreviousValues())
                                        <td class="py-3 px-6 text-[13px] text-[#5B6472] border-b border-[#EEF1F5] align-top whitespace-pre-line break-words">{{ $row['before'] }}</td>
                                        <td class="py-3 px-6 text-[13px] border-b border-[#EEF1F5] align-top whitespace-pre-line break-words {{ $row['changed'] ? 'font-semibold text-[#0B3B70]' : 'text-[#1C2430]' }}">
                                            {{ $row['after'] }}
                                            @if($row['changed'])<span class="sr-only"> (changed)</span>@endif
                                        </td>
                                    @else
                                        <td class="py-3 px-6 text-[13px] text-[#1C2430] border-b border-[#EEF1F5] align-top whitespace-pre-line break-words">{{ $row['after'] }}</td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="px-6 py-8 text-center text-[13px] text-[#5B6472]">No field-level details were recorded for this activity.</p>
            @endif
        </section>

        {{-- Linked Stock Movements --}}
        @if($linkedMovements->isNotEmpty())
            <section aria-labelledby="movements-heading" class="bg-white border border-[#E5E9EF] rounded-[16px] overflow-hidden">
                <div class="px-6 py-4 border-b border-[#E5E9EF]">
                    <h2 id="movements-heading" class="font-sans text-[15px] font-bold text-gray-900 m-0">Stock Movements</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full border-collapse">
                        <thead>
                            <tr class="bg-[#F8FAFC]">
                                <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-6 border-b border-[#E5E9EF]">Product</th>
                                <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-6 border-b border-[#E5E9EF]">Change</th>
                                <th scope="col" class="text-left text-[12px] font-semibold text-[#5B6472] py-3 px-6 border-b border-[#E5E9EF]">Balance After</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($linkedMovements as $mv)
                                <tr>
                                    <td class="py-3 px-6 text-[13px] font-semibold text-[#1C2430] border-b border-[#EEF1F5]">{{ $mv->product?->name ?? 'Unknown product' }}</td>
                                    <td class="py-3 px-6 text-[13px] border-b border-[#EEF1F5]">
                                        <span class="font-semibold {{ $mv->filled_qty_change >= 0 ? 'text-[#1E8E5A]' : 'text-[#B5504B]' }}">{{ sprintf('%+d', $mv->filled_qty_change) }} filled</span>
                                        @if($mv->empty_qty_change != 0)
                                            <span class="text-[#5B6472]"> &middot; {{ sprintf('%+d', $mv->empty_qty_change) }} empty</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-6 text-[13px] text-[#1C2430] border-b border-[#EEF1F5]">{{ $mv->filled_qty_after }} filled &middot; {{ $mv->empty_qty_after }} empty</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif
    </div>
</x-app-layout>
