@extends('layouts.app')

@section('title', 'Credits')

@section('content')
<div class="mx-auto max-w-7xl">
    <div class="mb-6 flex flex-col gap-5 md:flex-row md:items-center md:justify-between">
        <div class="min-w-0">
            <h2 class="text-xl font-bold text-slate-900">Credit Management</h2>
            <p class="mt-0.5 text-sm text-slate-500">Track member credit purchases</p>
        </div>
        @include('credits._download-report')
    </div>

    <x-tab-bar
        data-credit-tabs
        :tabs="[
            ['key' => 'credits', 'label' => 'Credits', 'href' => route('credits.index')],
            ['key' => 'analytics', 'label' => 'Analytics', 'href' => route('credits.index', ['tab' => 'analytics'])],
        ]"
        :active="$tab"
    />

    <div data-credit-panel="credits" class="{{ $tab === 'credits' ? '' : 'hidden' }}">

    {{-- Summary cards --}}
    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-amber-50 text-base text-amber-600">
                <x-icon name="credit-card" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">&#8369;{{ number_format($totalCredit, 2) }}</div>
                <div class="text-xs text-slate-500">Total Credit</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-slate-100 text-base text-slate-600">
                <x-icon name="money" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $creditCount }}</div>
                <div class="text-xs text-slate-500">Credit Records</div>
            </div>
        </div>
        <div class="flex items-center gap-4 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-blue-50 text-base text-blue-600">
                <x-icon name="users" class="h-4 w-4" />
            </div>
            <div class="min-w-0">
                <div class="truncate text-xl font-bold text-slate-900">{{ $membersWithCredit }}</div>
                <div class="text-xs text-slate-500">Members with Credit</div>
            </div>
        </div>
    </div>

    {{-- Members with highest credit --}}
    <div class="mb-6 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="mb-4">
            <h3 class="text-base font-semibold text-slate-900">Members with highest credit</h3>
            <p class="mt-0.5 text-xs text-slate-500">Ranked by total credit</p>
        </div>

        <div id="creditRankingEmpty" class="{{ empty($creditRankings['all']) ? 'flex' : 'hidden' }} h-[180px] flex-col items-center justify-center rounded-lg border border-dashed border-slate-200 bg-slate-50 px-6 text-center">
            <div class="mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-white text-slate-400 shadow-sm">
                <x-icon name="credit-card" class="h-5 w-5" />
            </div>
            <p class="text-sm font-semibold text-slate-700">No credit data to display</p>
            <p class="mt-1 text-xs text-slate-500">Credit records will appear here.</p>
        </div>

        <div id="creditRankingWrap" class="{{ empty($creditRankings['all']) ? 'hidden' : '' }} relative h-[300px]">
            <canvas id="creditRankingChart"></canvas>
        </div>
    </div>

    {{-- Credits grouped by member --}}
    <div data-credit-table class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
        <div class="max-h-[calc(100vh-24rem)] overflow-auto">
            <table class="w-full text-left text-sm">
                <thead class="sticky top-0 z-10 bg-slate-50 text-xs uppercase tracking-wider text-slate-500">
                    <tr>
                        <th class="px-5 py-3.5 font-semibold">Member</th>
                        <th class="px-5 py-3.5 text-right font-semibold">Total Credit</th>
                        <th class="px-5 py-3.5 text-center font-semibold">Credits</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($groupedCredits as $memberId => $group)
                    {{-- Member row --}}
                    <tr class="cursor-pointer transition-colors hover:bg-slate-50" onclick="toggleMember({{ $memberId }})">
                        <td class="px-5 py-3.5">
                            <div class="flex items-center gap-2">
                                <x-icon id="chevron-{{ $memberId }}" name="chevron-right" class="h-3 w-3 text-slate-400 transition-transform" />
                                <div>
                                    <div class="font-medium text-slate-900">{{ $group['member']->first_name }} {{ $group['member']->last_name }}</div>
                                    <div class="text-xs text-slate-400">{{ $group['member']->member_number }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="whitespace-nowrap px-5 py-3.5 text-right font-semibold text-slate-700">&#8369;{{ number_format($group['total'], 2) }}</td>
                        <td class="px-5 py-3.5 text-center">
                            <span class="inline-flex items-center justify-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-600">{{ $group['credits']->count() }}</span>
                        </td>
                    </tr>

                    {{-- Expandable credit details --}}
                    <tr id="member-credits-{{ $memberId }}" style="display: none;">
                        <td colspan="3" class="bg-slate-50 px-5 py-0">
                            <table class="w-full text-xs">
                                <thead>
                                    <tr class="text-slate-400">
                                        <th class="py-2.5 text-left font-medium">Date</th>
                                        <th class="py-2.5 text-left font-medium">Items</th>
                                        <th class="py-2.5 text-right font-medium">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @foreach($group['credits'] as $credit)
                                    <tr class="hover:bg-white">
                                        <td class="py-2 text-slate-600">{{ $credit->created_at->format('M d, Y') }}</td>
                                        <td class="py-2">
                                            @if($credit->items_snapshot)
                                                @foreach($credit->items_snapshot as $item)
                                                    <span class="mr-1 inline-block rounded bg-slate-200 px-1.5 py-0.5 text-[10px] font-medium text-slate-600">{{ $item['product_name'] }} x{{ $item['quantity'] }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-slate-400">—</span>
                                            @endif
                                        </td>
                                        <td class="whitespace-nowrap py-2 text-right font-medium text-slate-700">&#8369;{{ number_format($credit->amount, 2) }}</td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="3" class="px-5 py-16 text-center">
                            <div class="flex flex-col items-center">
                                <div class="mb-4 flex h-14 w-14 items-center justify-center rounded-full bg-slate-100 text-2xl text-slate-400">
                                    <x-icon name="credit-card" class="h-6 w-6" />
                                </div>
                                <p class="text-base font-semibold text-slate-700">No credit records found</p>
                                <p class="mt-1 text-sm text-slate-500">Credit sales will appear here.</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    </div>

    @include('credits._analytics')
</div>

<script>
const openMemberRows = new Set();

function rememberOpenMemberRows() {
    try {
        sessionStorage.setItem('creditsOpenMembers', JSON.stringify([...openMemberRows]));
    } catch (error) {
        // Storage can be unavailable in private browsing; expansion still works for this page.
    }
}

function restoreOpenMemberRows() {
    try {
        const stored = JSON.parse(sessionStorage.getItem('creditsOpenMembers') || '[]');

        if (Array.isArray(stored)) {
            stored.forEach(function (memberId) {
                openMemberRows.add(String(memberId));
            });
        }
    } catch (error) {
        // Ignore malformed or unavailable storage.
    }

    openMemberRows.forEach(function (memberId) {
        const row = document.getElementById('member-credits-' + memberId);
        const chevron = document.getElementById('chevron-' + memberId);

        if (row) {
            row.style.display = '';
            if (chevron) {
                chevron.style.transform = 'rotate(90deg)';
            }
        }
    });
}

function toggleMember(memberId) {
    const row = document.getElementById('member-credits-' + memberId);
    const chevron = document.getElementById('chevron-' + memberId);

    if (!row) {
        return;
    }

    const isVisible = row.style.display !== 'none';
    row.style.display = isVisible ? 'none' : '';

    if (chevron) {
        chevron.style.transform = isVisible ? '' : 'rotate(90deg)';
    }

    if (isVisible) {
        openMemberRows.delete(String(memberId));
    } else {
        openMemberRows.add(String(memberId));
    }

    rememberOpenMemberRows();
}

document.addEventListener('DOMContentLoaded', function () {
    restoreOpenMemberRows();
});

document.addEventListener('DOMContentLoaded', function () {
    const tabsRoot = document.querySelector('[data-credit-tabs]');
    const panels = document.querySelectorAll('[data-credit-panel]');

    if (!tabsRoot || panels.length === 0) {
        return;
    }

    function activateCreditTab(key, updateUrl) {
        panels.forEach(function (panel) {
            panel.classList.toggle('hidden', panel.dataset.creditPanel !== key);
        });

        tabsRoot.querySelectorAll('[data-tab]').forEach(function (link) {
            const isActive = link.dataset.tab === key;
            link.classList.toggle('border-brand-600', isActive);
            link.classList.toggle('text-brand-700', isActive);
            link.classList.toggle('border-transparent', !isActive);
            link.classList.toggle('text-slate-500', !isActive);
            link.classList.toggle('hover:text-slate-800', !isActive);
            link.setAttribute('aria-selected', isActive ? 'true' : 'false');
        });

        if (updateUrl) {
            const url = new URL(window.location.href);
            url.searchParams.set('tab', key);
            window.history.replaceState({}, '', url);
        }

        if (key === 'analytics' && typeof window.renderCreditAnalytics === 'function') {
            window.renderCreditAnalytics();
        }

        if (key === 'credits' && typeof window.renderCreditRanking === 'function') {
            window.renderCreditRanking();
        }
    }

    tabsRoot.querySelectorAll('[data-tab]').forEach(function (link) {
        link.addEventListener('click', function (event) {
            event.preventDefault();
            activateCreditTab(link.dataset.tab, true);
        });
    });

    if (document.querySelector('[data-credit-panel="analytics"]:not(.hidden)')) {
        activateCreditTab('analytics', false);
    }
});
</script>

<script>
    const creditRankingData = @json($creditRankings);

    let creditRankingChart = null;

    function formatCreditRankingPeso(value, decimals) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: decimals,
            maximumFractionDigits: decimals
        }).format(Number(value || 0));
    }

    function setCreditRankingEmptyState(isEmpty) {
        const empty = document.getElementById('creditRankingEmpty');
        const wrap = document.getElementById('creditRankingWrap');

        if (!empty || !wrap) {
            return;
        }

        if (isEmpty) {
            empty.classList.remove('hidden');
            empty.classList.add('flex');
            wrap.classList.add('hidden');
        } else {
            empty.classList.add('hidden');
            empty.classList.remove('flex');
            wrap.classList.remove('hidden');
        }
    }

    function renderCreditRanking() {
        if (typeof Chart === 'undefined') {
            return;
        }

        const wrap = document.getElementById('creditRankingWrap');
        const canvas = document.getElementById('creditRankingChart');

        if (!wrap || !canvas) {
            return;
        }

        const entries = Array.isArray(creditRankingData.all) ? creditRankingData.all : [];
        const values = entries.map(function (entry) {
            return Number(entry.total);
        });
        const maxValue = values.length ? Math.max.apply(null, values) : 0;

        if (!entries.length || maxValue <= 0) {
            setCreditRankingEmptyState(true);

            if (creditRankingChart) {
                creditRankingChart.destroy();
                creditRankingChart = null;
            }

            return;
        }

        setCreditRankingEmptyState(false);

        const labels = entries.map(function (entry) {
            return entry.name;
        });
        const colors = entries.map(function (_, index) {
            return index === 0 ? '#16a34a' : '#86efac';
        });

        wrap.style.height = Math.max(180, entries.length * 48 + 60) + 'px';

        if (creditRankingChart) {
            creditRankingChart.destroy();
        }

        creditRankingChart = new Chart(canvas.getContext('2d'), {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors,
                    borderRadius: 4,
                    borderSkipped: false,
                    barThickness: 22
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                layout: {
                    padding: {
                        right: 80
                    }
                },
                scales: {
                    x: {
                        beginAtZero: true,
                        max: maxValue,
                        grid: {
                            color: '#f1f5f9'
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#94a3b8',
                            font: {
                                size: 11
                            },
                            callback: function (value) {
                                return formatCreditRankingPeso(value, 0);
                            }
                        }
                    },
                    y: {
                        grid: {
                            display: false
                        },
                        border: {
                            display: false
                        },
                        ticks: {
                            color: '#334155',
                            autoSkip: false,
                            font: {
                                size: 12,
                                weight: '600'
                            },
                            callback: function (value) {
                                const label = this.getLabelForValue(value);

                                return label.length > 22 ? label.slice(0, 21) + '…' : label;
                            }
                        }
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
                        callbacks: {
                            title: function (items) {
                                return items.length ? entries[items[0].dataIndex].name : '';
                            },
                            label: function (context) {
                                const entry = entries[context.dataIndex];

                                return [
                                    'Member ID: ' + entry.member_id,
                                    'Total Credit: ' + formatCreditRankingPeso(entry.total, 2)
                                ];
                            }
                        }
                    }
                }
            },
            plugins: [{
                id: 'creditRankingValueLabels',
                afterDatasetsDraw: function (chart) {
                    const ctx = chart.ctx;

                    ctx.save();
                    ctx.font = '600 11px ui-sans-serif, system-ui, sans-serif';
                    ctx.fillStyle = '#14532d';
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';

                    chart.getDatasetMeta(0).data.forEach(function (bar, index) {
                        ctx.fillText(formatCreditRankingPeso(entries[index].total, 0), bar.x + 8, bar.y);
                    });

                    ctx.restore();
                }
            }]
        });
    }

    window.renderCreditRanking = renderCreditRanking;

    document.addEventListener('DOMContentLoaded', function () {
        if (document.querySelector('[data-credit-panel="credits"]:not(.hidden)')) {
            renderCreditRanking();
        }
    });
</script>
@endsection
