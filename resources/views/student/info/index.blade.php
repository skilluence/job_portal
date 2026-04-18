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
        <div class="stp-page-sub">Update your personal details, documents, and portal password</div>
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
                        <label class="stp-label">Full Name <span class="stp-required">*</span></label>
                        <input type="text" name="full_name" class="stp-input" value="{{ old('full_name', $candidate->full_name) }}" required>
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">Email ID <span class="stp-required">*</span></label>
                        <input type="email" name="email_id" class="stp-input" value="{{ old('email_id', $candidate->email_id) }}" required>
                    </div>
                    <div class="stp-form-group">
                        <label class="stp-label">Phone Number</label>
                        <input type="text" name="phone_number" class="stp-input" value="{{ old('phone_number', $candidate->phone_number) }}" placeholder="+1 (555) 000-0000">
                    </div>
                    <div class="stp-form-group">
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

    {{-- Right column: Enrollment Info + Documents --}}
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
                    <div class="stp-enroll-icon" style="background:#fff7ed;color:#ea580c;"><i class="bi bi-file-earmark-text"></i></div>
                    <div>
                        <div class="stp-enroll-label">Applications</div>
                        <div class="stp-enroll-val">{{ $candidate->no_of_applications }}</div>
                    </div>
                </div>
                <div class="stp-enroll-item">
                    <div class="stp-enroll-icon" style="background:#ecfdf5;color:#059669;"><i class="bi bi-calendar-check"></i></div>
                    <div>
                        <div class="stp-enroll-label">Interviews</div>
                        <div class="stp-enroll-val">{{ $candidate->interviews_count }}</div>
                    </div>
                </div>
                @if ($candidate->visa_immigration_status)
                    <div class="stp-enroll-item">
                        <div class="stp-enroll-icon" style="background:#eff6ff;color:#2563eb;"><i class="bi bi-shield-check"></i></div>
                        <div>
                            <div class="stp-enroll-label">Visa Status</div>
                            <div class="stp-enroll-val">{{ ucfirst(str_replace('_', ' ', $candidate->visa_immigration_status)) }}</div>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <div class="stp-card" style="margin-top:16px;">
            <div class="stp-card-head">
                <div class="stp-card-title"><i class="bi bi-folder2-open"></i> Documents</div>
                <div class="stp-card-hint">View and upload your files</div>
            </div>

            @if ($candidate->cv_file_path || $candidate->candidate_details_file_path)
                <div class="stp-doc-list" style="margin-bottom:16px;">
                    @if ($candidate->cv_file_path)
                        <a href="{{ route('student.files', 'cv') }}" target="_blank" class="stp-doc-item">
                            <div class="stp-doc-icon"><i class="bi bi-file-earmark-pdf-fill"></i></div>
                            <div class="stp-doc-body">
                                <div class="stp-doc-name">Curriculum Vitae (CV)</div>
                                <div class="stp-doc-hint">Click to open in browser</div>
                            </div>
                            <i class="bi bi-box-arrow-up-right stp-doc-arrow"></i>
                        </a>
                    @endif
                    @if ($candidate->candidate_details_file_path)
                        <a href="{{ route('student.files', 'details') }}" target="_blank" class="stp-doc-item">
                            <div class="stp-doc-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-file-earmark-text-fill"></i></div>
                            <div class="stp-doc-body">
                                <div class="stp-doc-name">Candidate Details</div>
                                <div class="stp-doc-hint">Click to open in browser</div>
                            </div>
                            <i class="bi bi-box-arrow-up-right stp-doc-arrow"></i>
                        </a>
                    @endif
                </div>
            @endif

            <form method="POST" action="{{ route('student.info.documents') }}" enctype="multipart/form-data">
                @csrf
                <div class="stp-form-group">
                    <label class="stp-label">
                        Upload / Replace CV
                        @if ($candidate->cv_file_path)
                            <span style="font-size:11px;color:var(--orange-text);font-weight:500;margin-left:6px;">Will replace existing</span>
                        @endif
                    </label>
                    <input type="file" name="cv_file" class="stp-input stp-file-input" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                </div>
                <div class="stp-file-hint">Allowed: PDF, DOC, DOCX, JPG, PNG (max 5 MB each)</div>
                <div style="margin-top:12px;">
                    <button type="submit" class="stp-btn-primary">
                        <i class="bi bi-cloud-arrow-up-fill"></i> Upload CV
                    </button>
                </div>
            </form>
        </div>

        {{-- ── Resumes Section ── --}}
        <div class="stp-card" style="margin-top:16px;">
            <div class="stp-card-head">
                <div class="stp-card-title"><i class="bi bi-file-earmark-person-fill"></i> My Resumes</div>
                <div class="stp-card-hint">Upload multiple resumes with designation</div>
            </div>

            @if ($candidate->resumes->count() > 0)
                <div style="margin-bottom:16px;overflow-x:auto;">
                    <table style="width:100%;border-collapse:collapse;font-size:13px;">
                        <thead>
                            <tr style="background:var(--stp-bg,#f8fafc);">
                                <th style="padding:8px 12px;text-align:left;font-weight:600;border-bottom:1px solid #e2e8f0;">Designation</th>
                                <th style="padding:8px 12px;text-align:left;font-weight:600;border-bottom:1px solid #e2e8f0;">File</th>
                                <th style="padding:8px 12px;text-align:left;font-weight:600;border-bottom:1px solid #e2e8f0;">Uploaded At</th>
                                <th style="padding:8px 12px;text-align:center;font-weight:600;border-bottom:1px solid #e2e8f0;">View</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($candidate->resumes as $resume)
                            <tr style="border-bottom:1px solid #e2e8f0;">
                                <td style="padding:8px 12px;font-weight:500;">{{ $resume->designation }}</td>
                                <td style="padding:8px 12px;color:#64748b;max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
                                    {{ $resume->original_filename }}
                                </td>
                                <td style="padding:8px 12px;color:#64748b;">{{ $resume->created_at->format('M d, Y') }}</td>
                                <td style="padding:8px 12px;text-align:center;">
                                    <a href="{{ route('student.resumes.download', $resume) }}" target="_blank"
                                        class="stp-btn-secondary" style="padding:4px 10px;font-size:12px;display:inline-flex;align-items:center;gap:4px;">
                                        <i class="bi bi-box-arrow-up-right"></i>
                                    </a>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif

            <form method="POST" action="{{ route('student.resumes.store') }}" enctype="multipart/form-data">
                @csrf
                <div id="resumeEntriesWrap">
                    <div style="display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;flex-wrap:wrap;">
                        <div style="flex:1;min-width:160px;">
                            <input type="text" name="resumes[0][designation]" class="stp-input"
                                placeholder="Designation (e.g. Java Developer)" required>
                        </div>
                        <div style="flex:1;min-width:160px;">
                            <input type="file" name="resumes[0][file]" class="stp-input stp-file-input"
                                accept=".pdf,.doc,.docx" required>
                        </div>
                    </div>
                </div>
                <div class="stp-file-hint" style="margin-bottom:10px;">Allowed: PDF, DOC, DOCX (max 5 MB each)</div>
                <div style="display:flex;gap:10px;flex-wrap:wrap;">
                    <button type="button" class="stp-btn-secondary" onclick="addResumeEntry()">
                        <i class="bi bi-plus-lg"></i> Add More
                    </button>
                    <button type="submit" class="stp-btn-primary">
                        <i class="bi bi-cloud-arrow-up-fill"></i> Upload Resumes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var resumeCount = 1;
function addResumeEntry() {
    var wrap = document.getElementById('resumeEntriesWrap');
    var idx  = resumeCount++;
    var div  = document.createElement('div');
    div.style.cssText = 'display:flex;gap:10px;align-items:flex-start;margin-bottom:10px;flex-wrap:wrap;';
    div.innerHTML =
        '<div style="flex:1;min-width:160px;"><input type="text" name="resumes['+idx+'][designation]" class="stp-input" placeholder="Designation" required></div>' +
        '<div style="flex:1;min-width:160px;"><input type="file" name="resumes['+idx+'][file]" class="stp-input stp-file-input" accept=".pdf,.doc,.docx" required></div>';
    wrap.appendChild(div);
}
</script>

@endsection
