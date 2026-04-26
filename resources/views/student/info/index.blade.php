@extends('layouts.student')
@section('title', 'My Profile')
@section('content')

@php
$visaOptions = [
    'us_citizen'  => 'U.S. Citizen',
    'green_card'  => 'Green Card',
    'h1b'         => 'H-1B',
    'h4_ead'      => 'H-4 EAD',
    'opt_f1'      => 'OPT (F-1)',
    'stem_opt'    => 'STEM OPT',
    'cpt'         => 'CPT',
    'l1'          => 'L-1',
    'tn_visa'     => 'TN Visa',
    'other'       => 'Other',
];
$workAuthOptions = [
    'applied_pending'  => 'Applied — pending approval',
    'not_applied'      => 'Not Applied yet',
    'already_obtained' => 'Already obtained / active',
];
@endphp

<style>
.stp-section-title {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: .5px; color: var(--stp-muted, #94a3b8);
    margin: 20px 0 10px; padding-bottom: 6px;
    border-bottom: 1px solid #e2e8f0;
}
.stp-section-title:first-of-type { margin-top: 0; }
.stp-form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 12px 16px; margin-bottom: 4px; }
.stp-field-error { min-height: 15px; color: #dc2626; font-size: 11.5px; line-height: 1.3; }
.stp-input.is-invalid,
.subdomain-badge-wrap.is-invalid {
    border-color: #dc2626 !important;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, .10) !important;
}
.stp-help-text { color: #94a3b8; font-size: 11px; font-weight: 400; }
.stp-badge-input {
    border-color: #e2e8f0;
    background: #f8fafc;
    min-height: 44px;
}
.stp-badge-input .subdomain-text-input { color: #1e293b; }
.stp-select-loading { opacity: .7; cursor: wait; }
.stp-geo-message { color: #dc2626; font-size: 11.5px; margin-top: 4px; display: none; }
.select2-container--default .select2-selection--single.stp-select2-selection {
    min-height: 42px;
    border: 1.5px solid #e2e8f0 !important;
    border-radius: 8px !important;
    background: #f8fafc;
}
.select2-container--default .stp-select2-selection .select2-selection__rendered {
    line-height: 40px !important;
    color: #1e293b;
    font-size: 13.5px;
    padding-left: 12px;
}
.select2-container--default .stp-select2-selection .select2-selection__arrow { height: 40px !important; }
.stp-phone-field .iti { width: 100%; display: block; }
.stp-phone-field .iti__tel-input { width: 100%; }
.stp-phone-field .iti__selected-country {
    border-radius: 8px 0 0 8px;
    background: #eef4ff;
    border-right: 1px solid #dbe4f0;
}
@media (max-width: 860px) { .stp-form-grid-3 { grid-template-columns: 1fr 1fr; } }
@media (max-width: 520px)  { .stp-form-grid-3 { grid-template-columns: 1fr; } }
</style>

@if (session('success'))
    <div class="toast-container" id="flashToast">
        <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
    </div>
@endif

@if ($errors->any())
    <div class="stp-alert-error">
        <i class="bi bi-exclamation-circle-fill"></i>
        <div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="stp-page-header">
    <div class="stp-page-icon"><i class="bi bi-person-fill"></i></div>
    <div>
        <div class="stp-page-title">My Profile</div>
        <div class="stp-page-sub">Update your personal details, address, visa status, and portal password</div>
    </div>
</div>

{{-- ── Full-width Personal Details Form ────────────────────────────── --}}
<div class="stp-card" style="margin-bottom:20px;">
    <div class="stp-card-head">
        <div class="stp-card-title"><i class="bi bi-pencil-square"></i> Personal Details</div>
        <div class="stp-card-hint">Fields you can update yourself</div>
    </div>
    <form method="POST" action="{{ route('student.info.update') }}" id="studentProfileForm" novalidate>
        @csrf

        {{-- ── Name ─────────────────────────────────────── --}}
        <div class="stp-section-title">Name</div>
        <div class="stp-form-grid-3">
            <div class="stp-form-group">
                <label class="stp-label">First Name <span class="stp-required">*</span></label>
                <input type="text" name="first_name" class="stp-input"
                    value="{{ old('first_name', $candidate->first_name) }}" required placeholder="First name">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Middle Name</label>
                <input type="text" name="middle_name" class="stp-input"
                    value="{{ old('middle_name', $candidate->middle_name) }}" placeholder="Optional">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Last Name <span class="stp-required">*</span></label>
                <input type="text" name="last_name" class="stp-input"
                    value="{{ old('last_name', $candidate->last_name) }}" required placeholder="Last name">
            </div>
        </div>

        {{-- ── Contact ───────────────────────────────────── --}}
        <div class="stp-section-title">Contact</div>
        <div class="stp-form-grid-3">
            <div class="stp-form-group">
                <label class="stp-label">Personal Email <span class="stp-required">*</span></label>
                <input type="email" name="email_id" class="stp-input"
                    value="{{ old('email_id', $candidate->email_id) }}" required>
            </div>
            <div class="stp-form-group stp-phone-field">
                <label class="stp-label">Phone Number</label>
                <input type="hidden" name="phone_cc" id="student_phone_cc" value="+1">
                <input type="tel" name="phone_number" id="student_phone_number"
                    class="stp-input js-phone-input" data-cc-target="student_phone_cc"
                    value="{{ old('phone_number', $candidate->phone_number) }}" placeholder="+1 (555) 000-0000">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">LinkedIn URL</label>
                <input type="url" name="linkedin_url" class="stp-input"
                    value="{{ old('linkedin_url', $candidate->linkedin_url) }}" placeholder="https://linkedin.com/in/username">
            </div>
        </div>

        {{-- ── Personal Info ─────────────────────────────── --}}
        <div class="stp-section-title">Personal Information</div>
        <div class="stp-form-grid-3">
            <div class="stp-form-group">
                <label class="stp-label">Date of Birth</label>
                <input type="text" name="date_of_birth" class="stp-input js-stp-date"
                    value="{{ old('date_of_birth', $candidate->date_of_birth?->format('Y-m-d')) }}">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Gender</label>
                <select name="gender" class="stp-input">
                    <option value="">Select Gender</option>
                    <option value="male"              @selected(old('gender', $candidate->gender) === 'male')>Male</option>
                    <option value="female"            @selected(old('gender', $candidate->gender) === 'female')>Female</option>
                    <option value="other"             @selected(old('gender', $candidate->gender) === 'other')>Other</option>
                    <option value="prefer_not_to_say" @selected(old('gender', $candidate->gender) === 'prefer_not_to_say')>Prefer not to say</option>
                </select>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Nationality</label>
                <input type="text" name="nationality" class="stp-input"
                    value="{{ old('nationality', $candidate->nationality) }}" placeholder="e.g. Indian">
            </div>
        </div>

        {{-- ── Domain & Salary ──────────────────────────── --}}
        <div class="stp-section-title">Domain &amp; Salary</div>
        <div class="stp-form-grid-3">
            <div class="stp-form-group">
                <label class="stp-label">Domain / Technology</label>
                <input type="text" name="domain" class="stp-input"
                    value="{{ old('domain', $candidate->domain) }}" placeholder="e.g. Java, Data Science">
            </div>
            <div class="stp-form-group" style="grid-column:span 2;">
                <label class="stp-label">Sub-Domain / Skills
                    <span class="stp-help-text">(type and press Enter)</span>
                </label>
                <input type="hidden" name="sub_domain" id="student_sub_domain"
                    value="{{ old('sub_domain', $candidate->sub_domain) }}">
                <div class="subdomain-badge-wrap stp-badge-input" id="student_subdomain_badges"
                    onclick="document.getElementById('student_subdomain_input').focus();">
                    <input type="text" id="student_subdomain_input" class="subdomain-text-input"
                        placeholder="e.g. Spring Boot">
                </div>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Date of Arrival in USA</label>
                <input type="text" name="date_of_arrival_usa" class="stp-input js-stp-date"
                    value="{{ old('date_of_arrival_usa', $candidate->date_of_arrival_usa?->format('Y-m-d')) }}">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Current Salary ($)</label>
                <input type="number" name="current_salary" class="stp-input" min="0" step="0.01"
                    value="{{ old('current_salary', $candidate->current_salary) }}" placeholder="0.00">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Expected Salary ($)</label>
                <input type="number" name="expected_salary" class="stp-input" min="0" step="0.01"
                    value="{{ old('expected_salary', $candidate->expected_salary) }}" placeholder="0.00">
            </div>
        </div>

        {{-- ── Address ───────────────────────────────────── --}}
        <div class="stp-section-title">Address</div>
        <div class="stp-form-grid-3">
            <div class="stp-form-group" style="grid-column:span 2;">
                <label class="stp-label">Street Address</label>
                <input type="text" name="street_address" class="stp-input"
                    value="{{ old('street_address', $candidate->street_address) }}" placeholder="123 Main St">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">APT / Unit</label>
                <input type="text" name="apartment_unit" class="stp-input"
                    value="{{ old('apartment_unit', $candidate->apartment_unit) }}" placeholder="Apt 4B">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Country</label>
                <select name="country" id="student_country" class="stp-input"
                    data-selected="{{ old('country', $candidate->country ?? 'United States') }}">
                    <option value="">Select Country</option>
                </select>
                <div class="stp-geo-message" id="student_country_msg"></div>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">State / Province</label>
                <select name="state_province" id="student_state" class="stp-input"
                    data-selected="{{ old('state_province', $candidate->state_province) }}">
                    <option value="">Select Country First</option>
                </select>
                <div class="stp-geo-message" id="student_state_msg"></div>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">City</label>
                <select name="city" id="student_city" class="stp-input"
                    data-selected="{{ old('city', $candidate->city) }}">
                    <option value="">Select State First</option>
                </select>
                <div class="stp-geo-message" id="student_city_msg"></div>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">ZIP Code</label>
                <input type="text" name="zip_code" class="stp-input" maxlength="10"
                    value="{{ old('zip_code', $candidate->zip_code) }}" placeholder="10001">
            </div>
        </div>

        {{-- ── Visa & Work Auth ─────────────────────────── --}}
        <div class="stp-section-title">Visa &amp; Work Authorization</div>
        <div class="stp-form-grid-3">
            <div class="stp-form-group">
                <label class="stp-label">Visa / Immigration Status</label>
                <select name="visa_immigration_status" class="stp-input">
                    <option value="">Select Status</option>
                    @foreach ($visaOptions as $val => $label)
                        <option value="{{ $val }}" @selected(old('visa_immigration_status', $candidate->visa_immigration_status) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Work Auth Status</label>
                <select name="work_auth_status" class="stp-input">
                    <option value="">Select Status</option>
                    @foreach ($workAuthOptions as $val => $label)
                        <option value="{{ $val }}" @selected(old('work_auth_status', $candidate->work_auth_status) === $val)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Visa Expiry Date</label>
                <input type="text" name="visa_expiry_date" class="stp-input js-stp-date"
                    value="{{ old('visa_expiry_date', $candidate->visa_expiry_date?->format('Y-m-d')) }}">
            </div>
            @php
                $relocRaw = old('open_to_relocation') !== null
                    ? old('open_to_relocation')
                    : $candidate->open_to_relocation;
                $relocInt = ($relocRaw !== null && $relocRaw !== '') ? (int) $relocRaw : null;
            @endphp
            <div class="stp-form-group">
                <label class="stp-label">Open to Relocation</label>
                <div style="display:flex;gap:20px;align-items:center;margin-top:6px;">
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                        <input type="radio" name="open_to_relocation" value="1"
                            {{ $relocInt === 1 ? 'checked' : '' }}> Yes
                    </label>
                    <label style="display:flex;align-items:center;gap:6px;cursor:pointer;font-size:13px;">
                        <input type="radio" name="open_to_relocation" value="0"
                            {{ $relocInt === 0 ? 'checked' : '' }}> No
                    </label>
                </div>
            </div>
            <div class="stp-form-group" style="grid-column:span 2;">
                <label class="stp-label">Preferred City <span class="stp-help-text">(type and press Enter)</span></label>
                <input type="hidden" name="preferred_city" id="student_preferred_city"
                    value="{{ old('preferred_city', $candidate->preferred_city) }}">
                <div class="subdomain-badge-wrap stp-badge-input" id="student_prefcity_badges"
                    onclick="document.getElementById('student_prefcity_input').focus();">
                    <input type="text" id="student_prefcity_input" class="subdomain-text-input"
                        placeholder="e.g. New York">
                </div>
            </div>
        </div>

        <div style="margin-top:20px;padding-top:16px;border-top:1px solid #e2e8f0;">
            <button type="submit" class="stp-btn-primary">
                <i class="bi bi-check-lg"></i> Save Changes
            </button>
        </div>
    </form>
</div>

{{-- ── Bottom 2-col: Change Password + Enrollment Info ────────────── --}}
<div class="stp-profile-grid">

    {{-- Change Password --}}
    <div class="stp-profile-col-left">
        <div class="stp-card">
            <div class="stp-card-head">
                <div class="stp-card-title"><i class="bi bi-shield-lock-fill"></i> Change Password</div>
                <div class="stp-card-hint">Update your portal login password</div>
            </div>
            <form method="POST" action="{{ route('student.info.password') }}">
                @csrf
                <div class="stp-form-group" style="margin-bottom:12px;">
                    <label class="stp-label">Current Password <span class="stp-required">*</span></label>
                    <div class="input-with-icon">
                        <input type="password" name="current_password" class="stp-input" required>
                        <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="stp-form-grid">
                    <div class="stp-form-group">
                        <label class="stp-label">New Password <span class="stp-required">*</span></label>
                        <div class="input-with-icon">
                            <input type="password" name="password" class="stp-input" minlength="8" required>
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">Confirm New Password <span class="stp-required">*</span></label>
                        <div class="input-with-icon">
                            <input type="password" name="password_confirmation" class="stp-input" minlength="8" required>
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
                <div style="margin-top:4px;">
                    <button type="submit" class="stp-btn-secondary">
                        <i class="bi bi-shield-check"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Enrollment Info --}}
    <div class="stp-profile-col-right">
        <div class="stp-card">
            <div class="stp-card-head">
                <div class="stp-card-title"><i class="bi bi-info-circle-fill"></i> Enrollment Info</div>
                <div class="stp-card-hint">Set by your recruiter and admin team</div>
            </div>
            <div class="stp-enroll-list">
                <div class="stp-enroll-item">
                    <div class="stp-enroll-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-cpu"></i></div>
                    <div>
                        <div class="stp-enroll-label">Domain</div>
                        <div class="stp-enroll-val">
                            {{ implode(' / ', array_filter([$candidate->domain, $candidate->sub_domain])) ?: '—' }}
                        </div>
                    </div>
                </div>
                <div class="stp-enroll-item">
                    <div class="stp-enroll-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="stp-enroll-label">Recruiter</div>
                        <div class="stp-enroll-val">{{ $candidate->recruiter?->name ?? '—' }}</div>
                    </div>
                </div>
                <div class="stp-enroll-item">
                    <div class="stp-enroll-icon" style="background:#fdf4ff;color:#a21caf;"><i class="bi bi-award"></i></div>
                    <div>
                        <div class="stp-enroll-label">Status</div>
                        <div class="stp-enroll-val">
                            <span class="sp-status-badge {{ $candidate->status }}">{{ ucfirst($candidate->status) }}</span>
                        </div>
                    </div>
                </div>
                <div class="stp-enroll-item">
                    <div class="stp-enroll-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="stp-enroll-label">Total Interviews</div>
                        <div class="stp-enroll-val">{{ $candidate->interviews_count ?: '0' }}</div>
                    </div>
                </div>
                @if ($candidate->visa_immigration_status)
                    <div class="stp-enroll-item">
                        <div class="stp-enroll-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="stp-enroll-label">Visa / Immigration Status</div>
                            <div class="stp-enroll-val">{{ ucfirst(str_replace('_', ' ', $candidate->visa_immigration_status)) }}</div>
                        </div>
                    </div>
                @endif
                @if ($candidate->cv_file_path)
                    <div class="stp-enroll-item">
                        <div class="stp-enroll-icon" style="background:#fef2f2;color:#dc2626;"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div class="stp-enroll-label">CV on File</div>
                            <div class="stp-enroll-val">
                                <a href="{{ route('student.files', 'cv') }}" target="_blank"
                                   style="color:#2563eb;text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:5px;">
                                    View CV <i class="bi bi-box-arrow-up-right" style="font-size:11px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
                @if ($candidate->candidate_details_file_path)
                    <div class="stp-enroll-item">
                        <div class="stp-enroll-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-file-earmark-text-fill"></i></div>
                        <div style="flex:1;min-width:0;">
                            <div class="stp-enroll-label">Candidate Details File</div>
                            <div class="stp-enroll-val">
                                <a href="{{ route('student.files', 'details') }}" target="_blank"
                                   style="color:#2563eb;text-decoration:none;font-weight:500;display:inline-flex;align-items:center;gap:5px;">
                                    View File <i class="bi bi-box-arrow-up-right" style="font-size:11px;"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
const studentGeoBase = 'https://countriesnow.space/api/v0.1';
const studentGeoCache = { countries: null, states: {}, cities: {} };

function stpEsc(value) {
    return String(value || '').replace(/[&<>"']/g, function (char) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[char];
    });
}

function stpSplitCsv(value) {
    return String(value || '').split(',').map(function (item) {
        return item.trim();
    }).filter(Boolean);
}

function stpSetCsv(hidden, values) {
    hidden.value = values.map(function (value) {
        return value.replace(/,/g, '').trim();
    }).filter(Boolean).join(',');
}

function stpCreateBadgeInput(config) {
    var hidden = document.getElementById(config.hiddenId);
    var wrap = document.getElementById(config.wrapId);
    var input = document.getElementById(config.inputId);
    if (!hidden || !wrap || !input) return;

    function values() {
        return stpSplitCsv(hidden.value);
    }

    function render() {
        wrap.querySelectorAll('.subdomain-badge').forEach(function (badge) { badge.remove(); });
        values().forEach(function (value, index) {
            var badge = document.createElement('span');
            badge.className = 'subdomain-badge';
            badge.innerHTML = stpEsc(value) +
                '<button type="button" class="subdomain-badge-x" aria-label="Remove ' + stpEsc(value) + '">&times;</button>';
            badge.querySelector('button').addEventListener('click', function () {
                var next = values();
                next.splice(index, 1);
                stpSetCsv(hidden, next);
                render();
            });
            wrap.insertBefore(badge, input);
        });
    }

    function addPending() {
        var value = input.value.replace(/,/g, '').trim();
        if (!value) return;
        var next = values();
        if (!next.some(function (item) { return item.toLowerCase() === value.toLowerCase(); })) {
            next.push(value);
            stpSetCsv(hidden, next);
            render();
        }
        input.value = '';
    }

    input.addEventListener('keydown', function (event) {
        if (event.key === 'Enter' || event.key === ',') {
            event.preventDefault();
            addPending();
        }
        if (event.key === 'Backspace' && !input.value) {
            var next = values();
            next.pop();
            stpSetCsv(hidden, next);
            render();
        }
    });

    input.addEventListener('blur', addPending);
    hidden._flushBadgeInput = addPending;
    render();
}

function stpSetOptions(select, items, placeholder, selectedValue) {
    if (!select) return;
    select.innerHTML = '<option value="">' + stpEsc(placeholder) + '</option>';
    items.forEach(function (item) {
        var option = document.createElement('option');
        option.value = item.v;
        option.textContent = item.l;
        select.appendChild(option);
    });
    if (selectedValue && !Array.from(select.options).some(function (option) { return option.value === selectedValue; })) {
        var custom = document.createElement('option');
        custom.value = selectedValue;
        custom.textContent = selectedValue;
        select.appendChild(custom);
    }
    if (selectedValue) select.value = selectedValue;
    stpRefreshSelect2(select);
}

function stpSetSelectLoading(select, loading) {
    if (!select) return;
    select.classList.toggle('stp-select-loading', loading);
    select.disabled = !!loading;
    select.setAttribute('aria-busy', loading ? 'true' : 'false');
    stpRefreshSelect2(select);
}

function stpSetGeoMessage(id, message) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = message || '';
    el.style.display = message ? 'block' : 'none';
}

function stpRefreshSelect2(select) {
    if (window.jQuery && jQuery(select).data('select2')) {
        jQuery(select).trigger('change.select2');
    }
}

function stpInitSelect2() {
    if (!window.jQuery || !jQuery.fn.select2) return;
    jQuery('#student_country,#student_state,#student_city').each(function () {
        var $select = jQuery(this);
        if ($select.data('select2')) return;
        $select.select2({
            width: '100%',
            placeholder: $select.find('option:first').text() || 'Select',
            allowClear: true,
            selectionCssClass: 'stp-select2-selection',
            dropdownCssClass: 'candidate-select2-dropdown',
            language: {
                noResults: function () {
                    return $select.hasClass('stp-select-loading') ? 'Loading...' : 'No results found';
                }
            }
        });
    });
}

function stpLoadCountries() {
    if (studentGeoCache.countries) return Promise.resolve(studentGeoCache.countries);

    return fetch(studentGeoBase + '/countries/positions')
        .then(function (response) { return response.json(); })
        .then(function (payload) {
            var countries = (payload.data || []).map(function (country) {
                return { v: country.name, l: country.name };
            }).sort(function (a, b) { return a.l.localeCompare(b.l); });
            studentGeoCache.countries = countries;
            return countries;
        })
        .catch(function () {
            stpSetGeoMessage('student_country_msg', 'Country list could not load. Please check connection and try again.');
            return [];
        });
}

function stpLoadStates(country) {
    if (!country) return Promise.resolve([]);
    if (studentGeoCache.states[country]) return Promise.resolve(studentGeoCache.states[country]);

    return fetch(studentGeoBase + '/countries/states', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ country: country })
    }).then(function (response) { return response.json(); })
        .then(function (payload) {
            var states = ((payload.data && payload.data.states) || []).map(function (state) {
                return { v: state.name, l: state.name };
            }).sort(function (a, b) { return a.l.localeCompare(b.l); });
            studentGeoCache.states[country] = states;
            return states;
        })
        .catch(function () {
            stpSetGeoMessage('student_state_msg', 'State list could not load. Please try again.');
            return [];
        });
}

