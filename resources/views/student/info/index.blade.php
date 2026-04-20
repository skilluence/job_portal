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
$usStates = [
    'AL'=>'Alabama','AK'=>'Alaska','AZ'=>'Arizona','AR'=>'Arkansas','CA'=>'California',
    'CO'=>'Colorado','CT'=>'Connecticut','DE'=>'Delaware','FL'=>'Florida','GA'=>'Georgia',
    'HI'=>'Hawaii','ID'=>'Idaho','IL'=>'Illinois','IN'=>'Indiana','IA'=>'Iowa','KS'=>'Kansas',
    'KY'=>'Kentucky','LA'=>'Louisiana','ME'=>'Maine','MD'=>'Maryland','MA'=>'Massachusetts',
    'MI'=>'Michigan','MN'=>'Minnesota','MS'=>'Mississippi','MO'=>'Missouri','MT'=>'Montana',
    'NE'=>'Nebraska','NV'=>'Nevada','NH'=>'New Hampshire','NJ'=>'New Jersey','NM'=>'New Mexico',
    'NY'=>'New York','NC'=>'North Carolina','ND'=>'North Dakota','OH'=>'Ohio','OK'=>'Oklahoma',
    'OR'=>'Oregon','PA'=>'Pennsylvania','RI'=>'Rhode Island','SC'=>'South Carolina',
    'SD'=>'South Dakota','TN'=>'Tennessee','TX'=>'Texas','UT'=>'Utah','VT'=>'Vermont',
    'VA'=>'Virginia','WA'=>'Washington','WV'=>'West Virginia','WI'=>'Wisconsin','WY'=>'Wyoming',
    'DC'=>'District of Columbia',
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
    <form method="POST" action="{{ route('student.info.update') }}">
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
            <div class="stp-form-group">
                <label class="stp-label">Phone Number</label>
                <input type="text" name="phone_number" class="stp-input"
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
                <input type="date" name="date_of_birth" class="stp-input"
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
                    <span style="font-weight:400;color:#94a3b8;font-size:11px;">(comma-separated)</span>
                </label>
                <input type="text" name="sub_domain" class="stp-input"
                    value="{{ old('sub_domain', $candidate->sub_domain) }}" placeholder="e.g. Spring Boot, Hibernate, REST APIs">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">Date of Arrival in USA</label>
                <input type="date" name="date_of_arrival_usa" class="stp-input"
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
                <label class="stp-label">City</label>
                <input type="text" name="city" class="stp-input"
                    value="{{ old('city', $candidate->city) }}" placeholder="Your city">
            </div>
            <div class="stp-form-group">
                <label class="stp-label">State</label>
                <select name="state_province" class="stp-input">
                    <option value="">Select State</option>
                    @foreach ($usStates as $code => $name)
                        <option value="{{ $code }}" @selected(old('state_province', $candidate->state_province) === $code)>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="stp-form-group">
                <label class="stp-label">ZIP Code</label>
                <input type="text" name="zip_code" class="stp-input" maxlength="10"
                    value="{{ old('zip_code', $candidate->zip_code) }}" placeholder="10001">
            </div>
            <div class="stp-form-group" style="grid-column:span 2;">
                <label class="stp-label">Country</label>
                <input type="text" name="country" class="stp-input"
                    value="{{ old('country', $candidate->country ?? 'United States') }}" placeholder="United States">
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
                <input type="date" name="visa_expiry_date" class="stp-input"
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
                <label class="stp-label">Preferred City <span style="font-weight:400;color:#94a3b8;font-size:11px;">(if not relocating)</span></label>
                <input type="text" name="preferred_city" class="stp-input"
                    value="{{ old('preferred_city', $candidate->preferred_city) }}" placeholder="e.g. New York">
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

@endsection
