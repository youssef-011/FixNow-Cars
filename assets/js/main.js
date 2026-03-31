/*
    Shared JavaScript for the FixNow Cars project.
    It handles the small mobile menu interaction and auto-hides flash messages.
*/

document.addEventListener('DOMContentLoaded', function () {
    var navToggle = document.querySelector('.nav-toggle');
    var mainNav = document.querySelector('.main-nav');

    if (navToggle && mainNav) {
        navToggle.setAttribute('aria-controls', 'main-navigation');
        navToggle.setAttribute('aria-expanded', 'false');

        navToggle.addEventListener('click', function () {
            mainNav.classList.toggle('is-open');
            navToggle.setAttribute('aria-expanded', mainNav.classList.contains('is-open') ? 'true' : 'false');
        });
    }

    var flashMessages = document.querySelectorAll('[data-auto-hide="true"]');

    flashMessages.forEach(function (message) {
        window.setTimeout(function () {
            message.classList.add('is-hidden');
        }, 4500);
    });
});
