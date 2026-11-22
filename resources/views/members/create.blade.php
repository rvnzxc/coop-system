@extends('layouts.app')

@section('title', 'Add Member')

@section('content')
@php
    $inputClass = 'w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $phoneGroupClass = 'flex w-full items-center rounded-lg border border-slate-300 bg-white text-sm text-slate-900 focus-within:border-brand-500 focus-within:ring-2 focus-within:ring-brand-500/30';
@endphp

<div class="mx-auto max-w-xl">
    <div class="mb-6 flex items-center justify-between gap-3">
        <div>
            <h2 class="text-xl font-bold text-slate-900">Add New Member</h2>
            <p class="mt-0.5 text-sm text-slate-500">Register a cooperative member</p>
        </div>
        <x-button href="{{ route('members.index') }}" color="secondary" size="sm">
            <x-icon name="arrow-left" class="h-3 w-3" /> Back
        </x-button>
    </div>

    <x-card>
        <form id="memberForm" action="{{ route('members.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5" novalidate>
            @csrf

            <x-field label="First Name" name="first_name" required>
                <input type="text" id="first_name" name="first_name" value="{{ old('first_name') }}" required autocomplete="given-name" class="{{ $inputClass }}">
                <p id="first_name_error" class="mt-1.5 hidden text-sm text-red-600"></p>
            </x-field>

            <x-field label="Last Name" name="last_name" required>
                <input type="text" id="last_name" name="last_name" value="{{ old('last_name') }}" required autocomplete="family-name" class="{{ $inputClass }}">
                <p id="last_name_error" class="mt-1.5 hidden text-sm text-red-600"></p>
            </x-field>

            <x-field label="Email" name="email">
                <input type="email" id="email" name="email" value="{{ old('email') }}" placeholder="member@example.com" class="{{ $inputClass }}">
            </x-field>

            <x-field label="Phone" name="phone">
                <input type="hidden" id="phone" name="phone" value="{{ old('phone') }}">
                <div id="phone_group" class="{{ $phoneGroupClass }}">
                    <span class="select-none border-r border-slate-200 pl-3.5 pr-3 text-sm text-slate-900">+63</span>
                    <input type="tel" id="phone_display" inputmode="numeric" autocomplete="tel-national" maxlength="12" placeholder="912 345 6789" class="w-full rounded-r-lg border-0 bg-transparent py-2.5 pl-3 pr-3.5 text-sm text-slate-900 placeholder:text-slate-400 focus:outline-none focus:ring-0" aria-describedby="phone_error">
                </div>
                <p id="phone_error" class="mt-1.5 text-sm text-red-600 {{ $errors->has('phone') ? '' : 'hidden' }}">{{ $errors->first('phone') }}</p>
            </x-field>

            <div>
                <span class="mb-1.5 block text-sm font-medium text-slate-700">Photo</span>
                @include('members._photo-upload')
            </div>

            <div class="flex gap-3 pt-2">
                <x-button type="submit" id="memberSubmit" color="primary" class="flex-1">
                    <x-icon name="user-plus" class="h-3.5 w-3.5" /> Add Member
                </x-button>
                <x-button type="button" color="secondary" onclick="location.href='{{ route('members.index') }}'">
                    Cancel
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
        if (group) {
            el.classList.remove(...baseGroup);
            el.classList.add(...badGroup);
        } else {
            el.classList.remove(...baseInput);
            el.classList.add(...badInput);
        }
        if (errEl) { errEl.textContent = msg || ''; errEl.classList.toggle('hidden', !msg); }
    }
    function setValid(el, errEl, group) {
        if (group) {
            el.classList.remove(...badGroup);
            el.classList.add(...baseGroup);
        } else {
            el.classList.remove(...badInput);
            el.classList.add(...baseInput);
        }
        if (errEl) { errEl.textContent = ''; errEl.classList.add('hidden'); }
    }

    function cleanNameValue(v) {
        let out = '';
        for (const ch of (v || '')) {
            if (/[\p{L}\p{M} .'\-]/u.test(ch)) out += ch;
        }
        return out;
    }

    function normalizeNameRaw(v) {
        return (v || '').trim().replace(/\s+/g, ' ');
    }

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
        if (!nameValidRe.test(v)) {
            setInvalid(f.input, f.error, f.msg);
            return false;
        }
        setValid(f.input, f.error);
        return true;
    }

    function attachNameHandlers(key) {
        const f = fields[key];
        f.input.addEventListener('beforeinput', (e) => {
            if (e.inputType === 'insertText' && e.data && !nameAllowedRe.test(e.data)) {
                e.preventDefault();
            }
        });
        f.input.addEventListener('paste', (e) => {
            e.preventDefault();
            const text = (e.clipboardData || window.clipboardData).getData('text') || '';
            const cleaned = cleanNameValue(text);
            if (text !== cleaned) setInvalid(f.input, f.error, f.msg);
            const start = f.input.selectionStart || 0;
            const end = f.input.selectionEnd || 0;
            f.input.setRangeText(cleaned, start, end, 'end');
            if (text === cleaned) validateName(key);
            updateSubmit();
        });
        f.input.addEventListener('input', (e) => {
            const before = f.input.value;
            const cleaned = cleanNameValue(before);
            if (before !== cleaned) {
                f.input.value = cleaned;
                setInvalid(f.input, f.error, f.msg);
            } else {
                validateName(key);
            }
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

    function getPhoneDigits() {
        return extractPhoneDigits(phoneDisplay.value);
    }

    function syncPhoneHidden() {
        const d = getPhoneDigits();
        phoneHidden.value = d ? ('+63' + d) : '';
    }

    function validatePhone() {
        const d = getPhoneDigits();
        const hasAny = (phoneDisplay.value || '').trim() !== '' || d !== '';
        syncPhoneHidden();
        if (!hasAny) {
            setValid(phoneGroup, phoneError, true);
            return true;
        }
        if (d.length !== 10 || d[0] !== '9') {
            setInvalid(phoneGroup, phoneError, 'Enter a valid PH mobile number (e.g. +63 912 345 6789).', true);
            return false;
        }
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
        const d = extractPhoneDigits(text);
        phoneDisplay.value = formatPhone(d);
        validatePhone();
        updateSubmit();
    });
    phoneDisplay.addEventListener('input', () => {
        let d = getPhoneDigits();
        if (d.length > 0 && d[0] !== '9') {
            d = d[0] === '0' ? extractPhoneDigits('0' + d) : d; // handled, but block leading non-9
        }
        // block if first digit not 9
        d = extractPhoneDigits(phoneDisplay.value);
        if (d.length === 1 && d[0] !== '9') d = '';
        if (d.length > 1 && d[0] !== '9') d = d[0] === '9' ? d : d.replace(/^[^9]+/, '');
        if (d.length > 0 && d[0] !== '9') d = '';
        phoneDisplay.value = formatPhone(d.slice(0, 10));
        validatePhone();
        updateSubmit();
    });
    phoneDisplay.addEventListener('blur', () => { validatePhone(); updateSubmit(); });

    function formValid() {
        const n1 = validateName('first_name', false);
        const n2 = validateName('last_name', false);
        const p = validatePhone();
        return n1 && n2 && p;
    }

    function updateSubmit() {
        // compute without forcing trim for names except blur; lightweight check
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

    // init from old/posted value (e.g. +639..., 091..., spaced)
    (function initPhone() {
        const initial = phoneHidden.value || '';
        const d = extractPhoneDigits(initial);
        phoneDisplay.value = formatPhone(d);
        syncPhoneHidden();
    })();
    // initial state
    setValid(fields.first_name.input, fields.first_name.error);
    setValid(fields.last_name.input, fields.last_name.error);
    if ((phoneDisplay.value || '').trim() !== '') validatePhone(); else { syncPhoneHidden(); setValid(phoneGroup, phoneError, true); }
    updateSubmit();

    form.addEventListener('submit', (e) => {
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
