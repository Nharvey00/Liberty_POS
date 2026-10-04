<x-app-layout>
    <x-slot name="header">
        <h1 class="font-sans text-[19px] font-bold text-gray-900 m-0">Reports &amp; Analytics Hub</h1>
        <div class="text-[12.5px] text-[#5B6472] mt-[2px]">Executive reporting, inventory movements, credit summaries, and tax metrics</div>
    </x-slot>

    <!-- Sub-Navigation Tabs -->
    <div class="flex items-center gap-1.5 bg-[#E5E9EF]/60 p-1 rounded-xl mb-6 flex-wrap print:hidden">
        <a href="{{ route('reports.index') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold bg-white text-[#0B3B70] shadow-xs">Overview Hub</a>
        <a href="{{ route('reports.sales') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Sales Report</a>
        <a href="{{ route('reports.inventory') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Inventory Report</a>
        <a href="{{ route('reports.utang') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Utang (Credit) Report</a>
        <a href="{{ route('reports.discounts') }}" class="px-3.5 py-1.5 rounded-lg text-[12.5px] font-bold text-[#5B6472] hover:text-[#1C2430]">Discounts Summary</a>
    </div>

    <!-- Hub Cards Grid -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Sales Report -->
        <a href="{{ route('reports.sales') }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md hover:border-[#0B3B70] transition-all group">
            <div class="flex items-start">
                <div class="p-3.5 rounded-xl bg-[#EBF4FF] text-[#0B3B70] group-hover:bg-[#0B3B70] group-hover:text-white transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="font-sans text-[16px] font-bold text-gray-900 group-hover:text-[#0B3B70]">Sales Report</h3>
                        <span class="text-[12px] text-[#5D89B0] font-bold group-hover:translate-x-1 transition-transform">Open →</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Track revenue, 12% VAT calculations, daily transactions, discounts, and cashier performance over any date range.</p>
                </div>
            </div>
        </a>

        <!-- Inventory Report -->
        <a href="{{ route('reports.inventory') }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md hover:border-[#0B3B70] transition-all group">
            <div class="flex items-start">
                <div class="p-3.5 rounded-xl bg-[#EBF4FF] text-[#0B3B70] group-hover:bg-[#0B3B70] group-hover:text-white transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="font-sans text-[16px] font-bold text-gray-900 group-hover:text-[#0B3B70]">Inventory Report</h3>
                        <span class="text-[12px] text-[#5D89B0] font-bold group-hover:translate-x-1 transition-transform">Open →</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Audit stock movements, supplier restocks (Stock In), manual deductions (Stock Out), filled tank sales, and empty shell turnover.</p>
                </div>
            </div>
        </a>

        <!-- Utang Report -->
        <a href="{{ route('reports.utang') }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md hover:border-[#0B3B70] transition-all group">
            <div class="flex items-start">
                <div class="p-3.5 rounded-xl bg-[#EBF4FF] text-[#0B3B70] group-hover:bg-[#0B3B70] group-hover:text-white transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="font-sans text-[16px] font-bold text-gray-900 group-hover:text-[#0B3B70]">Utang (Credit) Report</h3>
                        <span class="text-[12px] text-[#5D89B0] font-bold group-hover:translate-x-1 transition-transform">Open →</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Audit receivables, customer credit balances, total charges vs collections, and accounts across all client classifications.</p>
                </div>
            </div>
        </a>

        <!-- Discounts Report -->
        <a href="{{ route('reports.discounts') }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md hover:border-[#0B3B70] transition-all group">
            <div class="flex items-start">
                <div class="p-3.5 rounded-xl bg-[#EBF4FF] text-[#0B3B70] group-hover:bg-[#0B3B70] group-hover:text-white transition-colors shrink-0">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.391.562l8 8a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-8-8A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                    </svg>
                </div>
                <div class="ml-4 flex-1">
                    <div class="flex items-center justify-between">
                        <h3 class="font-sans text-[16px] font-bold text-gray-900 group-hover:text-[#0B3B70]">Discounts Report</h3>
                        <span class="text-[12px] text-[#5D89B0] font-bold group-hover:translate-x-1 transition-transform">Open →</span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1">Monthly summary of discounts provided, Senior Citizen ID compliance, PWD privileges, and loan transactions per customer.</p>
                </div>
            </div>
        </a>
    </div>

    <style>
        @media print {
            nav, aside, header button, .print\:hidden, .no-print, button, form, input, select {
                display: none !important;
            }
            body {
                background: #ffffff !important;
                color: #000000 !important;
                font-size: 11pt !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            main {
                padding: 0 !important;
                margin: 0 !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .bg-white, .border, .rounded-\[16px\], .rounded-lg, .shadow-sm, .shadow-xs {
                box-shadow: none !important;
                border-radius: 0 !important;
                border: 1px solid #000000 !important;
            }
        }
    </style>
</x-app-layout>
