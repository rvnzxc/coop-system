@if(auth()->check() && in_array(auth()->user()->role, ['admin', 'cashier'], true))
    @php
        $cashierCreditReport = auth()->user()->role === 'cashier';
        $creditReportParams = ($from ?? null) && ($to ?? null)
            ? ['scope' => 'range', 'from' => $from, 'to' => $to, 'format' => 'pdf']
            : ['scope' => 'period', 'period' => 'yearly', 'format' => 'pdf'];
    @endphp

    <div class="flex flex-col items-end gap-1.5">
        <button type="button" id="creditReportDownload" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-brand-600 bg-brand-600 px-4 py-2 text-sm font-semibold text-white transition-colors hover:bg-brand-700 focus:outline-none focus:ring-2 focus:ring-brand-500/40 focus:ring-offset-1 disabled:cursor-wait disabled:opacity-70">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3v12m0 0 4-4m-4 4-4-4M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
            <span id="creditReportDownloadLabel">{{ $cashierCreditReport ? 'Download My Credits Report' : 'Download Credits Report' }}</span>
        </button>
        <p id="creditReportError" class="text-xs text-red-600" role="alert" aria-live="polite" hidden></p>
    </div>

    <script>
    (function () {
        var button = document.getElementById('creditReportDownload');
        var label = document.getElementById('creditReportDownloadLabel');
        var errorBox = document.getElementById('creditReportError');
        if (!button || !label || !errorBox) return;

        var url = '{{ route('reports.credits') }}?' + new URLSearchParams(@json($creditReportParams)).toString();

        button.addEventListener('click', async function () {
            errorBox.hidden = true;
            var original = label.textContent;
            button.disabled = true;
            label.textContent = 'Generating…';

            try {
                var response = await fetch(url, { credentials: 'same-origin' });
                if (!response.ok) {
                    var message = 'Could not generate the credits report.';
                    try {
                        var body = await response.json();
                        if (body && body.message) message = body.message;
                    } catch (ignored) {}
                    throw new Error(message);
                }

                var blob = await response.blob();
                var match = (response.headers.get('Content-Disposition') || '').match(/filename="([^"]+)"/);
                var filename = match ? match[1] : 'ccfmpc-credits-report.pdf';
                var link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                link.remove();
                window.setTimeout(function () { URL.revokeObjectURL(link.href); }, 1000);
            } catch (error) {
                errorBox.textContent = error.message || 'Could not generate the credits report.';
                errorBox.hidden = false;
            } finally {
                button.disabled = false;
                label.textContent = original;
            }
        });
    })();
    </script>
@endif
