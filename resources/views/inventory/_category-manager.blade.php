@php
    $sortedCategories = $categories->sortBy(fn ($c) => mb_strtolower($c->display_name))->values();
@endphp

<dialog id="categoryModal" class="cat-dialog" data-category-manager-modal aria-labelledby="manageCategoriesTitle">
    <div class="cat-panel">
        <div class="cat-header">
            <div class="cat-header-text">
                <h3 id="manageCategoriesTitle" class="cat-title">Manage Categories</h3>
                <p class="cat-subtitle">Add categories or remove unused ones.</p>
            </div>
            <button type="button" class="cat-icon-btn" data-close-category-manager aria-label="Close manage categories">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="cat-add">
            <div class="cat-add-row">
                <input type="text" maxlength="50" autocomplete="off" placeholder="New category name" class="cat-input" data-manager-category-name aria-label="New category name">
                <button type="button" class="cat-btn cat-btn-primary" data-manager-add>Add</button>
            </div>
            <p class="cat-form-error" role="alert" aria-live="polite" data-manager-form-error hidden></p>
            <p class="cat-message" role="status" aria-live="polite" data-manager-message></p>
        </div>

        <div class="cat-search-wrap" data-category-search-wrap hidden>
            <label for="categoryManagerSearch" class="cat-sr">Filter categories</label>
            <input type="text" id="categoryManagerSearch" autocomplete="off" placeholder="Filter categories..." class="cat-input cat-search-input" data-category-search>
        </div>

        <div class="cat-list-wrap" data-category-list-wrap>
            <div class="cat-list" data-category-list>
                @forelse($sortedCategories as $category)
                    @php
                        $count = (int) $category->items_count;
                        $displayName = $category->display_name;
                    @endphp
                    <div class="cat-row" data-category-row data-id="{{ $category->id }}" data-name="{{ $category->name }}" data-display-name="{{ $displayName }}" data-count="{{ $count }}">
                        <div class="cat-row-info">
                            <div class="cat-row-name" data-category-label>{{ $displayName }}</div>
                            <div class="cat-row-count" data-category-count>{{ $count }} {{ $count === 1 ? 'item' : 'items' }}</div>
                        </div>
                        <button type="button" class="cat-delete-btn" data-delete-category data-id="{{ $category->id }}" data-name="{{ $category->name }}" data-display-name="{{ $displayName }}" data-count="{{ $count }}" @if($count > 0) disabled title="Move or delete the {{ $count }} {{ $count === 1 ? 'item' : 'items' }} first" aria-label="Cannot delete {{ $displayName }} because it has {{ $count }} {{ $count === 1 ? 'item' : 'items' }}" @else title="Delete category" aria-label="Delete {{ $displayName }}" @endif>
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>
                        </button>
                    </div>
                @empty
                    <div class="cat-empty" data-empty-categories>
                        <p class="cat-empty-title">No categories yet</p>
                        <p class="cat-empty-sub">Add your first category above.</p>
                    </div>
                @endforelse
            </div>
            <div class="cat-no-results" data-no-filter-results hidden>No categories match your filter.</div>
        </div>

        <div class="cat-footer">
            <button type="button" class="cat-btn cat-btn-secondary" data-footer-close-category-manager>Close</button>
        </div>
    </div>
</dialog>

<dialog id="categoryConfirmDialog" class="cat-dialog cat-confirm" aria-labelledby="confirmCategoryDeleteTitle">
    <div class="cat-panel cat-confirm-panel">
        <h3 id="confirmCategoryDeleteTitle" class="cat-title">Delete Category</h3>
        <p class="cat-confirm-text">Are you sure you want to delete <span class="cat-confirm-name" data-confirm-category-name></span>? This cannot be undone.</p>
        <p class="cat-form-error" role="alert" aria-live="polite" data-confirm-error hidden></p>
        <div class="cat-confirm-actions">
            <button type="button" class="cat-btn cat-btn-secondary" data-cancel-category-delete>Cancel</button>
            <button type="button" class="cat-btn cat-btn-danger" data-confirm-category-delete>Delete</button>
        </div>
    </div>
