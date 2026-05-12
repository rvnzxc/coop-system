@extends('layouts.app')

@section('title', 'Sales Performance Analytics')

@section('content')
<div style="max-width: 1400px; margin: 30px auto; padding: 0 20px;">
    <h2 style="color: #1b3a1b; font-size: 28px; font-weight: bold; margin-bottom: 30px;">Sales Performance Analytics</h2>
    
    <!-- Time Range Toggles -->
    <div style="background: white; border-radius: 12px; padding: 20px; margin-bottom: 30px; box-shadow: 0 4px 12px rgba(0,0,0,0.1);">
        <div style="display: flex; gap: 10px;">
            <button onclick="setTimeRange('daily')" id="dailyBtn" style="flex: 1; padding: 12px 24px; background: #1b3a1b; color: white; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Daily</button>
            <button onclick="setTimeRange('weekly')" id="weeklyBtn" style="flex: 1; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Weekly</button>
            <button onclick="setTimeRange('monthly')" id="monthlyBtn" style="flex: 1; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Monthly</button>
            <button onclick="setTimeRange('yearly')" id="yearlyBtn" style="flex: 1; padding: 12px 24px; background: #f0f0f0; color: #666; border: none; border-radius: 8px; font-size: 14px; font-weight: bold; cursor: pointer;">Yearly</button>
        </div>
    </div>

    <!-- Chart Section -->
    <div style="background: white; border-radius: 12px; padding: 25px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); margin-bottom: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="color: #1b3a1b; font-size: 18px; margin: 0;">
                <span id="chartTitle">Daily Sales (Today by Hour)</span>
            </h3>
            <div style="display: flex; gap: 10px;">
                <!-- Date Selector for Daily -->
                <div id="dateSelectorContainer" style="display: none;">
                    <input type="date" id="dateSelector" onchange="handleDateChange()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; color: #333;">
                </div>
                
                <!-- Week Selector for Weekly -->
                <div id="weekSelectorContainer" style="display: none;">
                    <select id="weekSelector" onchange="handleWeekChange()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; color: #333;">
                        <!-- Week options will be populated by JavaScript -->
                    </select>
                </div>
                
                <!-- Month Selector for Monthly -->
                <div id="monthSelectorContainer" style="display: none;">
                    <select id="monthSelector" onchange="handleMonthChange()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; color: #333;">
                        <!-- Month options will be populated by JavaScript -->
                    </select>
                </div>
                
                <!-- Year Selector for Yearly -->
                <div id="yearSelectorContainer" style="display: none;">
                    <select id="yearSelector" onchange="handleYearChange()" style="padding: 8px 12px; border: 1px solid #ddd; border-radius: 6px; font-size: 14px; background: white; color: #333;">
                        <!-- Year options will be populated by JavaScript -->
                    </select>
                </div>
            </div>
        </div>
        <div style="height: 400px; position: relative;">
            <canvas id="salesChart" style="width: 100%; height: 100%;"></canvas>
            <div id="loadingIndicator" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none; color: #aaa;">Loading...</div>
            <div id="emptyState" style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); display: none; color: #aaa; text-align: center; font-size: 13px;">
                No sales data yet —<br/>complete a sale in POS to begin
            </div>
        </div>
    </div>

    <!-- Summary Cards -->
    <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px;">
        <div style="background: linear-gradient(135deg, #1b3a1b 0%, #2d5a2d 100%); color: white; padding: 25px; border-radius: 12px;">
            <h4 style="margin: 0; font-size: 16px; margin-bottom: 15px;">Total Revenue</h4>
            <div id="totalSalesAmount" style="font-size: 32px; font-weight: bold;">₱0.00</div>
        </div>
        <div style="background: linear-gradient(135deg, #1b3a1b 0%, #2d5a2d 100%); color: white; padding: 25px; border-radius: 12px;">
            <h4 style="margin: 0; font-size: 16px; margin-bottom: 15px;">Average Sales</h4>
            <div id="avgSalesAmount" style="font-size: 32px; font-weight: bold;">₱0.00</div>
        </div>
        <div style="background: linear-gradient(135deg, #1b3a1b 0%, #2d5a2d 100%); color: white; padding: 25px; border-radius: 12px;">
            <h4 style="margin: 0; font-size: 16px; margin-bottom: 15px;">Peak Sales</h4>
            <div id="peakSalesAmount" style="font-size: 32px; font-weight: bold;">₱0.00</div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            btn.style.background = '#1b3a1b';
            btn.style.color = 'white';
        } else {
            btn.style.background = '#f0f0f0';
            btn.style.color = '#666';
        }
    });
}

