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
                icon.className = isDark ? 'bi bi-sun' : 'bi bi-moon';
            }
            // Update text if button has a label span
            var label = btn.querySelector('.theme-label');
            if (label) {
                label.textContent = isDark ? 'Light Theme' : 'Dark Theme';
            }
        });
    }

    function applyTheme(theme) {
        // Suppress transitions for an instant theme switch
        document.documentElement.classList.add('theme-switching');
        document.documentElement.setAttribute('data-bs-theme', theme);
        updateThemeUI(theme);
        // Allow reflow, then release the transition lock
        requestAnimationFrame(function () {
            requestAnimationFrame(function () {
                document.documentElement.classList.remove('theme-switching');
            });
        });
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
        var label = show ? 'Hide password' : 'Show password';
        btn.setAttribute('aria-label', label);
        btn.setAttribute('title', label);
        var icon = btn.querySelector('i');
        if (icon) {
            icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        }
        var hiddenLabel = btn.querySelector('.visually-hidden');
        if (hiddenLabel) {
            hiddenLabel.textContent = label;
        }
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
            document.querySelectorAll('.js-doc-toggle[data-doc-drawer="' + targetId + '"]').forEach(function (btn) {
                btn.setAttribute('aria-expanded', 'false');
            });
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
            document.querySelectorAll('.js-doc-toggle[data-doc-drawer="' + targetId + '"]').forEach(function (btn) {
                btn.setAttribute('aria-expanded', 'true');
            });
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

    /* ---- Native confirmation dialog for all .js-confirm forms or submitters ----- */
    var dialog           = document.getElementById('confirm-dialog');
    var message          = document.getElementById('confirm-dialog-message');
    var pending          = null;
    var pendingSubmitter = null;

    document.addEventListener('submit', function (ev) {
        var form      = ev.target;
        var submitter = ev.submitter || null;
        if (!form) { return; }

        if (submitter && submitter.hasAttribute('data-require-field')) {
            var reqField = document.querySelector(submitter.getAttribute('data-require-field'));
            if (reqField && !reqField.value.trim()) {
                ev.preventDefault();
                reqField.setCustomValidity('Please enter rejection remarks before rejecting.');
                reqField.reportValidity();
                reqField.focus();
                reqField.addEventListener('input', function clearMsg() {
                    reqField.setCustomValidity('');
                    reqField.removeEventListener('input', clearMsg);
                });
                return;
            }
        }

        var confirmMsg = (submitter && submitter.getAttribute('data-confirm-message'))
            || (form.classList && form.classList.contains('js-confirm') ? form.getAttribute('data-confirm-message') : null);
        if (!confirmMsg && !(form.classList && form.classList.contains('js-confirm'))) { return; }

        if (dialog && typeof dialog.showModal === 'function') {
            ev.preventDefault();
            pending          = form;
            pendingSubmitter = submitter;
            message.textContent = confirmMsg || 'Are you sure? This cannot be undone from the interface.';
            dialog.showModal();
        }
    });

    if (dialog) {
        dialog.addEventListener('close', function () {
            if (dialog.returnValue === 'confirm' && pending) {
                if (pendingSubmitter && pendingSubmitter.name) {
                    var hidden = document.createElement('input');
                    hidden.type  = 'hidden';
                    hidden.name  = pendingSubmitter.name;
                    hidden.value = pendingSubmitter.value;
                    pending.appendChild(hidden);
                }
                pending.submit();
            }
            dialog.returnValue = '';
            pending          = null;
            pendingSubmitter = null;
        });
    }

    /* ---- Reset Password Dialog (User Accounts) ----- */
    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest('.js-reset-password');
        if (!btn) { return; }
        var dialog     = document.getElementById('reset-password-dialog');
        var userIdInput = document.getElementById('reset-user-id');
        var targetInput = document.getElementById('reset-password-target');
        var passwordInput = document.getElementById('reset-new-password');
        if (!dialog || !userIdInput || !targetInput || !passwordInput) { return; }
        ev.preventDefault();
        try {
            userIdInput.value    = btn.getAttribute('data-user-id') || '';
            targetInput.textContent = btn.getAttribute('data-user') || '';
            passwordInput.value = '';
            dialog.showModal();
            passwordInput.focus();
        } catch (err) {
            try { dialog.show(); } catch (e2) {}
        }
    });

    document.addEventListener('click', function (ev) {
        if (!ev.target.closest('.js-reset-cancel')) { return; }
        var dialog = document.getElementById('reset-password-dialog');
        if (!dialog) { return; }
        ev.preventDefault();
        dialog.close();
    });

    /* ---- Focus first server-invalid control on page load ----- */
    document.addEventListener('DOMContentLoaded', function () {
        var firstInvalid = document.querySelector('[aria-invalid="true"]');
        if (firstInvalid && typeof firstInvalid.focus === 'function') {
            firstInvalid.focus();
        }
    });

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
            dropzone.addEventListener('keydown', function (e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    fileInput.click();
                }
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

    /* ---- Mobile Off-Canvas Navigation Drawer ----- */
    document.addEventListener('DOMContentLoaded', function () {
        var sidebar  = document.getElementById('sdmsSidebar');
        var openBtn  = document.getElementById('mobileNavOpen');
        var closeBtn = document.getElementById('mobileNavClose');
        var backdrop = document.getElementById('mobileNavBackdrop');
        var mobileBar = document.querySelector('.sdms-mobile-bar');
        var mainEl   = document.getElementById('main-content');
        var footerEl = document.querySelector('footer');
        if (!sidebar || !openBtn || !backdrop) { return; }

        var mq = window.matchMedia('(max-width: 768px)');

        function setBackgroundInert(state) {
            if (mainEl) { mainEl.inert = state; }
            if (footerEl) { footerEl.inert = state; }
            if (mobileBar) { mobileBar.inert = state; }
        }

        function drawerFocusable() {
            return Array.prototype.slice.call(sidebar.querySelectorAll(
                'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]:not([tabindex="-1"])'
            )).filter(function (element) {
                return element.offsetParent !== null;
            });
        }

        function openDrawer() {
            if (!mq.matches) { return; }
            sidebar.classList.add('is-open');
            sidebar.setAttribute('role', 'dialog');
            sidebar.setAttribute('aria-modal', 'true');
            sidebar.setAttribute('aria-label', 'Navigation menu');
            backdrop.hidden = false;
            requestAnimationFrame(function () {
                backdrop.classList.add('is-open');
            });
            openBtn.setAttribute('aria-expanded', 'true');
            setBackgroundInert(true);
            var firstFocusable = drawerFocusable()[0];
            if (firstFocusable) { firstFocusable.focus(); }
        }

        function closeDrawer(restoreFocus) {
            if (!sidebar.classList.contains('is-open')) { return; }
            sidebar.classList.remove('is-open');
            sidebar.removeAttribute('role');
            sidebar.removeAttribute('aria-modal');
            sidebar.removeAttribute('aria-label');
            backdrop.classList.remove('is-open');
            backdrop.hidden = true;
            openBtn.setAttribute('aria-expanded', 'false');
            setBackgroundInert(false);
            if (restoreFocus !== false) { openBtn.focus(); }
        }

        openBtn.addEventListener('click', openDrawer);
        if (closeBtn) { closeBtn.addEventListener('click', function () { closeDrawer(true); }); }
        backdrop.addEventListener('click', function () { closeDrawer(true); });

        document.addEventListener('keydown', function (e) {
            if (!sidebar.classList.contains('is-open')) { return; }

            if (e.key === 'Escape') {
                closeDrawer(true);
                return;
            }

            if (e.key !== 'Tab') { return; }
            var focusable = drawerFocusable();
            if (!focusable.length) { return; }

            var first = focusable[0];
            var last = focusable[focusable.length - 1];
            if (e.shiftKey && document.activeElement === first) {
                e.preventDefault();
                last.focus();
            } else if (!e.shiftKey && document.activeElement === last) {
                e.preventDefault();
                first.focus();
            }
        });

        sidebar.addEventListener('click', function (e) {
            var link = e.target.closest ? e.target.closest('a.sidebar-link:not([data-bs-toggle]), a.sidebar-sub-link') : null;
            if (link && mq.matches) {
                closeDrawer(false);
            }
        });

        if (mq.addEventListener) {
            mq.addEventListener('change', function (e) {
                if (!e.matches) { closeDrawer(false); }
            });
        }
    });

    /* ---- Login Campus Background Parallax (Optional Enhancement) ----- */
    document.addEventListener('DOMContentLoaded', function () {
        var page = document.querySelector('.auth-page');
        var background = page ? page.querySelector('.auth-background') : null;
        var motionQuery = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)') : null;
        var pointerQuery = window.matchMedia ? window.matchMedia('(pointer: fine)') : null;
        if (!page || !background || !pointerQuery || !pointerQuery.matches || (motionQuery && motionQuery.matches)) {
            return;
        }

        var frame = null;
        var targetX = 0;
        var targetY = 0;
        var currentX = 0;
        var currentY = 0;

        function render() {
            currentX += (targetX - currentX) * 0.12;
            currentY += (targetY - currentY) * 0.12;
            page.style.setProperty('--auth-parallax-x', currentX.toFixed(2) + 'px');
            page.style.setProperty('--auth-parallax-y', currentY.toFixed(2) + 'px');
            if (Math.abs(targetX - currentX) > 0.05 || Math.abs(targetY - currentY) > 0.05) {
                frame = window.requestAnimationFrame(render);
            } else {
                frame = null;
            }
        }

        page.addEventListener('pointermove', function (event) {
            if (event.pointerType && event.pointerType !== 'mouse') { return; }
            var bounds = page.getBoundingClientRect();
            var x = (event.clientX - bounds.left) / bounds.width - 0.5;
            var y = (event.clientY - bounds.top) / bounds.height - 0.5;
            targetX = x * 12;
            targetY = y * 8;
            if (!frame) { frame = window.requestAnimationFrame(render); }
        });

        page.addEventListener('pointerleave', function () {
            targetX = 0;
            targetY = 0;
            if (!frame) { frame = window.requestAnimationFrame(render); }
        });
    });
})();
