@php
    $member = $member ?? null;
    $photoUrl = $member?->photo_url;
    $removeRequested = (string) old('remove_photo', 0) === '1';
    $showCurrentPhoto = $photoUrl && ! $removeRequested;
    $photoName = trim(
        ($member?->first_name ?? old('first_name', '')).' '.($member?->last_name ?? old('last_name', ''))
    );
    $photoInitials = collect(preg_split('/\s+/', $photoName))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))
        ->implode('');
    $photoAlt = $photoName !== '' ? $photoName : 'Member photo';
@endphp

<input type="hidden" name="remove_photo" id="remove_photo" value="{{ old('remove_photo', 0) ? 1 : 0 }}">

<div class="flex flex-col gap-4 sm:flex-row sm:items-center" data-photo-upload>
    <div class="relative flex h-28 w-28 shrink-0 items-center justify-center overflow-hidden rounded-full border-2 border-white bg-brand-100 shadow-sm ring-2 ring-brand-200">
        <span data-photo-initials class="text-2xl font-bold text-brand-800 {{ $showCurrentPhoto ? 'hidden' : '' }}">
            @if($photoInitials !== '')
                {{ $photoInitials }}
            @else
                <x-icon name="user" class="h-9 w-9 text-brand-700/80" />
            @endif
        </span>
        <img data-photo-image @if($showCurrentPhoto) src="{{ $photoUrl }}" @endif alt="{{ $photoAlt }}" class="absolute inset-0 h-full w-full object-cover {{ $showCurrentPhoto ? '' : 'hidden' }}">
    </div>

    <div class="min-w-0 flex-1">
        <input type="file" id="photo" name="photo" accept="image/jpeg,image/png,image/webp" aria-describedby="photo_help photo_error" data-photo-input class="peer sr-only">

        <label for="photo" data-photo-dropzone tabindex="0" class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded-xl border-2 border-dashed border-brand-200 bg-brand-50/40 px-4 py-5 text-center transition-colors hover:border-brand-400 hover:bg-brand-50 peer-focus-visible:outline-none peer-focus-visible:ring-2 peer-focus-visible:ring-brand-500/40 data-[dragging=true]:border-brand-600 data-[dragging=true]:bg-brand-100">
            <svg class="h-7 w-7 text-brand-600" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                <path d="M12 16V4m0 0L7 9m5-5 5 5" />
                <path d="M4 16v3a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1v-3" />
            </svg>
            <span class="text-sm font-semibold text-slate-700">Click to upload or drag and drop</span>
            <span id="photo_help" class="text-xs text-slate-500">JPG, PNG or WEBP. Max 2 MB.</span>
        </label>

        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1">
            <span data-photo-file-name class="hidden max-w-full truncate text-sm font-medium text-slate-700"></span>
            <span data-photo-file-size class="hidden text-xs text-slate-500"></span>
            <button type="button" data-photo-change class="hidden text-sm font-semibold text-brand-700 hover:underline focus:outline-none focus:ring-2 focus:ring-brand-500/40">Change</button>
            <button type="button" data-photo-remove class="{{ $showCurrentPhoto ? '' : 'hidden' }} text-sm font-semibold text-red-600 hover:underline focus:outline-none focus:ring-2 focus:ring-red-500/40">Remove photo</button>
        </div>

        <p id="photo_error" data-photo-error class="mt-2 text-sm text-red-600 {{ $errors->has('photo') ? '' : 'hidden' }}">{{ $errors->first('photo') }}</p>
    </div>
</div>