function stpLoadCities(country, state) {
    if (!country || !state) return Promise.resolve([]);
    var key = country + '|' + state;
    if (studentGeoCache.cities[key]) return Promise.resolve(studentGeoCache.cities[key]);

    return fetch(studentGeoBase + '/countries/state/cities', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ country: country, state: state })
    }).then(function (response) { return response.json(); })
        .then(function (payload) {
            var cities = (payload.data || []).map(function (city) {
                return { v: city, l: city };
            });
            studentGeoCache.cities[key] = cities;
            return cities;
        })
        .catch(function () {
            stpSetGeoMessage('student_city_msg', 'City list could not load. Please try again.');
            return [];
        });
}

function stpNormalizeStateValue(value) {
    return value || '';
}

function stpInitGeoSelects() {
    var country = document.getElementById('student_country');
    var state = document.getElementById('student_state');
    var city = document.getElementById('student_city');
    if (!country || !state || !city) return;
    stpInitSelect2();

    var selectedCountry = country.dataset.selected || 'United States';
    var selectedState = stpNormalizeStateValue(state.dataset.selected || '');
    var selectedCity = city.dataset.selected || '';

    stpSetOptions(country, selectedCountry ? [{ v: selectedCountry, l: selectedCountry }] : [], 'Select Country', selectedCountry);
    stpSetOptions(state, selectedState ? [{ v: selectedState, l: selectedState }] : [], 'Select Country First', selectedState);
    stpSetOptions(city, selectedCity ? [{ v: selectedCity, l: selectedCity }] : [], 'Select State First', selectedCity);

    stpSetSelectLoading(country, true);
    stpSetGeoMessage('student_country_msg', '');
    stpLoadCountries().then(function (countries) {
        stpSetOptions(country, countries, 'Select Country', selectedCountry);
        stpSetSelectLoading(country, false);
        return stpRefreshStates(selectedState, selectedCity);
    });

    var handleCountryChange = function () {
        stpRefreshStates('', '');
    };
    var handleStateChange = function () {
        stpRefreshCities('');
    };

    country.addEventListener('change', handleCountryChange);
    state.addEventListener('change', handleStateChange);
    if (window.jQuery) {
        jQuery(country).on('change.select2-student-geo', handleCountryChange);
        jQuery(state).on('change.select2-student-geo', handleStateChange);
    }
}

