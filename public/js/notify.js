/*
 * Shared in-page notification system.
 *
 * Exposes:
 *   showToast(message, type, options)   type: "success" | "error" | "warning" | "info"
 *                                       options: { title, duration }
 *   showSuccessModal({ title, message, details, confirmText, onClose })
 *   showConfirm({ title, message, confirmText, cancelText, danger }) -> Promise<boolean>
 *   confirmDestructive(event, message, title)  inline helper for delete forms
 *
 * Also available as window.Notify.*. Requires notify.css to be loaded.
 */
(function () {
    'use strict';

    var TOAST_TYPES = ['success', 'error', 'warning', 'info'];
    var TOAST_ICONS = { success: '\u2713', error: '\u2715', warning: '!', info: 'i' };
    var TOAST_DURATIONS = { success: 2000, info: 3000, warning: 4000, error: 4000 };
    var ANIMATION_MS = 180;

    function create(tag, className, text) {
        var node = document.createElement(tag);
        if (className) node.className = className;
        if (text !== undefined && text !== null) node.textContent = String(text);
        return node;
    }

    /* ------------------------------------------------------------- Toasts */

    function toastContainer() {
        var container = document.querySelector('.ntf-toast-container');
        if (!container) {
            container = create('div', 'ntf-toast-container');
            document.body.appendChild(container);
        }
        return container;
    }

    function toastIcon(type) {
        var icon = create('span', 'ntf-toast-icon ntf-toast-icon--' + type);
        icon.setAttribute('aria-hidden', 'true');

        if (type === 'success') {
            icon.innerHTML = '<svg class="ntf-toast-check" viewBox="0 0 24 24" focusable="false">'
                + '<circle class="ntf-toast-check-circle" cx="12" cy="12" r="10"></circle>'
                + '<path class="ntf-toast-check-mark" d="M7.5 12.5 l3 3 l6 -7"></path>'
                + '</svg>';
        } else {
            icon.textContent = TOAST_ICONS[type];
        }

        return icon;
    }

    function showToast(message, type, options) {
        if (!message) return;
        if (TOAST_TYPES.indexOf(type) === -1) type = 'info';

        options = options || {};

        var toast = create('div', 'ntf-toast ntf-toast--' + type);
        toast.setAttribute('role', type === 'error' ? 'alert' : 'status');
        toast.setAttribute('aria-live', type === 'error' ? 'assertive' : 'polite');
        toast.setAttribute('aria-atomic', 'true');

        toast.appendChild(toastIcon(type));

        var body = create('div', 'ntf-toast-body');
        if (options.title) {
            body.appendChild(create('p', 'ntf-toast-title', options.title));
        }
        body.appendChild(create('div', 'ntf-toast-text', message));
        toast.appendChild(body);

        toastContainer().appendChild(toast);

        window.requestAnimationFrame(function () {
            toast.classList.add('ntf-toast--visible');
        });

        var duration = Number(options.duration) > 0
            ? Number(options.duration)
            : (TOAST_DURATIONS[type] || 3000);

        var removed = false;
        function dismiss() {
            if (removed) return;
            removed = true;
            toast.classList.remove('ntf-toast--visible');
            window.setTimeout(function () {
                toast.remove();
            }, 340);
        }

        window.setTimeout(dismiss, duration);

        return { dismiss: dismiss, element: toast };
    }

    /* ------------------------------------------------------------- Overlay */

    function createOverlay() {
        var overlay = create('div', 'ntf-overlay');
        var modal = create('div', 'ntf-modal');
        modal.setAttribute('role', 'dialog');
        modal.setAttribute('aria-modal', 'true');
        overlay.appendChild(modal);
        return overlay;
    }

    function openOverlay(overlay, focusTarget) {
        var previouslyFocused = document.activeElement;
        document.body.classList.add('ntf-body-lock');
        document.body.appendChild(overlay);

        window.requestAnimationFrame(function () {
            overlay.classList.add('ntf-overlay--visible');
            if (focusTarget && typeof focusTarget.focus === 'function') {
                focusTarget.focus();
            }
        });

        return function restoreFocus() {
            if (previouslyFocused && typeof previouslyFocused.focus === 'function'
                && document.body.contains(previouslyFocused)) {
                previouslyFocused.focus();
            }
        };
    }

    function closeOverlay(overlay, restoreFocus, done) {
        overlay.classList.remove('ntf-overlay--visible');
        window.setTimeout(function () {
            overlay.remove();
            if (!document.querySelector('.ntf-overlay')) {
                document.body.classList.remove('ntf-body-lock');
            }
            if (typeof restoreFocus === 'function') restoreFocus();
            if (typeof done === 'function') done();
        }, ANIMATION_MS);
    }

    function normalizeDetails(details) {
        if (!details) return [];
        if (Array.isArray(details)) {
            return details.filter(function (row) {
                return row && (row.label !== undefined || row.value !== undefined);
            });
        }
        return Object.keys(details).map(function (key) {
            return { label: key, value: details[key] };
        });
    }

    function buildDetails(details) {
        var rows = normalizeDetails(details);
        if (!rows.length) return null;

        var container = create('div', 'ntf-details');
        rows.forEach(function (row) {
            var line = create('div', 'ntf-detail-row');
            line.appendChild(create('span', 'ntf-detail-label', row.label || ''));
            line.appendChild(create('span', 'ntf-detail-value', row.value === undefined ? '' : row.value));
            container.appendChild(line);
        });
        return container;
    }

    function checkIcon() {
        var wrap = create('div', 'ntf-check');
        wrap.setAttribute('aria-hidden', 'true');
        wrap.innerHTML = '<svg viewBox="0 0 52 52" focusable="false">'
            + '<circle class="ntf-check-circle" cx="26" cy="26" r="24"></circle>'
            + '<path class="ntf-check-mark" d="M14 27 l9 9 l16 -18"></path>'
            + '</svg>';
        return wrap;
    }

    /* ------------------------------------------------------ Success modal */

    function showSuccessModal(options) {
        options = options || {};
        var title = options.title || 'Success';
        var message = options.message || '';
        var confirmText = options.confirmText || 'OK';
        var onClose = typeof options.onClose === 'function' ? options.onClose : null;

        var overlay = createOverlay();
        var modal = overlay.querySelector('.ntf-modal');
        var titleId = 'ntf-title-' + Math.random().toString(36).slice(2, 9);
        modal.setAttribute('aria-labelledby', titleId);

        modal.appendChild(checkIcon());

        var heading = create('h2', 'ntf-title', title);
        heading.id = titleId;
        modal.appendChild(heading);

        if (message) {
            modal.appendChild(create('p', 'ntf-message', message));
        }

        var details = buildDetails(options.details);
        if (details) modal.appendChild(details);

        var actions = create('div', 'ntf-actions');
        var confirmBtn = create('button', 'ntf-btn ntf-btn-primary', confirmText);
        confirmBtn.type = 'button';
        actions.appendChild(confirmBtn);
        modal.appendChild(actions);

        var closed = false;
        var restoreFocus = null;

        function close() {
            if (closed) return;
            closed = true;
            document.removeEventListener('keydown', onKeyDown, true);
            closeOverlay(overlay, restoreFocus, function () {
                if (onClose) onClose();
            });
        }

        function onKeyDown(event) {
            if (event.key === 'Escape' || event.key === 'Enter') {
                event.preventDefault();
                event.stopPropagation();
                close();
            }
        }

        confirmBtn.addEventListener('click', close);
        overlay.addEventListener('click', function (event) {
            if (event.target === overlay) close();
        });
        document.addEventListener('keydown', onKeyDown, true);

        restoreFocus = openOverlay(overlay, confirmBtn);
        return { close: close, element: overlay };
    }

    /* ------------------------------------------------------ Confirm modal */

    function showConfirm(options) {
        options = options || {};
        var title = options.title || 'Are you sure?';
        var message = options.message || '';
        var confirmText = options.confirmText || 'Confirm';
        var cancelText = options.cancelText || 'Cancel';
        var danger = options.danger === true;

        return new Promise(function (resolve) {
            var overlay = createOverlay();
            var modal = overlay.querySelector('.ntf-modal');
            var titleId = 'ntf-title-' + Math.random().toString(36).slice(2, 9);
            modal.setAttribute('aria-labelledby', titleId);

            var icon = create('div', 'ntf-confirm-icon', '?');
            icon.setAttribute('aria-hidden', 'true');
            modal.appendChild(icon);

            var heading = create('h2', 'ntf-title', title);
            heading.id = titleId;
            modal.appendChild(heading);

            if (message) {
                modal.appendChild(create('p', 'ntf-message', message));
            }

            var actions = create('div', 'ntf-actions');
            var cancelBtn = create('button', 'ntf-btn ntf-btn-ghost', cancelText);
            cancelBtn.type = 'button';
            var confirmBtn = create(
                'button',
                'ntf-btn ' + (danger ? 'ntf-btn-danger' : 'ntf-btn-primary'),
                confirmText
            );
            confirmBtn.type = 'button';
            actions.appendChild(cancelBtn);
            actions.appendChild(confirmBtn);
            modal.appendChild(actions);

            var settled = false;
            var restoreFocus = null;

            function finish(value) {
                if (settled) return;
                settled = true;
                document.removeEventListener('keydown', onKeyDown, true);
                closeOverlay(overlay, restoreFocus, function () {
                    resolve(value);
                });
            }

            function onKeyDown(event) {
                if (event.key === 'Escape') {
                    event.preventDefault();
                    event.stopPropagation();
                    finish(false);
                } else if (event.key === 'Enter') {
                    event.preventDefault();
                    event.stopPropagation();
                    finish(true);
                }
            }

            cancelBtn.addEventListener('click', function () { finish(false); });
            confirmBtn.addEventListener('click', function () { finish(true); });
            overlay.addEventListener('click', function (event) {
                if (event.target === overlay) finish(false);
            });
            document.addEventListener('keydown', onKeyDown, true);

            restoreFocus = openOverlay(overlay, confirmBtn);
        });
    }

    /* ---------------------------------------- Inline delete-confirm helper */

    function confirmDestructive(event, message, title) {
        if (event && typeof event.preventDefault === 'function') {
            event.preventDefault();
        }

        var trigger = event && event.currentTarget ? event.currentTarget : (event && event.target);
        var form = trigger && typeof trigger.closest === 'function'
            ? trigger.closest('form')
            : null;

        (async function () {
            var confirmed = await showConfirm({
                title: title || 'Please Confirm',
                message: message || 'Are you sure you want to continue?',
                confirmText: 'Delete',
                cancelText: 'Cancel',
                danger: true
            });

            if (!confirmed || !form) return;

            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit();
            } else {
                form.submit();
            }
        })();

        return false;
    }

    window.showSuccessModal = showSuccessModal;
    window.showToast = showToast;
    window.showConfirm = showConfirm;
    window.confirmDestructive = confirmDestructive;

    window.Notify = {
        showSuccessModal: showSuccessModal,
        showToast: showToast,
        showConfirm: showConfirm,
        confirmDestructive: confirmDestructive
    };
})();
