@extends('layouts.app')

@section('title', 'Sales Performance Analytics')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-6">
        <h2 class="text-xl font-bold text-slate-900">Sales Performance Analytics</h2>
        <p class="mt-0.5 text-sm text-slate-500">Track revenue trends across your cooperative store</p>
    </div>

    {{-- Time Range Toggles --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="grid grid-cols-2 gap-2 sm:grid-cols-4">
            <button onclick="setTimeRange('daily')" id="dailyBtn" style="background: #16a34a; color: white;" class="cursor-pointer rounded-lg px-4 py-2.5 text-sm font-semibold transition-colors">Daily</button>
            <button onclick="setTimeRange('weekly')" id="weeklyBtn" style="background: #ffffff; color: #64748b;" class="cursor-pointer rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition-colors">Weekly</button>
            <button onclick="setTimeRange('monthly')" id="monthlyBtn" style="background: #ffffff; color: #64748b;" class="cursor-pointer rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition-colors">Monthly</button>
            <button onclick="setTimeRange('yearly')" id="yearlyBtn" style="background: #ffffff; color: #64748b;" class="cursor-pointer rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition-colors">Yearly</button>
        </div>
    </div>

    {{-- Chart Section --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-900">
                <span id="chartTitle">Daily Sales (This Week)</span>
            </h3>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Week Selector for Daily (days within the selected week) -->
                <div id="weekSelectorContainer" style="display: none;">
                    <select id="weekSelector" onchange="handleWeekChange()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <!-- Week options will be populated by JavaScript -->
                    </select>
                </div>
                
                <!-- Month Selector for Weekly (weeks within the selected month) -->
                <div id="monthSelectorContainer" style="display: none;">
                    <select id="monthSelector" onchange="handleMonthChange()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <!-- Month options will be populated by JavaScript -->
                    </select>
                </div>
                
                <!-- Year Selector for Monthly (months within the selected year) -->
                <div id="yearSelectorContainer" style="display: none;">
                    <select id="yearSelector" onchange="handleYearChange()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        <!-- Year options will be populated by JavaScript -->
                    </select>
                </div>
            </div>
        </div>
        <div class="relative h-[400px]">
            <canvas id="salesChart" style="width: 100%; height: 100%;"></canvas>
            <div id="loadingIndicator" style="display: none;" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-sm text-slate-400">Loading...</div>
            <div id="emptyState" style="display: none;" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-center text-sm text-slate-400">
                No sales data yet —<br/>complete a sale in POS to begin
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-lg text-brand-600">
                <i class="fa fa-money"></i>
            </div>
            <div class="min-w-0">
                <div id="totalSalesAmount" class="truncate text-2xl font-bold text-slate-900">₱0.00</div>
                <div class="mt-0.5 text-sm text-slate-500">Total Revenue</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-lg text-sky-600">
                <i class="fa fa-line-chart"></i>
            </div>
            <div class="min-w-0">
                <div id="avgSalesAmount" class="truncate text-2xl font-bold text-slate-900">₱0.00</div>
                <div class="mt-0.5 text-sm text-slate-500">Average Sales</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-lg text-amber-600">
                <i class="fa fa-trophy"></i>
            </div>
            <div class="min-w-0">
                <div id="peakSalesAmount" class="truncate text-2xl font-bold text-slate-900">₱0.00</div>
                <div class="mt-0.5 text-sm text-slate-500">Peak Sales</div>
            </div>
        </div>
    </div>

    {{-- Member vs Non-Member Breakdown --}}
    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-base font-semibold text-slate-900">Member vs Non-Member Sales</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex items-center gap-4 rounded-lg border border-brand-200 bg-brand-50 p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-lg text-brand-700">
                    <i class="fa fa-users"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div id="memberSalesTotal" class="truncate text-xl font-bold text-brand-900">₱0.00</div>
                    <div class="text-xs text-brand-600">Member Sales</div>
                </div>
                <div class="text-right">
                    <div id="memberSalesCount" class="text-sm font-semibold text-brand-700">0</div>
                    <div class="text-xs text-brand-500">transactions</div>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-lg text-amber-700">
                    <i class="fa fa-user-o"></i>
                </div>
                <div class="min-w-0 flex-1">
                    <div id="nonMemberSalesTotal" class="truncate text-xl font-bold text-amber-900">₱0.00</div>
                    <div class="text-xs text-amber-600">Non-Member Sales</div>
                </div>
                <div class="text-right">
                    <div id="nonMemberSalesCount" class="text-sm font-semibold text-amber-700">0</div>
                    <div class="text-xs text-amber-500">transactions</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
let currentPeriod = 'daily';
let salesChart = null;

// Helper function to get week number
Date.prototype.getWeek = function() {
    const d = new Date(Date.UTC(this.getFullYear(), this.getMonth(), this.getDate()));
    const dayNum = d.getUTCDay() || 7;
    d.setUTCDate(d.getUTCDate() + 4 - dayNum);
    const yearStart = new Date(Date.UTC(d.getUTCFullYear(),0,1));
    return Math.ceil((((d - yearStart) / 86400000) + 1)/7);
};

// Format currency
const formatPeso = (n) => '₱' + Number(n).toLocaleString('en-PH', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});

// Update button states
function updateButtonStates(activeRange) {
    const buttons = ['dailyBtn', 'weeklyBtn', 'monthlyBtn', 'yearlyBtn'];
    buttons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (btnId === activeRange + 'Btn') {
            btn.style.background = '#16a34a';
            btn.style.color = 'white';
        } else {
            btn.style.background = '#ffffff';
            btn.style.color = '#64748b';
        }
    });
}

// Fetch data from API
async function fetchSalesData(period, params = {}) {
    try {
        let url = `/api/analytics/sales?period=${period}`;
        
        // Add parameters based on period
        if (period === 'daily' && params.week) {
            url += `&week=${params.week}`;
        } else if (period === 'weekly' && params.month) {
            url += `&month=${params.month}`;
        } else if (period === 'monthly' && params.year) {
            url += `&year=${params.year}`;
        }
        
        console.log(`Fetching data for period: ${period}, params:`, params);
        const response = await fetch(url);
        const data = await response.json();
        console.log('API Response:', data);
        return data;
    } catch (error) {
        console.error('Error fetching sales data:', error);
        return { data: [], total_revenue: 0, average_sales: 0, peak_sales: 0 };
    }
}

// Create or update chart
function updateChart(chartData, type = 'line') {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (salesChart) {
        salesChart.destroy();
    }

    const labels = chartData.map(d => d.label);
    const values = chartData.map(d => d.value);

    salesChart = new Chart(ctx, {
        type: type,
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: '#16a34a',
                backgroundColor: type === 'line' ? 'rgba(22, 163, 74, 0.1)' : 'rgba(22, 163, 74, 0.75)',
                borderWidth: type === 'line' ? 3 : 1,
                maxBarThickness: 48,
                fill: type === 'line',
                tension: 0.4,
                pointBackgroundColor: '#16a34a',
                pointBorderColor: '#16a34a',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'white',
                    borderColor: '#16a34a',
                    borderWidth: 1.5,
                    titleColor: '#334155',
                    bodyColor: '#14532d',
                    padding: 12,
                    displayColors: false,
                    callbacks: {
                        label: function(context) {
                            return formatPeso(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    grid: {
                        color: '#e2e8f0',
                        borderDash: [3, 3]
                    },
                    ticks: {
                        color: '#64748b',
                        font: {
                            size: 12
                        },
                        callback: function(value) {
                            return '₱' + value.toLocaleString();
                        }
                    }
                }
            }
        }
    });
}

// Update KPI cards
function updateKpis(kpis) {
    document.getElementById('totalSalesAmount').textContent = formatPeso(kpis.total);
    document.getElementById('avgSalesAmount').textContent = formatPeso(kpis.average);
    document.getElementById('peakSalesAmount').textContent = formatPeso(kpis.peak);
}

// Fetch and update member vs non-member breakdown
async function fetchCustomerTypeBreakdown(period, params = {}) {
    try {
        let url = `/api/analytics/customer-type?period=${period}`;
        if (period === 'daily' && params.week) url += `&week=${params.week}`;
        else if (period === 'weekly' && params.month) url += `&month=${params.month}`;
        else if (period === 'monthly' && params.year) url += `&year=${params.year}`;

        const response = await fetch(url);
        const data = await response.json();

        document.getElementById('memberSalesTotal').textContent = formatPeso(data.member_total);
        document.getElementById('memberSalesCount').textContent = data.member_count;
        document.getElementById('nonMemberSalesTotal').textContent = formatPeso(data.non_member_total);
        document.getElementById('nonMemberSalesCount').textContent = data.non_member_count;
    } catch (error) {
        console.error('Error fetching customer type breakdown:', error);
    }
}

// Initialize week selector
function initializeWeekSelector() {
    const selector = document.getElementById('weekSelector');
    const now = new Date();
    const currentWeek = now.getFullYear() + '-' + String(now.getWeek()).padStart(2, '0');
    
    // Generate week options for the last 12 weeks
    for (let i = 11; i >= 0; i--) {
        const date = new Date(now.getFullYear(), now.getMonth(), now.getDate() - (i * 7));
        const weekNum = date.getWeek();
        const year = date.getFullYear();
        const value = year + '-' + String(weekNum).padStart(2, '0');
        const label = `Week ${weekNum} (${year})`;
        
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        if (value === currentWeek) {
            option.selected = true;
        }
        selector.appendChild(option);
    }
}

// Handle week change (Daily tab: days within the selected week)
async function handleWeekChange() {
    const selectedWeek = document.getElementById('weekSelector').value;
    const [year, week] = selectedWeek.split('-');
    document.getElementById('chartTitle').textContent = `Daily Sales (Week ${week}, ${year})`;
    
    await loadDataWithParams('daily', { week: selectedWeek });
}

// Initialize month selector
function initializeMonthSelector() {
    const selector = document.getElementById('monthSelector');
    const now = new Date();
    const currentMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
    
    // Generate month options for the last 12 months
    for (let i = 0; i < 12; i++) {
        const date = new Date(now.getFullYear(), now.getMonth() - i, 1);
        const value = date.getFullYear() + '-' + String(date.getMonth() + 1).padStart(2, '0');
        const label = date.toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
        
        const option = document.createElement('option');
        option.value = value;
        option.textContent = label;
        if (value === currentMonth) {
            option.selected = true;
        }
        selector.appendChild(option);
    }
}

// Handle month change (Weekly tab: weeks within the selected month)
async function handleMonthChange() {
    const selectedMonth = document.getElementById('monthSelector').value;
    const monthName = new Date(selectedMonth + '-01').toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
    document.getElementById('chartTitle').textContent = `Weekly Sales (${monthName})`;
    
    await loadDataWithParams('weekly', { month: selectedMonth });
}

// Initialize year selector
function initializeYearSelector() {
    const selector = document.getElementById('yearSelector');
    const currentYear = new Date().getFullYear();
    
    // Generate year options for the last 5 years
    for (let i = 0; i < 5; i++) {
        const year = currentYear - i;
        const option = document.createElement('option');
        option.value = year;
        option.textContent = year;
        if (year === currentYear) {
            option.selected = true;
        }
        selector.appendChild(option);
    }
}

// Handle year change (Monthly tab: months within the selected year)
async function handleYearChange() {
    const selectedYear = document.getElementById('yearSelector').value;
    document.getElementById('chartTitle').textContent = `Monthly Sales (${selectedYear})`;
    
    await loadDataWithParams('monthly', { year: selectedYear });
}

// Load yearly data (Yearly tab: annual totals for all years)
async function loadYearlyData() {
    document.getElementById('chartTitle').textContent = 'Yearly Sales (All Years)';
    
    await loadDataWithParams('yearly');
}

// Helper function to load data with parameters
async function loadDataWithParams(period, params) {
    // Show loading
    document.getElementById('loadingIndicator').style.display = 'block';
    document.getElementById('emptyState').style.display = 'none';
    
    // Fetch data
    const data = await fetchSalesData(period, params);
    
    // Hide loading
    document.getElementById('loadingIndicator').style.display = 'none';
    
    // Check if empty
    const isEmpty = data.data && data.data.every(d => d.value === 0);
    if (isEmpty) {
        document.getElementById('emptyState').style.display = 'block';
        if (salesChart) {
            salesChart.destroy();
            salesChart = null;
        }
    } else {
        document.getElementById('emptyState').style.display = 'none';
        try {
            updateChart(data.data || [], 'line');
        } catch (error) {
            console.error('Error rendering chart:', error);
        }
    }
    
    // Update KPIs
    updateKpis({
        total: data.total_revenue || 0,
        average: data.average_sales || 0,
        peak: data.peak_sales || 0
    });

    // Update member vs non-member breakdown
    const breakdownParams = { ...params };
    await fetchCustomerTypeBreakdown(period, breakdownParams);
}

// Main function to set time range and update everything
async function setTimeRange(range) {
    currentPeriod = range;
    
    // Update UI
    updateButtonStates(range);
    
    // Hide all selectors first
    document.getElementById('weekSelectorContainer').style.display = 'none';
    document.getElementById('monthSelectorContainer').style.display = 'none';
    document.getElementById('yearSelectorContainer').style.display = 'none';
    
    // Show appropriate selector and initialize if needed
    if (range === 'daily') {
        // Daily: days within the selected week -> week selector
        document.getElementById('weekSelectorContainer').style.display = 'block';
        if (!document.getElementById('weekSelector').options.length) {
            initializeWeekSelector();
        }
        await handleWeekChange();
        return;
    } else if (range === 'weekly') {
        // Weekly: weeks within the selected month -> month selector
        document.getElementById('monthSelectorContainer').style.display = 'block';
        if (!document.getElementById('monthSelector').options.length) {
            initializeMonthSelector();
        }
        await handleMonthChange();
        return;
    } else if (range === 'monthly') {
        // Monthly: months within the selected year -> year selector
        document.getElementById('yearSelectorContainer').style.display = 'block';
        if (!document.getElementById('yearSelector').options.length) {
            initializeYearSelector();
        }
        await handleYearChange();
        return;
    } else if (range === 'yearly') {
        // Yearly: annual totals for all years -> no selector
        await loadYearlyData();
        return;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    console.log('Analytics page loaded, initializing...');
    setTimeRange('daily');
});
</script>
@endsection
