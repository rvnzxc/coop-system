@php
    $displayName = \Illuminate\Support\Str::limit(
        trim($member->first_name.' '.$member->last_name),
        30,
        '...'
    );

    $initials = collect(preg_split('/\s+/', trim($member->first_name.' '.$member->last_name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');

    $backgroundPath = public_path('images/IDCARD.jpg');
    $backgroundImage = is_file($backgroundPath)
        ? 'data:image/jpeg;base64,'.base64_encode(file_get_contents($backgroundPath))
        : null;

    $photo = $member->photo_url ?? $member->photo ?? null;
    $photoSource = null;

    if ($photo) {
        if (str_starts_with($photo, 'data:')) {
            $photoSource = $photo;
        } else {
            $path = (string) (parse_url($photo, PHP_URL_PATH) ?? $photo);
            $relative = ltrim($path, '/');
            $storageRelative = $relative;

            if (preg_match('#(?:^|/)storage/(.+)$#', $path, $matches)) {
                $storageRelative = ltrim($matches[1], '/');
            }

            $candidates = [
                public_path($relative),
                public_path('storage/'.$storageRelative),
                \Illuminate\Support\Facades\Storage::disk('public')->path($storageRelative),
                storage_path('app/public/'.$storageRelative),
            ];

            foreach ($candidates as $candidate) {
                if (is_file($candidate)) {
                    $mime = @mime_content_type($candidate) ?: 'image/jpeg';
                    $photoSource = 'data:'.$mime.';base64,'.base64_encode(file_get_contents($candidate));
                    break;
                }
            }

            if (! $photoSource && (str_starts_with($photo, 'http://') || str_starts_with($photo, 'https://'))) {
                $photoSource = $photo;
            }
        }
    }
@endphp
<svg xmlns="http://www.w3.org/2000/svg" width="700" height="450" viewBox="0 0 700 450">
    <defs>
        <clipPath id="memberCardClip">
            <rect width="700" height="450" rx="32" />
        </clipPath>
        <clipPath id="memberAvatarClip">
            <circle cx="176" cy="176" r="60" />
        </clipPath>
        <linearGradient id="memberCardGradient" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0%" stop-color="#14532d" />
            <stop offset="55%" stop-color="#166534" />
            <stop offset="100%" stop-color="#15803d" />
        </linearGradient>
    </defs>

    <g clip-path="url(#memberCardClip)">
        @if($backgroundImage)
        <image href="{{ $backgroundImage }}" x="0" y="0" width="700" height="450" preserveAspectRatio="xMidYMid slice" />
        @else
        <rect width="700" height="450" fill="url(#memberCardGradient)" />
        @endif

        <circle cx="176" cy="176" r="60" fill="#14532d" fill-opacity="0.55" />
        @if($initials)
        <text x="176" y="190" text-anchor="middle" font-family="Inter, Arial, Helvetica, sans-serif" font-size="40" font-weight="600" fill="#ffffff">
            {{ $initials }}
        </text>
        @else
        <circle cx="176" cy="166" r="16" fill="#ffffff" fill-opacity="0.9" />
        <path d="M144 210c0-17.7 14.3-32 32-32s32 14.3 32 32" fill="#ffffff" fill-opacity="0.9" />
        @endif
        @if($photoSource)
        <image href="{{ $photoSource }}" x="116" y="116" width="120" height="120" preserveAspectRatio="xMidYMid slice" clip-path="url(#memberAvatarClip)" />
        @endif
        <circle cx="176" cy="176" r="62" fill="none" stroke="#ffffff" stroke-opacity="0.45" stroke-width="4" />

        <text x="264" y="169" font-family="Inter, Arial, Helvetica, sans-serif" font-size="28" font-weight="600" fill="#ffffff">
            {{ $displayName }}
        </text>
        <text x="264" y="202" font-family="Inter, Arial, Helvetica, sans-serif" font-size="22" font-weight="600" fill="#ffffff" fill-opacity="0.88">
            {{ $member->member_number }}
        </text>

        <rect x="81" y="271" width="538" height="100" rx="8" fill="#ffffff" />
        <g transform="translate(97 281) scale(2)">
            {!! \App\Services\BarcodeService::generateBarcode($member->member_number, 253, false) !!}
        </g>
    </g>
</svg>
