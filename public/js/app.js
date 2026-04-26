/* ============================================================
   Skilluence Portal - Main JavaScript
   ============================================================ */

(function () {
    'use strict';

    /* ── Theme ─────────────────────────────────────────────────── */
    var html = document.documentElement;
    html.setAttribute('data-theme', localStorage.getItem('theme') || 'light');

    // Bind ALL .theme-toggle buttons (header + profile preferences)
    function bindThemeToggles() {
        document.querySelectorAll('.theme-toggle').forEach(function (btn) {
            if (btn.dataset.themeBound) return;
            btn.dataset.themeBound = '1';
            btn.addEventListener('click', function () {
                var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                html.setAttribute('data-theme', next);
                localStorage.setItem('theme', next);
            });
        });
    }
    bindThemeToggles();

    /* ── Sidebar ────────────────────────────────────────────────── */
    var sidebar       = document.querySelector('.sidebar');
    var sidebarToggle = document.querySelector('.sidebar-toggle');   // pin btn inside sidebar
    var headerToggle  = document.getElementById('headerSidebarToggle'); // hamburger in top header

    function setSidebarLocked(locked) {
        sidebar.classList.toggle('locked', locked);
        localStorage.setItem('sidebarLocked', locked ? 'true' : 'false');
    }

    if (sidebar) {
        // Restore persisted state
        setSidebarLocked(localStorage.getItem('sidebarLocked') === 'true');

        // Pin button inside sidebar
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', function (e) {
                e.stopPropagation();
                setSidebarLocked(!sidebar.classList.contains('locked'));
            });
        }

        // Hamburger button in main header
        if (headerToggle) {
            headerToggle.addEventListener('click', function () {
                setSidebarLocked(!sidebar.classList.contains('locked'));
            });
        }
    }

    /* ── Password eye toggles (event delegation — works for all modals) ── */
    document.addEventListener('click', function (e) {
        var button = e.target.closest('.password-toggle');
        if (!button) return;
        var wrapper = button.closest('.input-with-icon');
        var input = wrapper ? wrapper.querySelector('input[type="password"], input[type="text"]') : null;
        if (!input) return;
        input.type = input.type === 'password' ? 'text' : 'password';
        button.innerHTML = input.type === 'password'
            ? '<i class="bi bi-eye"></i>'
            : '<i class="bi bi-eye-slash"></i>';
    });

    /* —— Disable browser password autofill across forms —— */
    function hardenPasswordInputs(scope) {
        var root = scope || document;
        root.querySelectorAll('form').forEach(function (form) {
            if (!form.hasAttribute('autocomplete')) {
                form.setAttribute('autocomplete', 'off');
            }
        });
        root.querySelectorAll('input[type="password"]').forEach(function (input) {
            if (!input.hasAttribute('autocomplete')) {
                input.setAttribute('autocomplete', 'new-password');
            }
            input.setAttribute('autocorrect', 'off');
            input.setAttribute('autocapitalize', 'off');
            input.setAttribute('spellcheck', 'false');

            // Clear browser-prefilled password values for non-readonly inputs.
            if (!input.readOnly && input.value) {
                input.value = '';
            }
        });
    }
    hardenPasswordInputs();

    /* ── Global form submit-once protection ─────────────────────── */
    // Prevents double-submissions (e.g. Import clicking multiple times)
    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        var form = event.target;
        var method = (form.getAttribute('method') || 'get').toLowerCase();
        if (form.hasAttribute('data-submit-lock-skip') || method === 'get') {
            return;
        }
        if (form.dataset.submitting === '1') {
            event.preventDefault();
            return;
        }
        form.dataset.submitting = '1';
        var btn = form.querySelector('button[type="submit"], input[type="submit"]');
        if (btn) {
            btn.disabled = true;
            var originalHTML = btn.innerHTML;
            btn.dataset.originalHtml = originalHTML;
            // Replace leading icon with spinner
            btn.innerHTML = originalHTML.replace(/<i class="[^"]*"><\/i>/, '<i class="bi bi-hourglass-split"></i>');
        }
    }, false);

    /* ── Flatpickr date pickers ─────────────────────────────────── */
    function initDatePickers(scope) {
        if (typeof flatpickr === 'undefined') return;
        var root = scope || document;
        root.querySelectorAll('input[type="date"]').forEach(function (el) {
            if (el._flatpickr) return;
            flatpickr(el, {
                dateFormat: 'Y-m-d',
                allowInput: true,
                disableMobile: false,
                theme: 'material_blue',
            });
        });
    }
    initDatePickers();

    /* ── Dropzone enhancement ───────────────────────────────────── */
    function initDropzones(scope) {
        var root = scope || document;
        root.querySelectorAll('input[type="file"]').forEach(function (input) {
            if (input.dataset.dzInit) return;
            input.dataset.dzInit = '1';

            var label = input.closest('.form-group, .stp-form-group');
            if (!label) return;

            // Build dropzone wrapper
            var dz = document.createElement('div');
            dz.className = 'dropzone-wrap';

            var inner = document.createElement('div');
            inner.className = 'dropzone-inner';
            inner.innerHTML =
                '<i class="bi bi-cloud-arrow-up dropzone-icon"></i>' +
                '<div class="dropzone-text">Drop file here or <span class="dropzone-browse">browse</span></div>' +
                '<div class="dropzone-hint">PDF, DOC, DOCX, JPG, PNG &mdash; max 5 MB</div>' +
                '<div class="dropzone-fname"></div>';

            dz.appendChild(inner);

            // Move input into dropzone
            input.parentNode.insertBefore(dz, input);
            dz.appendChild(input);
            input.classList.add('dz-input-hidden');

            // Update filename display
            input.addEventListener('change', function () {
                var fnEl = dz.querySelector('.dropzone-fname');
                if (this.files && this.files[0]) {
                    fnEl.textContent = this.files[0].name;
                    dz.classList.add('dz-has-file');
                } else {
                    fnEl.textContent = '';
                    dz.classList.remove('dz-has-file');
                }
            });

            // Drag events
            dz.addEventListener('dragover', function (e) {
                e.preventDefault();
                dz.classList.add('dz-drag');
            });
            dz.addEventListener('dragleave', function () {
                dz.classList.remove('dz-drag');
            });
            dz.addEventListener('drop', function (e) {
                e.preventDefault();
                dz.classList.remove('dz-drag');
                if (e.dataTransfer && e.dataTransfer.files.length) {
                    try {
                        input.files = e.dataTransfer.files;
                    } catch (_) {}
                    var ev = new Event('change', { bubbles: true });
                    input.dispatchEvent(ev);
                }
            });

            // Click dropzone opens file picker
            inner.addEventListener('click', function () {
                input.click();
            });
        });
    }
    initDropzones();
    window._initDropzones = initDropzones; // expose for dynamic elements

    /* ── Inline status editor (double-click on table status cell) ── */
    var STATUS_BADGES = {
        active:    'badge-success',
        enrolled:  'badge-info',
        interview: 'badge-warning',
        offer:     'badge-primary',
        placed:    'badge-success',
        onhold:    'badge-warning',
        inactive:  'badge-danger'
    };
    var STATUS_LIST = ['active', 'enrolled', 'interview', 'offer', 'placed', 'onhold', 'inactive'];

    var ispEl = null;
    var ispCell = null;

    function buildISP() {
        var el = document.createElement('div');
        el.id = 'inlineStatusPicker';
        el.className = 'inline-status-picker';

        STATUS_LIST.forEach(function (s) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'isp-btn';
            btn.dataset.status = s;
            btn.innerHTML = '<span class="badge ' + STATUS_BADGES[s] + '">' + s.charAt(0).toUpperCase() + s.slice(1) + '</span>';
            btn.addEventListener('mousedown', function (e) {
                e.preventDefault();
                ispApply(s);
            });
            el.appendChild(btn);
        });

        document.body.appendChild(el);
        return el;
    }

    function ispOpen(cell) {
        if (!ispEl) ispEl = buildISP();
        ispCell = cell;

        var rect = cell.getBoundingClientRect();
        var top = rect.bottom + window.scrollY + 4;
        var left = rect.left + window.scrollX;

        // Ensure it doesn't go off screen right
        var pickerWidth = 160;
        if (left + pickerWidth > window.innerWidth) {
            left = window.innerWidth - pickerWidth - 8;
        }

        ispEl.style.top = top + 'px';
        ispEl.style.left = left + 'px';

        ispEl.querySelectorAll('.isp-btn').forEach(function (btn) {
            btn.classList.toggle('isp-current', btn.dataset.status === cell.dataset.currentStatus);
        });

        ispEl.classList.add('open');
        document.addEventListener('mousedown', ispOutside);
    }

    function ispClose() {
        if (ispEl) ispEl.classList.remove('open');
        document.removeEventListener('mousedown', ispOutside);
        ispCell = null;
    }

    function ispOutside(e) {
        if (ispEl && !ispEl.contains(e.target) && e.target !== ispCell && !ispCell?.contains(e.target)) {
            ispClose();
        }
    }

    function ispApply(newStatus) {
        var cell = ispCell;
        ispClose();
        if (!cell) return;

        var url = cell.dataset.statusUrl;
        var oldStatus = cell.dataset.currentStatus;
        if (newStatus === oldStatus) return;

        var badge = cell.querySelector('.badge');
        var csrfToken = document.querySelector('meta[name="csrf-token"]');
        csrfToken = csrfToken ? csrfToken.content : '';

        // Optimistic UI update
        badge.className = 'badge ' + STATUS_BADGES[newStatus];
        badge.textContent = newStatus.charAt(0).toUpperCase() + newStatus.slice(1);
        cell.dataset.currentStatus = newStatus;
        cell.style.opacity = '0.6';

        fetch(url, {
            method: 'PATCH',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ status: newStatus })
        }).then(function (response) {
            return response.json().then(function (data) {
                if (!response.ok) throw new Error(data.message || 'Update failed');
                return data;
            });
        }).then(function () {
            cell.style.opacity = '';
            // Also update the edit button payload so modal shows correct status
            var tr = cell.closest('tr');
            if (tr) {
                var editBtn = tr.querySelector('[data-candidate]');
                if (editBtn) {
                    try {
                        var payload = JSON.parse(atob(editBtn.dataset.candidate));
                        payload.status = newStatus;
                        editBtn.dataset.candidate = btoa(JSON.stringify(payload));
                    } catch (_) {}
                }
            }
        }).catch(function (error) {
            // Revert on error
            badge.className = 'badge ' + STATUS_BADGES[oldStatus];
            badge.textContent = oldStatus.charAt(0).toUpperCase() + oldStatus.slice(1);
            cell.dataset.currentStatus = oldStatus;
            cell.style.opacity = '';
            if (window.showToast) {
                window.showToast(error && error.message ? error.message : 'Could not update status.', 'error');
            }
        });
    }

    document.addEventListener('dblclick', function (e) {
        var cell = e.target.closest('[data-inline-status]');
        if (cell) {
            e.preventDefault();
            ispOpen(cell);
        }
    });

    /* ── Modal open/close ──────────────────────────────────────── */
    var hideCandidatePasswordPrompt = function () {
        var tooltip = document.getElementById('candidate_password_verify_tooltip');
        if (tooltip) tooltip.classList.remove('open');

        setVal('candidate_verify_login_password', '');
        var error = document.getElementById('candidate_verify_error');
        if (error) error.textContent = '';

        var submit = document.getElementById('candidate_verify_submit');
        if (submit) {
            submit.disabled = false;
            submit.textContent = 'Verify';
        }
    };

    var resetCandidatePasswordReveal = function () {
        var currentPassword = document.getElementById('edit_current_login_password');
        if (currentPassword) {
            currentPassword.value = '********';
            currentPassword.type = 'password';
            currentPassword.dataset.unlocked = '0';
        }

        var button = document.getElementById('edit_reveal_password_btn');
        if (button) button.innerHTML = '<i class="bi bi-eye"></i>';

        hideCandidatePasswordPrompt();
    };

    window.openModal = function (id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';

        // Init pickers inside the just-opened modal
        initDatePickers(overlay);
        initDropzones(overlay);
        if (window._initCandidateSelects) window._initCandidateSelects(overlay);
        if (window._initPhoneInputs) window._initPhoneInputs(overlay);
        hardenPasswordInputs(overlay);
        bindThemeToggles();
    };

    window.closeModal = function (id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.remove('open');
        document.body.style.overflow = '';

        if (id === 'editCandidateModal') resetCandidatePasswordReveal();
    };

    window.smoothToggleElement = function (element, shouldShow, displayValue) {
        if (!element) return;

        var reduceMotion = window.matchMedia &&
            window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (element._smoothToggleTimer) {
            window.clearTimeout(element._smoothToggleTimer);
            element._smoothToggleTimer = null;
        }

        if (reduceMotion) {
            element.style.display = shouldShow ? (displayValue || 'block') : 'none';
            element.style.maxHeight = '';
            element.style.opacity = '';
            element.style.transform = '';
            element.style.overflow = '';
            element.classList.remove('is-smooth-toggling');
            return;
        }

        element.classList.add('is-smooth-toggling');
        element.style.overflow = 'hidden';
        element.style.transition = 'max-height 220ms ease, opacity 180ms ease, transform 220ms ease';

        if (shouldShow) {
            element.style.display = displayValue || 'block';
            element.style.maxHeight = '0px';
            element.style.opacity = '0';
            element.style.transform = 'translateY(-4px)';

            window.requestAnimationFrame(function () {
                element.style.maxHeight = element.scrollHeight + 'px';
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
            });

            element._smoothToggleTimer = window.setTimeout(function () {
                element.style.maxHeight = '';
                element.style.opacity = '';
                element.style.transform = '';
                element.style.overflow = '';
                element.style.transition = '';
                element.classList.remove('is-smooth-toggling');
            }, 240);
            return;
        }

        element.style.maxHeight = element.scrollHeight + 'px';
        element.style.opacity = '1';
        element.style.transform = 'translateY(0)';

        window.requestAnimationFrame(function () {
            element.style.maxHeight = '0px';
            element.style.opacity = '0';
            element.style.transform = 'translateY(-4px)';
        });

        element._smoothToggleTimer = window.setTimeout(function () {
            element.style.display = 'none';
            element.style.maxHeight = '';
            element.style.opacity = '';
            element.style.transform = '';
            element.style.overflow = '';
            element.style.transition = '';
            element.classList.remove('is-smooth-toggling');
        }, 240);
    };
    var smoothToggleElement = window.smoothToggleElement;

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal-overlay')) {
            if (event.target.id === 'editCandidateModal') resetCandidatePasswordReveal();
            event.target.classList.remove('open');
            document.body.style.overflow = '';
            return;
        }

        var tooltip = document.getElementById('candidate_password_verify_tooltip');
        if (!tooltip || !tooltip.classList.contains('open')) return;

        var wrap = document.getElementById('candidate_password_verify_wrap');
        if (wrap && !wrap.contains(event.target)) hideCandidatePasswordPrompt();
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;
        hideCandidatePasswordPrompt();
        ispClose();

        document.querySelectorAll('.modal-overlay.open').forEach(function (overlay) {
            overlay.classList.remove('open');
            if (overlay.id === 'editCandidateModal') resetCandidatePasswordReveal();
        });
        document.body.style.overflow = '';
    });

    /* ── Utility helpers ────────────────────────────────────────── */
    window.setVal = function (id, value) {
        var element = document.getElementById(id);
        if (!element) return;
        element.value = (value !== undefined && value !== null) ? value : '';
    };

    window.filterSelectOptions = function (searchInputId, selectId) {
        var searchInput = document.getElementById(searchInputId);
        var select = document.getElementById(selectId);
        if (!searchInput || !select) return;

        var needle = searchInput.value.trim().toLowerCase();
        Array.prototype.forEach.call(select.options, function (option) {
            if (!option.value) { option.hidden = false; return; }
            option.hidden = needle.length > 0 && option.text.toLowerCase().indexOf(needle) === -1;
        });
    };

    /* ── Candidate password reveal (Fetch) ──────────────────────── */
    var decodeJsonPayload = function (payload) {
        if (!payload) throw new Error('Empty payload');
        var normalized = payload.trim();
        var attempts = [normalized];
        try { attempts.unshift(atob(normalized)); } catch (_) {}

        for (var i = 0; i < attempts.length; i++) {
            if (!attempts[i]) continue;
            try { return JSON.parse(attempts[i]); } catch (_) {}
        }
        throw new Error('Invalid JSON payload');
    };

    window.toggleCandidateCurrentPassword = function () {
        var currentPassword = document.getElementById('edit_current_login_password');
        var button = document.getElementById('edit_reveal_password_btn');
        var tooltip = document.getElementById('candidate_password_verify_tooltip');
        var verifyInput = document.getElementById('candidate_verify_login_password');

        if (!currentPassword || !button || !tooltip) return;

        if (currentPassword.dataset.unlocked === '1') {
            currentPassword.type = currentPassword.type === 'password' ? 'text' : 'password';
            button.innerHTML = currentPassword.type === 'password'
                ? '<i class="bi bi-eye"></i>'
                : '<i class="bi bi-eye-slash"></i>';
            return;
        }

        tooltip.classList.add('open');
        if (verifyInput) {
            verifyInput.value = '';
            setTimeout(function () { verifyInput.focus(); }, 0);
        }
    };

    window.cancelCandidatePasswordReveal = function () { hideCandidatePasswordPrompt(); };

    window.handleCandidateRevealPasswordKeydown = function (event) {
        if (event.key !== 'Enter') return;
        event.preventDefault();
        window.confirmCandidatePasswordReveal();
    };

    window.confirmCandidatePasswordReveal = function () {
        var form = document.getElementById('editCandidateForm');
        var currentPassword = document.getElementById('edit_current_login_password');
        var verifyInput = document.getElementById('candidate_verify_login_password');
        var submit = document.getElementById('candidate_verify_submit');
        var error = document.getElementById('candidate_verify_error');
        var button = document.getElementById('edit_reveal_password_btn');

        if (!form || !currentPassword || !verifyInput || !submit || !button) return;

        var revealUrl = form.dataset.revealUrl || '';
        if (!revealUrl) {
            if (error) error.textContent = 'Password reveal URL is missing.';
            return;
        }

        if (!verifyInput.value) {
            if (error) error.textContent = 'Please enter your login password.';
            verifyInput.focus();
            return;
        }

        var csrfInput = form.querySelector('input[name="_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';

        if (error) error.textContent = '';
        submit.disabled = true;
        submit.textContent = 'Verifying...';

        fetch(revealUrl, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: JSON.stringify({ current_password: verifyInput.value })
        }).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) {
                    var message = (data.errors && data.errors.current_password && data.errors.current_password[0])
                        || data.message
                        || 'Unable to verify password. Please try again.';
                    throw new Error(message);
                }
                return data;
            });
        }).then(function (data) {
            currentPassword.value = data.password || '';
            currentPassword.type = 'text';
            currentPassword.dataset.unlocked = '1';
            button.innerHTML = '<i class="bi bi-eye-slash"></i>';
            hideCandidatePasswordPrompt();
        }).catch(function (fetchError) {
            if (error) error.textContent = fetchError.message || 'Unable to reveal password right now.';
        }).finally(function () {
            submit.disabled = false;
            submit.textContent = 'Verify';
        });
    };

    /* ── Modal tab switching ────────────────────────────────────── */
    window.switchModalTab = function (tabGroupId, activeTabId) {
        var group = document.getElementById(tabGroupId);
        if (!group) return;

        group.querySelectorAll('.modal-tab-btn').forEach(function (btn) {
            btn.classList.toggle('active', btn.getAttribute('data-tab') === activeTabId);
        });

        // Find parent modal and toggle panels
        var modal = group.closest('.modal');
        if (!modal) return;
        modal.querySelectorAll('.modal-tab-panel').forEach(function (panel) {
            panel.classList.toggle('active', panel.id === activeTabId);
        });
    };

    // Reset tab group to first tab
    function resetModalTabs(modalId) {
        var modal = document.getElementById(modalId);
        if (!modal) return;
        var firstBtn = modal.querySelector('.modal-tab-btn');
        if (!firstBtn) return;
        switchModalTab(firstBtn.closest('.modal-tabs').id, firstBtn.getAttribute('data-tab'));
    }

    /* ── Open candidate modal (add or edit) ─────────────────────── */
    window.openCandidateModal = function (mode) {
        if (mode === 'add') {
            initSubDomainBadges('add', '');
            initPrefCityBadges('add', '');
            resetResumeEntries('add');
            pickAssignee('add', '', '', '');
            loadCandidateCountries('add');
            initPhoneInputs(document.getElementById('addCandidateModal'));
            setupCandidateValidator('add');
            resetModalTabs('addCandidateModal');
            openModal('addCandidateModal');
        }
    };

    /* ── Sub-domain badge system ────────────────────────────────── */
    window.handleSubDomainKey = function (e, prefix) {
        if (e.key !== 'Enter' && e.key !== ',') return;
        e.preventDefault();
        var input  = document.getElementById(prefix + '_subdomain_input');
        var hidden = document.getElementById(prefix + '_sub_domain');
        if (!input || !hidden) return;
        var val = input.value.trim().replace(/,/g, '');
        if (!val) return;
        var values = hidden.value ? hidden.value.split(',').map(function(v){ return v.trim(); }).filter(Boolean) : [];
        if (!values.includes(val)) { values.push(val); hidden.value = values.join(','); }
        input.value = '';
        renderSubDomainBadges(prefix);
    };

    window.removeSubDomainBadge = function (prefix, idx) {
        var hidden = document.getElementById(prefix + '_sub_domain');
        if (!hidden) return;
        var values = hidden.value ? hidden.value.split(',').map(function(v){ return v.trim(); }).filter(Boolean) : [];
        values.splice(idx, 1);
        hidden.value = values.join(',');
        renderSubDomainBadges(prefix);
    };

    function renderSubDomainBadges(prefix) {
        var container = document.getElementById(prefix + '_subdomain_badges');
        var hidden    = document.getElementById(prefix + '_sub_domain');
        if (!container || !hidden) return;
        // Remove only badge spans — keep the input element intact
        container.querySelectorAll('.subdomain-badge').forEach(function(el) { el.remove(); });
        var input  = document.getElementById(prefix + '_subdomain_input');
        var values = hidden.value ? hidden.value.split(',').map(function(v){ return v.trim(); }).filter(Boolean) : [];
        values.forEach(function(val, i) {
            var span = document.createElement('span');
            span.className = 'subdomain-badge';
            span.innerHTML = val + '<button type="button" class="subdomain-badge-x" onclick="removeSubDomainBadge(\'' + prefix + '\',' + i + ')">&times;</button>';
            container.insertBefore(span, input);
        });
    }

    function initSubDomainBadges(prefix, value) {
        var hidden = document.getElementById(prefix + '_sub_domain');
        if (hidden) hidden.value = value || '';
        renderSubDomainBadges(prefix);
    }

    /* ── Resume list in edit modal ──────────────────────────────── */
    function renderCandidateResumes(prefix, resumes) {
        var emptyEl = document.getElementById(prefix + '_resume_empty');
        var tableWrap = document.getElementById(prefix + '_resume_table_wrap');
        var tbody = document.getElementById(prefix + '_resume_tbody');
        if (!emptyEl || !tableWrap || !tbody) return;

        if (!resumes || resumes.length === 0) {
            emptyEl.style.display = '';
            tableWrap.style.display = 'none';
            return;
        }

        emptyEl.style.display = 'none';
        tableWrap.style.display = '';
        tbody.innerHTML = '';
        resumes.forEach(function(r) {
            var tr = document.createElement('tr');
            tr.innerHTML =
                '<td style="padding:8px 12px;">' + escHtml(r.designation) + '</td>' +
                '<td style="padding:8px 12px;color:var(--text-muted);max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">' + escHtml(r.original_filename) + '</td>' +
                '<td style="padding:8px 12px;white-space:nowrap;">' + escHtml(r.uploaded_at) + '</td>' +
                '<td style="padding:8px 12px;text-align:center;"><a href="' + r.url + '" target="_blank" class="btn btn-outline btn-sm" title="View resume"><i class="bi bi-box-arrow-up-right"></i></a></td>';
            tbody.appendChild(tr);
        });
    }

    function escHtml(str) {
        return String(str || '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }

    /* ── Searchable select (ss-wrap) ────────────────────────────── */
    document.addEventListener('click', function (e) {
        if (!e.target.closest('.ss-wrap')) {
            document.querySelectorAll('.ss-wrap.ss-open').forEach(function (w) {
                w.classList.remove('ss-open');
            });
        }
    });

    window.toggleSS = function (wrapId) {
        var wrap = document.getElementById(wrapId);
        if (!wrap) return;
        var wasOpen = wrap.classList.contains('ss-open');
        document.querySelectorAll('.ss-wrap.ss-open').forEach(function (w) { w.classList.remove('ss-open'); });
        if (!wasOpen) {
            wrap.classList.add('ss-open');
            var inp = wrap.querySelector('.ss-search-input');
            if (inp) { inp.value = ''; filterSS(wrapId, ''); setTimeout(function () { inp.focus(); }, 10); }
        }
    };

    window.filterSS = function (wrapId, query) {
        var wrap = document.getElementById(wrapId);
        if (!wrap) return;
        var needle = query.trim().toLowerCase();
        wrap.querySelectorAll('.ss-option').forEach(function (opt) {
            if (opt.dataset.value === '') { opt.style.display = ''; return; } // "— None —" always visible
            var name = (opt.dataset.name || opt.textContent).trim().toLowerCase();
            opt.style.display = (needle && name.indexOf(needle) === -1) ? 'none' : '';
        });
        // Show group labels only if at least one option under them is visible
        wrap.querySelectorAll('.ss-group-label').forEach(function (label) {
            var hasVisible = false;
            var next = label.nextElementSibling;
            while (next && !next.classList.contains('ss-group-label')) {
                if (next.classList.contains('ss-option') && next.style.display !== 'none') {
                    hasVisible = true; break;
                }
                next = next.nextElementSibling;
            }
            label.style.display = hasVisible ? '' : 'none';
        });
    };

    // prefix='add'|'edit', field='recruiter'|'manager'
    window.pickSS = function (prefix, field, value, label) {
        var wrapId   = prefix + '_' + field + '_wrap';
        var hiddenId = prefix + (field === 'recruiter' ? '_recruiter_id' : '_team_manager_id');
        var labelId  = prefix + '_' + field + '_label';
        var wrap = document.getElementById(wrapId);
        var hidden = document.getElementById(hiddenId);
        var labelEl = document.getElementById(labelId);
        if (hidden)  hidden.value = value;
        if (labelEl) labelEl.textContent = label;
        if (wrap)    wrap.classList.remove('ss-open');
        // Mutual exclusivity: selecting a non-empty value clears the other field
        if (value) {
            var other       = field === 'recruiter' ? 'manager' : 'recruiter';
            var otherHidden = document.getElementById(prefix + (other === 'recruiter' ? '_recruiter_id' : '_team_manager_id'));
            var otherLabel  = document.getElementById(prefix + '_' + other + '_label');
            if (otherHidden) otherHidden.value = '';
            if (otherLabel)  otherLabel.textContent = '— None —';
        }
    };

    function setSS(prefix, field, value) {
        var wrapId   = prefix + '_' + field + '_wrap';
        var hiddenId = prefix + (field === 'recruiter' ? '_recruiter_id' : '_team_manager_id');
        var labelId  = prefix + '_' + field + '_label';
        var hidden  = document.getElementById(hiddenId);
        var labelEl = document.getElementById(labelId);
        if (!hidden || !labelEl) return;
        hidden.value = value || '';
        if (!value) { labelEl.textContent = '— None —'; return; }
        var wrap = document.getElementById(wrapId);
        var opt  = wrap ? wrap.querySelector('.ss-option[data-value="' + value + '"]') : null;
        labelEl.textContent = opt ? opt.textContent.trim() : String(value);
    }

    /* ── Resume entry blocks (add/edit modal) ───────────────────── */
    function buildResumeEntryBlock(idx, prefix) {
        var block = document.createElement('div');
        block.className = 'resume-entry-block';
        block.innerHTML =
            '<div class="resume-entry-top">' +
            '<input type="text" name="resumes[' + idx + '][designation]" class="form-control" placeholder="Designation (e.g. Java Developer)">' +
            '<button type="button" class="resume-del-btn" onclick="removeOrClearResumeEntry(this,\'' + prefix + '\')"><i class="bi bi-x"></i></button>' +
            '</div>' +
            '<div class="form-group" style="margin-bottom:0;">' +
            '<input type="file" name="resumes[' + idx + '][file]" class="form-control" accept=".pdf,.doc,.docx">' +
            '</div>';
        return block;
    }

    function reindexResumeEntries(prefix) {
        var wrap = document.getElementById(prefix + '_resume_entries');
        if (!wrap) return;
        wrap.querySelectorAll('.resume-entry-block').forEach(function (block, i) {
            var desig = block.querySelector('input[type="text"]');
            var file  = block.querySelector('input[type="file"]');
            if (desig) desig.name = 'resumes[' + i + '][designation]';
            if (file)  file.name  = 'resumes[' + i + '][file]';
        });
    }

    function resetResumeEntries(prefix) {
        var wrap = document.getElementById(prefix + '_resume_entries');
        if (!wrap) return;
        wrap.innerHTML = '';
        var block = buildResumeEntryBlock(0, prefix);
        wrap.appendChild(block);
        if (window._initDropzones) window._initDropzones(block);
    }

    window.addResumeEntryRow = function (prefix) {
        var wrap = document.getElementById(prefix + '_resume_entries');
        if (!wrap) return;
        var idx   = wrap.querySelectorAll('.resume-entry-block').length;
        var block = buildResumeEntryBlock(idx, prefix);
        wrap.appendChild(block);
        if (window._initDropzones) window._initDropzones(block);
    };

    window.removeOrClearResumeEntry = function (btn, prefix) {
        var wrap  = document.getElementById(prefix + '_resume_entries');
        var block = btn.closest('.resume-entry-block');
        if (!wrap || !block) return;
        var allBlocks = wrap.querySelectorAll('.resume-entry-block');
        if (allBlocks.length > 1) {
            block.remove();
            reindexResumeEntries(prefix);
        } else {
            // Last block — just clear inputs
            var desig = block.querySelector('input[type="text"]');
            var dz    = block.querySelector('.dropzone-wrap');
            if (desig) desig.value = '';
            if (dz) {
                var fileInput = dz.querySelector('input[type="file"]');
                var fname     = dz.querySelector('.dropzone-fname');
                if (fileInput) fileInput.value = '';
                if (fname)     fname.textContent = '';
                dz.classList.remove('dz-has-file');
            }
        }
    };

    /* ── Edit candidate modal ───────────────────────────────────── */
    window.editCandidate = function (candidate) {
        var form = document.getElementById('editCandidateForm');
        if (!form) return;

        form.action = form.dataset.base + '/' + candidate.id;
        form.dataset.revealUrl = candidate.reveal_password_url || '';

        // ── Personal Info
        setVal('edit_first_name',        candidate.first_name || '');
        setVal('edit_middle_name',       candidate.middle_name || '');
        setVal('edit_last_name',         candidate.last_name || '');
        setVal('edit_date_of_birth',     candidate.date_of_birth || '');
        setVal('edit_gender',            candidate.gender || '');
        setVal('edit_nationality',       candidate.nationality || '');
        setVal('edit_email_id',          candidate.email_id || '');
        setVal('edit_phone_number',      candidate.phone_number || '');
        setVal('edit_domain',            candidate.domain || '');
        // SSN: not pre-populated for security
        setVal('edit_ssn',               '');
        setVal('edit_date_of_arrival_usa', candidate.date_of_arrival_usa || '');
        setVal('edit_current_salary',    candidate.current_salary || '');
        setVal('edit_expected_salary',   candidate.expected_salary || '');
        // Sub-domain badge system
        initSubDomainBadges('edit', candidate.sub_domain || '');

        // ── Address (country → state → city cascade, async)
        setVal('edit_street_address', candidate.street_address || '');
        setVal('edit_apartment_unit', candidate.apartment_unit || '');
        setVal('edit_zip_code',       candidate.zip_code || '');
        setCandidateGeo('edit', candidate.country || '', candidate.state_province || '', candidate.city || '');
        populateEducationCountries('edit', candidate.masters_country || '', candidate.bachelors_country || '');

        // ── Visa
        setVal('edit_visa_immigration_status', candidate.visa_immigration_status || '');
        setVal('edit_work_auth_status',  candidate.work_auth_status || '');
        setVal('edit_visa_expiry_date',  candidate.visa_expiry_date || '');

        // ── Preferred city badge system
        initPrefCityBadges('edit', candidate.preferred_city || '');

        // Open to relocation radio
        var relYes = document.getElementById('edit_relocation_yes');
        var relNo  = document.getElementById('edit_relocation_no');
        if (relYes && relNo) {
            relYes.checked = candidate.open_to_relocation === true || candidate.open_to_relocation === 1 || candidate.open_to_relocation === '1';
            relNo.checked  = candidate.open_to_relocation === false || candidate.open_to_relocation === 0 || candidate.open_to_relocation === '0';
        }

        // ── Marketing
        setVal('edit_marketing_phone',         candidate.marketing_phone || '');
        setVal('edit_marketing_email',         candidate.marketing_email || '');
        setVal('edit_marketing_email_password', candidate.marketing_email_password || '');
        setVal('edit_marketing_linkedin_id',   candidate.marketing_linkedin_id || '');
        setVal('edit_marketing_linkedin_password', candidate.marketing_linkedin_password || '');
        setVal('edit_github_url',              candidate.github_url || '');
        setVal('edit_linkedin_url',            candidate.linkedin_url || '');
        setVal('edit_portfolio_url',           candidate.portfolio_url || '');
        setDocReplaceHint('edit_speedy_hint',  candidate.speedy_file_url, 'Speedy Apply JSON');

        // ── Education
        setVal('edit_masters_university', candidate.masters_university || '');
        setVal('edit_masters_program',    candidate.masters_program || '');
        setVal('edit_masters_start',      candidate.masters_start || '');
        setVal('edit_masters_end',        candidate.masters_end || '');
        setVal('edit_masters_country',    candidate.masters_country || '');
        setVal('edit_bachelors_university', candidate.bachelors_university || '');
        setVal('edit_bachelors_program',  candidate.bachelors_program || '');
        setVal('edit_bachelors_start',    candidate.bachelors_start || '');
        setVal('edit_bachelors_end',      candidate.bachelors_end || '');
        setVal('edit_bachelors_country',  candidate.bachelors_country || '');

        // ── Resume tab
        renderCandidateResumes('edit', candidate.resumes || []);
        resetResumeEntries('edit');
        setVal('edit_recruiter_notes',    candidate.recruiter_notes || '');

        // ── Portal
        setVal('edit_status',             candidate.status || 'active');
        setVal('edit_no_of_applications', candidate.no_of_applications || 0);
        // Sync hidden field for disabled no_of_applications (non-admin)
        var hiddenApps = document.getElementById('edit_no_of_applications_hidden');
        if (hiddenApps) hiddenApps.value = candidate.no_of_applications || 0;

        setAssignee('edit', candidate.recruiter_id || '', candidate.team_manager_id || '');
        setVal('edit_login_password', '');

        initPhoneInputs(document.getElementById('editCandidateModal'));
        setupCandidateValidator('edit');
        resetCandidatePasswordReveal();

        resetModalTabs('editCandidateModal');
        openModal('editCandidateModal');

        // Sync flatpickr dates
        var modal = document.getElementById('editCandidateModal');
        if (modal) {
            modal.querySelectorAll('input[type="date"]').forEach(function (el) {
                if (el._flatpickr) el._flatpickr.setDate(el.value, false);
            });
        }
    };

    function setDocReplaceHint(hintId, fileUrl, label) {
        var el = document.getElementById(hintId);
        if (!el) return;
        if (fileUrl) {
            el.innerHTML = '<a href="' + fileUrl + '" target="_blank" rel="noopener" class="doc-replace-link">' +
                '<i class="bi bi-file-earmark-fill"></i> View current ' + label + '</a>' +
                '<span class="doc-replace-warn">Uploading will replace the existing file.</span>';
        } else {
            el.innerHTML = '';
        }
    }

    window.editCandidateFromButton = function (button) {
        if (!button) return;
        var payload = button.getAttribute('data-candidate');
        if (!payload) return;
        try {
            editCandidate(decodeJsonPayload(payload));
        } catch (error) {
            console.error('Invalid candidate payload', error);
        }
    };

    /* ── Edit user modal ────────────────────────────────────────── */
    window.editUser = function (user) {
        var form = document.getElementById('editUserForm');
        if (!form) return;

        form.action = form.dataset.base + '/' + user.id;
        setVal('edit_user_name',   user.name   || '');
        setVal('edit_user_email',  user.email  || '');
        setVal('edit_user_status', user.status || 'active');
        setVal('edit_user_team_manager', user.team_manager_id || '');
        var emailInput = document.getElementById('edit_user_email');
        var emailGroup = document.getElementById('edit_user_email_group');
        var emailLockedGroup = document.getElementById('edit_user_email_locked_group');
        var emailLockedText = document.getElementById('edit_user_email_locked_text');
        var roleSelect = document.getElementById('edit_user_role');
        var roleSelectGroup = document.getElementById('edit_user_role_group');
        var roleLockedGroup = document.getElementById('edit_user_role_locked_group');
        var roleHiddenInput = document.getElementById('edit_user_role_hidden');
        var statusSelect = document.getElementById('edit_user_status');
        var statusGroup = document.getElementById('edit_user_status_group');
        var statusLockedGroup = document.getElementById('edit_user_status_locked_group');
        var statusLockedText = document.getElementById('edit_user_status_locked_text');
        var teamManagerGroup = document.getElementById('edit_team_manager_group');
        var teamManagerSelect = document.getElementById('edit_user_team_manager');
        var isLockedAdmin = !!user.is_admin;

        if (emailLockedText) emailLockedText.value = user.email || '';
        if (statusLockedText) statusLockedText.value = user.status ? (user.status.charAt(0).toUpperCase() + user.status.slice(1)) : 'Active';

        if (roleSelect) {
            if (isLockedAdmin) {
                if (emailGroup) smoothToggleElement(emailGroup, false);
                if (emailLockedGroup) smoothToggleElement(emailLockedGroup, true);
                if (emailInput) {
                    emailInput.disabled = true;
                    emailInput.required = false;
                    emailInput.name = '';
                }

                setVal('edit_user_role', 'manager');
                roleSelect.disabled = true;
                roleSelect.required = false;
                roleSelect.name = '';
                if (roleSelectGroup) smoothToggleElement(roleSelectGroup, false);
                if (roleLockedGroup) smoothToggleElement(roleLockedGroup, true);
                if (roleHiddenInput) {
                    roleHiddenInput.name = 'role';
                    roleHiddenInput.value = 'admin';
                }

                if (statusGroup) smoothToggleElement(statusGroup, false);
                if (statusLockedGroup) smoothToggleElement(statusLockedGroup, true);
                if (statusSelect) {
                    statusSelect.disabled = true;
                    statusSelect.required = false;
                    statusSelect.name = '';
                }

                if (teamManagerGroup) smoothToggleElement(teamManagerGroup, false);
                if (teamManagerSelect) {
                    teamManagerSelect.required = false;
                    teamManagerSelect.value = '';
                }
            } else {
                if (emailGroup) smoothToggleElement(emailGroup, true);
                if (emailLockedGroup) smoothToggleElement(emailLockedGroup, false);
                if (emailInput) {
                    emailInput.disabled = false;
                    emailInput.required = true;
                    emailInput.name = 'email';
                }

                roleSelect.disabled = false;
                roleSelect.required = true;
                roleSelect.name = 'role';
                setVal('edit_user_role', user.role || 'recruiter');
                if (roleSelectGroup) smoothToggleElement(roleSelectGroup, true);
                if (roleLockedGroup) smoothToggleElement(roleLockedGroup, false);
                if (roleHiddenInput) {
                    roleHiddenInput.name = '';
                    roleHiddenInput.value = '';
                }

                if (statusGroup) smoothToggleElement(statusGroup, true);
                if (statusLockedGroup) smoothToggleElement(statusLockedGroup, false);
                if (statusSelect) {
                    statusSelect.disabled = false;
                    statusSelect.required = true;
                    statusSelect.name = 'status';
                }

                roleSelect.dispatchEvent(new Event('change'));
            }
        }

        // ── Current password display (admin editing non-admin only) ──
        var currentPwGroup = document.getElementById('edit_current_pw_group');
        if (currentPwGroup) {
            smoothToggleElement(currentPwGroup, !isLockedAdmin);
        }
        var currentPwDisplay = document.getElementById('edit_current_pw_display');
        if (currentPwDisplay) {
            currentPwDisplay.value = user.current_password_plain || '';
            currentPwDisplay.placeholder = user.current_password_plain ? '' : 'Not available. Reset password to save it.';
        }

        // ── Clear password fields on open ────────────────────────────
        var newPwInput     = document.getElementById('edit_user_new_password');
        var confirmPwInput = document.getElementById('edit_user_confirm_password');
        if (newPwInput)     { newPwInput.value = '';     newPwInput.type = 'password'; }
        if (confirmPwInput) { confirmPwInput.value = ''; confirmPwInput.type = 'password'; }

        // ── Reset current-password eye icon ──────────────────────────
        if (currentPwDisplay) {
            currentPwDisplay.type = 'password';
            var eyeBtn = currentPwDisplay.closest('.input-with-icon') &&
                         currentPwDisplay.closest('.input-with-icon').querySelector('.password-toggle');
            if (eyeBtn) eyeBtn.innerHTML = '<i class="bi bi-eye"></i>';
        }

        // ── Reset submit button ──────────────────────────────────────
        var $submitBtn = $('#editUserModal .modal-footer .btn-primary');
        $submitBtn.prop('disabled', false).html('<i class="bi bi-check-lg"></i> Save Changes');

        openModal('editUserModal');

        // ── Wire up jQuery validation ────────────────────────────────
        setupUserEditValidator();
    };

    window.editUserFromButton = function (button) {
        if (!button) return;
        var payload = button.getAttribute('data-user');
        if (!payload) return;
        try {
            editUser(decodeJsonPayload(payload));
        } catch (error) {
            console.error('Invalid user payload', error);
        }
    };

    /* ── Auth tabs (login page) ─────────────────────────────────── */
    var authCard = document.getElementById('authCard');
    if (authCard) {
        var authTabs = authCard.querySelectorAll('[data-auth-tab]');
        var authForms = authCard.querySelectorAll('[data-auth-form]');
        var initialTab = authCard.getAttribute('data-initial-tab') || 'signin';

        var setAuthTab = function (tabName) {
            authTabs.forEach(function (tab) {
                tab.classList.toggle('active', tab.getAttribute('data-auth-tab') === tabName);
            });
            authForms.forEach(function (form) {
                form.classList.toggle('active', form.getAttribute('data-auth-form') === tabName);
            });
        };

        authTabs.forEach(function (tab) {
            tab.addEventListener('click', function () {
                if (tab.disabled) return;
                setAuthTab(tab.getAttribute('data-auth-tab'));
            });
        });

        setAuthTab(initialTab);
    }

    /* ── Flash toast auto-dismiss ───────────────────────────────── */
    ['flashToast', 'errorToast'].forEach(function (toastId) {
        var toastNode = document.getElementById(toastId);
        if (!toastNode) return;
        setTimeout(function () {
            toastNode.style.opacity = '0';
            setTimeout(function () {
                if (toastNode.parentNode) toastNode.parentNode.removeChild(toastNode);
            }, 400);
        }, 4000);
    });

    /* ── Programmatic toast (success / error) ───────────────────── */
    window.showToast = function (message, type) {
        var container = document.createElement('div');
        container.className = 'toast-container';
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + (type || 'success');
        var icon = type === 'error'
            ? '<i class="bi bi-exclamation-circle-fill"></i>'
            : '<i class="bi bi-check-circle-fill"></i>';
        toast.innerHTML = icon + '<span>' + message + '</span>';
        container.appendChild(toast);
        document.body.appendChild(container);
        setTimeout(function () {
            container.style.opacity = '0';
            container.style.transition = 'opacity 0.4s';
            setTimeout(function () { container.remove(); }, 400);
        }, 3500);
    };

    /* ── jQuery Validate custom methods (registered once) ──────── */
    function registerJQVMethods() {
        if (typeof $ === 'undefined' || typeof $.validator === 'undefined') return;
        if ($.validator.methods.urlAllowed) return; // already registered

        $.validator.addMethod('urlAllowed', function (value, element) {
            return this.optional(element) || /^(https?:\/\/|www\.)\S{3,}/.test(value.trim());
        }, 'URL must start with https://, http://, or www.');

        $.validator.addMethod('phoneDigits', function (value, element) {
            if (this.optional(element)) return true;
            var d = value.replace(/[\s+\-(). x]/g, '');
            return d.length >= 7 && d.length <= 15;
        }, 'Phone number must have 7-15 digits.');

        $.validator.addMethod('noSpacesOnly', function (value, element) {
            return this.optional(element) || value.trim().length > 0;
        }, 'Cannot be blank spaces only.');

        $.validator.addMethod('minLenIfFilled', function (value, element, param) {
            return this.optional(element) || value.trim().length >= param;
        }, 'Must be at least {0} characters.');

        $.validator.addMethod('dateGte', function (value, element, param) {
            if (this.optional(element)) return true;
            var startVal = $(param).val();
            if (!startVal) return true;
            return new Date(value) >= new Date(startVal);
        }, 'End date must be on or after start date.');

        $.validator.addMethod('equalToIfFilled', function (value, element, param) {
            var refVal = $(param).val();
            if (!refVal) return true; // new password not filled — skip confirm validation
            return value === refVal;
        }, 'Passwords do not match.');
    }
    registerJQVMethods(); // Call immediately on load

    function setupCandidateValidator(prefix) {
        if (typeof $ === 'undefined' || typeof $.validator === 'undefined') return;
        var modalId = prefix === 'add' ? 'addCandidateModal' : 'editCandidateModal';
        var $form = $('#' + modalId + ' form');
        if (!$form.length) return;
        var isEdit = (prefix === 'edit');

        // Re-register methods in case jQuery loaded late
        registerJQVMethods();

        // Destroy previous validator on this form
        if ($form.data('validator')) { $form.data('validator').destroy(); }

        $form.validate({
            // Skip truly-hidden inputs but NOT fields inside tab panels (they're display:none but must validate)
            ignore: function (i, el) {
                var $el = $(el);
                // Always skip type="hidden" (CSRF, badge values, CC codes, method spoofing)
                if ($el.attr('type') === 'hidden') return true;
                // Skip helper inputs inside PCC / badge / dropzone wrappers
                if ($el.hasClass('dz-input-hidden') || $el.hasClass('subdomain-text-input')) return true;
                // Skip resume sub-fields (not part of candidate base validation)
                if (/^resumes\[/.test($el.attr('name') || '')) return true;
                // Do NOT skip fields in tab panels even if the panel is hidden
                return false;
            },
            onfocusout: function (element) {
                this.element(element); // validate on blur
            },
            rules: {
                first_name: { required: true },
                last_name:  { required: true },
                email_id:   { required: true, email: true },
                phone_number: { phoneDigits: true },
                marketing_phone: { phoneDigits: true },
                marketing_email: { email: true },
                marketing_email_password: { noSpacesOnly: true },
                marketing_linkedin_password: { noSpacesOnly: true },
                github_url:    { urlAllowed: true },
                linkedin_url:  { urlAllowed: true },
                portfolio_url: { urlAllowed: true },
                masters_end: { dateGte: '#' + prefix + '_masters_start' },
                bachelors_end: { dateGte: '#' + prefix + '_bachelors_start' },
                status: { required: true },
                login_password: isEdit
                    ? { noSpacesOnly: true, minLenIfFilled: 8 }
                    : { required: true, noSpacesOnly: true, minlength: 8 }
            },
            messages: {
                first_name: { required: 'First name is required.' },
                last_name:  { required: 'Last name is required.' },
                email_id:   { required: 'Email address is required.', email: 'Please enter a valid email address.' },
                phone_number: { phoneDigits: 'Phone number must have 7-15 digits.' },
                marketing_phone: { phoneDigits: 'Phone number must have 7-15 digits.' },
                marketing_email: { email: 'Please enter a valid marketing email address.' },
                marketing_email_password: { noSpacesOnly: 'Password cannot be blank spaces only.' },
                marketing_linkedin_password: { noSpacesOnly: 'Password cannot be blank spaces only.' },
                github_url:    { urlAllowed: 'URL must start with https://, http://, or www.' },
                linkedin_url:  { urlAllowed: 'URL must start with https://, http://, or www.' },
                portfolio_url: { urlAllowed: 'URL must start with https://, http://, or www.' },
                masters_end:   { dateGte: "End date must be on or after the master's start date." },
                bachelors_end: { dateGte: "End date must be on or after the bachelor's start date." },
                status: { required: 'Status is required.' },
                login_password: {
                    required: 'Portal login password is required.',
                    noSpacesOnly: 'Password cannot be blank spaces only.',
                    minLenIfFilled: 'Password must be at least 8 characters.',
                    minlength: 'Password must be at least 8 characters.'
                }
            },
            errorPlacement: function (error, element) {
                var id = element.attr('id') || '';
                var $span = $('#' + id + '_err');
                if ($span.length) { $span.text(error.text()); } // use our existing spans
                // Don't insert jQuery's label into DOM
            },
            highlight: function (element) { $(element).addClass('is-invalid'); },
            unhighlight: function (element) { $(element).removeClass('is-invalid'); },
            invalidHandler: function (event, validator) {
                if (!validator.errorList || !validator.errorList.length) return;
                var firstEl = validator.errorList[0].element;
                var $panel  = $(firstEl).closest('.modal-tab-panel');
                if ($panel.length) {
                    var $tabs = $panel.closest('.modal-body').prev('.modal-tabs');
                    if ($tabs.length) window.switchModalTab($tabs.attr('id'), $panel.attr('id'));
                }
                window.showToast('Please fix the highlighted errors before submitting.', 'error');
                setTimeout(function () { firstEl.focus(); }, 80);
            },
            submitHandler: function (form) {
                // Auto-flush pending badge text
                var subInput  = document.getElementById(prefix + '_subdomain_input');
                var subHidden = document.getElementById(prefix + '_sub_domain');
                if (subInput && subHidden && subInput.value.trim()) {
                    var sv = subInput.value.trim().replace(/,/g, '');
                    var svArr = subHidden.value ? subHidden.value.split(',').map(function(v){return v.trim();}).filter(Boolean) : [];
                    if (!svArr.includes(sv)) svArr.push(sv);
                    subHidden.value = svArr.join(','); subInput.value = '';
                }
                var cityInput  = document.getElementById(prefix + '_prefcity_input');
                var cityHidden = document.getElementById(prefix + '_preferred_city');
                if (cityInput && cityHidden && cityInput.value.trim()) {
                    var cv = cityInput.value.trim().replace(/,/g, '');
                    var cvArr = cityHidden.value ? cityHidden.value.split(',').map(function(v){return v.trim();}).filter(Boolean) : [];
                    if (!cvArr.includes(cv)) cvArr.push(cv);
                    cityHidden.value = cvArr.join(','); cityInput.value = '';
                }
                normalizeCandidatePhones(prefix);
                // Disable save button to prevent double-click
                var $btn = $(form).closest('.modal-overlay').find('.modal-footer .btn-primary');
                $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Saving…');
                form.submit(); // Native submit (no event, bypasses jQuery Validate listener)
            }
        });
    }

    /* ── Add User form validation (jQuery Validate) ─────────────── */
    function setupUserAddValidator() {
        if (typeof $ === 'undefined' || typeof $.validator === 'undefined') return;
        registerJQVMethods();

        var $form = $('#addUserForm');
        if (!$form.length) return;

        if ($form.data('validator')) { $form.data('validator').destroy(); }

        $form.find('.sp-field-error').text('');
        $form.find('.form-control').removeClass('is-invalid');

        $form.validate({
            ignore: function (i, el) {
                var $el = $(el);
                return $el.attr('type') === 'hidden' || $el.prop('disabled');
            },
            onfocusout: function (element) {
                this.element(element);
            },
            rules: {
                name: { required: true, noSpacesOnly: true, maxlength: 255 },
                email: { required: true, email: true, maxlength: 255 },
                role: { required: true },
                status: { required: true },
                team_manager_id: {
                    required: function () {
                        var roleSelect = document.getElementById('add_user_role');
                        var group = document.getElementById('add_team_manager_group');
                        if (group && group.style.display === 'none') return false;
                        return !roleSelect || roleSelect.value === 'recruiter';
                    }
                },
                password: { required: true, noSpacesOnly: true, minlength: 8 },
                password_confirmation: {
                    required: true,
                    equalTo: '#add_user_password'
                }
            },
            messages: {
                name: {
                    required: 'Full name is required.',
                    noSpacesOnly: 'Name cannot be blank spaces only.',
                    maxlength: 'Name cannot be more than 255 characters.'
                },
                email: {
                    required: 'Email address is required.',
                    email: 'Please enter a valid email address.',
                    maxlength: 'Email cannot be more than 255 characters.'
                },
                role: { required: 'Role is required.' },
                status: { required: 'Status is required.' },
                team_manager_id: { required: 'Please assign a team manager for this recruiter.' },
                password: {
                    required: 'Password is required.',
                    noSpacesOnly: 'Password cannot be blank spaces only.',
                    minlength: 'Password must be at least 8 characters.'
                },
                password_confirmation: {
                    required: 'Please confirm the password.',
                    equalTo: 'Passwords do not match.'
                }
            },
            errorPlacement: function (error, element) {
                var id = element.attr('id') || '';
                var $span = $('#' + id + '_err');
                if ($span.length) { $span.text(error.text()); }
            },
            success: function (label, element) {
                var id = $(element).attr('id') || '';
                var $span = $('#' + id + '_err');
                if ($span.length) { $span.text(''); }
            },
            highlight: function (element) { $(element).addClass('is-invalid'); },
            unhighlight: function (element) { $(element).removeClass('is-invalid'); },
            invalidHandler: function (event, validator) {
                if (!validator.errorList || !validator.errorList.length) return;
                window.showToast('Please fix the highlighted errors before submitting.', 'error');
                setTimeout(function () { validator.errorList[0].element.focus(); }, 80);
            },
            submitHandler: function (form) {
                var $btn = $('#add_user_submit');
                $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Creating...');
                form.submit();
            }
        });
    }
    window.setupUserAddValidator = setupUserAddValidator;

    if (document.getElementById('addUserForm')) {
        setupUserAddValidator();
    }

    /* ── Edit User form validation (jQuery Validate) ────────────── */
    function setupUserEditValidator() {
        if (typeof $ === 'undefined' || typeof $.validator === 'undefined') return;
        registerJQVMethods();

        var $form = $('#editUserForm');
        if (!$form.length) return;

        // Destroy previous validator instance
        if ($form.data('validator')) { $form.data('validator').destroy(); }

        // Clear previous validation state
        $form.find('.sp-field-error').text('');
        $form.find('.form-control').removeClass('is-invalid');

        $form.validate({
            ignore: function (i, el) {
                var $el = $(el);
                if ($el.attr('type') === 'hidden') return true;
                if ($el.prop('disabled')) return true;
                // Always skip the current-password display (read-only visual field)
                if ($el.attr('id') === 'edit_current_pw_display') return true;
                return false;
            },
            onfocusout: function (element) {
                this.element(element);
            },
            rules: {
                name: { required: true, noSpacesOnly: true },
                email: { required: true, email: true },
                role: { required: true },
                status: { required: true },
                team_manager_id: {
                    required: function () {
                        var roleSelect = document.getElementById('edit_user_role');
                        if (!roleSelect || roleSelect.disabled) return false;
                        var grp = document.getElementById('edit_team_manager_group');
                        if (grp && grp.style.display === 'none') return false;
                        return roleSelect.value === 'recruiter';
                    }
                },
                password: { noSpacesOnly: true, minLenIfFilled: 8 },
                password_confirmation: {
                    required: function () {
                        var pwInput = document.getElementById('edit_user_new_password');
                        return pwInput && pwInput.value.trim().length > 0;
                    },
                    equalToIfFilled: '#edit_user_new_password'
                }
            },
            messages: {
                name: {
                    required: 'Full name is required.',
                    noSpacesOnly: 'Name cannot be blank spaces only.'
                },
                email: {
                    required: 'Email address is required.',
                    email: 'Please enter a valid email address.'
                },
                role: { required: 'Role is required.' },
                status: { required: 'Status is required.' },
                team_manager_id: { required: 'Please assign a team manager for this recruiter.' },
                password: {
                    noSpacesOnly: 'Password cannot be blank spaces only.',
                    minLenIfFilled: 'New password must be at least 8 characters.'
                },
                password_confirmation: {
                    required: 'Please confirm the new password.',
                    equalToIfFilled: 'Passwords do not match.'
                }
            },
            errorPlacement: function (error, element) {
                var id = element.attr('id') || '';
                var $span = $('#' + id + '_err');
                if ($span.length) { $span.text(error.text()); }
            },
            success: function (label, element) {
                var id = $(element).attr('id') || '';
                var $span = $('#' + id + '_err');
                if ($span.length) { $span.text(''); }
            },
            highlight: function (element) { $(element).addClass('is-invalid'); },
            unhighlight: function (element) { $(element).removeClass('is-invalid'); },
            invalidHandler: function (event, validator) {
                if (!validator.errorList || !validator.errorList.length) return;
                window.showToast('Please fix the highlighted errors before submitting.', 'error');
                setTimeout(function () { validator.errorList[0].element.focus(); }, 80);
            },
            submitHandler: function (form) {
                var $btn = $('#editUserModal .modal-footer .btn-primary');
                $btn.prop('disabled', true).html('<i class="bi bi-hourglass-split"></i> Saving…');
                form.submit();
            }
        });
    }

    /* ── Multi-tab candidate form validation (jQuery Validate) ─── */
    window.validateCandidateForm = function (prefix) {
        var modalId = prefix === 'add' ? 'addCandidateModal' : 'editCandidateModal';
        var $modal  = $(document.getElementById(modalId));
        if (!$modal.length) return;
        var $form = $modal.find('form');
        if (!$form.length) return;

        // Clear previous inline error spans (jQuery Validate handles classes)
        $modal.find('.sp-field-error').text('');

        // Ensure validator is initialized
        if (!$form.data('validator')) { setupCandidateValidator(prefix); }

        // Trigger jQuery Validate — invalidHandler fires on fail, submitHandler on success
        $form.submit();
    };

    /* ── Preferred City badge system ────────────────────────────── */
    window.handlePrefCityKey = function (e, prefix) {
        if (e.key !== 'Enter' && e.key !== ',') return;
        e.preventDefault();
        var input  = document.getElementById(prefix + '_prefcity_input');
        var hidden = document.getElementById(prefix + '_preferred_city');
        if (!input || !hidden) return;
        var val = input.value.trim().replace(/,/g, '');
        if (!val) return;
        var values = hidden.value ? hidden.value.split(',').map(function (v) { return v.trim(); }).filter(Boolean) : [];
        if (!values.includes(val)) { values.push(val); hidden.value = values.join(','); }
        input.value = '';
        renderPrefCityBadges(prefix);
    };

    window.removePrefCityBadge = function (prefix, idx) {
        var hidden = document.getElementById(prefix + '_preferred_city');
        if (!hidden) return;
        var values = hidden.value ? hidden.value.split(',').map(function (v) { return v.trim(); }).filter(Boolean) : [];
        values.splice(idx, 1);
        hidden.value = values.join(',');
        renderPrefCityBadges(prefix);
    };

    function renderPrefCityBadges(prefix) {
        var container = document.getElementById(prefix + '_prefcity_badges');
        var hidden    = document.getElementById(prefix + '_preferred_city');
        if (!container || !hidden) return;
        container.querySelectorAll('.subdomain-badge').forEach(function (el) { el.remove(); });
        var input  = document.getElementById(prefix + '_prefcity_input');
        var values = hidden.value ? hidden.value.split(',').map(function (v) { return v.trim(); }).filter(Boolean) : [];
        values.forEach(function (val, i) {
            var span = document.createElement('span');
            span.className = 'subdomain-badge';
            span.innerHTML = escHtml(val) + '<button type="button" class="subdomain-badge-x" onclick="removePrefCityBadge(\'' + prefix + '\',' + i + ')">&times;</button>';
            container.insertBefore(span, input);
        });
    }

    function initPrefCityBadges(prefix, value) {
        var hidden = document.getElementById(prefix + '_preferred_city');
        if (hidden) hidden.value = value || '';
        renderPrefCityBadges(prefix);
    }

    /* ── Unified Assignee Dropdown ───────────────────────────────── */
    window.pickAssignee = function (prefix, type, value, label) {
        var recHid  = document.getElementById(prefix + '_recruiter_id');
        var mgrHid  = document.getElementById(prefix + '_team_manager_id');
        var labelEl = document.getElementById(prefix + '_assignee_label');
        var wrap    = document.getElementById(prefix + '_assignee_wrap');
        if (recHid) recHid.value = '';
        if (mgrHid) mgrHid.value = '';
        if (type === 'recruiter' && value) {
            if (recHid) recHid.value = value;
            if (labelEl) labelEl.innerHTML = escHtml(label) + ' <span class="ss-assignee-role">(Recruiter)</span>';
        } else if (type === 'manager' && value) {
            if (mgrHid) mgrHid.value = value;
            if (labelEl) labelEl.innerHTML = escHtml(label) + ' <span class="ss-assignee-role">(Manager)</span>';
        } else {
            if (labelEl) labelEl.textContent = '— Unassigned —';
        }
        if (wrap) wrap.classList.remove('ss-open');
    };

    function setAssignee(prefix, recruiterId, managerId) {
        var recHid  = document.getElementById(prefix + '_recruiter_id');
        var mgrHid  = document.getElementById(prefix + '_team_manager_id');
        var labelEl = document.getElementById(prefix + '_assignee_label');
        if (recHid) recHid.value = recruiterId || '';
        if (mgrHid) mgrHid.value = managerId   || '';
        if (recruiterId && mgrHid) mgrHid.value = '';
        if (!recruiterId && managerId && recHid) recHid.value = '';
        if (!labelEl) return; // recruiter role — no assignee UI
        if (recruiterId) {
            var wrap = document.getElementById(prefix + '_assignee_wrap');
            var opt  = wrap ? wrap.querySelector('.ss-option[data-value="rec_' + recruiterId + '"]') : null;
            var name = opt ? (opt.dataset.name || opt.textContent.trim()) : String(recruiterId);
            labelEl.innerHTML = escHtml(name) + ' <span class="ss-assignee-role">(Recruiter)</span>';
        } else if (managerId) {
            var wrap = document.getElementById(prefix + '_assignee_wrap');
            var opt  = wrap ? wrap.querySelector('.ss-option[data-value="mgr_' + managerId + '"]') : null;
            var name = opt ? (opt.dataset.name || opt.textContent.trim()) : String(managerId);
            labelEl.innerHTML = escHtml(name) + ' <span class="ss-assignee-role">(Manager)</span>';
        } else {
            labelEl.textContent = '— Unassigned —';
        }
    }

    /* Candidate phone inputs - intl-tel-input */
    var phoneInputInstances = new WeakMap();

    function updatePhoneCountryCode(input) {
        if (!input) return;
        var targetId = input.getAttribute('data-cc-target');
        var target = targetId ? document.getElementById(targetId) : null;
        var iti = phoneInputInstances.get(input);
        if (!target || !iti) return;
        var countryData = iti.getSelectedCountryData ? iti.getSelectedCountryData() : null;
        target.value = countryData && countryData.dialCode ? '+' + countryData.dialCode : '';
    }

    function selectedDialCode(input) {
        var iti = phoneInputInstances.get(input);
        if (!iti || !iti.getSelectedCountryData) return '';
        var data = iti.getSelectedCountryData();
        return data && data.dialCode ? data.dialCode : '';
    }

    function stripVisibleDialCode(input) {
        if (!input || !input.value) return;
        var dialCode = selectedDialCode(input);
        var digits = input.value.replace(/\D/g, '');
        if (dialCode && digits.indexOf(dialCode) === 0) {
            digits = digits.slice(dialCode.length);
        }
        input.value = digits;
    }

    function initPhoneInputs(scope) {
        var root = scope || document;
        if (typeof window.intlTelInput === 'undefined') return;

        root.querySelectorAll('.js-phone-input').forEach(function (input) {
            if (!phoneInputInstances.has(input)) {
                var iti = window.intlTelInput(input, {
                    initialCountry: 'us',
                    separateDialCode: true,
                    nationalMode: true,
                    formatOnDisplay: true,
                    autoPlaceholder: 'polite'
                });
                phoneInputInstances.set(input, iti);
                input.addEventListener('countrychange', function () {
                    updatePhoneCountryCode(input);
                    stripVisibleDialCode(input);
                });
                input.addEventListener('blur', function () { normalizePhoneInput(input); });
            }

            var itiInstance = phoneInputInstances.get(input);
            if (itiInstance && input.value) {
                try { itiInstance.setNumber(input.value); } catch (_) {}
                stripVisibleDialCode(input);
            }
            updatePhoneCountryCode(input);
        });
    }

    function normalizePhoneInput(input) {
        if (!input || !input.value.trim()) return;
        var raw = input.value.trim();
        var digits = raw.replace(/\D/g, '');
        if (!digits) return;
        var dialCode = selectedDialCode(input);
        if (dialCode && digits.indexOf(dialCode) === 0) {
            digits = digits.slice(dialCode.length);
        }
        input.value = digits;
    }

    function normalizeCandidatePhones(prefix) {
        ['phone_number', 'marketing_phone'].forEach(function (field) {
            var input = document.getElementById(prefix + '_' + field);
            normalizePhoneInput(input);
            updatePhoneCountryCode(input);
            if (!input || !input.value.trim()) return;
            var dialCode = selectedDialCode(input);
            var digits = input.value.replace(/\D/g, '');
            if (dialCode && digits && digits.indexOf(dialCode) !== 0) {
                input.value = '+' + dialCode + digits;
            }
        });
    }

    /* Candidate searchable selects - Select2 */
    function initCandidateSelects(scope) {
        if (typeof $ === 'undefined' || !$.fn || !$.fn.select2) return;
        var root = scope || document;
        $(root).find('.js-geo-select, .js-education-country').each(function () {
            var $select = $(this);
            if ($select.data('select2')) return;
            var $fieldParent = $select.closest('.form-group');
            $fieldParent.addClass('select2-field-parent');
            $select.select2({
                width: '100%',
                dropdownParent: $(document.body),
                placeholder: $select.data('placeholder') || 'Select',
                allowClear: true,
                selectionCssClass: 'candidate-select2-selection',
                dropdownCssClass: 'candidate-select2-dropdown candidate-select2-floating',
                language: {
                    noResults: function () {
                        return $select.hasClass('is-loading') ? 'Loading...' : 'No results found';
                    }
                }
            });

            $select.on('select2:opening', function () {
                var field = this.closest('.form-group');
                if (!field) return;
                var rendered = $(this).next('.select2-container')[0];
                if (rendered) {
                    var rect = rendered.getBoundingClientRect();
                    document.documentElement.style.setProperty('--candidate-select2-width', rect.width + 'px');
                }
            });

            $select.on('select2:open', function () {
                var selectEl = this;
                var modalBody = selectEl.closest('.modal-body');
                var modalScrollTop = modalBody ? modalBody.scrollTop : 0;

                function positionDropdown() {
                    var rendered = $(selectEl).next('.select2-container')[0];
                    var dropdown = document.querySelector('body > .select2-container--open');
                    if (!rendered || !dropdown) {
                        if (modalBody) modalBody.scrollTop = modalScrollTop;
                        return;
                    }

                    var rect = rendered.getBoundingClientRect();
                    document.documentElement.style.setProperty('--candidate-select2-width', rect.width + 'px');
                    dropdown.style.left = (rect.left + window.scrollX) + 'px';
                    dropdown.style.top = (rect.bottom + window.scrollY + 4) + 'px';
                    dropdown.style.setProperty('width', rect.width + 'px', 'important');
                    dropdown.style.zIndex = '10050';
                    if (modalBody) modalBody.scrollTop = modalScrollTop;

                    dropdown.querySelectorAll('.select2-dropdown--above').forEach(function (dropdown) {
                        dropdown.classList.remove('select2-dropdown--above');
                        dropdown.classList.add('select2-dropdown--below');
                    });
                }

                positionDropdown();
                requestAnimationFrame(positionDropdown);
            });
        });
    }

    function refreshSelect2(selectId) {
        if (typeof $ === 'undefined') return;
        var el = document.getElementById(selectId);
        if (el && $(el).data('select2')) $(el).trigger('change.select2');
    }

    /* Country / State / City data */
    var _geoCache = { countries: null, states: {}, cities: {} };
    var GEO_BASE = 'https://countriesnow.space/api/v0.1';

    function setGeoLoading(selectId, isLoading, placeholder) {
        var sel = document.getElementById(selectId);
        if (!sel) return;
        sel.classList.toggle('is-loading', !!isLoading);
        sel.disabled = !!isLoading;
        if (placeholder) geoPopulate(selectId, [], placeholder, '');
        refreshSelect2(selectId);
    }

    function geoPopulate(selectId, items, placeholder, selectedValue) {
        var sel = document.getElementById(selectId);
        if (!sel) return;
        var keep = selectedValue !== undefined ? selectedValue : sel.value;
        sel.innerHTML = '<option value="">' + escHtml(placeholder) + '</option>';
        items.forEach(function (item) {
            var option = document.createElement('option');
            option.value = item.v;
            option.textContent = item.l;
            sel.appendChild(option);
        });
        if (keep) ensureSelectOption(sel, keep, keep);
        if (keep) sel.value = keep;
        refreshSelect2(selectId);
    }

    function ensureSelectOption(sel, value, label) {
        if (!sel || !value) return;
        var exists = Array.prototype.some.call(sel.options, function (option) { return option.value === value; });
        if (!exists) {
            var opt = document.createElement('option');
            opt.value = value;
            opt.textContent = label || value;
            sel.appendChild(opt);
        }
    }

    function loadCountries() {
        if (_geoCache.countries) return Promise.resolve(_geoCache.countries);
        return fetch(GEO_BASE + '/countries/positions')
            .then(function (response) { return response.json(); })
            .then(function (payload) {
                var list = (payload.data || []).map(function (country) {
                    return { v: country.name, l: country.name };
                }).sort(function (a, b) { return a.l.localeCompare(b.l); });
                _geoCache.countries = list;
                return list;
            });
    }

    function loadStates(country) {
        if (!country) return Promise.resolve([]);
        if (_geoCache.states[country]) return Promise.resolve(_geoCache.states[country]);
        return fetch(GEO_BASE + '/countries/states', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ country: country })
        }).then(function (response) { return response.json(); })
          .then(function (payload) {
              var items = ((payload.data && payload.data.states) || []).map(function (state) {
                  return { v: state.name, l: state.name };
              }).sort(function (a, b) { return a.l.localeCompare(b.l); });
              _geoCache.states[country] = items;
              return items;
          });
    }

    function loadCities(country, state) {
        if (!country || !state) return Promise.resolve([]);
        var cacheKey = country + '|' + state;
        if (_geoCache.cities[cacheKey]) return Promise.resolve(_geoCache.cities[cacheKey]);
        return fetch(GEO_BASE + '/countries/state/cities', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ country: country, state: state })
        }).then(function (response) { return response.json(); })
          .then(function (payload) {
              var items = (payload.data || []).map(function (city) { return { v: city, l: city }; });
              _geoCache.cities[cacheKey] = items;
              return items;
          });
    }

    function populateEducationCountries(prefix, selectedMasters, selectedBachelors) {
        loadCountries().then(function (countries) {
            geoPopulate(prefix + '_masters_country', countries, 'Select Country', selectedMasters || undefined);
            geoPopulate(prefix + '_bachelors_country', countries, 'Select Country', selectedBachelors || undefined);
        }).catch(function () {});
    }

    function loadCandidateCountries(prefix) {
        var modal = document.getElementById(prefix === 'add' ? 'addCandidateModal' : 'editCandidateModal');
        initCandidateSelects(modal || document);
        loadCountries().then(function (countries) {
            geoPopulate(prefix + '_country', countries, 'Select Country');
            populateEducationCountries(prefix);
        }).catch(function () {
            geoPopulate(prefix + '_country', [], 'Countries unavailable');
            populateEducationCountries(prefix);
        });
    }

    window.onCandidateCountryChange = function (prefix) {
        var countrySel = document.getElementById(prefix + '_country');
        var country = countrySel ? countrySel.value : '';
        setGeoLoading(prefix + '_state_province', !!country, country ? 'Loading states...' : 'Select Country First');
        setGeoLoading(prefix + '_city', false, 'Select State First');
        if (!country) return;

        loadStates(country).then(function (states) {
            setGeoLoading(prefix + '_state_province', false);
            geoPopulate(prefix + '_state_province', states, states.length ? 'Select State' : 'No states found');
            geoPopulate(prefix + '_city', [], 'Select State First', '');
        }).catch(function () {
            setGeoLoading(prefix + '_state_province', false);
            geoPopulate(prefix + '_state_province', [], 'States unavailable');
        });
    };

    window.onCandidateStateChange = function (prefix) {
        var countrySel = document.getElementById(prefix + '_country');
        var stateSel = document.getElementById(prefix + '_state_province');
        var country = countrySel ? countrySel.value : '';
        var state = stateSel ? stateSel.value : '';
        setGeoLoading(prefix + '_city', !!state, state ? 'Loading cities...' : 'Select State First');
        if (!country || !state) return;

        loadCities(country, state).then(function (cities) {
            setGeoLoading(prefix + '_city', false);
            geoPopulate(prefix + '_city', cities, cities.length ? 'Select City' : 'No cities found');
        }).catch(function () {
            setGeoLoading(prefix + '_city', false);
            geoPopulate(prefix + '_city', [], 'Cities unavailable');
        });
    };

    function setCandidateGeo(prefix, country, state, city) {
        var modal = document.getElementById(prefix === 'add' ? 'addCandidateModal' : 'editCandidateModal');
        initCandidateSelects(modal || document);
        loadCountries().then(function (countries) {
            geoPopulate(prefix + '_country', countries, 'Select Country', country || '');
            if (!country) return Promise.resolve([]);
            return loadStates(country);
        }).then(function (states) {
            if (!country) return;
            geoPopulate(prefix + '_state_province', states || [], 'Select State', state || '');
            if (!state) return Promise.resolve([]);
            return loadCities(country, state);
        }).then(function (cities) {
            if (country && state) {
                geoPopulate(prefix + '_city', cities || [], 'Select City', city || '');
            }
        }).catch(function () {
            if (country) ensureSelectOption(document.getElementById(prefix + '_country'), country, country);
            if (state) ensureSelectOption(document.getElementById(prefix + '_state_province'), state, state);
            if (city) ensureSelectOption(document.getElementById(prefix + '_city'), city, city);
            refreshSelect2(prefix + '_country');
            refreshSelect2(prefix + '_state_province');
            refreshSelect2(prefix + '_city');
        });
    }

    window._initCandidateSelects = initCandidateSelects;
    window._initPhoneInputs = initPhoneInputs;
    window._setCandidateGeo = setCandidateGeo;

})();
