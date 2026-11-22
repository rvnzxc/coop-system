@if(auth()->check() && in_array(auth()->user()->role, ['admin', 'cashier'], true))
    @php
        $cashierReport = auth()->user()->role === 'cashier';
        $reportMembers = \App\Models\Member::orderBy('last_name')->orderBy('first_name')->get();
        $reportCategories = \App\Models\Category::orderBy('name')->get();
        $reportRoutes = [
            'credits' => route('reports.credits'),
            'sales' => route('reports.sales'),
            'inventory' => route('reports.inventory'),
            'member-purchases' => route('reports.member-purchases'),
            'transactions' => route('reports.transactions'),
        ];
    @endphp

    <button type="button" id="reportDialogOpen" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-brand-600 bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-1">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
        {{ $cashierReport ? 'My Reports' : 'Reports' }}
    </button>

    <dialog id="reportDialog" class="rpt-dialog" aria-labelledby="reportDialogTitle">
        <div class="rpt-panel">
            <div class="rpt-header">
                <div>
                    <h3 id="reportDialogTitle" class="rpt-title">{{ $cashierReport ? 'My Reports' : 'Download Reports' }}</h3>
                    <p class="rpt-subtitle">{{ $cashierReport ? 'Generate a report of your own sales and credits.' : 'Generate an admin report for the selected period.' }}</p>
                </div>
                <button type="button" class="rpt-icon-btn" data-report-close aria-label="Close reports dialog">&times;</button>
            </div>

            <form id="reportForm" novalidate>
                <div class="rpt-body">
                    <div class="rpt-grid">
                        <div class="rpt-field">
                            <label for="reportType">Report</label>
                            <select id="reportType" class="rpt-input">
                                <option value="credits">{{ $cashierReport ? 'My Credits Report' : 'Credits Report' }}</option>
                                <option value="sales">{{ $cashierReport ? 'My Sales Report' : 'Sales Report' }}</option>
                                <option value="inventory">Inventory Report</option>
                                <option value="member-purchases">{{ $cashierReport ? 'My Member Purchases Report' : 'Member Purchases Report' }}</option>
                                <option value="transactions">{{ $cashierReport ? 'My Transactions Report' : 'Transactions Report' }}</option>
                            </select>
                        </div>

                        <div class="rpt-field">
                            <label for="reportFormat">Format</label>
                            <select id="reportFormat" class="rpt-input">
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV (Excel)</option>
                            </select>
                        </div>

                        <div class="rpt-field">
                            <label for="reportScope">Date basis</label>
                            <select id="reportScope" class="rpt-input">
                                <option value="period">Active Analytics period</option>
                                <option value="range">Custom date range</option>
                            </select>
                        </div>

                        <div class="rpt-field" data-report-period-field>
                            <label for="reportPeriod">Period</label>
                            <select id="reportPeriod" class="rpt-input">
                                <option value="daily">Daily</option>
                                <option value="weekly">Weekly</option>
                                <option value="monthly">Monthly</option>
                                <option value="quarterly">Quarterly</option>
                                <option value="yearly">Yearly</option>
                            </select>
                        </div>

                        <div class="rpt-field" data-report-context-field hidden>
                            <label for="reportDate" data-report-context-label>Date</label>
                            <input id="reportDate" type="date" class="rpt-input">
                            <input id="reportMonth" type="month" class="rpt-input" hidden>
                            <input id="reportYear" type="number" min="2000" max="2100" class="rpt-input" hidden>
                        </div>

                        <div class="rpt-field" data-report-range-field hidden>
                            <label for="reportFrom">From</label>
                            <input id="reportFrom" type="date" class="rpt-input">
                        </div>

                        <div class="rpt-field" data-report-range-field hidden>
                            <label for="reportTo">To</label>
                            <input id="reportTo" type="date" class="rpt-input">
                        </div>

                        <div class="rpt-field" data-report-member-field>
                            <label for="reportMember">Member (optional)</label>
                            <select id="reportMember" class="rpt-input">
                                <option value="">All members</option>
                                @foreach($reportMembers as $member)
                                    <option value="{{ $member->id }}">{{ $member->full_name }} · {{ $member->member_number }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rpt-field" data-report-status-field>
                            <label for="reportStatus">Status</label>
                            <select id="reportStatus" class="rpt-input">
                                <option value="all">All</option>
                                <option value="unpaid">Unpaid</option>
                                <option value="partial">Partial</option>
                                <option value="paid">Paid</option>
                                <option value="overdue">Overdue</option>
                            </select>
                        </div>

                        <div class="rpt-field" data-report-category-field hidden>
                            <label for="reportCategory">Category (optional)</label>
                            <select id="reportCategory" class="rpt-input">
                                <option value="">All categories</option>
                                @foreach($reportCategories as $category)
                                    <option value="{{ $category->id }}">{{ $category->display_name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="rpt-field" data-report-payment-field hidden>
                            <label for="reportPayment">Payment type</label>
                            <select id="reportPayment" class="rpt-input">
                                <option value="all">All</option>
                                <option value="cash">Cash</option>
                                <option value="credit">Credit</option>
                            </select>
                        </div>
                    </div>

                    <p id="reportError" class="rpt-error" role="alert" aria-live="polite" hidden></p>
                </div>

                <div class="rpt-footer">
                    <button type="button" class="rpt-btn rpt-btn-secondary" data-report-close>Close</button>
                    <button type="submit" class="rpt-btn rpt-btn-primary" id="reportDownloadBtn">Download</button>
                </div>
            </form>
        </div>
    </dialog>

    <style>
        .rpt-dialog { position: fixed; inset: 0; margin: auto; width: calc(100% - 32px); max-width: 540px; max-height: calc(100dvh - 32px); height: fit-content; border: 0; padding: 0; border-radius: 16px; box-shadow: 0 20px 50px rgba(0,0,0,.25); background: #fff; color: #0f172a; font-family: inherit; overflow: hidden; }
        .rpt-dialog::backdrop { background: rgba(0,0,0,.5); }
        .rpt-dialog[open] { display: flex; }
        .rpt-panel { display: flex; flex-direction: column; min-height: 0; max-height: calc(100dvh - 32px); width: 100%; }
        .rpt-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 18px 22px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
        .rpt-title { margin: 0; font-size: 16px; font-weight: 600; color: #14532d; }
        .rpt-subtitle { margin: 3px 0 0; font-size: 12px; color: #64748b; }
        .rpt-icon-btn { border: 0; background: transparent; color: #64748b; font-size: 24px; line-height: 1; cursor: pointer; padding: 0 2px; }
        .rpt-icon-btn:hover { color: #0f172a; }
        .rpt-body { padding: 18px 22px; overflow-y: auto; }
        .rpt-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
        .rpt-field { display: flex; flex-direction: column; gap: 6px; min-width: 0; }
        .rpt-field[hidden] { display: none !important; }
        .rpt-field label { font-size: 12px; font-weight: 600; color: #475569; }
        .rpt-input { width: 100%; box-sizing: border-box; padding: 9px 11px; border: 1px solid #cbd5e1; border-radius: 8px; background: #fff; color: #0f172a; font-size: 13px; font-family: inherit; }
        .rpt-input:focus { border-color: #16a34a; outline: 2px solid rgba(22,163,74,.2); }
        .rpt-error { margin: 14px 0 0; padding: 9px 11px; border-radius: 8px; background: #fef2f2; color: #b91c1c; font-size: 12px; }
        .rpt-error[hidden] { display: none !important; }
        .rpt-footer { display: flex; justify-content: flex-end; gap: 10px; padding: 14px 22px; border-top: 1px solid #e2e8f0; background: #f8fafc; flex-shrink: 0; }
        .rpt-btn { border-radius: 8px; padding: 9px 16px; font-size: 13px; font-weight: 600; cursor: pointer; font-family: inherit; }
        .rpt-btn-secondary { border: 1px solid #cbd5e1; background: #fff; color: #334155; }
        .rpt-btn-secondary:hover { background: #f1f5f9; }
        .rpt-btn-primary { border: 1px solid #16a34a; background: #16a34a; color: #fff; }
        .rpt-btn-primary:hover:not(:disabled) { background: #15803d; }
        .rpt-btn-primary:disabled { opacity: .65; cursor: wait; }
        @media (max-width: 560px) {
            .rpt-grid { grid-template-columns: 1fr; }
            .rpt-dialog { width: calc(100% - 16px); max-height: calc(100dvh - 16px); }
            .rpt-panel { max-height: calc(100dvh - 16px); }
        }
    </style>

    <script>
    (function () {
        var dialog = document.getElementById('reportDialog');
        var openButton = document.getElementById('reportDialogOpen');
        var form = document.getElementById('reportForm');
        var errorBox = document.getElementById('reportError');
        var downloadButton = document.getElementById('reportDownloadBtn');
        if (!dialog || !openButton || !form || !downloadButton) return;

        var typeField = document.getElementById('reportType');
        var formatField = document.getElementById('reportFormat');
        var scopeField = document.getElementById('reportScope');
        var periodField = document.getElementById('reportPeriod');
        var dateField = document.getElementById('reportDate');
        var monthField = document.getElementById('reportMonth');
        var yearField = document.getElementById('reportYear');
        var fromField = document.getElementById('reportFrom');
        var toField = document.getElementById('reportTo');
        var memberField = document.getElementById('reportMember');
        var statusField = document.getElementById('reportStatus');
        var categoryField = document.getElementById('reportCategory');
        var paymentField = document.getElementById('reportPayment');
        var contextField = dialog.querySelector('[data-report-context-field]');
        var contextLabel = dialog.querySelector('[data-report-context-label]');
        var periodRow = dialog.querySelector('[data-report-period-field]');
        var rangeFields = dialog.querySelectorAll('[data-report-range-field]');
        var memberRow = dialog.querySelector('[data-report-member-field]');
        var statusRow = dialog.querySelector('[data-report-status-field]');
        var categoryRow = dialog.querySelector('[data-report-category-field]');
        var paymentRow = dialog.querySelector('[data-report-payment-field]');

        var routes = @json($reportRoutes);

        function activePeriod() {
            var active = document.querySelector('[id$="Btn"][aria-pressed="true"]');
            if (!active) return 'daily';
            var map = { dailyBtn: 'daily', weeklyBtn: 'weekly', monthlyBtn: 'monthly', quarterlyBtn: 'quarterly', yearlyBtn: 'yearly' };
            return map[active.id] || 'daily';
        }

        function show(element, visible) {
            if (!element) return;
            element.hidden = !visible;
        }

        function syncFields() {
            var type = typeField.value;
            var scope = scopeField.value;
            var period = periodField.value;

            show(periodRow, scope === 'period');
            Array.prototype.forEach.call(rangeFields, function (field) { show(field, scope === 'range'); });

            var contextual = scope === 'period' && period !== 'yearly';
            show(contextField, contextual);
            dateField.hidden = true;
            monthField.hidden = true;
            yearField.hidden = true;

            if (contextual) {
                if (period === 'daily') {
                    contextLabel.textContent = 'Date';
                    dateField.hidden = false;
                } else if (period === 'weekly') {
                    contextLabel.textContent = 'Month';
                    monthField.hidden = false;
                } else {
                    contextLabel.textContent = 'Year';
                    yearField.hidden = false;
                }
            }

            show(memberRow, ['credits', 'member-purchases', 'transactions'].indexOf(type) !== -1);
            show(statusRow, type === 'credits');
            show(categoryRow, type === 'inventory');
            show(paymentRow, type === 'transactions');
        }

        function syncFromChart() {
            var period = activePeriod();
            periodField.value = period;
            var today = new Date();
            var iso = today.getFullYear() + '-' + String(today.getMonth() + 1).padStart(2, '0') + '-' + String(today.getDate()).padStart(2, '0');

            var dateSelector = document.getElementById('dateSelector');
            dateField.value = (dateSelector && dateSelector.value) ? dateSelector.value : iso;

            var monthSelector = document.getElementById('monthSelector');
            monthField.value = (monthSelector && monthSelector.value) ? monthSelector.value : iso.slice(0, 7);

            var month = new Date();
            yearField.value = month.getFullYear();
            if (period === 'monthly') {
                var yearSelector = document.getElementById('yearSelector');
                if (yearSelector && yearSelector.value) yearField.value = yearSelector.value;
            } else if (period === 'quarterly') {
                var quarterSelector = document.getElementById('quarterYearSelector');
                if (quarterSelector && quarterSelector.value) yearField.value = quarterSelector.value;
            }

            fromField.value = iso.slice(0, 8) + '01';
            toField.value = iso;
            syncFields();
        }

        function showError(message) {
            errorBox.textContent = message;
            errorBox.hidden = false;
        }

        function clearError() {
            errorBox.textContent = '';
            errorBox.hidden = true;
        }

        function buildUrl() {
            var type = typeField.value;
            var params = new URLSearchParams();
            params.set('format', formatField.value);
            params.set('scope', scopeField.value);

            if (scopeField.value === 'range') {
                params.set('from', fromField.value);
                params.set('to', toField.value);
            } else {
                params.set('period', periodField.value);
                if (periodField.value === 'daily' && dateField.value) params.set('date', dateField.value);
                if (periodField.value === 'weekly' && monthField.value) params.set('month', monthField.value);
                if ((periodField.value === 'monthly' || periodField.value === 'quarterly') && yearField.value) params.set('year', yearField.value);
            }

            var memberRow = dialog.querySelector('[data-report-member-field]');
            if (!memberRow.hidden && memberField.value) params.set('member_id', memberField.value);
            var statusRow = dialog.querySelector('[data-report-status-field]');
            if (!statusRow.hidden && statusField.value !== 'all') params.set('status', statusField.value);
            var categoryRow = dialog.querySelector('[data-report-category-field]');
            if (!categoryRow.hidden && categoryField.value) params.set('category_id', categoryField.value);
            var paymentRow = dialog.querySelector('[data-report-payment-field]');
            if (!paymentRow.hidden && paymentField.value !== 'all') params.set('payment_method', paymentField.value);

            return routes[type] + '?' + params.toString();
        }

        openButton.addEventListener('click', function () {
            clearError();
            syncFromChart();
            if (typeof dialog.showModal === 'function') dialog.showModal();
            else dialog.setAttribute('open', '');
        });

        if (window.location.hash === '#reports') {
            window.setTimeout(function () { openButton.click(); }, 50);
        }

        dialog.querySelectorAll('[data-report-close]').forEach(function (button) {
            button.addEventListener('click', function () {
                if (typeof dialog.close === 'function') dialog.close();
                else dialog.removeAttribute('open');
            });
        });

        typeField.addEventListener('change', syncFields);
        scopeField.addEventListener('change', syncFields);
        periodField.addEventListener('change', syncFields);

        form.addEventListener('submit', async function (event) {
            event.preventDefault();
            clearError();

            if (scopeField.value === 'range' && (!fromField.value || !toField.value)) {
                showError('Select both a start date and an end date.');
                return;
            }
            if (scopeField.value === 'range' && fromField.value > toField.value) {
                showError('The start date must be before the end date.');
                return;
            }

            var originalText = downloadButton.textContent;
            downloadButton.disabled = true;
            downloadButton.textContent = 'Generating…';

            try {
                var response = await fetch(buildUrl(), { credentials: 'same-origin' });
                if (!response.ok) {
                    var message = 'Could not generate the report. Please try again.';
                    try {
                        var body = await response.json();
                        if (body && body.message) message = body.message;
                    } catch (ignored) {}
                    throw new Error(message);
                }

                var blob = await response.blob();
                var disposition = response.headers.get('Content-Disposition') || '';
                var match = disposition.match(/filename="([^"]+)"/);
                var filename = match ? match[1] : 'ccfmpc-' + typeField.value + '-report.' + (formatField.value === 'csv' ? 'csv' : 'pdf');
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
            } catch (error) {
                showError(error.message || 'Could not generate the report. Please try again.');
            } finally {
                downloadButton.disabled = false;
                downloadButton.textContent = originalText;
            }
        });
    })();
    </script>
@endif
