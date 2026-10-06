(function () {
    "use strict";

    // While a deposit is pending, ask the server every few seconds whether
    // Paynow has confirmed it, and reload the page once the status changes.
    var token = new URLSearchParams(window.location.search).get('t');
    if (!token) {
        return;
    }

    var checks = 0;
    var timer = setInterval(function () {
        checks += 1;
        if (checks > 30) {
            clearInterval(timer);
            return;
        }
        fetch('/payment-check.php?t=' + encodeURIComponent(token), { headers: { 'Accept': 'application/json' } })
            .then(function (response) {
                return response.ok ? response.json() : null;
            })
            .then(function (data) {
                if (data && data.status && data.status !== 'pending') {
                    clearInterval(timer);
                    window.location.replace('/booking-status.php?t=' + encodeURIComponent(token));
                }
            })
            .catch(function () {
                // A failed check is harmless; the next one will try again
            });
    }, 6000);
})();