</dialog>

<style>
.cat-dialog { position: fixed; inset: 0; margin: auto; height: fit-content; border: 0; padding: 0; border-radius: 16px; width: calc(100% - 32px); max-width: 480px; max-height: calc(100dvh - 32px); box-shadow: 0 20px 50px rgba(0,0,0,.25); background: #fff; overflow: hidden; color: #0f172a; font-family: inherit; }
.cat-dialog::backdrop { background: rgba(0,0,0,.5); }
.cat-dialog[open] { display: flex; flex-direction: column; }
.cat-dialog.cat-confirm { max-width: 420px; }
.cat-panel { display: flex; flex-direction: column; min-height: 0; max-height: calc(100dvh - 32px); }
.cat-confirm-panel { padding: 24px; overflow-y: auto; }
.cat-header { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; padding: 20px 24px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
.cat-header-text { min-width: 0; }
.cat-title { margin: 0; font-size: 16px; font-weight: 600; line-height: 1.4; color: #0f172a; }
.cat-subtitle { margin: 2px 0 0; font-size: 12px; line-height: 1.4; color: #64748b; }
.cat-icon-btn { flex-shrink: 0; width: 36px; height: 36px; margin: -4px -8px 0 0; border: 0; border-radius: 8px; background: transparent; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
.cat-icon-btn:hover { background: #f1f5f9; color: #0f172a; }
.cat-icon-btn:focus-visible, .cat-btn:focus-visible, .cat-input:focus-visible, .cat-delete-btn:focus-visible { outline: 2px solid #16a34a; outline-offset: 2px; }
.cat-add { padding: 16px 24px; border-bottom: 1px solid #e2e8f0; flex-shrink: 0; }
.cat-add-row { display: flex; gap: 8px; align-items: stretch; }
.cat-input { flex: 1; min-width: 0; width: 100%; box-sizing: border-box; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 8px; font-size: 14px; line-height: 1.4; color: #0f172a; background: #fff; }
.cat-input::placeholder { color: #94a3b8; }
.cat-input:focus { border-color: #16a34a; outline: 2px solid rgba(22,163,74,.2); outline-offset: 0; }
.cat-btn { flex-shrink: 0; border: 1px solid transparent; border-radius: 8px; padding: 10px 16px; font-size: 14px; font-weight: 600; line-height: 1.2; cursor: pointer; font-family: inherit; }
.cat-btn:disabled { opacity: .6; cursor: not-allowed; }
.cat-btn-primary { background: #16a34a; color: #fff; }
.cat-btn-primary:hover:not(:disabled) { background: #15803d; }
.cat-btn-secondary { background: #fff; color: #334155; border-color: #cbd5e1; }
.cat-btn-secondary:hover:not(:disabled) { background: #f8fafc; }
.cat-btn-danger { background: #dc2626; color: #fff; }
.cat-btn-danger:hover:not(:disabled) { background: #b91c1c; }
.cat-form-error { margin: 8px 0 0; font-size: 13px; color: #dc2626; }
.cat-form-error[hidden], .cat-message[hidden], .cat-search-wrap[hidden], .cat-no-results[hidden] { display: none !important; }
.cat-message { margin: 8px 0 0; min-height: 20px; font-size: 13px; color: #16a34a; }
.cat-message.is-error { color: #dc2626; }
.cat-search-wrap { padding: 16px 24px 0; flex-shrink: 0; }
.cat-search-input { width: 100%; }
.cat-sr { position: absolute; width: 1px; height: 1px; padding: 0; margin: -1px; overflow: hidden; clip: rect(0,0,0,0); white-space: nowrap; border: 0; }
.cat-list-wrap { flex: 1; min-height: 0; overflow-y: auto; padding: 16px 24px; overscroll-behavior: contain; scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent; }
.cat-list-wrap::-webkit-scrollbar { width: 8px; }
.cat-list-wrap::-webkit-scrollbar-track { background: transparent; }
.cat-list-wrap::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 999px; border: 2px solid transparent; background-clip: padding-box; }
.cat-list { border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; background: #fff; }
.cat-row { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; background: #fff; }
.cat-row:last-child { border-bottom: 0; }
.cat-row:hover { background: #f0fdf4; }
.cat-row[hidden] { display: none !important; }
.cat-row-info { min-width: 0; }
.cat-row-name { font-size: 14px; font-weight: 500; color: #0f172a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
.cat-row-count { margin-top: 2px; font-size: 12px; color: #64748b; }
.cat-delete-btn { flex-shrink: 0; width: 32px; height: 32px; border: 0; border-radius: 8px; background: transparent; color: #dc2626; cursor: pointer; display: inline-flex; align-items: center; justify-content: center; }
.cat-delete-btn:hover:not(:disabled) { background: #fef2f2; }
.cat-delete-btn:disabled { color: #cbd5e1; cursor: not-allowed; background: transparent; }
.cat-empty { padding: 40px 16px; text-align: center; }
.cat-empty-title { margin: 0; font-size: 14px; font-weight: 500; color: #475569; }
.cat-empty-sub { margin: 4px 0 0; font-size: 12px; color: #94a3b8; }
.cat-no-results { padding: 24px 16px; text-align: center; font-size: 14px; color: #64748b; }
.cat-footer { padding: 16px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: flex-end; flex-shrink: 0; }
.cat-confirm-text { margin: 8px 0 0; font-size: 14px; line-height: 1.5; color: #475569; }
.cat-confirm-name { font-weight: 600; color: #0f172a; }
.cat-confirm-actions { margin-top: 20px; display: flex; justify-content: flex-end; gap: 12px; }
@media (max-width: 640px) {
    .cat-dialog { width: calc(100% - 16px); max-width: 100%; max-height: calc(100dvh - 16px); }
    .cat-panel { max-height: calc(100dvh - 16px); }
    .cat-header, .cat-add, .cat-list-wrap, .cat-footer, .cat-search-wrap { padding-left: 16px; padding-right: 16px; }
}
</style>

<script>
(function () {
    var openButton = document.querySelector('[data-open-category-manager]');
    var dialog = document.getElementById('categoryModal');
    var confirmDialog = document.getElementById('categoryConfirmDialog');
    if (!openButton || !dialog || !confirmDialog) return;

    var nameInput = dialog.querySelector('[data-manager-category-name]');
    var addButton = dialog.querySelector('[data-manager-add]');
    var formError = dialog.querySelector('[data-manager-form-error]');
    var message = dialog.querySelector('[data-manager-message]');
    var list = dialog.querySelector('[data-category-list]');
    var emptyState = dialog.querySelector('[data-empty-categories]');
    var noFilterResults = dialog.querySelector('[data-no-filter-results]');
    var searchWrap = dialog.querySelector('[data-category-search-wrap]');
    var searchInput = dialog.querySelector('[data-category-search]');
    var closeButtons = dialog.querySelectorAll('[data-close-category-manager], [data-footer-close-category-manager]');
    var confirmName = confirmDialog.querySelector('[data-confirm-category-name]');
    var confirmError = confirmDialog.querySelector('[data-confirm-error]');
    var cancelDelete = confirmDialog.querySelector('[data-cancel-category-delete]');
    var confirmDelete = confirmDialog.querySelector('[data-confirm-category-delete]');
    var storeUrl = @json(route('categories.store'));
    var csrfToken = @json(csrf_token());

    var pendingCategory = null;
    var adding = false;
    var deleting = false;

    function displayName(name) {
        return String(name || '').replace(/[-_]+/g, ' ').replace(/\s+/g, ' ').trim().replace(/\b\w/g, function (c) { return c.toUpperCase(); });
    }
    function rows() { return Array.from(list.querySelectorAll('[data-category-row]')); }
    function setMessage(text, tone) {
        message.textContent = text || '';
        message.className = 'cat-message' + (tone === 'error' ? ' is-error' : '');
        message.hidden = !text;
    }
    function showFormError(text) { formError.textContent = text || 'Could not add the category. Please try again.'; formError.hidden = false; }
    function clearFormError() { formError.textContent = ''; formError.hidden = true; }
    function lockBody() { document.body.style.overflow = 'hidden'; }
    function unlockBody() { if (!dialog.open && !confirmDialog.open) document.body.style.overflow = ''; }

    function updateSearchVisibility() {
        var show = rows().length > 8;
        searchWrap.hidden = !show;
        if (!show && searchInput) searchInput.value = '';
    }
    function applyFilter() {
        var q = ((searchInput && searchInput.value) || '').trim().toLowerCase();
        var visible = 0;
        rows().forEach(function (row) {
            var name = (row.dataset.displayName || row.dataset.name || '').toLowerCase();
            var match = !q || name.indexOf(q) !== -1;
            row.hidden = !match;
            if (match) visible++;
        });
        var hasRows = rows().length > 0;
        noFilterResults.hidden = !(hasRows && visible === 0);
    }
    function updateEmptyState() {
        var hasRows = rows().length > 0;
        if (emptyState) emptyState.hidden = hasRows;
        updateSearchVisibility();
        applyFilter();
    }

    function openModal() {
        setMessage('', 'success');
        clearFormError();
        confirmError.hidden = true;
        dialog.showModal();
        lockBody();
        updateEmptyState();
        window.requestAnimationFrame(function () { nameInput.focus(); });
    }
    function closeModal() { if (dialog.open) dialog.close(); }

    dialog.addEventListener('close', function () {
        unlockBody();
        openButton.focus();
    });
    confirmDialog.addEventListener('close', function () {
        unlockBody();
        if (dialog.open && pendingCategory && pendingCategory.button && pendingCategory.button.isConnected) {
            pendingCategory.button.focus();
        } else if (dialog.open) {
            nameInput.focus();
        }
        pendingCategory = null;
    });

    openButton.addEventListener('click', openModal);
    closeButtons.forEach(function (btn) { btn.addEventListener('click', closeModal); });

    dialog.addEventListener('click', function (e) { if (e.target === dialog) closeModal(); });
    confirmDialog.addEventListener('click', function (e) { if (e.target === confirmDialog) confirmDialog.close(); });

    function makeRow(id, name, count) {
        var dName = displayName(name);
        var row = document.createElement('div');
        row.className = 'cat-row';
        row.dataset.categoryRow = '';
        row.dataset.id = String(id);
        row.dataset.name = String(name);
        row.dataset.displayName = dName;
        row.dataset.count = String(count);

        var info = document.createElement('div');
        info.className = 'cat-row-info';
        var label = document.createElement('div');
        label.className = 'cat-row-name';
        label.dataset.categoryLabel = '';
        label.textContent = dName;
        var countText = document.createElement('div');
        countText.className = 'cat-row-count';
        countText.dataset.categoryCount = '';
        countText.textContent = count + ' ' + (count === 1 ? 'item' : 'items');
        info.appendChild(label);
        info.appendChild(countText);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'cat-delete-btn';
        btn.dataset.deleteCategory = '';
        btn.dataset.id = String(id);
        btn.dataset.name = String(name);
        btn.dataset.displayName = dName;
        btn.dataset.count = String(count);
        btn.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 6h18M8 6V4h8v2m-9 0 1 14h8l1-14"/></svg>';
        if (count > 0) {
            btn.disabled = true;
            btn.title = 'Move or delete the ' + count + ' ' + (count === 1 ? 'item' : 'items') + ' first';
            btn.setAttribute('aria-label', 'Cannot delete ' + dName + ' because it has ' + count + ' ' + (count === 1 ? 'item' : 'items'));
        } else {
            btn.title = 'Delete category';
            btn.setAttribute('aria-label', 'Delete ' + dName);
        }
        row.appendChild(info);
        row.appendChild(btn);
        return row;
    }

    function addOrUpdateRow(id, name, count) {
        var row = rows().find(function (item) {
            return item.dataset.id === String(id) || item.dataset.name.toLowerCase() === String(name).toLowerCase();
        });
        if (!row) {
            row = makeRow(id, name, count);
            var dName = displayName(name);
            var next = rows().find(function (item) {
                return (item.dataset.displayName || displayName(item.dataset.name)).localeCompare(dName, undefined, { sensitivity: 'base' }) > 0;
            });
            if (next) list.insertBefore(row, next); else list.appendChild(row);
        }
        updateEmptyState();
    }

    async function addCategory() {
        if (adding) return;
        var name = nameInput.value.trim();
        if (!name) { showFormError('Please enter a category name.'); nameInput.focus(); return; }
        if (name.length > 50) { showFormError('Category name must not be longer than 50 characters.'); nameInput.focus(); return; }
        adding = true; addButton.disabled = true; clearFormError(); setMessage('', 'success');
        try {
            var response = await fetch(storeUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
                body: JSON.stringify({ name: name })
            });
            var data = await response.json().catch(function () { return {}; });
            if (!response.ok) {
                var err = (data.errors && data.errors.name) ? data.errors.name[0] : (response.status === 403 ? 'You do not have permission to add categories.' : (data.message || 'Could not add the category. Please try again.'));
                showFormError(err); return;
            }
            var savedName = data.name || name;
            addOrUpdateRow(data.id, savedName, 0);
            nameInput.value = '';
            setMessage((response.status === 200 ? 'Already exists: ' : 'Added: ') + displayName(savedName), 'success');
        } catch (e) {
            showFormError('Could not reach the server. Please try again.');
        } finally {
            adding = false; addButton.disabled = false; nameInput.focus();
        }
    }

    addButton.addEventListener('click', addCategory);
    nameInput.addEventListener('keydown', function (e) { if (e.key === 'Enter') { e.preventDefault(); addCategory(); } });
    if (searchInput) searchInput.addEventListener('input', applyFilter);

    list.addEventListener('click', function (e) {
        var button = e.target.closest('[data-delete-category]');
        if (!button || button.disabled) return;
        pendingCategory = { id: button.dataset.id, name: button.dataset.name, displayName: button.dataset.displayName || displayName(button.dataset.name), button: button };
        confirmName.textContent = pendingCategory.displayName;
        confirmError.textContent = ''; confirmError.hidden = true;
        confirmDialog.showModal();
        lockBody();
        window.requestAnimationFrame(function () { cancelDelete.focus(); });
    });

    cancelDelete.addEventListener('click', function () { confirmDialog.close(); });
    async function deleteCategory() {
        if (deleting || !pendingCategory) return;
        deleting = true; confirmDelete.disabled = true; confirmError.hidden = true; confirmError.textContent = '';
        try {
            var response = await fetch(storeUrl + '/' + encodeURIComponent(pendingCategory.id), {
                method: 'DELETE',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }
            });
            var data = await response.json().catch(function () { return {}; });
            if (!response.ok) {
                confirmError.textContent = response.status === 403 ? 'You do not have permission to delete categories.' : (data.message || 'Could not delete the category. Please try again.');
                confirmError.hidden = false; return;
            }
            var row = list.querySelector('[data-category-row][data-id="' + pendingCategory.id + '"]');
            var deletedName = pendingCategory.displayName;
            if (row) row.remove();
            updateEmptyState();
            setMessage('Deleted: ' + deletedName, 'success');
            pendingCategory = null;
            confirmDialog.close();
            nameInput.focus();
        } catch (e) {
            confirmError.textContent = 'Could not reach the server. Please try again.';
            confirmError.hidden = false;
        } finally {
            deleting = false; confirmDelete.disabled = false;
        }
    }
    confirmDelete.addEventListener('click', deleteCategory);

    updateEmptyState();
})();
</script>
