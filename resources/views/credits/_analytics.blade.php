@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $labelClass = 'mb-1.5 block text-xs font-semibold uppercase tracking-wider text-slate-400';
    $topCategory = $creditAnalytics['top_category'] ?? null;
    $hasAnalyticsFilters = filled($from) || filled($to);
    $hasAnalyticsData = ! empty($creditAnalytics['categories']);
@endphp

<div data-credit-panel="analytics" class="{{ $tab === 'analytics' ? '' : 'hidden' }}">
    {{-- Summary cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-brand-50 text-base text-brand-600">
                <x-icon name="money" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">&#8369;{{ number_format($creditAnalytics['total_sales'], 2) }}</div>
                <div class="text-xs text-slate-500">Total Credit Sales</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-sky-50 text-base text-sky-600">
                <x-icon name="credit-card" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ number_format($creditAnalytics['transactions']) }}</div>
                <div class="text-xs text-slate-500">Credit Transactions</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-base text-amber-600">
                <x-icon name="trophy" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $topCategory['name'] ?? '—' }}</div>
                <div class="text-xs text-slate-500">Top Category{{ $topCategory ? ' · '.$topCategory['quantity'].' qty' : '' }}</div>
            </div>
        </div>
    </div>

    {{-- Filters --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
        <form id="creditAnalyticsFilters" method="GET" action="{{ route('credits.index') }}">
            <input type="hidden" name="tab" value="analytics">
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                <div>
                    <label for="creditDateFrom" class="{{ $labelClass }}">From</label>
                    <input type="date" id="creditDateFrom" name="from" value="{{ $from }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label for="creditDateTo" class="{{ $labelClass }}">To</label>
                    <input type="date" id="creditDateTo" name="to" value="{{ $to }}" class="{{ $inputClass }}">
                </div>
            </div>

            <div class="mt-4 flex flex-col gap-3 border-t border-slate-100 pt-4 lg:flex-row lg:items-center lg:justify-between">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-xs font-semibold uppercase tracking-wider text-slate-400">Quick range</span>
                    <button type="button" onclick="setCreditRange('today')" class="whitespace-nowrap rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">Today</button>
                    <button type="button" onclick="setCreditRange('week')" class="whitespace-nowrap rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">This Week</button>
                    <button type="button" onclick="setCreditRange('month')" class="whitespace-nowrap rounded-full border border-slate-200 bg-white px-3 py-1 text-xs font-medium text-slate-600 transition-colors hover:bg-slate-50">This Month</button>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($hasAnalyticsFilters)
                        <x-button href="{{ route('credits.index', ['tab' => 'analytics']) }}" color="ghost" size="sm" class="table-toolbar-btn">Reset</x-button>
                    @endif
                    <x-button type="submit" color="primary" size="sm" class="table-toolbar-btn">
                        <x-icon name="search" class="h-3 w-3" /> Apply filters
                    </x-button>
                </div>
            </div>
        </form>
    </div>

    {{-- Chart --}}
    <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h3 id="creditChartTitle" class="text-base font-semibold text-slate-900">Credit purchases by category</h3>
                <p class="mt-0.5 text-xs text-slate-500">Quantity sold on credit for completed transactions</p>
            </div>
            <div class="w-full sm:w-56">
                <label for="creditCategoryFilter" class="{{ $labelClass }}">Category</label>
                <select id="creditCategoryFilter" class="{{ $inputClass }}">
                    <option value="">All categories</option>
                    @foreach($creditAnalytics['categories'] as $category)
                        <option value="{{ $category['name'] }}">{{ $category['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div id="creditChartEmpty" class="{{ $hasAnalyticsData ? 'hidden' : '' }} flex h-[360px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
                    <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                        <x-icon name="credit-card" class="h-5 w-5" />
                    </div>
                    <p class="text-sm font-semibold text-slate-700">No credit purchases found for this period</p>
                    <p class="mt-1 text-xs text-slate-500">Try a different date range or clear the filters.</p>
                </div>
                <div id="creditChartWrap" class="relative h-[360px] {{ $hasAnalyticsData ? '' : 'hidden' }}">
                    <canvas id="creditPurchasesChart"></canvas>
                </div>
            </div>
            <div>
                <h4 id="creditLegendTitle" class="mb-3 text-sm font-semibold text-slate-900">Top categories</h4>
                <ol id="creditChartLegend" class="max-h-[360px] space-y-2 overflow-y-auto pr-1"></ol>
            </div>
        </div>
    </div>
</div>

<script>
    const creditAnalyticsData = @json($creditAnalytics);
    const creditChartPalette = [
        '#16a34a', '#0ea5e9', '#f59e0b', '#8b5cf6', '#ef4444',
        '#14b8a6', '#f97316', '#6366f1', '#ec4899', '#84cc16',
        '#06b6d4', '#a855f7'
    ];

    let creditChartInstance = null;

    function escapeCreditHtml(value) {
        return String(value ?? '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function formatCreditDate(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');

        return year + '-' + month + '-' + day;
    }

    function setCreditRange(range) {
        const from = document.getElementById('creditDateFrom');
        const to = document.getElementById('creditDateTo');
        const form = document.getElementById('creditAnalyticsFilters');

        if (!from || !to || !form) {
            return;
        }

        const today = new Date();
        let start = new Date(today);
        let end = new Date(today);

        if (range === 'week') {
            const day = (today.getDay() + 6) % 7;
            start.setDate(today.getDate() - day);
        } else if (range === 'month') {
            start = new Date(today.getFullYear(), today.getMonth(), 1);
        }

        from.value = formatCreditDate(start);
        to.value = formatCreditDate(end);
        form.submit();
    }

    function currentCreditSlices() {
        const select = document.getElementById('creditCategoryFilter');
        const selected = select ? select.value : '';

        if (!selected) {
            return creditAnalyticsData.categories;
        }

        const category = creditAnalyticsData.categories.find(function (entry) {
            return entry.name === selected;
        });

        return category ? category.items : [];
    }

    function renderCreditAnalytics() {
        if (typeof Chart === 'undefined') {
            return;
        }

        const select = document.getElementById('creditCategoryFilter');
        const selected = select ? select.value : '';
        const slices = currentCreditSlices();
        const total = slices.reduce(function (sum, slice) {
            return sum + Number(slice.quantity);
        }, 0);

        const title = document.getElementById('creditChartTitle');
        const legendTitle = document.getElementById('creditLegendTitle');
        const empty = document.getElementById('creditChartEmpty');
        const wrap = document.getElementById('creditChartWrap');
        const legend = document.getElementById('creditChartLegend');

        if (!title || !legendTitle || !empty || !wrap || !legend) {
            return;
        }

        title.textContent = selected
            ? 'Credit purchases in ' + selected
            : 'Credit purchases by category';
        legendTitle.textContent = selected ? 'Top items' : 'Top categories';

        if (!slices.length || total <= 0) {
            empty.classList.remove('hidden');
            wrap.classList.add('hidden');
            legend.innerHTML = '';

            if (creditChartInstance) {
                creditChartInstance.destroy();
                creditChartInstance = null;
            }

            return;
        }

        empty.classList.add('hidden');
        wrap.classList.remove('hidden');

        const labels = slices.map(function (slice) {
            return slice.name;
        });
        const values = slices.map(function (slice) {
            return Number(slice.quantity);
        });
        const colors = slices.map(function (_, index) {
            return creditChartPalette[index % creditChartPalette.length];
        });

        if (creditChartInstance) {
            creditChartInstance.destroy();
        }

        const canvas = document.getElementById('creditPurchasesChart');

        if (!canvas) {
            return;
        }

        creditChartInstance = new Chart(canvas.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderColor: '#ffffff',
                    borderWidth: 2,
                    hoverOffset: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '60%',
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
                        callbacks: {
                            label: function (context) {
                                const slice = slices[context.dataIndex];

                                return slice.name + ': ' + slice.quantity + ' qty (' + Number(slice.percentage).toFixed(1) + '%)';
                            }
                        }
                    }
                }
            }
        });

        legend.innerHTML = slices.map(function (slice, index) {
            const highlighted = index === 0;
            const trophy = highlighted ? ' <i class="fa fa-trophy text-amber-500"></i>' : '';

            return '<li class="flex items-center gap-3 rounded-lg px-3 py-2 ' + (highlighted ? 'bg-brand-50 ring-1 ring-brand-200' : 'hover:bg-slate-50') + '">'
                + '<span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-bold ' + (highlighted ? 'bg-brand-600 text-white' : 'bg-slate-100 text-slate-500') + '">' + (index + 1) + '</span>'
                + '<span class="min-w-0 flex-1 truncate text-sm ' + (highlighted ? 'font-semibold text-brand-800' : 'text-slate-700') + '">' + escapeCreditHtml(slice.name) + trophy + '</span>'
                + '<span class="whitespace-nowrap text-xs text-slate-500">' + slice.quantity + ' qty &middot; ' + Number(slice.percentage).toFixed(1) + '%</span>'
                + '</li>';
        }).join('');
    }

    window.renderCreditAnalytics = renderCreditAnalytics;

    document.addEventListener('DOMContentLoaded', function () {
        const select = document.getElementById('creditCategoryFilter');

        if (select) {
            select.addEventListener('change', renderCreditAnalytics);
        }
    });
</script>
