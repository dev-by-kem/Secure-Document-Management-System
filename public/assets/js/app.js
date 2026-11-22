/* ============================================================
   SDMS frontend behaviors — all progressive enhancement.
   Rules: no inline JS anywhere; if this file fails to load, every
   feature still works (forms submit, pages render, light theme shows).
   ============================================================ */
(function () {
    'use strict';

    var STORAGE_KEY = 'sdms-theme';

    /* ---- Theme: follow the system by default, optional override -----
       Two-state model from the dark-mode-toggle guidance:
       - no stored value  → follow the OS preference
       - stored value     → pin that scheme
       - toggling back to the system scheme removes the stored value,
         so the site keeps following the OS when it changes later.   */
    function systemTheme() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }
    function appliedTheme() {
        return document.documentElement.getAttribute('data-bs-theme') || 'light';
    }
    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        var btn = document.getElementById('themeToggle');
        if (btn) { btn.setAttribute('aria-pressed', String(theme === 'dark')); }
    }
    function storedTheme() {
        try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
    }
    function syncTheme() {
        applyTheme(storedTheme() || systemTheme());
    }
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('#themeToggle') : null;
        if (!btn) { return; }
        var target = appliedTheme() === 'dark' ? 'light' : 'dark';
        try {
            if (target === systemTheme()) {
                localStorage.removeItem(STORAGE_KEY); // undo the override
            } else {
                localStorage.setItem(STORAGE_KEY, target); // pin the override
            }
        } catch (e) { /* persistence is a nice-to-have, never required */ }
        applyTheme(target);
    });
    syncTheme(); // keeps the button's aria-pressed state honest on load

    /* ---- Confirm dialog: one native <dialog> for all .js-confirm forms --
       The dialog markup lives in app/includes/footer.php. The browser
       already provides focus containment, Esc-to-close and focus return. */
    var dialog  = document.getElementById('confirm-dialog');
    var message = document.getElementById('confirm-dialog-message');
    var pending = null;

    document.addEventListener('submit', function (ev) {
        var form = ev.target;
        if (!form || !form.classList || !form.classList.contains('js-confirm')) { return; }
        if (dialog && typeof dialog.showModal === 'function') {
            ev.preventDefault();
            pending = form;
            message.textContent = form.getAttribute('data-confirm-message')
                || 'Are you sure? This cannot be undone from the interface.';
            dialog.showModal();
        }
        // No <dialog> support (very old browser): the form simply submits.
    });

    if (dialog) {
        dialog.addEventListener('close', function () {
            if (dialog.returnValue === 'confirm' && pending) {
                pending.submit(); // programmatic submit skips the submit event on purpose
            }
            dialog.returnValue = ''; // reset: never leak a stale value into the next open
            pending = null;
        });
    }

    /* ---- Password visibility toggles (login, profile forms) ----------- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-password-toggle]') : null;
        if (!btn) { return; }
        var input = document.querySelector(btn.getAttribute('data-password-toggle'));
        if (!input) { return; }
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(show));
        btn.textContent = show ? 'Hide password' : 'Show password';
    });
})();
