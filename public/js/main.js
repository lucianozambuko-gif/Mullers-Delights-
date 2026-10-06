(function () {
    "use strict";

    // Initiate the wowjs
    new WOW().init();


    // Sticky Navbar
    var navbar = document.querySelector('.navbar');
    var onScroll = function () {
        var stuck = window.scrollY > 45;
        navbar.classList.toggle('sticky-top', stuck);
        navbar.classList.toggle('shadow-sm', stuck);
    };
    window.addEventListener('scroll', onScroll, { passive: true });
    onScroll();


    // Close the mobile menu after tapping a link
    var navCollapse = document.getElementById('navbarCollapse');
    navCollapse.querySelectorAll('a').forEach(function (link) {
        link.addEventListener('click', function () {
            // An instance only exists once the toggler has opened the menu
            var menu = bootstrap.Collapse.getInstance(navCollapse);
            if (menu && navCollapse.classList.contains('show')) {
                menu.hide();
            }
        });
    });


    // Booking form
    var form = document.getElementById('bookingForm');
    var errorBox = document.getElementById('bookingError');
    var packageSelect = document.getElementById('bookPackage');
    var submitButton = form.querySelector('button[type="submit"]');
    var submitLabel = submitButton.textContent;

    // Tapping a meal in the packages section preselects it in the form
    document.querySelectorAll('[data-package]').forEach(function (row) {
        row.addEventListener('click', function () {
            packageSelect.value = row.getAttribute('data-package');
        });
    });

    var showError = function (message) {
        errorBox.textContent = message;
        errorBox.classList.toggle('d-none', !message);
    };

    // Puts each server-side message under its field; clears them when called with {}
    var showFieldErrors = function (errors) {
        form.querySelectorAll('[name]').forEach(function (field) {
            var message = errors[field.name] || '';
            field.setCustomValidity(message);
            var feedback = field.parentElement.querySelector('.invalid-feedback');
            if (feedback) {
                feedback.textContent = message || feedback.getAttribute('data-default');
            }
        });
    };

    form.addEventListener('input', function (e) {
        if (e.target.name) {
            e.target.setCustomValidity('');
        }
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        showError('');
        showFieldErrors({});

        // Trim first so a name of only spaces fails the required check
        ['bookName', 'bookPhone', 'bookVenue'].forEach(function (id) {
            var field = document.getElementById(id);
            field.value = field.value.replace(/\s+/g, ' ').trim();
        });

        if (!form.checkValidity()) {
            form.classList.add('was-validated');
            var firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }
            return;
        }

        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';

        fetch(form.action, {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: new FormData(form)
        })
            .then(function (response) {
                return response.json();
            })
            .then(function (data) {
                if (data.ok) {
                    window.location.href = data.redirect;
                    return;
                }
                showFieldErrors(data.errors || {});
                form.classList.add('was-validated');
                showError(data.message || 'Your booking could not be sent. Please try again.');
                submitButton.disabled = false;
                submitButton.textContent = submitLabel;
            })
            .catch(function () {
                showError('Your booking could not be sent. Please check your connection and try again, or contact us on WhatsApp.');
                submitButton.disabled = false;
                submitButton.textContent = submitLabel;
            });
    });
})();
