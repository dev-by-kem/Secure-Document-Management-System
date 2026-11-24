/* ============================================================
   SDMS Frontend Behaviors — Progressive Enhancement
   Cavite State University (CvSU) Theme
   ============================================================ */
(function () {
    'use strict';

    var STORAGE_KEY = 'sdms-theme';

    /* ---- Theme Management: System Default + User Override ----- */
    function systemTheme() {
        return window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light';
    }

    function appliedTheme() {
        return document.documentElement.getAttribute('data-bs-theme') || 'light';
    }

    function updateThemeUI(theme) {
        var btns = document.querySelectorAll('#themeToggle, .js-theme-toggle');
        btns.forEach(function (btn) {
            btn.setAttribute('aria-pressed', String(theme === 'dark'));
            var isDark = theme === 'dark';
            btn.setAttribute('title', isDark ? 'Switch to light theme' : 'Switch to dark theme');
            btn.setAttribute('aria-label', isDark ? 'Switch to light theme' : 'Switch to dark theme');
            // Update icon inside the button if it exists
            var icon = btn.querySelector('i');
            if (icon) {
                icon.className = isDark ? 'bi bi-sun-fill text-warning' : 'bi bi-moon-stars';
            }
            // Update text if button has a label span
            var label = btn.querySelector('.theme-label');
            if (label) {
                label.textContent = isDark ? 'Light Theme' : 'Dark Theme';
            }
        });
    }

    function applyTheme(theme) {
        document.documentElement.setAttribute('data-bs-theme', theme);
        updateThemeUI(theme);
    }

    function storedTheme() {
        try { return localStorage.getItem(STORAGE_KEY); } catch (e) { return null; }
    }

    function syncTheme() {
        applyTheme(storedTheme() || systemTheme());
    }

    // Toggle theme click listener
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? (ev.target.closest('#themeToggle') || ev.target.closest('.js-theme-toggle')) : null;
        if (!btn) { return; }
        ev.preventDefault();
        var target = appliedTheme() === 'dark' ? 'light' : 'dark';
        try {
            if (target === systemTheme()) {
                localStorage.removeItem(STORAGE_KEY);
            } else {
                localStorage.setItem(STORAGE_KEY, target);
            }
        } catch (e) {}
        applyTheme(target);
    });

    // Run sync on script parse and DOM ready
    syncTheme();
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', syncTheme);
    }

    /* ---- Password Visibility Toggles ----- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-password-toggle]') : null;
        if (!btn) { return; }
        ev.preventDefault();
        var selector = btn.getAttribute('data-password-toggle');
        if (!selector) { return; }
        var input = document.querySelector(selector);
        if (!input) { return; }

        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(show));
        btn.textContent = show ? 'Hide' : 'Show';
    });

    /* ---- Document Details Drawer Expansion (Document Directory) ----- */
    document.addEventListener('click', function (ev) {
        var toggle = ev.target.closest ? (ev.target.closest('.js-doc-toggle') || ev.target.closest('[data-doc-drawer]')) : null;
        if (!toggle) { return; }

        var targetId = toggle.getAttribute('data-doc-drawer');
        if (!targetId) { return; }

        var drawer = document.getElementById(targetId);
        if (!drawer) { return; }

        ev.preventDefault();
        var isExpanded = !drawer.classList.contains('d-none');
        var parentRow  = toggle.closest ? toggle.closest('tr') : null;

        if (isExpanded) {
            drawer.classList.add('d-none');
            toggle.setAttribute('aria-expanded', 'false');
            if (parentRow) {
                parentRow.classList.remove('doc-row--expanded');
                var icon = parentRow.querySelector('.doc-chevron');
                if (icon) {
                    icon.classList.remove('bi-chevron-down');
                    icon.classList.add('bi-chevron-right');
                }
            }
        } else {
            drawer.classList.remove('d-none');
            toggle.setAttribute('aria-expanded', 'true');
            if (parentRow) {
                parentRow.classList.add('doc-row--expanded');
                var icon = parentRow.querySelector('.doc-chevron');
                if (icon) {
                    icon.classList.remove('bi-chevron-right');
                    icon.classList.add('bi-chevron-down');
                }
            }
        }
    });

    /* ---- Native confirmation dialog for all .js-confirm forms ----- */
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
    });

    if (dialog) {
        dialog.addEventListener('close', function () {
            if (dialog.returnValue === 'confirm' && pending) {
                pending.submit();
            }
            dialog.returnValue = '';
            pending = null;
        });
    }

    /* ---- Print Report Button Handler (FR-29) ----- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('#print-report') : null;
        if (btn) {
            ev.preventDefault();
            window.print();
        }
    });

    /* ---- Upload Dropzone Interaction (Progressive Enhancement) ----- */
    document.addEventListener('DOMContentLoaded', function () {
        var dropzone = document.getElementById('uploadDropzone');
        var fileInput = document.getElementById('document_file');
        var fileNameDisplay = document.getElementById('selectedFileName');

        if (dropzone && fileInput) {
            dropzone.addEventListener('click', function () {
                fileInput.click();
            });

            fileInput.addEventListener('change', function () {
                if (fileInput.files && fileInput.files.length > 0) {
                    var f = fileInput.files[0];
                    if (fileNameDisplay) {
                        fileNameDisplay.innerHTML = '<i class="bi bi-file-earmark-check text-success me-1"></i><strong>' +
                            escapeHtml(f.name) + '</strong> (' + formatBytes(f.size) + ')';
                    }
                }
            });

            ['dragenter', 'dragover'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.add('dragover');
                }, false);
            });

            ['dragleave', 'drop'].forEach(function (eventName) {
                dropzone.addEventListener(eventName, function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    dropzone.classList.remove('dragover');
                }, false);
            });

            dropzone.addEventListener('drop', function (e) {
                var dt = e.dataTransfer;
                if (dt && dt.files && dt.files.length > 0) {
                    fileInput.files = dt.files;
                    var event = new Event('change', { bubbles: true });
                    fileInput.dispatchEvent(event);
                }
            }, false);
        }
    });

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function formatBytes(bytes) {
        if (bytes >= 1048576) { return (bytes / 1048576).toFixed(1) + ' MB'; }
        if (bytes >= 1024)    { return (bytes / 1024).toFixed(1) + ' KB'; }
        return bytes + ' B';
    }
})();
