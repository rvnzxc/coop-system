@extends('layouts.app')

@section('title', 'Edit Member')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $phoneGroupClass = 'flex w-full items-center rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/30';
    $photoInitials = collect(preg_split('/\s+/', trim($member->first_name.' '.$member->last_name)))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Edit Member</h2>
            <p class="mt-0.5 text-sm text-slate-500">Update member information</p>
        </div>
        <x-button href="{{ route('members.index') }}" color="secondary" size="sm">
            <x-icon name="arrow-left" class="h-3 w-3" /> Back
        </x-button>
    </div>

    <x-card>
        <form id="memberForm" action="{{ route('members.update', $member->id) }}" method="POST" enctype="multipart/form-data" class="space-y-5" novalidate>
            @csrf
            @method('PUT')

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="First Name" name="first_name" required>
                    <input type="text" id="first_name" name="first_name" value="{{ old('first_name', $member->first_name) }}" required placeholder="Enter first name" autocomplete="given-name" class="{{ $inputClass }}">
                    <p id="first_name_error" class="mt-1.5 hidden text-sm text-red-600"></p>
                </x-field>

                <x-field label="Last Name" name="last_name" required>
                    <input type="text" id="last_name" name="last_name" value="{{ old('last_name', $member->last_name) }}" required placeholder="Enter last name" autocomplete="family-name" class="{{ $inputClass }}">
                    <p id="last_name_error" class="mt-1.5 hidden text-sm text-red-600"></p>
                </x-field>
            </div>

            <div class="grid gap-5 sm:grid-cols-2">
                <x-field label="Email" name="email">
                    <input type="email" id="email" name="email" value="{{ old('email', $member->email) }}" placeholder="member@example.com" class="{{ $inputClass }}">
                </x-field>

                <x-field label="Phone" name="phone">
                    <input type="hidden" id="phone" name="phone" value="{{ old('phone', $member->phone) }}">
                    <div id="phone_group" class="{{ $phoneGroupClass }}">
                        <span class="select-none border-r border-slate-200 pl-3.5 pr-3 text-sm text-slate-900">+63</span>
                        <input type="tel" id="phone_display" inputmode="numeric" autocomplete="tel-national" maxlength="12" placeholder="912 345 6789" class="w-full rounded-r-lg border-0 bg-transparent py-2.5 pl-3 pr-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0" aria-describedby="phone_error">
                    </div>
                <p id="phone_error" class="mt-1.5 text-sm text-red-600 {{ $errors->has('phone') ? '' : 'hidden' }}">{{ $errors->first('phone') }}</p>
            </x-field>
            </div>

            <x-field label="Photo" name="photo">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center">
                    <div id="photo_avatar" class="relative flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-brand-800/70 shadow-sm ring-1 ring-brand-100">
                        <span id="photo_initials" class="text-xl font-semibold text-white {{ $member->photo_url ? 'hidden' : '' }}">
                            @if ($photoInitials !== '')
                                {{ $photoInitials }}
                            @else
                                <x-icon name="user" class="h-8 w-8 text-white/90" />
                            @endif
                        </span>
                        <img id="photo_avatar_image" @if ($member->photo_url) src="{{ $member->photo_url }}" @endif alt="{{ $member->full_name }}" class="absolute inset-0 h-full w-full object-cover {{ $member->photo_url ? '' : 'hidden' }}">
                    </div>

                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" id="photo_upload_button" aria-controls="photo" class="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-brand-600 bg-white px-3.5 py-2 text-sm font-semibold text-brand-700 transition-colors hover:bg-brand-50 focus:outline-none focus:ring-2 focus:ring-brand-500/40">
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                    <path d="M12 16V4m0 0L7 9m5-5 5 5" />
                                    <path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
                                </svg>
                                Upload image
                            </button>
                            <span id="photo_file_name" class="hidden max-w-full truncate text-sm text-slate-600"></span>
                            <button type="button" id="photo_remove" class="hidden text-sm font-medium text-brand-700 hover:underline focus:outline-none focus:ring-2 focus:ring-brand-500/40">Remove</button>
                        </div>
                        <p id="photo_help" class="mt-2 text-xs text-slate-500">JPG, PNG or WEBP. Max 2 MB.</p>
                        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="photo_help photo_client_error" class="sr-only">
                        <p id="photo_client_error" class="mt-1.5 hidden text-sm text-red-600"></p>
                    </div>
                </div>
            </x-field>

            <label class="flex cursor-pointer items-center gap-2.5 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3">

                <input type="checkbox" name="is_active" value="1" {{ old('is_active', $member->is_active) ? 'checked' : '' }} class="h-4 w-4 rounded border-slate-300 text-brand-600 focus:ring-brand-500">
                <span class="text-sm font-medium text-slate-700">Active Member</span>
            </label>

            <div class="rounded-lg border border-slate-200 bg-slate-50 p-4">
                <h4 class="mb-3 text-xs font-semibold uppercase tracking-wider text-slate-500">Member Information</h4>
                <div class="grid gap-4 sm:grid-cols-3">
                    <div>
                        <span class="text-xs text-slate-500">Member ID</span><br>
                        <span class="text-sm font-semibold text-slate-900">{{ $member->member_number }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Total Purchases</span><br>
                        <span class="text-sm font-semibold text-slate-900">&#8369;{{ number_format($member->total_purchases, 2) }}</span>
                    </div>
                    <div>
                        <span class="text-xs text-slate-500">Purchase Count</span><br>
                        <span class="text-sm font-semibold text-slate-900">{{ $member->purchase_count }}</span>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-2">
                <x-button href="{{ route('members.analytics', $member->id) }}" color="secondary">
                    <x-icon name="line-chart" class="h-3.5 w-3.5" /> View Analytics
                </x-button>
                <x-button type="submit" id="memberSubmit" color="primary">
                    <x-icon name="save" class="h-3.5 w-3.5" /> Update Member
                </x-button>
            </div>
        </form>
    </x-card>
</div>

<script>
(function () {
    const form = document.getElementById('memberForm');
    if (!form) return;

    const fields = {
        first_name: { input: document.getElementById('first_name'), error: document.getElementById('first_name_error'), msg: 'Letters only.' },
        last_name: { input: document.getElementById('last_name'), error: document.getElementById('last_name_error'), msg: 'Letters only.' },
    };
    const phoneHidden = document.getElementById('phone');
    const phoneDisplay = document.getElementById('phone_display');
    const phoneGroup = document.getElementById('phone_group');
    const phoneError = document.getElementById('phone_error');
    const submitBtn = document.getElementById('memberSubmit');

    const nameAllowedRe = /^[\p{L}\p{M} .'\-]*$/u;
    const nameValidRe = /^[\p{L}\p{M} .'\-]+$/u;
    const baseInput = ['border-slate-300', 'focus:border-brand-500', 'focus:ring-brand-500/30'];
    const badInput = ['border-red-500', 'focus:border-red-500', 'focus:ring-red-500/30'];
    const baseGroup = ['border-slate-300', 'focus-within:border-brand-500', 'focus-within:ring-brand-500/30'];
    const badGroup = ['border-red-500', 'focus-within:border-red-500', 'focus-within:ring-red-500/30'];

    function setInvalid(el, errEl, msg, group) {
        if (group) { el.classList.remove(...baseGroup); el.classList.add(...badGroup); }
        else { el.classList.remove(...baseInput); el.classList.add(...badInput); }
        if (errEl) { errEl.textContent = msg || ''; errEl.classList.toggle('hidden', !msg); }
    }
    function setValid(el, errEl, group) {
        if (group) { el.classList.remove(...badGroup); el.classList.add(...baseGroup); }
        else { el.classList.remove(...badInput); el.classList.add(...baseInput); }
        if (errEl) { errEl.textContent = ''; errEl.classList.add('hidden'); }
    }
    function cleanNameValue(v) {
        let out = '';
        for (const ch of (v || '')) { if (/[\p{L}\p{M} .'\-]/u.test(ch)) out += ch; }
        return out;
    }
    function normalizeNameRaw(v) { return (v || '').trim().replace(/\s+/g, ' '); }

    function validateName(key, opts) {
        opts = opts || {};
        const finalize = !!opts.finalize;
        const forSubmit = !!opts.forSubmit;
        const f = fields[key];
        let v = f.input.value;
        if (finalize) v = normalizeNameRaw(v);
        f.input.value = v;
        if (v === '') {
            if (forSubmit) setInvalid(f.input, f.error, '');
            else setValid(f.input, f.error);
            return false;
        }
        if (!nameValidRe.test(v)) { setInvalid(f.input, f.error, f.msg); return false; }
        setValid(f.input, f.error);
        return true;
    }

    function attachNameHandlers(key) {
        const f = fields[key];
        f.input.addEventListener('beforeinput', (e) => { if (e.inputType === 'insertText' && e.data && !nameAllowedRe.test(e.data)) e.preventDefault(); });
        f.input.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text') || '';
            const cleaned = cleanNameValue(text);
            if (text !== cleaned) setInvalid(f.input, f.error, f.msg);
            const start = f.input.selectionStart || 0, end = f.input.selectionEnd || 0;
            f.input.setRangeText(cleaned, start, end, 'end');
            if (text === cleaned) validateName(key);
            updateSubmit();
        });
        f.input.addEventListener('input', () => {
            const before = f.input.value;
            const cleaned = cleanNameValue(before);
            if (before !== cleaned) { f.input.value = cleaned; setInvalid(f.input, f.error, f.msg); }
            else { validateName(key); }
            updateSubmit();
        });
        f.input.addEventListener('blur', () => { validateName(key, { finalize: true }); updateSubmit(); });
    }

    function extractPhoneDigits(raw) {
        let d = (raw || '').replace(/\D+/g, '');
        if (d.startsWith('63')) d = d.slice(2);
        else if (d.startsWith('0')) d = d.slice(1);
        return d.slice(0, 10);
    }
    function formatPhone(d) {
        if (!d) return '';
        let out = d.slice(0, 3);
        if (d.length > 3) out += ' ' + d.slice(3, 6);
        if (d.length > 6) out += ' ' + d.slice(6, 10);
        return out;
    }
    function getPhoneDigits() { return extractPhoneDigits(phoneDisplay.value); }
    function syncPhoneHidden() { const d = getPhoneDigits(); phoneHidden.value = d ? ('+63' + d) : ''; }

    function validatePhone() {
        const d = getPhoneDigits();
        const hasAny = (phoneDisplay.value || '').trim() !== '' || d !== '';
        syncPhoneHidden();
        if (!hasAny) { setValid(phoneGroup, phoneError, true); return true; }
        if (d.length !== 10 || d[0] !== '9') { setInvalid(phoneGroup, phoneError, 'Enter a valid PH mobile number (e.g. +63 912 345 6789).', true); return false; }
        setValid(phoneGroup, phoneError, true);
        return true;
    }

    phoneDisplay.addEventListener('beforeinput', (e) => {
        if (e.inputType === 'insertText' && e.data && /\D/.test(e.data)) {
            const digitsInData = e.data.replace(/\D/g, '');
            if (!digitsInData) e.preventDefault();
        }
    });
    phoneDisplay.addEventListener('paste', (e) => {
        e.preventDefault();
        const text = (e.clipboardData || window.clipboardData).getData('text') || '';
        phoneDisplay.value = formatPhone(extractPhoneDigits(text));
        validatePhone(); updateSubmit();
    });
    phoneDisplay.addEventListener('input', () => {
        let d = extractPhoneDigits(phoneDisplay.value);
        if (d.length > 0 && d[0] !== '9') d = '';
        phoneDisplay.value = formatPhone(d.slice(0, 10));
        validatePhone(); updateSubmit();
    });
    phoneDisplay.addEventListener('blur', () => { validatePhone(); updateSubmit(); });

    function updateSubmit() {
        const fOk = nameValidRe.test(normalizeNameRaw(fields.first_name.input.value)) && nameValidRe.test(normalizeNameRaw(fields.last_name.input.value));
        const d = getPhoneDigits();
        const pEmpty = (phoneDisplay.value || '').replace(/\s/g, '') === '';
        const pOk = pEmpty ? true : (d.length === 10 && d[0] === '9');
        const ok = fOk && pOk;
        if (submitBtn) {
            submitBtn.disabled = !ok;
            submitBtn.classList.toggle('opacity-50', !ok);
            submitBtn.classList.toggle('cursor-not-allowed', !ok);
        }
    }

    attachNameHandlers('first_name');
    attachNameHandlers('last_name');

    (function initPhone() {
        const initial = phoneHidden.value || '';
        const d = extractPhoneDigits(initial);
        phoneDisplay.value = formatPhone(d);
        syncPhoneHidden();
    })();
    setValid(fields.first_name.input, fields.first_name.error);
    setValid(fields.last_name.input, fields.last_name.error);
    if ((phoneDisplay.value || '').trim() !== '') validatePhone(); else { syncPhoneHidden(); setValid(phoneGroup, phoneError, true); }
    updateSubmit();

    const photoInput = document.getElementById('photo');
    const photoUploadButton = document.getElementById('photo_upload_button');
    const photoAvatarImage = document.getElementById('photo_avatar_image');
    const photoInitials = document.getElementById('photo_initials');
    const photoFileName = document.getElementById('photo_file_name');
    const photoRemove = document.getElementById('photo_remove');
    const photoClientError = document.getElementById('photo_client_error');
    const savedPhotoSrc = photoAvatarImage ? (photoAvatarImage.getAttribute('src') || '') : '';
    const allowedPhotoTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const allowedPhotoExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    const maxPhotoBytes = 2 * 1024 * 1024;
    let photoObjectUrl = null;

    function showPhotoClientError(message) {
        photoClientError.textContent = message;
        photoClientError.classList.remove('hidden');
        photoInput.setAttribute('aria-invalid', 'true');
    }

    function clearPhotoClientError() {
        photoClientError.textContent = '';
        photoClientError.classList.add('hidden');
        photoInput.removeAttribute('aria-invalid');
    }

    function releasePhotoObjectUrl() {
        if (photoObjectUrl) {
            URL.revokeObjectURL(photoObjectUrl);
            photoObjectUrl = null;
        }
    }

    function restoreSavedPhoto() {
        releasePhotoObjectUrl();
        photoFileName.textContent = '';
        photoFileName.classList.add('hidden');
        photoRemove.classList.add('hidden');

        if (savedPhotoSrc) {
            photoAvatarImage.src = savedPhotoSrc;
            photoAvatarImage.classList.remove('hidden');
            photoInitials.classList.add('hidden');
        } else {
            photoAvatarImage.removeAttribute('src');
            photoAvatarImage.classList.add('hidden');
            photoInitials.classList.remove('hidden');
        }
    }

    if (photoInput && photoUploadButton && photoClientError) {
        photoUploadButton.addEventListener('click', () => photoInput.click());

        photoRemove.addEventListener('click', () => {
            photoInput.value = '';
            clearPhotoClientError();
            restoreSavedPhoto();
            photoUploadButton.focus();
        });

        photoInput.addEventListener('change', () => {
            clearPhotoClientError();

            const file = photoInput.files && photoInput.files[0];
            if (!file) {
                restoreSavedPhoto();
                return;
            }

            const extension = (file.name.split('.').pop() || '').toLowerCase();
            const validType = allowedPhotoTypes.includes(file.type) || (!file.type && allowedPhotoExtensions.includes(extension));
            if (!validType || file.size > maxPhotoBytes) {
                photoInput.value = '';
                restoreSavedPhoto();
                showPhotoClientError('Please choose a JPG, PNG or WEBP image under 2 MB.');
                return;
            }

            releasePhotoObjectUrl();
            photoObjectUrl = URL.createObjectURL(file);
            photoAvatarImage.src = photoObjectUrl;
            photoAvatarImage.classList.remove('hidden');
            photoInitials.classList.add('hidden');
            photoFileName.textContent = file.name;
            photoFileName.classList.remove('hidden');
            photoRemove.classList.remove('hidden');
        });

        window.addEventListener('beforeunload', releasePhotoObjectUrl);
    }

    form.addEventListener('submit', (e) => {
        if (!photoClientError.classList.contains('hidden')) {
            e.preventDefault();
            photoUploadButton.focus();
            return;
        }

        const ok1 = validateName('first_name', { finalize: true, forSubmit: true });
        const ok2 = validateName('last_name', { finalize: true, forSubmit: true });
        const ok3 = validatePhone();
        updateSubmit();
        if (!(ok1 && ok2 && ok3)) {
            e.preventDefault();
            const firstBad = form.querySelector('.border-red-500, .focus-within\\:border-red-500');
            if (firstBad) { const inp = firstBad.querySelector('input') || firstBad; inp.focus(); }
        } else {
            syncPhoneHidden();
        }
    });
})();
</script>
@endsection
