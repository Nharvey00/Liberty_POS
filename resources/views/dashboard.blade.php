<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row md:items-center justify-between w-full">
            <div>
                <h1 class="font-['Inter'] text-[24px] font-bold text-[#0F1D3A] m-0">Dashboard</h1>
                <div class="text-[13px] text-[#5B6B88] mt-1">Real-time view of your branch</div>
            </div>
            
            <div class="flex items-center gap-3 mt-4 md:mt-0">
                
                <button class="bg-white border border-[#E5E7EB] rounded-lg px-4 py-2 text-[13px] font-semibold text-[#0F1D3A] shadow-sm hover:bg-gray-50 transition-colors">
                    Sync to Excel
                </button>
            </div>
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
            <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Today's sales</div>
            <div class="flex items-end justify-between mt-1">
                <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">&#8369;{{ number_format($todaySalesAmount, 2) }}</div>
                <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#E2F4EA] text-[#1A8A4F] flex items-center gap-1">
                    &#9650; 12% vs yesterday
                </div>
            </div>
        </div>

        <div class="bg-white border border-[#E5E7EB] rounded-[14px] p-5 shadow-sm">
            <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Cylinders in stock</div>
            <div class="flex items-end justify-between mt-1">
                <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">1,284</div>
                <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#FBE5E2] text-[#C0392B] flex items-center gap-1">
                    &#9660; {{ $lowStockProducts->count() }} items low
                </div>
            </div>
        </div>

        <div class="bg-white border border-[#E5E7EB] rounded-[14px] p-5 shadow-sm">
            <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Net income (MTD)</div>
            <div class="flex items-end justify-between mt-1">
                <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">&#8369;312,900</div>
                <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#E2F4EA] text-[#1A8A4F] flex items-center gap-1">
                    &#9650; 8% vs last month
                </div>
            </div>
        </div>

        <div class="bg-white border border-[#E5E7EB] rounded-[14px] p-5 shadow-sm">
            <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Loyal customers</div>
            <div class="flex items-end justify-between mt-1">
                <div class="font-['Inter'] text-[26px] font-bold text-[#0F1D3A] leading-none">{{ number_format($totalCustomers) }}</div>
                <div class="text-[11px] font-semibold px-2 py-0.5 rounded-full bg-[#E2F4EA] text-[#1A8A4F] flex items-center gap-1">
                    +4 this month
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-5">
        <a href="{{ route('pos.create') }}" class="{{ Auth::user()->isManagerOrOwner() ? 'md:col-span-2' : 'md:col-span-4' }} relative overflow-hidden rounded-[16px] text-left flex flex-col justify-end p-6 cursor-pointer text-white bg-gradient-to-br from-[#0A1128] to-[#0D2A5C] border border-[#0A1730] shadow-md hover:shadow-lg transition-shadow min-h-[160px] no-underline">
            <div class="w-[42px] h-[42px] rounded-xl flex items-center justify-center bg-white/10 text-[#8FB0E6] mb-auto">
                <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="9" cy="20" r="2"/><circle cx="20" cy="20" r="2"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
            </div>
            <div>
                <div class="font-['Inter'] text-[20px] font-bold">+ New Sale</div>
                <div class="text-[13px] text-[#9FB6DE] mt-1 font-medium">Walk-in or delivery &mdash; record it in seconds</div>
            </div>
        </a>

        @if(Auth::user()->isManagerOrOwner())
            <a href="{{ route('stock-ins.create') }}" class="relative overflow-hidden rounded-[16px] text-left flex flex-col justify-end p-6 cursor-pointer text-white bg-gradient-to-br from-[#0A1128] to-[#0D2A5C] border border-[#0A1730] shadow-md hover:shadow-lg transition-shadow min-h-[160px] no-underline">
                <div class="w-[42px] h-[42px] rounded-xl flex items-center justify-center bg-white/10 text-[#8FB0E6] mb-auto">
                    <svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                </div>
                <div>
                    <div class="font-['Inter'] text-[18px] font-bold">Stock In</div>
                    <div class="text-[13px] text-[#9FB6DE] mt-1 font-medium">Restock across branches</div>
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
                <div id="chart-subtitle" class="text-[13px] text-[#5B6B88] mt-1">Last 7 days</div>
            </div>
            <div class="flex bg-[#EAF0F9] p-1 rounded-xl">
                <button id="btn-weekly" class="chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold bg-[#0D2A5C] text-white shadow-sm transition-colors">Weekly</button>
                <button id="btn-monthly" class="chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A] transition-colors">Monthly</button>
                <button id="btn-quarterly" class="chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A] transition-colors">Quarterly</button>
            </div>
        </div>

        <div class="flex gap-10 mb-8 mt-6">
            <div>
                <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Total sales</div>
                <div class="font-['Inter'] text-[20px] font-bold text-[#0F1D3A]">&#8369;<span id="stat-total">0</span></div>
            </div>
            <div>
                <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Daily average</div>
                <div class="font-['Inter'] text-[20px] font-bold text-[#0F1D3A]">&#8369;<span id="stat-avg">0</span></div>
            </div>
            <div>
                <div class="text-[12.5px] text-[#5B6B88] font-medium mb-1">Best &middot; <span id="stat-best-label">Sun</span></div>
                <div class="font-['Inter'] text-[20px] font-bold text-[#0F1D3A]">&#8369;<span id="stat-best">0</span></div>
            </div>
        </div>

        <div class="relative h-[250px] w-full"><canvas id="salesChart"></canvas></div></div></div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const chartData = @json($chartData);
    
    const ctx = document.getElementById('salesChart').getContext('2d');
    let salesChart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [],
            datasets: [{
                label: 'Sales (₱)',
                data: [],
                backgroundColor: '#4B7FC0',
                hoverBackgroundColor: '#0D2A5C',
                borderRadius: { topLeft: 6, topRight: 6 },
                barPercentage: 0.6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: '#E5E7EB', borderDash: [5, 5], drawBorder: false },
                    ticks: {
                        color: '#5B6B88',
                        font: { size: 11, family: 'Inter' },
                        callback: function(value) { return '\u20B1' + (value >= 1000 ? (value/1000) + 'k' : value); }
                    }
                },
                x: {
                    grid: { display: false, drawBorder: true, borderColor: '#E5E7EB' },
                    ticks: { color: '#5B6B88', font: { size: 12.5, family: 'Inter' } }
                }
            },
            plugins: {
                legend: { display: false },
                tooltip: {
                    backgroundColor: '#0F1D3A',
                    titleFont: { size: 13, family: 'Inter' },
                    bodyFont: { size: 14, weight: 'bold', family: 'Inter' },
                    callbacks: {
                        label: function(context) { return '\u20B1' + context.parsed.y.toLocaleString(); }
                    }
                }
            }
        }
    });

    const btnWeekly = document.getElementById('btn-weekly');
    const btnMonthly = document.getElementById('btn-monthly');
    const btnQuarterly = document.getElementById('btn-quarterly');
    
    function formatMoney(n) {
        return Number(n).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    }

    function updateChart(period, btnElement, subtitle) {
        // Update Buttons
        [btnWeekly, btnMonthly, btnQuarterly].forEach(btn => {
            btn.className = 'chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A] transition-colors';
        });
        btnElement.className = 'chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold bg-[#0D2A5C] text-white shadow-sm transition-colors';
        
        // Update Data
        const data = chartData[period];
        salesChart.data.labels = data.labels;
        salesChart.data.datasets[0].data = data.data;
        salesChart.update();
        
        // Update Stats
        document.getElementById('stat-total').innerText = formatMoney(data.total);
        document.getElementById('stat-avg').innerText = formatMoney(data.avg);
        document.getElementById('stat-best').innerText = formatMoney(data.best);
        document.getElementById('stat-best-label').innerText = data.bestLabel;
        document.getElementById('chart-subtitle').innerText = subtitle;
    }

    btnWeekly.addEventListener('click', () => updateChart('weekly', btnWeekly, 'Last 7 days'));
    btnMonthly.addEventListener('click', () => updateChart('monthly', btnMonthly, 'Last 6 months'));
    btnQuarterly.addEventListener('click', () => updateChart('quarterly', btnQuarterly, 'Last 4 quarters'));

    // Init
    updateChart('weekly', btnWeekly, 'Last 7 days');
});
</script>
</x-app-layout>
