@extends('layouts.app')

@section('title', 'Sales Performance Analytics')

@section('content')
<div class="mx-auto max-w-6xl">
    <div class="mb-6">
        <div class="flex items-center gap-2">
            <h2 class="text-xl font-bold text-slate-900">Sales Performance Analytics</h2>
            @unless(auth()->user()->isAdmin())
                <x-badge color="blue">View only</x-badge>
            @endunless
        </div>
        <p class="mt-0.5 text-sm text-slate-500">Track revenue trends across your cooperative store</p>
        @include('analytics._reports-dialog')
    </div>

    {{-- Time Range Toggles --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-3 shadow-sm">
        <div class="flex gap-2 overflow-x-auto whitespace-nowrap pb-1">
            <button type="button" onclick="setTimeRange('daily')" id="dailyBtn" aria-pressed="true" class="inline-flex min-w-[6.5rem] flex-1 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-brand-600 px-4 py-2.5 text-sm font-semibold text-white transition-colors hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-1">Daily</button>
            <button type="button" onclick="setTimeRange('weekly')" id="weeklyBtn" aria-pressed="false" class="inline-flex min-w-[6.5rem] flex-1 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-1">Weekly</button>
            <button type="button" onclick="setTimeRange('monthly')" id="monthlyBtn" aria-pressed="false" class="inline-flex min-w-[6.5rem] flex-1 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-1">Monthly</button>
            <button type="button" onclick="setTimeRange('quarterly')" id="quarterlyBtn" aria-pressed="false" class="inline-flex min-w-[6.5rem] flex-1 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-1">Quarterly</button>
            <button type="button" onclick="setTimeRange('yearly')" id="yearlyBtn" aria-pressed="false" class="inline-flex min-w-[6.5rem] flex-1 shrink-0 cursor-pointer items-center justify-center rounded-lg bg-white px-4 py-2.5 text-sm font-semibold text-slate-500 transition-colors hover:bg-slate-50 hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-1">Yearly</button>
        </div>
    </div>

    {{-- Chart Section --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
            <h3 class="text-base font-semibold text-slate-900">
                <span id="chartTitle">Hourly Sales ({{ now('Asia/Manila')->format('D, M j, Y') }})</span>
            </h3>
            <div class="flex flex-wrap items-center gap-2">
                <!-- Date Selector for Daily (hourly breakdown for one day) -->
                <div id="dateSelectorContainer" class="flex items-center gap-2">
                    <button type="button" id="prevDayBtn" onclick="changeDay(-1)" aria-label="Previous day" class="flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition-colors hover:bg-slate-50">
                        <x-icon name="arrow-left" class="h-3 w-3" />
                    </button>
                    <input type="date" id="dateSelector" value="{{ now('Asia/Manila')->format('Y-m-d') }}" max="{{ now('Asia/Manila')->format('Y-m-d') }}" onchange="handleDateChange()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                    <button type="button" id="nextDayBtn" onclick="changeDay(1)" aria-label="Next day" class="flex h-9 w-9 shrink-0 cursor-pointer items-center justify-center rounded-lg border border-slate-300 bg-white text-slate-600 transition-colors hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-40">
                        <x-icon name="chevron-right" class="h-3 w-3" />
                    </button>
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

                <!-- Year Selector for Quarterly (quarters within the selected year) -->
                <div id="quarterYearSelectorContainer" style="display: none;">
                    <select id="quarterYearSelector" onchange="handleQuarterYearChange()" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30">
                        @foreach($quarterYears as $year)
                            <option value="{{ $year }}" @selected($year === now('Asia/Manila')->year)>{{ $year }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>
        <div class="relative h-[400px]">
            <canvas id="salesChart" class="block h-full w-full"></canvas>
            <div id="loadingIndicator" style="display: none;" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-sm text-slate-400">Loading...</div>
            <div id="emptyState" style="display: none;" class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 text-center text-sm text-slate-400">
                <span id="emptyStateText">No sales data yet —<br/>complete a sale in POS to begin</span>
            </div>
        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-lg text-brand-600">
                <x-icon name="money" class="h-4.5 w-4.5" />
            </div>
            <div class="min-w-0">
                <div id="totalSalesAmount" class="truncate text-2xl font-bold text-slate-900">&#8369;0.00</div>
                <div class="mt-0.5 text-sm text-slate-500">Total Revenue</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-violet-50 text-lg text-violet-600">
                <x-icon name="credit-card" class="h-4.5 w-4.5" />
            </div>
            <div class="min-w-0">
                <div id="creditSalesAmount" class="truncate text-2xl font-bold text-slate-900">&#8369;0.00</div>
                <div class="mt-0.5 text-sm text-slate-500">Credit Sales</div>
                <div id="creditSalesCount" class="mt-0.5 text-xs text-slate-400">0 transactions</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-lg text-amber-600">
                <x-icon name="trophy" class="h-4.5 w-4.5" />
            </div>
            <div class="min-w-0">
                <div id="peakSalesAmount" class="truncate text-2xl font-bold text-slate-900">&#8369;0.00</div>
                <div id="peakSalesLabel" class="mt-0.5 text-sm text-slate-500">Peak Sales</div>
            </div>
        </div>
    </div>

    {{-- Member vs Non-Member Breakdown --}}
    <div class="mt-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <h3 class="mb-4 text-base font-semibold text-slate-900">Member vs Non-Member Sales</h3>
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <div class="flex items-center gap-4 rounded-lg border border-brand-200 bg-brand-50 p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-brand-100 text-lg text-brand-700">
                    <x-icon name="users" class="h-4.5 w-4.5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div id="memberSalesTotal" class="truncate text-xl font-bold text-brand-900">&#8369;0.00</div>
                    <div class="text-xs text-brand-600">Member Sales</div>
                </div>
                <div class="text-right">
                    <div id="memberSalesCount" class="text-sm font-semibold text-brand-700">0</div>
                    <div class="text-xs text-brand-500">transactions</div>
                </div>
            </div>
            <div class="flex items-center gap-4 rounded-lg border border-amber-200 bg-amber-50 p-4">
                <div class="flex h-11 w-11 shrink-0 items-center justify-center rounded-lg bg-amber-100 text-lg text-amber-700">
                    <x-icon name="user-o" class="h-4.5 w-4.5" />
                </div>
                <div class="min-w-0 flex-1">
                    <div id="nonMemberSalesTotal" class="truncate text-xl font-bold text-amber-900">&#8369;0.00</div>
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
const APP_TODAY = '{{ now('Asia/Manila')->format('Y-m-d') }}';
const DAILY_OPENING_HOUR = 6;
const DAILY_CLOSING_HOUR = 20; // inclusive

const DEFAULT_EMPTY_HTML = document.getElementById('emptyStateText').innerHTML;

let currentPeriod = 'daily';
let salesChart = null;

// Format currency
const pesoFormatter = new Intl.NumberFormat('en-PH', {
    style: 'currency',
    currency: 'PHP',
    minimumFractionDigits: 2,
    maximumFractionDigits: 2
});
const formatPeso = (n) => pesoFormatter.format(Number(n || 0));

const formatHour = (hour) => {
    const suffix = hour < 12 ? 'AM' : 'PM';
    const display = hour % 12 === 0 ? 12 : hour % 12;
    return `${display}:00 ${suffix}`;
};

const formatHourRange = (hour) => `${formatHour(hour)} – ${formatHour(hour).replace(':00', ':59')}`;

const formatDisplayDate = (dateStr) => {
    const d = new Date(`${dateStr}T00:00:00`);
    return d.toLocaleDateString('en-US', { weekday: 'short', month: 'short', day: 'numeric', year: 'numeric' });
};

// Update button states
function updateButtonStates(activeRange) {
    const buttons = ['dailyBtn', 'weeklyBtn', 'monthlyBtn', 'quarterlyBtn', 'yearlyBtn'];
    buttons.forEach(btnId => {
        const btn = document.getElementById(btnId);
        if (!btn) return;

        const isActive = btnId === activeRange + 'Btn';
        btn.classList.toggle('bg-brand-600', isActive);
        btn.classList.toggle('text-white', isActive);
        btn.classList.toggle('hover:bg-brand-700', isActive);
        btn.classList.toggle('bg-white', !isActive);
        btn.classList.toggle('text-slate-500', !isActive);
        btn.classList.toggle('hover:bg-slate-50', !isActive);
        btn.classList.toggle('hover:text-slate-700', !isActive);
        btn.setAttribute('aria-pressed', String(isActive));
    });
}

// Fetch data from API
async function fetchSalesData(period, params = {}) {
    try {
        let url = `/api/analytics/sales?period=${period}`;
        
        // Add parameters based on period
        if (period === 'daily' && params.date) {
            url += `&date=${params.date}`;
        } else if (period === 'weekly' && params.month) {
            url += `&month=${params.month}`;
        } else if ((period === 'monthly' || period === 'quarterly') && params.year) {
            url += `&year=${params.year}`;
        }

        const response = await fetch(url);
        return await response.json();
    } catch (error) {
        console.error('Error fetching sales data:', error);
        return { data: [], total_revenue: 0, credit_sales: 0, credit_count: 0, peak_sales: 0 };
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
    const isDaily = currentPeriod === 'daily';

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
                cubicInterpolationMode: 'monotone',
                pointBackgroundColor: '#16a34a',
                pointBorderColor: '#16a34a',
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            layout: {
                padding: {
                    left: 12,
                    right: 12,
                    top: 8,
                    bottom: 4
                }
            },
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
                    callbacks: isDaily ? {
                        title: () => '',
                        label: function(context) {
                            const row = chartData[context.dataIndex];
                            return row && row.hour !== undefined
                                ? `${formatHourRange(row.hour)}: ${formatPeso(context.parsed.y)}`
                                : formatPeso(context.parsed.y);
                        }
                    } : {
                        label: function(context) {
                            return formatPeso(context.parsed.y);
                        }
                    }
                }
            },
            scales: {
                x: {
                    offset: true,
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
                    beginAtZero: true,
                    min: 0,
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
                            return formatPeso(value);
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
    document.getElementById('creditSalesAmount').textContent = formatPeso(kpis.creditSales);

    const creditCount = Number(kpis.creditCount || 0);
    document.getElementById('creditSalesCount').textContent =
        `${creditCount.toLocaleString('en-PH')} transaction${creditCount === 1 ? '' : 's'}`;

    document.getElementById('peakSalesAmount').textContent = formatPeso(kpis.peak);

    const isDaily = currentPeriod === 'daily';
    const isWeekly = currentPeriod === 'weekly';
    const peakHasLabel = (isDaily || isWeekly || currentPeriod === 'quarterly') && kpis.peakLabel;
    document.getElementById('peakSalesLabel').textContent =
        peakHasLabel ? `Peak Sales · ${kpis.peakLabel}` : 'Peak Sales';
}

// Fetch and update member vs non-member breakdown
async function fetchCustomerTypeBreakdown(period, params = {}) {
    try {
        let url = `/api/analytics/customer-type?period=${period}`;
        if (period === 'daily' && params.date) url += `&date=${params.date}`;
        else if (period === 'weekly' && params.month) url += `&month=${params.month}`;
        else if ((period === 'monthly' || period === 'quarterly') && params.year) url += `&year=${params.year}`;

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

// Keep the day navigation in sync with today
function updateDayNav() {
    const input = document.getElementById('dateSelector');
    document.getElementById('nextDayBtn').disabled = (input.value || APP_TODAY) >= APP_TODAY;
}

// Handle date change (Daily tab: hourly breakdown for one day)
async function handleDateChange() {
    const input = document.getElementById('dateSelector');
    let value = input.value || APP_TODAY;

    if (value > APP_TODAY) {
        value = APP_TODAY;
    }

    input.value = value;
    updateDayNav();
    document.getElementById('chartTitle').textContent = `Hourly Sales (${formatDisplayDate(value)})`;

    await loadDataWithParams('daily', { date: value });
}

// Move the selected day backwards or forwards
function changeDay(delta) {
    const input = document.getElementById('dateSelector');
    const base = new Date(`${input.value || APP_TODAY}T00:00:00`);
    base.setDate(base.getDate() + delta);

    const next = [
        base.getFullYear(),
        String(base.getMonth() + 1).padStart(2, '0'),
        String(base.getDate()).padStart(2, '0')
    ].join('-');

    if (next > APP_TODAY) {
        return;
    }

    input.value = next;
    handleDateChange();
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

// Handle year change (Quarterly tab: quarters within the selected year)
async function handleQuarterYearChange() {
    const selectedYear = document.getElementById('quarterYearSelector').value;
    document.getElementById('chartTitle').textContent = `Quarterly Sales (${selectedYear})`;

    await loadDataWithParams('quarterly', { year: selectedYear });
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

    const isDaily = period === 'daily';
    const rawData = data.data || [];
    const isEmpty = rawData.length === 0 || rawData.every(d => Number(d.value) === 0);
    const chartData = isDaily
        ? rawData.filter(d => d.hour >= DAILY_OPENING_HOUR && d.hour <= DAILY_CLOSING_HOUR)
        : rawData;

    // Check if empty
    if (isEmpty) {
        const emptyText = document.getElementById('emptyStateText');
        if (isDaily) {
            emptyText.textContent = 'No sales recorded on this day';
        } else {
            emptyText.innerHTML = DEFAULT_EMPTY_HTML;
        }
        document.getElementById('emptyState').style.display = 'block';
        if (salesChart) {
            salesChart.destroy();
            salesChart = null;
        }
    } else {
        document.getElementById('emptyStateText').innerHTML = DEFAULT_EMPTY_HTML;
        document.getElementById('emptyState').style.display = 'none';
        try {
            updateChart(chartData, 'line');
        } catch (error) {
            console.error('Error rendering chart:', error);
        }
    }

    const total = Number(data.total_revenue || 0);
    const peak = Number(data.peak_sales || 0);
    let peakLabel = data.peak_label || null;

    if (isDaily && peak > 0) {
        const peakRow = rawData.find(d => Number(d.value) === peak);
        peakLabel = peakRow ? peakRow.label : null;
    }

    // Update KPIs
    updateKpis({
        total: total,
        creditSales: Number(data.credit_sales || 0),
        creditCount: Number(data.credit_count || 0),
        peak: peak,
        peakLabel: peakLabel
    });

    // Update member vs non-member breakdown
    await fetchCustomerTypeBreakdown(period, { ...params });
}

// Main function to set time range and update everything
async function setTimeRange(range) {
    currentPeriod = range;

    if (salesChart) {
        salesChart.destroy();
        salesChart = null;
    }

    // Update UI
    updateButtonStates(range);
    
    // Hide all selectors first
    document.getElementById('dateSelectorContainer').style.display = 'none';
    document.getElementById('monthSelectorContainer').style.display = 'none';
    document.getElementById('yearSelectorContainer').style.display = 'none';
    document.getElementById('quarterYearSelectorContainer').style.display = 'none';

    // Show appropriate selector and initialize if needed
    if (range === 'daily') {
        // Daily: hourly breakdown for one day -> date selector
        document.getElementById('dateSelectorContainer').style.display = 'flex';
        const dateInput = document.getElementById('dateSelector');
        if (!dateInput.value) {
            dateInput.value = APP_TODAY;
        }
        await handleDateChange();
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
    } else if (range === 'quarterly') {
        // Quarterly: quarters within the selected year -> quarter year selector
        document.getElementById('quarterYearSelectorContainer').style.display = 'block';
        await handleQuarterYearChange();
        return;
    } else if (range === 'yearly') {
        // Yearly: annual totals for all years -> no selector
        await loadYearlyData();
        return;
    }
}

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    setTimeRange('daily');
});
</script>
@endsection
