<?php

$file = __DIR__ . '/resources/views/dashboard.blade.php';
$content = file_get_contents($file);

// Replace buttons so they have IDs
$content = str_replace(
    '<button class="px-4 py-1.5 rounded-lg text-[13px] font-semibold bg-[#0D2A5C] text-white shadow-sm">Weekly</button>',
    '<button id="btn-weekly" class="chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold bg-[#0D2A5C] text-white shadow-sm transition-colors">Weekly</button>',
    $content
);
$content = str_replace(
    '<button class="px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A]">Monthly</button>',
    '<button id="btn-monthly" class="chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A] transition-colors">Monthly</button>',
    $content
);
$content = str_replace(
    '<button class="px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A]">Quarterly</button>',
    '<button id="btn-quarterly" class="chart-btn px-4 py-1.5 rounded-lg text-[13px] font-semibold text-[#5B6B88] hover:text-[#0F1D3A] transition-colors">Quarterly</button>',
    $content
);

// Add spans with IDs for dynamic text replacement
$content = preg_replace(
    "/<div class=\"font-\['Inter'\] text-\[20px\] font-bold text-\[#0F1D3A\]\">&#8369;330,050<\/div>/",
    "<div class=\"font-['Inter'] text-[20px] font-bold text-[#0F1D3A]\">&#8369;<span id=\"stat-total\">0</span></div>",
    $content
);
$content = preg_replace(
    "/<div class=\"font-\['Inter'\] text-\[20px\] font-bold text-\[#0F1D3A\]\">&#8369;47,150<\/div>/",
    "<div class=\"font-['Inter'] text-[20px] font-bold text-[#0F1D3A]\">&#8369;<span id=\"stat-avg\">0</span></div>",
    $content
);
$content = preg_replace(
    "/<div class=\"text-\[12.5px\] text-\[#5B6B88\] font-medium mb-1\">Best day &middot; Sun<\/div>/",
    "<div class=\"text-[12.5px] text-[#5B6B88] font-medium mb-1\">Best &middot; <span id=\"stat-best-label\">Sun</span></div>",
    $content
);
$content = preg_replace(
    "/<div class=\"font-\['Inter'\] text-\[20px\] font-bold text-\[#0F1D3A\]\">&#8369;61,200<\/div>/",
    "<div class=\"font-['Inter'] text-[20px] font-bold text-[#0F1D3A]\">&#8369;<span id=\"stat-best\">0</span></div>",
    $content
);
$content = preg_replace(
    "/<div class=\"text-\[13px\] text-\[#5B6B88\] mt-1\">Last 7 days &middot; Sep 25 &ndash; Oct 1<\/div>/",
    "<div id=\"chart-subtitle\" class=\"text-[13px] text-[#5B6B88] mt-1\">Last 7 days</div>",
    $content
);

// Replace hardcoded bars with canvas
$barsStart = strpos($content, '<div class="h-[250px] flex items-end justify-between gap-2 px-2 relative border-b border-[#E5E7EB]">');
$barsEnd = strpos($content, '</div>', strpos($content, '<!-- Chart Area -->')) + 6; // Just need to cut to the end of the chart div, let's be careful.

// We will use regex to replace everything from the <div class="h-[250px]... to the end of that div (which is followed by </div> </div> </x-app-layout>)
$content = preg_replace('/<div class="h-\[250px\].*?<\/div>\s*<\/div>\s*<\/div>/s', '<div class="relative h-[250px] w-full"><canvas id="salesChart"></canvas></div></div></div>', $content);

// Add Chart.js script
$script = <<<'EOD'

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
                        callback: function(value) { return '₱' + (value >= 1000 ? (value/1000) + 'k' : value); }
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
                        label: function(context) { return '₱' + context.parsed.y.toLocaleString(); }
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
EOD;

$content = str_replace('</x-app-layout>', $script, $content);
file_put_contents($file, $content);
echo "Chart updated successfully!";
