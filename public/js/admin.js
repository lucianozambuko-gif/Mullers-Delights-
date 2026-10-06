(function () {
    "use strict";

    // Ask before anything destructive. The message comes from data-confirm on
    // the clicked button, or on the form when the whole form is one action.
    document.addEventListener('submit', function (e) {
        var form = e.target;
        var message = (e.submitter && e.submitter.getAttribute('data-confirm')) || form.getAttribute('data-confirm');
        if (message && !window.confirm(message)) {
            e.preventDefault();
        }
    });
})();
