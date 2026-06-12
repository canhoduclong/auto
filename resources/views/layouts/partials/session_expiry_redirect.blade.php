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
