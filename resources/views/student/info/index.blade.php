@extends('layouts.student')
@section('title', 'My Profile')
@section('content')

@if (session('success'))
    <div class="toast-container" id="flashToast">
        <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error mb-16">
        <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;"></i>
        <div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="sp-page-header">
    <div class="sp-page-icon"><i class="bi bi-person-fill"></i></div>
    <div>
        <div class="sp-page-title">My Profile</div>
        <div class="sp-page-sub">Update your personal details and portal password</div>
    </div>
</div>

<div class="sp-grid-2">
    <div class="sp-card">
        <div class="sp-card-head">
            <div>
                <div class="sp-card-title"><i class="bi bi-pencil-square"></i> Personal Details</div>
                <div class="sp-card-sub">Fields you can edit in the student portal</div>
            </div>
        </div>

        <form method="POST" action="{{ route('student.info.update') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Full Name <span style="color:var(--red-text)">*</span></label>
                <input type="text" name="full_name" class="form-control" value="{{ old('full_name', $candidate->full_name) }}"
                    required>
            </div>
            <div class="form-group">
                <label class="form-label">Email ID <span style="color:var(--red-text)">*</span></label>
                <input type="email" name="email_id" class="form-control" value="{{ old('email_id', $candidate->email_id) }}"
                    required>
            </div>
            <div class="form-group">
                <label class="form-label">LinkedIn ID</label>
                <input type="text" name="linkedin_id" class="form-control" value="{{ old('linkedin_id', $candidate->linkedin_id) }}">
            </div>
            <div class="form-group">
                <label class="form-label">Address</label>
                <textarea name="address" class="form-control" rows="2">{{ old('address', $candidate->address) }}</textarea>
            </div>
            <div class="form-group">
                <label class="form-label">Profile Description</label>
                <textarea name="profile" class="form-control" rows="4">{{ old('profile', $candidate->profile) }}</textarea>
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Notes</label>
                <textarea name="notes" class="form-control" rows="2">{{ old('notes', $candidate->notes) }}</textarea>
            </div>
            <div style="margin-top:16px;">
                <button type="submit" class="sp-submit-btn">
                    <i class="bi bi-check-lg"></i> Save Changes
                </button>
            </div>
        </form>
    </div>

    <div>
        <div class="sp-card" style="margin-bottom:16px;">
            <div class="sp-card-head">
                <div>
                    <div class="sp-card-title"><i class="bi bi-info-circle-fill"></i> Enrollment and Files</div>
                    <div class="sp-card-sub">Set by recruiter and admin team</div>
                </div>
            </div>
            <div class="sp-info-list">
                <div class="sp-info-item">
                    <div class="sp-ii-icon"><i class="bi bi-calendar3"></i></div>
                    <div>
                        <div class="sp-ii-label">Enrollment Date</div>
                        <div class="sp-ii-val">{{ $candidate->enrollment_date?->format('M d, Y') ?? '-' }}</div>
                    </div>
                </div>
                <div class="sp-info-item">
                    <div class="sp-ii-icon" style="background:#f0fdf4;color:#16a34a;"><i class="bi bi-person-badge"></i></div>
                    <div>
                        <div class="sp-ii-label">Recruiter</div>
                        <div class="sp-ii-val">{{ $candidate->recruiter?->name ?? '-' }}</div>
                    </div>
                </div>
                <div class="sp-info-item">
                    <div class="sp-ii-icon" style="background:#eef2ff;color:#6366f1;"><i class="bi bi-award"></i></div>
                    <div>
                        <div class="sp-ii-label">Status</div>
                        <div class="sp-ii-val"><span class="sp-status-badge {{ $candidate->status }}">{{ ucfirst($candidate->status) }}</span></div>
                    </div>
                </div>
            </div>

            <div style="margin-top:14px;padding-top:12px;border-top:1px solid #e2e8f0;">
                <div class="sp-card-sub mb-8">Documents</div>
                <div class="d-flex gap-8" style="flex-wrap:wrap;">
                    @if ($candidate->cv_file_path)
                        <a class="sp-edit-btn" href="{{ route('student.files', 'cv') }}"><i class="bi bi-file-earmark-arrow-down"></i> Download CV</a>
                    @endif
                    @if ($candidate->candidate_details_file_path)
                        <a class="sp-edit-btn" href="{{ route('student.files', 'details') }}"><i class="bi bi-file-earmark-arrow-down"></i> Candidate Details</a>
                    @endif
                    @if (!$candidate->cv_file_path && !$candidate->candidate_details_file_path)
                        <span class="text-sm text-muted">No files uploaded yet.</span>
                    @endif
                </div>

                <form method="POST" action="{{ route('student.info.documents') }}" enctype="multipart/form-data"
                    style="margin-top:14px;">
                    @csrf
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Upload CV</label>
                            <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Upload Candidate Details</label>
                            <input type="file" name="candidate_details_file" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                        </div>
                    </div>
                    <div class="text-sm text-muted" style="margin-top:-4px;">
                        Allowed formats: PDF, DOC, DOCX, JPG, JPEG, PNG (max 5MB each).
                    </div>
                    <div style="margin-top:12px;">
                        <button type="submit" class="sp-submit-btn">
                            <i class="bi bi-cloud-arrow-up-fill"></i> Upload Documents
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="sp-card">
            <div class="sp-card-head">
                <div>
                    <div class="sp-card-title"><i class="bi bi-shield-lock-fill"></i> Change Password</div>
                    <div class="sp-card-sub">Update your portal login password</div>
                </div>
            </div>
            <form method="POST" action="{{ route('student.info.password') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <input type="password" name="password" class="form-control" minlength="8" required>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Confirm Password</label>
                    <input type="password" name="password_confirmation" class="form-control" minlength="8" required>
                </div>
                <div style="margin-top:16px;">
                    <button type="submit" class="sp-submit-btn">
                        <i class="bi bi-shield-check"></i> Update Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection
