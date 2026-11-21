import './bootstrap';

import Chart from 'chart.js/auto';
window.Chart = Chart;

const nativeFetch = window.fetch.bind(window);
const ACCOUNT_DISABLED_REASON = 'account_disabled';
const ACCOUNT_MISSING_REASON = 'account_missing';
const ACCOUNT_DISABLED_MESSAGE = 'Your account has been disabled by an administrator.';
const ACCOUNT_STATUS_INTERVAL = 30000;
const LOGIN_PATH = '/login';

let accountStatusTimer = null;
let accountLogoutStarted = false;
let logoutInFlight = false;
let sessionCheckInFlight = false;

const stopAccountStatusPolling = () => {
    if (accountStatusTimer !== null) {
        window.clearInterval(accountStatusTimer);
        accountStatusTimer = null;
    }
};

const clearAppCookies = () => {
    try {
        document.cookie.split(';').forEach((cookie) => {
            const name = cookie.split('=')[0].trim();

            if (name) {
                document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
            }
        });
    } catch (error) {
        console.error(error);
    }
};

const clearLocalSessionData = () => {
    try {
        window.localStorage.clear();
    } catch (error) {
        console.error(error);
    }

    try {
        window.sessionStorage.clear();
    } catch (error) {
        console.error(error);
    }

    clearAppCookies();
};

const redirectToLogin = (query = '') => {
    if (accountLogoutStarted) {
        return;
    }

    accountLogoutStarted = true;
    stopAccountStatusPolling();
    clearLocalSessionData();

    window.location.replace(`${LOGIN_PATH}${query ? `?${query}` : ''}`);
};

const handleAccountDisabled = (message) => {
    if (accountLogoutStarted || logoutInFlight) {
        return;
    }

    window.dispatchEvent(new CustomEvent('ccfmpc:account-disabled', {
        detail: { message: message || ACCOUNT_DISABLED_MESSAGE },
    }));

    redirectToLogin('account_disabled=1');
};

const handleSessionExpired = () => {
    if (accountLogoutStarted || logoutInFlight) {
        return;
    }

    window.dispatchEvent(new CustomEvent('ccfmpc:session-ended'));

    redirectToLogin('session_expired=1');
};

const handleAuthFailure = async (response) => {
    if (response.status !== 401 && response.status !== 403) {
        return false;
    }

    const contentType = response.headers.get('content-type') || '';

    if (!contentType.includes('application/json')) {
        return false;
    }

    const data = await response.clone().json().catch(() => null);
    const reason = data && (data.reason || data.code);

    if (reason === ACCOUNT_DISABLED_REASON || reason === ACCOUNT_MISSING_REASON) {
        handleAccountDisabled(data.message);

        return true;
    }

    return false;
};

const performLogout = async (form) => {
    if (accountLogoutStarted || logoutInFlight) {
        return;
    }

    logoutInFlight = true;
    const token = form?.querySelector('input[name="_token"]')?.value || '';

    try {
        const response = await window.fetch(form.action || '/logout', {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': token,
            },
            credentials: 'same-origin',
            body: new URLSearchParams({ _token: token }),
        });

        if (!response.ok) {
            throw new Error('Unable to log out.');
        }
    } catch (error) {
        // Fall back to a normal form submission so the server can still end the session.
        if (form) {
            form.submit();
        }

        return;
    }

    accountLogoutStarted = true;
    logoutInFlight = false;
    stopAccountStatusPolling();
    clearLocalSessionData();

    window.dispatchEvent(new CustomEvent('ccfmpc:logout'));
    window.location.replace(`${LOGIN_PATH}?logged_out=1`);
};

const isSameOrigin = (url) => {
    try {
        return new URL(url, window.location.href).origin === window.location.origin;
    } catch (error) {
        return false;
    }
};

window.fetch = async (input, init = {}) => {
    let options = init || {};
    const requestUrl = typeof input === 'string'
        ? input
        : (input instanceof Request ? input.url : String(input));

    if (isSameOrigin(requestUrl)) {
        const headers = new Headers(input instanceof Request ? input.headers : undefined);
        new Headers(options.headers || {}).forEach((value, key) => headers.set(key, value));

        if (!headers.has('X-Requested-With')) {
            headers.set('X-Requested-With', 'XMLHttpRequest');
        }

        options = { ...options, headers };
    }

    const response = await nativeFetch(input, options);

    if (response.status === 401) {
        if (!(await handleAuthFailure(response))) {
            handleSessionExpired();
        }
    } else if (response.status === 403) {
        handleAuthFailure(response);
    }

    return response;
};

const checkAccountStatus = async () => {
    if (accountLogoutStarted || logoutInFlight) {
        return;
    }

    try {
        const response = await window.fetch('/account/status', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok && !(await handleAuthFailure(response))) {
            handleSessionExpired();
        }
    } catch (error) {
        // Transient network failures are retried on the next interval or action.
    }
};

const startAccountStatusPolling = () => {
    if (accountLogoutStarted || accountStatusTimer !== null) {
        return;
    }

    if (!document.querySelector('meta[name="ccfmpc-authenticated"]')) {
        return;
    }

    accountStatusTimer = window.setInterval(checkAccountStatus, ACCOUNT_STATUS_INTERVAL);
};