function stpRefreshStates(selectedState, selectedCity) {
    var country = document.getElementById('student_country');
    var state = document.getElementById('student_state');
    var city = document.getElementById('student_city');
    var countryValue = country ? country.value : '';

    stpSetGeoMessage('student_state_msg', '');
    stpSetOptions(city, [], 'Select State First', '');
    if (!countryValue) {
        stpSetOptions(state, [], 'Select Country First', '');
        return Promise.resolve();
    }

    stpSetOptions(state, [], 'Loading states...', '');
    stpSetSelectLoading(state, true);
    return stpLoadStates(countryValue).then(function (states) {
        stpSetOptions(state, states, states.length ? 'Select State / Province' : 'No states found', selectedState || '');
        stpSetSelectLoading(state, false);
        return stpRefreshCities(selectedCity || '');
    });
}

function stpRefreshCities(selectedCity) {
    var country = document.getElementById('student_country');
    var state = document.getElementById('student_state');
    var city = document.getElementById('student_city');
    var countryValue = country ? country.value : '';
    var stateValue = state ? state.value : '';

    stpSetGeoMessage('student_city_msg', '');
    if (!countryValue || !stateValue) {
        stpSetOptions(city, [], 'Select State First', '');
        return Promise.resolve();
    }

    stpSetOptions(city, [], 'Loading cities...', '');
    stpSetSelectLoading(city, true);
    return stpLoadCities(countryValue, stateValue).then(function (cities) {
        stpSetOptions(city, cities, cities.length ? 'Select City' : 'No cities found', selectedCity || '');
        stpSetSelectLoading(city, false);
    });
}

