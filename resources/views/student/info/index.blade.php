@extends('layouts.student')
@section('title', 'My Profile')
@section('content')

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
        <div class="stp-page-sub">Update your personal details and portal password</div>
    </div>
</div>

<div class="stp-profile-grid">

    {{-- Left column: Personal Details + Change Password --}}
    <div class="stp-profile-col-left">
        <div class="stp-card">
            <div class="stp-card-head">
                <div class="stp-card-title"><i class="bi bi-pencil-square"></i> Personal Details</div>
                <div class="stp-card-hint">These fields are editable by you</div>
            </div>
            <form method="POST" action="{{ route('student.info.update') }}">
                @csrf
                <div class="stp-form-grid">
                    <div class="stp-form-group">
                        <label class="stp-label">First Name <span class="stp-required">*</span></label>
                        <input type="text" name="first_name" class="stp-input" value="{{ old('first_name', $candidate->first_name) }}" required placeholder="First name">
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">Last Name <span class="stp-required">*</span></label>
                        <input type="text" name="last_name" class="stp-input" value="{{ old('last_name', $candidate->last_name) }}" required placeholder="Last name">
                    </div>
                    <div class="stp-form-group" style="grid-column:1/-1;">
                        <label class="stp-label">Middle Name <span style="font-size:11px;color:#94a3b8;font-weight:400;">(optional)</span></label>
                        <input type="text" name="middle_name" class="stp-input" value="{{ old('middle_name', $candidate->middle_name) }}" placeholder="Middle name">
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">Email ID <span class="stp-required">*</span></label>
                        <input type="email" name="email_id" class="stp-input" value="{{ old('email_id', $candidate->email_id) }}" required>
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">Phone Number</label>
                        <input type="text" name="phone_number" class="stp-input" value="{{ old('phone_number', $candidate->phone_number) }}" placeholder="+1 (555) 000-0000">
                    </div>
                    <div class="stp-form-group" style="grid-column:1/-1;">
                        <label class="stp-label">LinkedIn URL</label>
                        <input type="url" name="linkedin_url" class="stp-input" value="{{ old('linkedin_url', $candidate->linkedin_url) }}" placeholder="https://linkedin.com/in/username">
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">City</label>
                        <input type="text" name="city" class="stp-input" value="{{ old('city', $candidate->city) }}" placeholder="Your city">
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">ZIP Code</label>
                        <input type="text" name="zip_code" class="stp-input" value="{{ old('zip_code', $candidate->zip_code) }}" placeholder="10001" maxlength="10">
                    </div>
                </div>
                <div style="margin-top:16px;">
                    <button type="submit" class="stp-btn-primary">
                        <i class="bi bi-check-lg"></i> Save Changes
                    </button>
                </div>
            </form>
        </div>

        <div class="stp-card" style="margin-top:16px;">
            <div class="stp-card-head">
                <div class="stp-card-title"><i class="bi bi-shield-lock-fill"></i> Change Password</div>
                <div class="stp-card-hint">Update your portal login password</div>
            </div>
            <form method="POST" action="{{ route('student.info.password') }}">
                @csrf
                <div class="stp-form-group" style="margin-bottom:12px;">
                    <label class="stp-label">Current Password <span class="stp-required">*</span></label>
                    <input type="password" name="current_password" class="stp-input" required>
                </div>
                <div class="stp-form-grid">
                    <div class="stp-form-group">
                        <label class="stp-label">New Password <span class="stp-required">*</span></label>
                        <input type="password" name="password" class="stp-input" minlength="8" required>
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">Confirm New Password <span class="stp-required">*</span></label>
                        <input type="password" name="password_confirmation" class="stp-input" minlength="8" required>
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

    {{-- Right column: Enrollment Info only --}}
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
