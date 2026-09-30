<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="font-['Manrope'] text-[19px] font-extrabold text-gray-800">
                        Reports
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">Select a report to view analytics and metrics.</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-2 gap-6">
                <!-- Sales Report -->
                <a href="{{ route('reports.sales') ?? '#' }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-[#E5F5EC] text-[#1E8E5A]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="font-['Manrope'] text-[16px] font-bold text-gray-800">Sales Report</h3>
                            <p class="text-sm text-gray-500 mt-1">View revenue, VAT, and discounts over time.</p>
                        </div>
                    </div>
                </a>

                <!-- Inventory Report -->
                <a href="{{ route('reports.inventory') ?? '#' }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-[#EBF4FF] text-[#0B3B70]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="font-['Manrope'] text-[16px] font-bold text-gray-800">Inventory Report</h3>
                            <p class="text-sm text-gray-500 mt-1">Track stock levels, stock ins, and sales movements.</p>
                        </div>
                    </div>
                </a>

                <!-- Utang Report -->
                <a href="{{ route('reports.utang') ?? '#' }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-[#FBF0DD] text-[#B4700A]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="font-['Manrope'] text-[16px] font-bold text-gray-800">Utang (Credit) Report</h3>
                            <p class="text-sm text-gray-500 mt-1">Monitor credit accounts, balances, and payments.</p>
                        </div>
                    </div>
                </a>

                <!-- Discounts Report -->
                <a href="{{ route('reports.discounts') ?? '#' }}" class="bg-white border border-[#E5E9EF] rounded-[16px] p-6 hover:shadow-md transition-shadow">
                    <div class="flex items-center">
                        <div class="p-3 rounded-full bg-[#F7E9E8] text-[#B5504B]">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.391.562l8 8a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-8-8A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div class="ml-4 flex-1">
                            <h3 class="font-['Manrope'] text-[16px] font-bold text-gray-800">Discounts Report</h3>
                            <p class="text-sm text-gray-500 mt-1">Summary of discounts given to customers.</p>
                        </div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