const verifySession = async () => {
    if (accountLogoutStarted || logoutInFlight || sessionCheckInFlight) {
        return;
    }

    sessionCheckInFlight = true;

    try {
        const response = await window.fetch('/account/status', {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            if (!(await handleAuthFailure(response))) {
                handleSessionExpired();
            }

            return;
        }

        const data = await response.json().catch(() => ({}));

        if (data.active !== true) {
            handleSessionExpired();

            return;
        }

        document.documentElement.classList.remove('session-pending');
        document.documentElement.classList.add('session-ready');
        startAccountStatusPolling();
    } catch (error) {
        handleSessionExpired();
    } finally {
        sessionCheckInFlight = false;
    }
};

const isAuthenticatedPage = () => document.querySelector('meta[name="ccfmpc-authenticated"]') !== null;

const verifySessionSilently = () => {
    if (!isAuthenticatedPage()) {
        return;
    }

    verifySession();
};

const guardRestoredPage = () => {
    if (!isAuthenticatedPage()) {
        return;
    }

    document.documentElement.classList.add('session-pending');
    verifySession();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', verifySessionSilently);
} else {
    verifySessionSilently();
}

window.addEventListener('pagehide', (event) => {
    if (event.persisted && isAuthenticatedPage()) {
        document.documentElement.classList.add('session-pending');
    }
});

window.addEventListener('pageshow', (event) => {
    if (event.persisted) {
        guardRestoredPage();
    }
});

const logoutForm = document.getElementById('logout-form');

if (logoutForm) {
    logoutForm.addEventListener('submit', (event) => {
        event.preventDefault();
        performLogout(logoutForm);
    });
}

if (document.querySelector('meta[name="ccfmpc-login"]')) {
    const loginUrl = window.location.href;

    const lockLoginHistory = () => {
        try {
            window.history.pushState({ ccfmpcLogin: true }, '', loginUrl);
        } catch (error) {
            console.error(error);
        }
    };

    lockLoginHistory();
    window.addEventListener('popstate', lockLoginHistory);
}

const downloadSvgAsPng = async (link) => {
    if (link.dataset.downloading === 'true') {
        return;
    }

    link.dataset.downloading = 'true';

    try {
        const response = await fetch(link.href, { credentials: 'same-origin' });

        if (!response.ok) {
            throw new Error('Unable to fetch the image.');
        }

        const svg = await response.text();
        const svgUrl = URL.createObjectURL(new Blob([svg], { type: 'image/svg+xml;charset=utf-8' }));
        const image = new Image();

        await new Promise((resolve, reject) => {
            image.onload = resolve;
            image.onerror = () => reject(new Error('Unable to render the image.'));
            image.src = svgUrl;
        });

        const scale = 3;
        const width = image.naturalWidth || image.width;
        const height = image.naturalHeight || image.height;

        if (!width || !height) {
            throw new Error('The image has no dimensions.');
        }

        const canvas = document.createElement('canvas');
        canvas.width = Math.round(width * scale);
        canvas.height = Math.round(height * scale);

        const context = canvas.getContext('2d');
        context.fillStyle = '#ffffff';
        context.fillRect(0, 0, canvas.width, canvas.height);
        context.drawImage(image, 0, 0, canvas.width, canvas.height);

        const png = await new Promise((resolve) => canvas.toBlob(resolve, 'image/png'));

        if (!png) {
            throw new Error('Unable to create the PNG file.');
        }

        const disposition = response.headers.get('content-disposition') || '';
        const filenameMatch = disposition.match(/filename="?([^";]+)"?/i);
        const filename = (filenameMatch?.[1] || link.dataset.filename || 'download.svg')
            .replace(/\.svg$/i, '.png');

        const downloadUrl = URL.createObjectURL(png);
        const anchor = document.createElement('a');
        anchor.href = downloadUrl;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();

        window.setTimeout(() => URL.revokeObjectURL(downloadUrl), 1000);
        URL.revokeObjectURL(svgUrl);
    } catch (error) {
        console.error(error);
        var message = 'Unable to download the image. Please try again.';
        if (typeof window.showToast === 'function') {
            window.showToast(message, 'error');
        } else {
            console.error(message);
        }
    } finally {
        delete link.dataset.downloading;
    }
};

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[data-download-png]');

    if (!link) {
        return;
    }

    event.preventDefault();
    downloadSvgAsPng(link);
});

// Toggle the right-edge fade on shared data tables based on scroll position.
const initTableScrollHints = () => {
    document.querySelectorAll('[data-table-scroll]').forEach((wrap) => {
        const frame = wrap.closest('[data-table-frame]');

        if (!frame) {
            return;
        }

        const update = () => {
            const scrollable = wrap.scrollWidth > wrap.clientWidth + 1;
            const atEnd = Math.ceil(wrap.scrollLeft + wrap.clientWidth) >= wrap.scrollWidth - 1;

            frame.classList.toggle('is-scrollable', scrollable);
            frame.classList.toggle('is-at-end', atEnd);
        };

        wrap.addEventListener('scroll', update, { passive: true });
        window.addEventListener('resize', update, { passive: true });

        if (typeof ResizeObserver !== 'undefined') {
            new ResizeObserver(update).observe(wrap);
        }

        update();
    });
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTableScrollHints);
} else {
    initTableScrollHints();
}
