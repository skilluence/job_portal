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
    var sidebar = document.querySelector('.sidebar');
    var sidebarToggle = document.querySelector('.sidebar-toggle');

    if (sidebar && sidebarToggle) {
        if (localStorage.getItem('sidebarLocked') === 'true') {
            sidebar.classList.add('locked');
        }

        sidebarToggle.addEventListener('click', function (event) {
            event.stopPropagation();
            sidebar.classList.toggle('locked');
            localStorage.setItem('sidebarLocked', sidebar.classList.contains('locked'));
        });
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

    /* ── Global form submit-once protection ─────────────────────── */
    // Prevents double-submissions (e.g. Import clicking multiple times)
    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented) return;
        var form = event.target;
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
        }).catch(function () {
            // Revert on error
            badge.className = 'badge ' + STATUS_BADGES[oldStatus];
            badge.textContent = oldStatus.charAt(0).toUpperCase() + oldStatus.slice(1);
            cell.dataset.currentStatus = oldStatus;
            cell.style.opacity = '';
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
        bindThemeToggles();
    };

    window.closeModal = function (id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.remove('open');
        document.body.style.overflow = '';

        if (id === 'editCandidateModal') resetCandidatePasswordReveal();
    };

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
            resetResumeEntries('add');
            setSS('add', 'recruiter', '');
            setSS('add', 'manager',   '');
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
            if (!opt.dataset.value) { opt.style.display = ''; return; }
            opt.style.display = (needle && opt.textContent.trim().toLowerCase().indexOf(needle) === -1) ? 'none' : '';
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

        // ── Address
        setVal('edit_street_address',    candidate.street_address || '');
        setVal('edit_apartment_unit',    candidate.apartment_unit || '');
        setVal('edit_city',              candidate.city || '');
        setVal('edit_state_province',    candidate.state_province || '');
        setVal('edit_zip_code',          candidate.zip_code || '');
        setVal('edit_country',           candidate.country || 'United States');

        // ── Visa
        setVal('edit_visa_immigration_status', candidate.visa_immigration_status || '');
        setVal('edit_work_auth_status',  candidate.work_auth_status || '');
        setVal('edit_preferred_city',    candidate.preferred_city || '');
        setVal('edit_visa_expiry_date',  candidate.visa_expiry_date || '');

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
        setVal('edit_marketing_email_password','');
        setVal('edit_marketing_linkedin_id',   candidate.marketing_linkedin_id || '');
        setVal('edit_marketing_linkedin_password', '');
        setVal('edit_github_url',              candidate.github_url || '');
        setVal('edit_linkedin_url',            candidate.linkedin_url || '');
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

        setSS('edit', 'recruiter', candidate.recruiter_id || '');
        setSS('edit', 'manager',   candidate.team_manager_id || '');
        // Manager view: apply mutual exclusion on load —
        // if a recruiter is already assigned, clear the team-manager display so only one shows
        if (window.__isManager && candidate.recruiter_id) {
            var mgrLabelEl = document.getElementById('edit_manager_label');
            if (mgrLabelEl) mgrLabelEl.textContent = '— None —';
        }
        setVal('edit_login_password',     '');
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
        setVal('edit_user_role',   user.role   || 'recruiter');
        setVal('edit_user_status', user.status || 'active');
        setVal('edit_user_team_manager', user.team_manager_id || '');

        // Trigger role-change logic to show/hide team manager field
        var roleSelect = document.getElementById('edit_user_role');
        if (roleSelect) roleSelect.dispatchEvent(new Event('change'));

        openModal('editUserModal');
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
    var flashToast = document.getElementById('flashToast');
    if (flashToast) {
        setTimeout(function () {
            flashToast.style.opacity = '0';
            setTimeout(function () {
                if (flashToast.parentNode) flashToast.parentNode.removeChild(flashToast);
            }, 400);
        }, 4000);
    }

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

    /* ── Multi-tab candidate form validation ────────────────────── */
    window.validateCandidateForm = function (prefix) {
        var modalId = prefix === 'add' ? 'addCandidateModal' : 'editCandidateModal';
        var modal   = document.getElementById(modalId);
        if (!modal) return;
        var form = modal.querySelector('form');
        if (!form) return;

        var firstInvalidPanel = null;
        var firstInvalidField = null;
        var panels = modal.querySelectorAll('.modal-tab-panel');

        for (var i = 0; i < panels.length; i++) {
            var fields = panels[i].querySelectorAll('input[required], select[required], textarea[required]');
            for (var j = 0; j < fields.length; j++) {
                var field = fields[j];
                if (!field.value || field.value.trim() === '') {
                    firstInvalidPanel = panels[i];
                    firstInvalidField = field;
                    break;
                }
            }
            if (firstInvalidPanel) break;
        }

        if (firstInvalidPanel) {
            var tabGroup = modal.querySelector('.modal-tabs');
            if (tabGroup) window.switchModalTab(tabGroup.id, firstInvalidPanel.id);
            window.showToast('Please fill in all required fields before submitting.', 'error');
            if (firstInvalidField) {
                firstInvalidField.classList.add('is-invalid');
                firstInvalidField.focus();
                firstInvalidField.addEventListener('input', function onFix() {
                    firstInvalidField.classList.remove('is-invalid');
                    firstInvalidField.removeEventListener('input', onFix);
                });
            }
            return;
        }

        if (form.requestSubmit) { form.requestSubmit(); } else { form.submit(); }
    };

})();
