@auth
<script>
(function () {
    const loginUrl = @json(route('login'));
    const sessionCheckUrl = @json(route('session.check'));
    let redirecting = false;

    function redirectToLogin() {
        if (redirecting) return;
        redirecting = true;
        window.location.replace(loginUrl);
    }

    function isLoginResponse(response) {
        if (!response) return false;
        if (response.status === 401 || response.status === 419) return true;

        try {
            return response.redirected && new URL(response.url).pathname === new URL(loginUrl).pathname;
        } catch (_) {
            return false;
        }
    }

    const nativeFetch = window.fetch ? window.fetch.bind(window) : null;
    if (nativeFetch) {
        window.fetch = function () {
            return nativeFetch.apply(null, arguments).then(function (response) {
                if (isLoginResponse(response)) redirectToLogin();
                return response;
            });
        };

        window.setInterval(function () {
            if (document.visibilityState !== 'visible' || redirecting) return;

            nativeFetch(sessionCheckUrl, {
                method: 'GET',
                credentials: 'same-origin',
                cache: 'no-store',
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            }).then(function (response) {
                if (isLoginResponse(response)) redirectToLogin();
            }).catch(function () {
                // Network interruptions are not treated as an expired session.
            });
        }, 60000);
    }

    document.addEventListener('visibilitychange', function () {
        if (document.visibilityState !== 'visible' || !nativeFetch || redirecting) return;

        nativeFetch(sessionCheckUrl, {
            credentials: 'same-origin',
            cache: 'no-store',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
        }).then(function (response) {
            if (isLoginResponse(response)) redirectToLogin();
        }).catch(function () {});
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (window.axios?.interceptors) {
            window.axios.interceptors.response.use(
                function (response) { return response; },
                function (error) {
                    if (isLoginResponse(error?.response)) redirectToLogin();
                    return Promise.reject(error);
                }
            );
        }

        if (window.jQuery) {
            window.jQuery(document).ajaxError(function (_, xhr) {
                if (xhr.status === 401 || xhr.status === 419) redirectToLogin();
            });
        }
    });
})();
</script>
@endauth

<style>
    #network-offline-overlay {
        position: fixed;
        inset: 0;
        z-index: 100000;
        display: none;
        align-items: center;
        justify-content: center;
        padding: 20px;
        background: rgba(15, 23, 42, .72);
        backdrop-filter: blur(4px);
    }
    #network-offline-overlay.is-visible { display: flex; }
    .network-offline-card {
        width: min(100%, 420px);
        padding: 28px;
        border-radius: 18px;
        background: #fff;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .28);
        text-align: center;
    }
    .network-offline-icon {
        display: grid;
        place-items: center;
        width: 64px;
        height: 64px;
        margin: 0 auto 16px;
        border-radius: 50%;
        background: #fff7ed;
        color: #ea580c;
        font-size: 32px;
    }
</style>

<div id="network-offline-overlay" role="alertdialog" aria-modal="true" aria-labelledby="network-offline-title" aria-hidden="true">
    <div class="network-offline-card">
        <div class="network-offline-icon"><i class="ph ph-wifi-slash"></i></div>
        <h4 id="network-offline-title" class="fw-bold mb-2">Cần kết nối mạng</h4>
        <p class="text-muted mb-4">Không thể kết nối Internet hoặc máy chủ. Hệ thống sẽ tự tải lại khi kết nối được khôi phục.</p>
        <button type="button" id="network-retry-button" class="btn btn-primary px-4">
            <i class="ph ph-arrow-clockwise me-1"></i><span>Thử lại</span>
        </button>
        <div id="network-retry-status" class="small text-muted mt-3" aria-live="polite"></div>
    </div>
</div>

<script>
(function () {
    const overlay = document.getElementById('network-offline-overlay');
    const retryButton = document.getElementById('network-retry-button');
    const retryStatus = document.getElementById('network-retry-status');
    if (!overlay || !retryButton) return;

    let offlineDetected = !navigator.onLine;
    let checking = false;
    let retryTimer = null;

    function showOffline() {
        offlineDetected = true;
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
        scheduleRetry();
    }

    function hideOffline() {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
    }

    function scheduleRetry() {
        window.clearTimeout(retryTimer);
        retryTimer = window.setTimeout(function () { verifyConnection(true); }, 5000);
    }

    async function verifyConnection(autoReload) {
        if (checking) return;
        checking = true;
        retryButton.disabled = true;
        retryStatus.textContent = 'Đang kiểm tra kết nối...';

        try {
            const response = await fetch(window.location.origin + '/favicon.ico?network_check=' + Date.now(), {
                method: 'HEAD',
                cache: 'no-store',
                credentials: 'same-origin',
            });
            if (!response.ok && response.status >= 500) throw new Error('Server unavailable');

            retryStatus.textContent = 'Đã kết nối. Đang tải lại...';
            hideOffline();
            if (offlineDetected || autoReload) window.location.reload();
        } catch (_) {
            showOffline();
            retryStatus.textContent = 'Vẫn chưa có kết nối. Hệ thống sẽ tiếp tục thử lại.';
        } finally {
            checking = false;
            retryButton.disabled = false;
        }
    }

    retryButton.addEventListener('click', function () { verifyConnection(true); });
    window.addEventListener('offline', showOffline);
    window.addEventListener('online', function () { verifyConnection(true); });

    if (window.fetch) {
        const previousFetch = window.fetch.bind(window);
        window.fetch = function () {
            return previousFetch.apply(null, arguments).catch(function (error) {
                if (error?.name !== 'AbortError') showOffline();
                throw error;
            });
        };
    }

    window.addEventListener('unhandledrejection', function (event) {
        const reason = event.reason;
        if (!navigator.onLine || reason instanceof TypeError && /fetch|network/i.test(reason.message || '')) {
            showOffline();
        }
    });

    document.addEventListener('DOMContentLoaded', function () {
        if (!navigator.onLine) showOffline();

        if (window.axios?.interceptors) {
            window.axios.interceptors.response.use(
                function (response) { return response; },
                function (error) {
                    if (!error.response) showOffline();
                    return Promise.reject(error);
                }
            );
        }
        if (window.jQuery) {
            window.jQuery(document).ajaxError(function (_, xhr) {
                if (xhr.status === 0) showOffline();
            });
        }
    });
})();
</script>
