<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $meta['title'] }}</title>
    <style>
        @page { margin: 28px 28px 36px 28px; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 9px; color: #0f172a; margin: 0; }
        .masthead { border-bottom: 2px solid #16a34a; padding-bottom: 10px; margin-bottom: 12px; }
        .masthead-table { width: 100%; border-collapse: collapse; }
        .masthead-table td { vertical-align: middle; }
        .logo { width: 52px; height: 52px; }
        .coop-name { font-size: 15px; font-weight: bold; color: #14532d; line-height: 1.25; }
        .coop-sub { font-size: 9px; color: #475569; margin-top: 2px; }
        .sheet-title { font-size: 13px; font-weight: bold; color: #15803d; margin: 10px 0 6px; text-transform: uppercase; letter-spacing: .4px; }
        .meta { font-size: 8.5px; color: #475569; line-height: 1.6; margin-bottom: 10px; }
        .meta strong { color: #14532d; }
        .summary { width: 100%; border-collapse: separate; border-spacing: 6px 0; margin: 0 0 10px -6px; }
        .summary td { width: 25%; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 4px; padding: 7px 9px; }
        .summary .label { font-size: 7.5px; color: #15803d; text-transform: uppercase; letter-spacing: .3px; }
        .summary .value { font-size: 12px; font-weight: bold; color: #14532d; margin-top: 3px; }
        .note { background: #fffbeb; border-left: 3px solid #f59e0b; color: #78350f; padding: 6px 8px; margin: 0 0 10px; font-size: 8px; }
        .section-title { font-size: 10px; font-weight: bold; color: #14532d; margin: 16px 0 5px; }
        table.data { width: 100%; border-collapse: collapse; table-layout: fixed; }
        table.data thead { display: table-header-group; }
        table.data th { background: #16a34a; color: #fff; font-size: 8px; text-align: left; padding: 6px 7px; border: 1px solid #15803d; }
        table.data th.num, table.data td.num { text-align: right; }
        table.data td { padding: 5px 7px; border: 1px solid #e2e8f0; overflow: hidden; word-wrap: break-word; }
        table.data tbody tr:nth-child(even) { background: #f8fafc; }
        table.data tbody tr.low { background: #fef2f2; color: #b91c1c; font-weight: bold; }
        table.data tfoot td { background: #dcfce7; border-color: #86efac; font-weight: bold; color: #14532d; }
        .empty { text-align: center; color: #64748b; font-style: italic; padding: 18px !important; }
    </style>
</head>
<body>
    @php
        $cell = function ($value, int $index) use ($currencyColumns) {
            if (in_array($index, $currencyColumns, true) && is_numeric($value)) {
                return "\u{20B1}".number_format((float) $value, 2);
            }

            if ($value === null || $value === '') {
                return '&mdash;';
            }

            return e((string) $value);
        };
    @endphp

    <div class="masthead">
        <table class="masthead-table">
            <tr>
                <td style="width: 60px;">
                    @if($meta['logoData'] !== '')
                        <img class="logo" src="{{ $meta['logoData'] }}" alt="CCFMPC logo">
                    @endif
                </td>
                <td>
                    <div class="coop-name">Cavite College of Fisheries Multi-Purpose Cooperative</div>
                    <div class="coop-sub">CCFMPC · Cooperative Store</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="sheet-title">{{ $meta['title'] }}</div>
    <div class="meta">
        <div><strong>Period:</strong> {{ $meta['periodLabel'] }}</div>
        <div><strong>Filters:</strong> {{ $meta['filtersLabel'] }}</div>
        <div><strong>Generated:</strong> {{ $meta['generatedAt'] }} (Asia/Manila) · <strong>By:</strong> {{ $meta['generatedBy'] }}</div>
    </div>

    @if(! empty($summary))
        <table class="summary">
            <tr>
                @foreach($summary as $box)
                    <td>
                        <div class="label">{{ $box['label'] }}</div>
                        <div class="value">{{ $box['value'] }}</div>
                    </td>
                @endforeach
            </tr>
        </table>
    @endif

    @if($note !== '')
        <p class="note">{{ $note }}</p>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach($columns as $index => $column)
                    <th class="{{ in_array($index, $currencyColumns, true) ? 'num' : '' }}">{{ $column }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr class="{{ $row['class'] ?? '' }}">
                    @foreach($row['cells'] as $index => $value)
                        <td class="{{ in_array($index, $currencyColumns, true) ? 'num' : '' }}">{!! $cell($value, (int) $index) !!}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td class="empty" colspan="{{ count($columns) }}">No records for this period</td>
                </tr>
            @endforelse
        </tbody>
        @if($totals !== [])
            <tfoot>
                <tr>
                    @foreach($totals as $index => $value)
                        <td class="{{ in_array($index, $currencyColumns, true) ? 'num' : '' }}">{!! $cell($value, (int) $index) !!}</td>
                    @endforeach
                </tr>
            </tfoot>
        @endif
    </table>

    @foreach($sections as $section)
        <div class="section-title">{{ $section['title'] }}</div>
        <table class="data">
            <thead>
                <tr>
                    @foreach($section['columns'] as $index => $column)
                        <th class="{{ in_array($index, $section['currencyColumns'] ?? [], true) ? 'num' : '' }}">{{ $column }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @forelse($section['rows'] as $row)
                    <tr class="{{ $row['class'] ?? '' }}">
                        @foreach($row['cells'] as $index => $value)
                            <td class="{{ in_array($index, $section['currencyColumns'] ?? [], true) ? 'num' : '' }}">{!! $cell($value, (int) $index) !!}</td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td class="empty" colspan="{{ count($section['columns']) }}">No records for this period</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endforeach
</body>
</html>
