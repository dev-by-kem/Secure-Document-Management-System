/* ============================================================
   SDMS Frontend Behaviors — Progressive Enhancement
   Cavite State University (CvSU) Theme
   ============================================================ */
(function () {
    'use strict';

    var STORAGE_KEY = 'sdms-theme';

    /* ---- Theme: follow system by default, optional override ----- */
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
                localStorage.removeItem(STORAGE_KEY);
            } else {
                localStorage.setItem(STORAGE_KEY, target);
            }
        } catch (e) {}
        applyTheme(target);
    });
    syncTheme();

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

    /* ---- Password visibility toggles ----- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-password-toggle]') : null;
        if (!btn) { return; }
        var input = document.querySelector(btn.getAttribute('data-password-toggle'));
        if (!input) { return; }
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.setAttribute('aria-pressed', String(show));
        btn.textContent = show ? 'Hide' : 'Show';
    });

    /* ---- Print Report Button Handler (FR-29) ----- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('#print-report');
        if (btn) {
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