function stpFieldErrorTarget(field) {
    if (!field) return null;
    var group = field.closest('.stp-form-group');
    if (!group) return null;
    var error = group.querySelector('.stp-field-error');
    if (!error) {
        error = document.createElement('div');
        error.className = 'stp-field-error';
        group.appendChild(error);
    }
    return error;
}

function stpSetFieldError(field, message) {
    var target = stpFieldErrorTarget(field);
    var invalidTarget = field && field.classList && field.classList.contains('subdomain-badge-wrap')
        ? field
        : field;
    if (invalidTarget) invalidTarget.classList.toggle('is-invalid', !!message);
    if (target) target.textContent = message || '';
}

function stpFormValue(form, name) {
    var field = form.elements[name];
    return field ? String(field.value || '').trim() : '';
}

function stpValidDate(value) {
    if (!value) return true;
    var time = Date.parse(value + 'T00:00:00');
    return !Number.isNaN(time);
}

function stpValidUrl(value) {
    if (!value) return true;
    try {
        var parsed = new URL(value);
        return parsed.protocol === 'http:' || parsed.protocol === 'https:';
    } catch (_) {
        return false;
    }
}

function stpValidateProfileForm(event) {
    var form = event.target;
    if (!form || form.id !== 'studentProfileForm') return;

    ['student_sub_domain', 'student_preferred_city'].forEach(function (id) {
        var hidden = document.getElementById(id);
        if (hidden && hidden._flushBadgeInput) hidden._flushBadgeInput();
    });

    var checks = [
        ['first_name', stpFormValue(form, 'first_name') ? '' : 'First name is required.'],
        ['middle_name', stpFormValue(form, 'middle_name').length <= 100 ? '' : 'Middle name must be 100 characters or fewer.'],
        ['last_name', stpFormValue(form, 'last_name') ? '' : 'Last name is required.'],
        ['email_id', stpFormValue(form, 'email_id') ? '' : 'Personal email is required.'],
        ['phone_number', stpFormValue(form, 'phone_number').length <= 30 ? '' : 'Phone number must be 30 characters or fewer.'],
        ['linkedin_url', stpValidUrl(stpFormValue(form, 'linkedin_url')) ? '' : 'LinkedIn URL must start with http:// or https://.'],
        ['date_of_birth', stpValidDate(stpFormValue(form, 'date_of_birth')) ? '' : 'Enter a valid date of birth.'],
        ['nationality', stpFormValue(form, 'nationality').length <= 100 ? '' : 'Nationality must be 100 characters or fewer.'],
        ['domain', stpFormValue(form, 'domain').length <= 100 ? '' : 'Domain must be 100 characters or fewer.'],
        ['sub_domain', stpFormValue(form, 'sub_domain').length <= 500 ? '' : 'Sub-domain skills must be 500 characters or fewer.'],
        ['date_of_arrival_usa', stpValidDate(stpFormValue(form, 'date_of_arrival_usa')) ? '' : 'Enter a valid arrival date.'],
        ['current_salary', Number(stpFormValue(form, 'current_salary') || 0) >= 0 ? '' : 'Current salary cannot be negative.'],
        ['expected_salary', Number(stpFormValue(form, 'expected_salary') || 0) >= 0 ? '' : 'Expected salary cannot be negative.'],
        ['street_address', stpFormValue(form, 'street_address').length <= 255 ? '' : 'Street address must be 255 characters or fewer.'],
        ['apartment_unit', stpFormValue(form, 'apartment_unit').length <= 50 ? '' : 'APT / Unit must be 50 characters or fewer.'],
        ['city', stpFormValue(form, 'city').length <= 100 ? '' : 'City must be 100 characters or fewer.'],
        ['state_province', stpFormValue(form, 'state_province').length <= 100 ? '' : 'State must be 100 characters or fewer.'],
        ['zip_code', stpFormValue(form, 'zip_code').length <= 20 ? '' : 'ZIP code must be 20 characters or fewer.'],
        ['country', stpFormValue(form, 'country').length <= 100 ? '' : 'Country must be 100 characters or fewer.'],
        ['visa_expiry_date', stpValidDate(stpFormValue(form, 'visa_expiry_date')) ? '' : 'Enter a valid visa expiry date.'],
        ['preferred_city', stpFormValue(form, 'preferred_city').length <= 500 ? '' : 'Preferred cities must be 500 characters or fewer.'],
    ];

    var firstInvalid = null;
    checks.forEach(function (check) {
        var field = form.elements[check[0]];
        if (field && field.type === 'hidden') {
            var wrapId = check[0] === 'sub_domain' ? 'student_subdomain_badges' : 'student_prefcity_badges';
            field = document.getElementById(wrapId) || field;
        }
        stpSetFieldError(field, check[1]);
        if (check[1] && !firstInvalid) firstInvalid = field;
    });

    var email = form.elements.email_id;
    if (email && !firstInvalid && !email.validity.valid) {
        stpSetFieldError(email, 'Please enter a valid email address.');
        firstInvalid = email;
    }

    if (firstInvalid) {
        event.preventDefault();
        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
        if (firstInvalid.focus) setTimeout(function () { firstInvalid.focus(); }, 250);
        return;
    }

    stpNormalizeStudentPhone(form);
}