<script>
(function () {
    const root = document.querySelector('[data-photo-upload]');
    if (!root) return;

    const input = root.querySelector('[data-photo-input]');
    const dropzone = root.querySelector('[data-photo-dropzone]');
    const image = root.querySelector('[data-photo-image]');
    const initials = root.querySelector('[data-photo-initials]');
    const fileName = root.querySelector('[data-photo-file-name]');
    const fileSize = root.querySelector('[data-photo-file-size]');
    const changeButton = root.querySelector('[data-photo-change]');
    const removeButton = root.querySelector('[data-photo-remove]');
    const errorElement = root.querySelector('[data-photo-error]');
    const removeField = document.getElementById('remove_photo');
    const form = root.closest('form');
    const savedSrc = @json($photoUrl ?: '');
    const allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
    const allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
    const maxBytes = 2 * 1024 * 1024;
    let objectUrl = null;
    let hasNewFile = false;
    let removeSaved = removeField && removeField.value === '1';

    function formatSize(bytes) {
        if (bytes < 1024) return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    function showError(message) {
        errorElement.textContent = message;
        errorElement.classList.remove('hidden');
        input.setAttribute('aria-invalid', 'true');
        dropzone.classList.add('border-red-400', 'bg-red-50');
        dropzone.classList.remove('border-brand-200', 'bg-brand-50/40');
    }

    function clearError() {
        errorElement.textContent = '';
        errorElement.classList.add('hidden');
        input.removeAttribute('aria-invalid');
        dropzone.classList.remove('border-red-400', 'bg-red-50');
        dropzone.classList.add('border-brand-200', 'bg-brand-50/40');
    }

    function releaseObjectUrl() {
        if (objectUrl) {
            URL.revokeObjectURL(objectUrl);
            objectUrl = null;
        }
    }

    function updateRemoveButton() {
        removeButton.classList.toggle('hidden', (savedSrc === '' || removeSaved) && !hasNewFile);
    }

    function resetFileDetails() {
        fileName.textContent = '';
        fileName.classList.add('hidden');
        fileSize.textContent = '';
        fileSize.classList.add('hidden');
        changeButton.classList.add('hidden');
    }

    function showSavedPhoto() {
        releaseObjectUrl();
        hasNewFile = false;
        input.value = '';
        resetFileDetails();

        if (savedSrc && !removeSaved) {
            image.src = savedSrc;
            image.classList.remove('hidden');
            initials.classList.add('hidden');
        } else {
            image.removeAttribute('src');
            image.classList.add('hidden');
            initials.classList.remove('hidden');
        }

        if (removeField) removeField.value = removeSaved ? '1' : '0';
        updateRemoveButton();
    }

    function setInputFile(file) {
        try {
            const transfer = new DataTransfer();
            transfer.items.add(file);
            input.files = transfer.files;
            return true;
        } catch (error) {
            return false;
        }
    }

    function handleFile(file) {
        clearError();

        if (!file) {
            showSavedPhoto();
            return;
        }

        const extension = (file.name.split('.').pop() || '').toLowerCase();
        const validType = allowedTypes.includes(file.type) || (!file.type && allowedExtensions.includes(extension));

        if (!validType) {
            input.value = '';
            showSavedPhoto();
            showError('Please choose a JPG, PNG or WEBP image.');
            return;
        }

        if (file.size > maxBytes) {
            input.value = '';
            showSavedPhoto();
            showError('Photo must not be larger than 2 MB.');
            return;
        }

        releaseObjectUrl();
        objectUrl = URL.createObjectURL(file);
        image.src = objectUrl;
        image.classList.remove('hidden');
        initials.classList.add('hidden');
        fileName.textContent = file.name;
        fileName.classList.remove('hidden');
        fileSize.textContent = formatSize(file.size);
        fileSize.classList.remove('hidden');
        changeButton.classList.remove('hidden');
        hasNewFile = true;
        removeSaved = false;

        if (removeField) removeField.value = '0';
        updateRemoveButton();
    }

    input.addEventListener('change', () => handleFile(input.files && input.files[0]));

    dropzone.addEventListener('keydown', (event) => {
        if (event.key === 'Enter' || event.key === ' ') {
            event.preventDefault();
            input.click();
        }
    });

    ['dragenter', 'dragover'].forEach((type) => {
        dropzone.addEventListener(type, (event) => {
            event.preventDefault();
            event.stopPropagation();
            dropzone.dataset.dragging = 'true';
        });
    });

    dropzone.addEventListener('dragleave', (event) => {
        event.preventDefault();
        event.stopPropagation();
        if (dropzone.contains(event.relatedTarget)) return;
        delete dropzone.dataset.dragging;
    });

    dropzone.addEventListener('drop', (event) => {
        event.preventDefault();
        event.stopPropagation();
        delete dropzone.dataset.dragging;

        const file = event.dataTransfer && event.dataTransfer.files && event.dataTransfer.files[0];

        if (file && !setInputFile(file)) {
            handleFile(null);
            showError('Your browser does not support drag-and-drop uploads. Please click to choose a file.');
            return;
        }

        handleFile(file);
    });

    changeButton.addEventListener('click', () => input.click());

    removeButton.addEventListener('click', () => {
        clearError();

        if (hasNewFile) {
            showSavedPhoto();
            return;
        }

        if (savedSrc) {
            releaseObjectUrl();
            input.value = '';
            image.removeAttribute('src');
            image.classList.add('hidden');
            initials.classList.remove('hidden');
            resetFileDetails();
            removeSaved = true;
            removeButton.classList.add('hidden');
            if (removeField) removeField.value = '1';
        }
    });

    if (form) {
        form.addEventListener('submit', (event) => {
            if (!errorElement.classList.contains('hidden')) {
                event.preventDefault();
                input.focus();
            }
        });
    }

    window.addEventListener('beforeunload', releaseObjectUrl);
})();
</script>
