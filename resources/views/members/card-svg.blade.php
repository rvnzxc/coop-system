@php
    $displayName = \Illuminate\Support\Str::limit(
        trim($member->first_name.' '.$member->last_name),
        30,
        '...'
    );
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="700" height="440" viewBox="0 0 700 440">
    <defs>
        <linearGradient id="memberCardGradient" x1="0" y1="0" x2="0" y2="1">
            <stop offset="0%" stop-color="#166534" />
            <stop offset="100%" stop-color="#16a34a" />
        </linearGradient>
    </defs>

    <rect width="700" height="440" rx="28" fill="url(#memberCardGradient)" />

    <text x="350" y="72" text-anchor="middle" font-family="Inter, Arial, Helvetica, sans-serif" font-size="28" font-weight="700" letter-spacing="2" fill="#ffffff">
        MEMBERSHIP CARD
    </text>
    <line x1="70" y1="94" x2="630" y2="94" stroke="#ffffff" stroke-opacity="0.3" stroke-width="3" />

    <text x="350" y="176" text-anchor="middle" font-family="Inter, Arial, Helvetica, sans-serif" font-size="30" font-weight="700" fill="#ffffff">
        {{ $displayName }}
    </text>
    <text x="350" y="214" text-anchor="middle" font-family="Inter, Arial, Helvetica, sans-serif" font-size="24" font-weight="700" fill="#dcfce7">
        {{ $member->member_number }}
    </text>

    <rect x="75" y="255" width="550" height="120" rx="12" fill="#ffffff" />
    <g transform="translate(95 270)">
        {!! \App\Services\BarcodeService::generateBarcode($member->member_number, 510) !!}
    </g>

    <text x="350" y="412" text-anchor="middle" font-family="Inter, Arial, Helvetica, sans-serif" font-size="15" fill="#ffffff" fill-opacity="0.85">
        Cavite College of Fisheries Multi-Purpose Cooperative
    </text>
</svg>
