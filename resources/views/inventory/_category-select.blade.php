@php
    $canManageCategories = auth()->user()?->isAdmin() ?? false;
    $selectedName = (string) old('category', $selected ?? '');
    $addCategoryValue = '__add_category__';
    $knownNames = $categories->pluck('name')->map(fn ($name) => mb_strtolower(trim((string) $name)));
    $hasUnknownSelection = $selectedName !== '' && ! $knownNames->contains(mb_strtolower(trim($selectedName)));
@endphp

<select id="category" name="category" required class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30" data-category-select>
    <option value="">Select Category</option>
    @foreach($categories as $category)
        <option value="{{ $category->name }}" {{ mb_strtolower(trim($selectedName)) === mb_strtolower(trim((string) $category->name)) ? 'selected' : '' }}>{{ $category->name }}</option>
    @endforeach
    @if($hasUnknownSelection)
        <option value="{{ $selectedName }}" selected>{{ $selectedName }}</option>
    @endif
    @if($canManageCategories)
        <option disabled>──────────</option>
        <option value="{{ $addCategoryValue }}" class="font-semibold text-brand-700" data-add-category-option>+ Add new category</option>
    @endif
</select>

@if($canManageCategories)
    <div class="fixed inset-0 z-50 hidden items-center justify-center bg-slate-900/50 p-4" role="dialog" aria-modal="true" aria-labelledby="addCategoryTitle" tabindex="-1" data-category-modal>
        <div class="w-full max-w-md rounded-xl border border-brand-100 bg-white p-6 shadow-xl" data-category-modal-panel>
            <div class="mb-4 flex items-start justify-between gap-4">
                <div>
                    <h3 id="addCategoryTitle" class="text-base font-semibold text-slate-900">Add Category</h3>
                    <p class="mt-0.5 text-xs text-slate-500">Create a category and use it on this item.</p>
                </div>
                <x-icon name="times" class="h-4 w-4 text-slate-400" />
            </div>

            <label for="new_category_name" class="mb-1.5 block text-sm font-medium text-slate-700">Category name</label>
            <input type="text" id="new_category_name" data-category-name maxlength="50" autocomplete="off" autofocus class="w-full rounded-lg border border-slate-300 bg-white px-3.5 py-2.5 text-sm text-slate-900 placeholder:text-slate-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30" placeholder="e.g. Frozen">

            <p class="mt-2 hidden text-sm text-red-600" role="alert" aria-live="polite" data-category-error></p>

            <div class="mt-5 flex justify-end gap-3">
                <x-button type="button" color="secondary" data-category-cancel>Cancel</x-button>
                <x-button type="button" color="primary" data-category-submit>Add</x-button>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const select = document.querySelector('[data-category-select]');
        const modal = document.querySelector('[data-category-modal]');
        if (!select || !modal) return;

        const input = modal.querySelector('[data-category-name]');
        const errorText = modal.querySelector('[data-category-error]');
        const cancelButton = modal.querySelector('[data-category-cancel]');
        const submitButton = modal.querySelector('[data-category-submit]');
        const storeUrl = @json(route('categories.store'));
        const csrfToken = @json(csrf_token());
        const addValue = @json($addCategoryValue);

        let previousValue = '';
        let submitting = false;

        function focusableElements() {
            return Array.from(modal.querySelectorAll('button:not([disabled]), input:not([disabled])'));
        }

        function trapFocus(event) {
            if (event.key !== 'Tab') return;

            const elements = focusableElements();
            if (!elements.length) return;

            const first = elements[0];
            const last = elements[elements.length - 1];

            if (event.shiftKey && document.activeElement === first) {
                event.preventDefault();
                last.focus();
            } else if (!event.shiftKey && document.activeElement === last) {
                event.preventDefault();
                first.focus();
            }
        }

        function clearError() {
            errorText.textContent = '';
            errorText.classList.add('hidden');
            input.removeAttribute('aria-invalid');
        }

        function showError(message) {
            errorText.textContent = message || 'Could not add the category. Please try again.';
            errorText.classList.remove('hidden');
            input.setAttribute('aria-invalid', 'true');
            input.focus();
        }

        function openModal() {
            previousValue = select.value;
            clearError();
            input.value = '';
            modal.classList.remove('hidden');
            modal.classList.add('flex');
            input.focus();
        }

        function closeModal(restoreSelection) {
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            if (restoreSelection) select.value = previousValue;
            clearError();
            select.focus();
        }

        function insertCategoryOption(name) {
            const value = String(name);
            let option = Array.from(select.options).find((item) => item.value !== addValue && item.value.toLowerCase() === value.toLowerCase());

            if (!option) {
                option = new Option(value, value);
                const sentinel = Array.from(select.options).find((item) => item.value === addValue);
                const normalOptions = Array.from(select.options).filter((item) => item.value !== '' && item.value !== addValue && !item.disabled);
                const next = normalOptions.find((item) => item.textContent.localeCompare(value, undefined, { sensitivity: 'base' }) > 0);

                if (next) select.insertBefore(option, next);
                else if (sentinel) select.insertBefore(option, sentinel);
                else select.add(option);
            }

            select.value = option.value;
        }

        async function submitCategory() {
            if (submitting) return;

            const name = input.value.trim();
            if (!name) {
                showError('Please enter a category name.');
                return;
            }
            if (name.length > 50) {
                showError('Category name must not be longer than 50 characters.');
                return;
            }

            submitting = true;
            submitButton.disabled = true;
            clearError();

            try {
                const response = await fetch(storeUrl, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ name: name }),
                });
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data.errors && data.errors.name
                        ? data.errors.name[0]
                        : (response.status === 403
                            ? 'You do not have permission to add categories.'
                            : (data.message || 'Could not add the category. Please try again.'));

                    showError(message);
                    select.value = previousValue;
                    return;
                }

                insertCategoryOption(data.name);
                closeModal(false);
            } catch (error) {
                showError('Could not reach the server. Please try again.');
                select.value = previousValue;
            } finally {
                submitting = false;
                submitButton.disabled = false;
            }
        }

        select.addEventListener('change', () => {
            if (select.value === addValue) openModal();
        });

        input.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                event.preventDefault();
                submitCategory();
            }
        });

        cancelButton.addEventListener('click', () => closeModal(true));
        submitButton.addEventListener('click', submitCategory);

        modal.addEventListener('mousedown', (event) => {
            if (event.target === modal) closeModal(true);
        });

        modal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                event.preventDefault();
                closeModal(true);
                return;
            }
            trapFocus(event);
        });

        const itemForm = select.closest('form');
        if (itemForm) {
            itemForm.addEventListener('submit', () => {
                if (select.value === addValue) select.value = previousValue || '';
            });
        }
    })();
    </script>
@endif