// Fetch data from API
async function fetchSalesData(period, params = {}) {
    try {
        let url = `/api/analytics/sales?period=${period}`;
        
        // Add parameters based on period
        if (period === 'daily' && params.date) {
            url += `&date=${params.date}`;
        } else if (period === 'weekly' && params.week) {
            url += `&week=${params.week}`;
        } else if (period === 'monthly' && params.month) {
            url += `&month=${params.month}`;
        } else if (period === 'yearly' && params.year) {
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
function updateChart(chartData) {
    const ctx = document.getElementById('salesChart').getContext('2d');
    
    if (salesChart) {
        salesChart.destroy();
    }

    const labels = chartData.map(d => d.label);
    const values = chartData.map(d => d.value);

    salesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: 'Sales',
                data: values,
                borderColor: '#1b3a1b',
                backgroundColor: 'rgba(27, 58, 27, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointBackgroundColor: '#1b3a1b',
                pointBorderColor: '#1b3a1b',
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
                    borderColor: '#1b3a1b',
                    borderWidth: 1.5,
                    titleColor: '#333',
                    bodyColor: '#1b3a1b',
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
                        color: '#666',
                        font: {
                            size: 12
                        }
                    }
                },
                y: {
                    grid: {
                        color: '#e5e5e5',
                        borderDash: [3, 3]
                    },
                    ticks: {
                        color: '#666',
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

// Initialize date selector
function initializeDateSelector() {
    const selector = document.getElementById('dateSelector');
    const today = new Date();
    const todayStr = today.toISOString().split('T')[0];
    selector.value = todayStr;
    selector.max = todayStr; // Can't select future dates
}

// Handle date change
async function handleDateChange() {
    const selectedDate = document.getElementById('dateSelector').value;
    const dateName = new Date(selectedDate + 'T00:00:00').toLocaleDateString('en-US', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });
    document.getElementById('chartTitle').textContent = `Daily Sales (${dateName})`;
    
    await loadDataWithParams('daily', { date: selectedDate });
}

// Initialize week selector
function initializeWeekSelector() {
    const selector = document.getElementById('weekSelector');
    const now = new Date();
    const currentWeek = now.getFullYear() + '-' + String(now.getWeek()).padStart(2, '0');
    
    // Generate week options for the last 12 weeks
    for (let i = 0; i < 12; i++) {
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

// Handle week change
async function handleWeekChange() {
    const selectedWeek = document.getElementById('weekSelector').value;
    const [year, week] = selectedWeek.split('-');
    document.getElementById('chartTitle').textContent = `Weekly Sales (Week ${week}, ${year})`;
    
    await loadDataWithParams('weekly', { week: selectedWeek });
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

// Handle month change
async function handleMonthChange() {
    const selectedMonth = document.getElementById('monthSelector').value;
    const monthName = new Date(selectedMonth + '-01').toLocaleDateString('en-US', { year: 'numeric', month: 'long' });
    document.getElementById('chartTitle').textContent = `Monthly Sales (${monthName})`;
    
    await loadDataWithParams('monthly', { month: selectedMonth });
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

// Handle year change
async function handleYearChange() {
    const selectedYear = document.getElementById('yearSelector').value;
    document.getElementById('chartTitle').textContent = `Yearly Sales (${selectedYear})`;
    
    await loadDataWithParams('yearly', { year: selectedYear });
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
        updateChart(data.data || []);
    }
    
    // Update KPIs
    updateKpis({
        total: data.total_revenue || 0,
        average: data.average_sales || 0,
        peak: data.peak_sales || 0
    });
}

// Main function to set time range and update everything
async function setTimeRange(range) {
    currentPeriod = range;
    
    // Update UI
    updateButtonStates(range);
    
    // Hide all selectors first
    document.getElementById('dateSelectorContainer').style.display = 'none';
    document.getElementById('weekSelectorContainer').style.display = 'none';
    document.getElementById('monthSelectorContainer').style.display = 'none';
    document.getElementById('yearSelectorContainer').style.display = 'none';
    
    // Show appropriate selector and initialize if needed
    if (range === 'daily') {
        document.getElementById('dateSelectorContainer').style.display = 'block';
        if (!document.getElementById('dateSelector').value) {
            initializeDateSelector();
        }
        await handleDateChange();
        return;
    } else if (range === 'weekly') {
        document.getElementById('weekSelectorContainer').style.display = 'block';
        if (!document.getElementById('weekSelector').hasChildNodes()) {
            initializeWeekSelector();
        }
        await handleWeekChange();
        return;
    } else if (range === 'monthly') {
        document.getElementById('monthSelectorContainer').style.display = 'block';
        if (!document.getElementById('monthSelector').hasChildNodes()) {
            initializeMonthSelector();
        }
        await handleMonthChange();
        return;
    } else if (range === 'yearly') {
        document.getElementById('yearSelectorContainer').style.display = 'block';
        if (!document.getElementById('yearSelector').hasChildNodes()) {
            initializeYearSelector();
        }
        await handleYearChange();
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