function stpNormalizeStudentPhone(form) {
    var input = document.getElementById('student_phone_number');
    if (!input) return;
    var raw = String(input.value || '').trim();
    if (!raw) return;

    var iti = window.intlTelInputGlobals && window.intlTelInputGlobals.getInstance
        ? window.intlTelInputGlobals.getInstance(input)
        : null;
    var dialCode = '';
    if (iti && iti.getSelectedCountryData) {
        var countryData = iti.getSelectedCountryData();
        dialCode = countryData && countryData.dialCode ? countryData.dialCode : '';
    }

    var digits = raw.replace(/\D/g, '');
    if (!digits) return;
    if (dialCode && digits.indexOf(dialCode) === 0) {
        digits = digits.slice(dialCode.length);
    }
    input.value = dialCode ? '+' + dialCode + digits : raw;
    if (form && form.elements.phone_cc) {
        form.elements.phone_cc.value = dialCode ? '+' + dialCode : '';
    }
}

document.addEventListener('DOMContentLoaded', function () {
    if (typeof flatpickr !== 'undefined') {
        flatpickr('.js-stp-date', {
            dateFormat: 'Y-m-d',
            altInput: true,
            altFormat: 'd-m-Y',
            allowInput: true
        });
    }

    stpCreateBadgeInput({
        hiddenId: 'student_sub_domain',
        wrapId: 'student_subdomain_badges',
        inputId: 'student_subdomain_input'
    });
    stpCreateBadgeInput({
        hiddenId: 'student_preferred_city',
        wrapId: 'student_prefcity_badges',
        inputId: 'student_prefcity_input'
    });
    stpInitGeoSelects();
    if (window._initPhoneInputs) {
        window._initPhoneInputs(document.getElementById('studentProfileForm') || document);
    }

    var form = document.getElementById('studentProfileForm');
    if (form) {
        form.addEventListener('submit', stpValidateProfileForm);
        form.querySelectorAll('.stp-input').forEach(function (field) {
            field.addEventListener('input', function () { stpSetFieldError(field, ''); });
            field.addEventListener('change', function () { stpSetFieldError(field, ''); });
        });
    }
});
</script>

@endsection
