/* ============================================================
   Skilluence Portal - Main JavaScript
   ============================================================ */

(function () {
    'use strict';

    var html = document.documentElement;
    var themeToggle = document.querySelector('.theme-toggle');
    var sidebar = document.querySelector('.sidebar');
    var sidebarToggle = document.querySelector('.sidebar-toggle');

    html.setAttribute('data-theme', localStorage.getItem('theme') || 'light');

    if (themeToggle) {
        themeToggle.addEventListener('click', function () {
            var next = html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            html.setAttribute('data-theme', next);
            localStorage.setItem('theme', next);
        });
    }

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

    document.querySelectorAll('.password-toggle').forEach(function (button) {
        button.addEventListener('click', function () {
            var wrapper = button.closest('.input-with-icon');
            var input = wrapper ? wrapper.querySelector('input') : null;
            if (!input) return;

            input.type = input.type === 'password' ? 'text' : 'password';
            button.innerHTML = input.type === 'password'
                ? '<i class="bi bi-eye"></i>'
                : '<i class="bi bi-eye-slash"></i>';
        });
    });

    var hideCandidatePasswordPrompt = function () {
        var tooltip = document.getElementById('candidate_password_verify_tooltip');
        if (tooltip) {
            tooltip.classList.remove('open');
        }

        setVal('candidate_verify_login_password', '');
        var error = document.getElementById('candidate_verify_error');
        if (error) {
            error.textContent = '';
        }

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
        if (button) {
            button.innerHTML = '<i class="bi bi-eye"></i>';
        }

        hideCandidatePasswordPrompt();
    };

    window.openModal = function (id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.add('open');
        document.body.style.overflow = 'hidden';
    };

    window.closeModal = function (id) {
        var overlay = document.getElementById(id);
        if (!overlay) return;
        overlay.classList.remove('open');
        document.body.style.overflow = '';

        if (id === 'editCandidateModal') {
            resetCandidatePasswordReveal();
        }
    };

    document.addEventListener('click', function (event) {
        if (event.target.classList.contains('modal-overlay')) {
            if (event.target.id === 'editCandidateModal') {
                resetCandidatePasswordReveal();
            }

            event.target.classList.remove('open');
            document.body.style.overflow = '';
            return;
        }

        var tooltip = document.getElementById('candidate_password_verify_tooltip');
        if (!tooltip || !tooltip.classList.contains('open')) {
            return;
        }

        var wrap = document.getElementById('candidate_password_verify_wrap');
        if (wrap && !wrap.contains(event.target)) {
            hideCandidatePasswordPrompt();
        }
    });

    document.addEventListener('keydown', function (event) {
        if (event.key !== 'Escape') return;

        hideCandidatePasswordPrompt();

        document.querySelectorAll('.modal-overlay.open').forEach(function (overlay) {
            overlay.classList.remove('open');
            if (overlay.id === 'editCandidateModal') {
                resetCandidatePasswordReveal();
            }
        });
        document.body.style.overflow = '';
    });

    window.setVal = function (id, value) {
        var element = document.getElementById(id);
        if (!element) return;
        element.value = value !== undefined && value !== null ? value : '';
    };

    window.filterSelectOptions = function (searchInputId, selectId) {
        var searchInput = document.getElementById(searchInputId);
        var select = document.getElementById(selectId);
        if (!searchInput || !select) return;

        var needle = searchInput.value.trim().toLowerCase();
        Array.prototype.forEach.call(select.options, function (option) {
            if (!option.value) {
                option.hidden = false;
                return;
            }

            option.hidden = needle.length > 0 && option.text.toLowerCase().indexOf(needle) === -1;
        });
    };

    var decodeJsonPayload = function (payload) {
        if (!payload) {
            throw new Error('Empty payload');
        }

        var normalized = payload.trim();
        var attempts = [normalized];

        try {
            attempts.unshift(atob(normalized));
        } catch (_error) {
            // The payload can still be plain JSON; ignore base64 decode failures.
        }

        for (var i = 0; i < attempts.length; i++) {
            if (!attempts[i]) continue;

            try {
                return JSON.parse(attempts[i]);
            } catch (_error) {
                // Continue trying remaining candidates.
            }
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
            setTimeout(function () {
                verifyInput.focus();
            }, 0);
        }
    };

    window.cancelCandidatePasswordReveal = function () {
        hideCandidatePasswordPrompt();
    };

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
            if (error) {
                error.textContent = 'Password reveal URL is missing.';
            }
            return;
        }

        if (!verifyInput.value) {
            if (error) {
                error.textContent = 'Please enter your login password.';
            }
            verifyInput.focus();
            return;
        }

        var csrfInput = form.querySelector('input[name="_token"]');
        var csrfToken = csrfInput ? csrfInput.value : '';

        if (error) {
            error.textContent = '';
        }
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
            body: JSON.stringify({
                current_password: verifyInput.value
            })
        }).then(function (response) {
            return response.json().catch(function () {
                return {};
            }).then(function (data) {
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
            if (error) {
                error.textContent = fetchError.message || 'Unable to reveal password right now.';
            }
        }).finally(function () {
            submit.disabled = false;
            submit.textContent = 'Verify';
        });
    };

    window.editCandidate = function (candidate) {
        var form = document.getElementById('editCandidateForm');
        if (!form) return;

        form.action = form.dataset.base + '/' + candidate.id;
        form.dataset.revealUrl = candidate.reveal_password_url || '';

        setVal('edit_full_name', candidate.full_name);
        setVal('edit_enrollment_date', candidate.enrollment_date || '');
        setVal('edit_sales_agent', candidate.sales_agent || '');
        setVal('edit_no_of_applications', candidate.no_of_applications || 0);
        setVal('edit_interviews_count', candidate.interviews_count || 0);
        setVal('edit_status', candidate.status || 'active');
        setVal('edit_recruiter_id', candidate.recruiter_id || '');
        setVal('edit_linkedin_id', candidate.linkedin_id || '');
        setVal('edit_linkedin_password', candidate.linkedin_password || '');
        setVal('edit_email_id', candidate.email_id || '');
        setVal('edit_email_password', candidate.email_password || '');
        setVal('edit_linkedin_updated', candidate.linkedin_updated || '');
        setVal('edit_address', candidate.address || '');
        setVal('edit_profile', candidate.profile || '');
        setVal('edit_notes', candidate.notes || '');
        setVal('edit_login_password', '');
        setVal('edit_recruiter_search', '');
        resetCandidatePasswordReveal();

        var cvCurrent = document.getElementById('edit_cv_current');
        if (cvCurrent) {
            cvCurrent.innerHTML = candidate.cv_file_url
                ? '<a href="' + candidate.cv_file_url + '">Current CV file</a>'
                : 'No CV file uploaded yet.';
        }

        var detailsCurrent = document.getElementById('edit_details_current');
        if (detailsCurrent) {
            detailsCurrent.innerHTML = candidate.details_file_url
                ? '<a href="' + candidate.details_file_url + '">Current candidate details file</a>'
                : 'No candidate details file uploaded yet.';
        }

        openModal('editCandidateModal');
    };

    window.editCandidateFromButton = function (button) {
        if (!button) return;

        var payload = button.getAttribute('data-candidate');
        if (!payload) return;

        try {
            var candidate = decodeJsonPayload(payload);
            editCandidate(candidate);
        } catch (error) {
            console.error('Invalid candidate payload', error);
        }
    };

    window.editUser = function (user) {
        var form = document.getElementById('editUserForm');
        if (!form) return;

        form.action = form.dataset.base + '/' + user.id;
        setVal('edit_user_name', user.name || '');
        setVal('edit_user_email', user.email || '');
        setVal('edit_user_role', user.role || 'recruiter');
        setVal('edit_user_status', user.status || 'active');
        setVal('edit_user_team_manager_id', user.team_manager_id || '');

        openModal('editUserModal');
    };

    window.editUserFromButton = function (button) {
        if (!button) return;

        var payload = button.getAttribute('data-user');
        if (!payload) return;

        try {
            var user = decodeJsonPayload(payload);
            editUser(user);
        } catch (error) {
            console.error('Invalid user payload', error);
        }
    };

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

    var flashToast = document.getElementById('flashToast');
    if (flashToast) {
        setTimeout(function () {
            flashToast.style.opacity = '0';
            setTimeout(function () {
                if (flashToast.parentNode) {
                    flashToast.parentNode.removeChild(flashToast);
                }
            }, 400);
        }, 4000);
    }
})();
