@extends('layouts.admin')
@section('title', 'Candidates')
@section('module-title', 'Candidates')
@section('module-description', 'Create, assign, and manage all candidates with recruiter ownership and document uploads.')
@section('content')

@if (session('success'))
    <div class="toast-container" id="flashToast">
        <div class="toast toast-success">
            <i class="bi bi-check-circle-fill"></i>
            <span>{{ session('success') }}</span>
        </div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error mb-16">
        <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;font-size:16px;"></i>
        <div>
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    </div>
@endif

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div>
            <div class="card-title">All Candidates</div>
            <div class="card-subtitle">{{ $candidates->total() }} total records</div>
        </div>
        <div class="d-flex gap-8" style="flex-wrap:wrap;align-items:center;">
            <form method="GET" action="{{ route('admin.candidates') }}" class="d-flex gap-8" style="flex-wrap:wrap;">
                <input type="text" name="search" class="form-control" placeholder="Search name, email, profile..."
                    value="{{ $search }}" style="width:240px;">
                <select name="status" class="form-control" style="width:160px;" onchange="this.form.submit()">
                    <option value="">All Status</option>
                    @foreach ($statusOptions as $statusItem)
                        <option value="{{ $statusItem }}" @selected($status === $statusItem)>{{ ucfirst($statusItem) }}</option>
                    @endforeach
                </select>
                <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
                @if ($search || $status)
                    <a href="{{ route('admin.candidates') }}" class="btn btn-outline" title="Clear filters">
                        <i class="bi bi-x-lg"></i>
                    </a>
                @endif
            </form>
            <button class="btn btn-primary" onclick="openModal('addCandidateModal')">
                <i class="bi bi-plus-lg"></i> Add Candidate
            </button>
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Candidate</th>
                    <th>Recruiter</th>
                    <th>Status</th>
                    <th>Applications</th>
                    <th>Interviews</th>
                    <th>Email</th>
                    <th>Documents</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($candidates as $i => $candidate)
                    <tr>
                        <td class="text-muted text-sm">{{ $candidates->firstItem() + $i }}</td>
                        <td>
                            <div class="avatar-row">
                                <div class="avatar-sm">{{ strtoupper(substr($candidate->full_name, 0, 1)) }}</div>
                                <div>
                                    <div class="avatar-name">{{ $candidate->full_name }}</div>
                                    <div class="avatar-sub">
                                        Enrolled:
                                        {{ $candidate->enrollment_date ? $candidate->enrollment_date->format('M d, Y') : '-' }}
                                    </div>
                                </div>
                            </div>
                        </td>
                        <td>{{ $candidate->recruiter?->name ?? '-' }}</td>
                        <td><span class="badge {{ $candidate->status_badge }}">{{ ucfirst($candidate->status) }}</span></td>
                        <td>{{ $candidate->no_of_applications }}</td>
                        <td>{{ $candidate->interviews_count }}</td>
                        <td class="text-muted text-sm">{{ $candidate->email_id }}</td>
                        <td>
                            <div class="d-flex gap-6" style="flex-wrap:wrap;">
                                @if ($candidate->cv_file_path)
                                    <a class="btn btn-outline btn-sm"
                                        href="{{ route('admin.candidates.files', [$candidate, 'cv']) }}">CV</a>
                                @endif
                                @if ($candidate->candidate_details_file_path)
                                    <a class="btn btn-outline btn-sm"
                                        href="{{ route('admin.candidates.files', [$candidate, 'details']) }}">Details</a>
                                @endif
                                @if (!$candidate->cv_file_path && !$candidate->candidate_details_file_path)
                                    <span class="text-muted text-sm">-</span>
                                @endif
                            </div>
                        </td>
                        <td>
                            @php
                                $candidatePayload = [
                                    'id' => $candidate->id,
                                    'full_name' => $candidate->full_name,
                                    'enrollment_date' => optional($candidate->enrollment_date)->format('Y-m-d'),
                                    'sales_agent' => $candidate->sales_agent,
                                    'no_of_applications' => $candidate->no_of_applications,
                                    'interviews_count' => $candidate->interviews_count,
                                    'status' => $candidate->status,
                                    'recruiter_id' => $candidate->recruiter_id,
                                    'linkedin_id' => $candidate->linkedin_id,
                                    'linkedin_password' => $candidate->linkedin_password,
                                    'email_id' => $candidate->email_id,
                                    'email_password' => $candidate->email_password,
                                    'linkedin_updated' => optional($candidate->linkedin_updated)->format('Y-m-d'),
                                    'address' => $candidate->address,
                                    'profile' => $candidate->profile,
                                    'notes' => $candidate->notes,
                                    'cv_file_url' => $candidate->cv_file_path ? route('admin.candidates.files', [$candidate, 'cv']) : null,
                                    'details_file_url' => $candidate->candidate_details_file_path ? route('admin.candidates.files', [$candidate, 'details']) : null,
                                    'reveal_password_url' => route('admin.candidates.reveal-password', $candidate),
                                ];
                            @endphp
                            <button class="btn btn-outline btn-sm"
                                data-candidate="{{ base64_encode(json_encode($candidatePayload, JSON_UNESCAPED_SLASHES)) }}"
                                onclick="editCandidateFromButton(this)" title="Edit candidate">
                                <i class="bi bi-pencil"></i>
                            </button>
                            <form method="POST" action="{{ route('admin.candidates.destroy', $candidate) }}" style="display:inline;"
                                onsubmit="return confirm('Delete candidate {{ addslashes($candidate->full_name) }}? This will block student login.');">
                                @csrf
                                @method('DELETE')
                                <button class="btn btn-danger btn-sm" title="Delete candidate">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="page-empty mb-0">
                                <i class="bi bi-people"></i>
                                <p>No candidates found{{ ($search || $status) ? ' matching your filters' : '' }}.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($candidates->hasPages())
        <div class="pagination-wrap">
            <span class="pagination-info">
                Showing {{ $candidates->firstItem() }}-{{ $candidates->lastItem() }} of {{ $candidates->total() }}
            </span>
            {{ $candidates->links('pagination.custom') }}
        </div>
    @endif
</div>

<div class="modal-overlay" id="addCandidateModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-person-plus" style="margin-right:6px;"></i> Add New Candidate</div>
            <button class="modal-close" onclick="closeModal('addCandidateModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.candidates.store') }}">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--red-text)">*</span></label>
                        <input type="text" name="full_name" class="form-control" required
                            value="{{ old('full_name') }}" placeholder="e.g. John Smith">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enrollment Date</label>
                        <input type="date" name="enrollment_date" class="form-control" value="{{ old('enrollment_date') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sales Agent</label>
                        <input type="text" name="sales_agent" class="form-control" value="{{ old('sales_agent') }}"
                            placeholder="Agent name">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. of Applications <span style="color:var(--red-text)">*</span></label>
                        <input type="number" name="no_of_applications" class="form-control"
                            value="{{ old('no_of_applications', 0) }}" min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Interviews Count</label>
                        <input type="number" name="interviews_count" class="form-control"
                            value="{{ old('interviews_count', 0) }}" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span style="color:var(--red-text)">*</span></label>
                        <select name="status" class="form-control" required>
                            @foreach ($statusOptions as $statusItem)
                                <option value="{{ $statusItem }}" @selected(old('status', 'active') === $statusItem)>
                                    {{ ucfirst($statusItem) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($isAdmin)
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Search Recruiter</label>
                            <input type="text" id="add_recruiter_search" class="form-control"
                                placeholder="Type recruiter name..." oninput="filterSelectOptions('add_recruiter_search','add_recruiter_id')">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign to Recruiter <span style="color:var(--red-text)">*</span></label>
                            <select name="recruiter_id" id="add_recruiter_id" class="form-control" required>
                                <option value="">Select recruiter</option>
                                @foreach ($recruiters as $rec)
                                    <option value="{{ $rec->id }}" @selected(old('recruiter_id') == $rec->id)>{{ $rec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @else
                    <div class="alert mb-16" style="background:var(--blue-light);color:var(--blue-text);border:1px solid rgba(37,99,235,0.2);">
                        <i class="bi bi-info-circle-fill"></i>
                        <span>Candidates created from your login are automatically assigned to you.</span>
                    </div>
                @endif

                <p class="text-sm text-muted mb-8" style="font-weight:600;padding-top:4px;">LinkedIn and Email Details</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">LinkedIn ID</label>
                        <input type="text" name="linkedin_id" class="form-control" value="{{ old('linkedin_id') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">LinkedIn Password</label>
                        <input type="text" name="linkedin_password" class="form-control" value="{{ old('linkedin_password') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email ID <span style="color:var(--red-text)">*</span></label>
                        <input type="email" name="email_id" class="form-control" required value="{{ old('email_id') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Password</label>
                        <input type="text" name="email_password" class="form-control" value="{{ old('email_password') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">LinkedIn Updated At</label>
                        <input type="date" name="linkedin_updated" class="form-control" value="{{ old('linkedin_updated') }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" class="form-control" rows="2" placeholder="Full address">{{ old('address') }}</textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Profile Description</label>
                    <textarea name="profile" class="form-control" rows="3"
                        placeholder="Candidate profile summary / skills">{{ old('profile') }}</textarea>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2" placeholder="Internal notes">{{ old('notes') }}</textarea>
                </div>

                <div style="margin-top:20px;padding-top:16px;border-top:1px solid var(--border-color);">
                    <p class="text-sm mb-8" style="font-weight:600;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
                        <i class="bi bi-shield-lock-fill" style="color:var(--blue);"></i> Student Portal Login
                    </p>
                    <div class="form-group mb-0">
                        <label class="form-label">Portal Login Password <span style="color:var(--red-text)">*</span></label>
                        <div class="input-with-icon">
                            <input type="password" name="login_password" class="form-control"
                                placeholder="Set student portal password (min 8 chars)" required>
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addCandidateModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Add Candidate</button>
            </div>
        </form>
    </div>
</div>

<div class="modal-overlay" id="editCandidateModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-pencil-square" style="margin-right:6px;"></i> Edit Candidate</div>
            <button class="modal-close" onclick="closeModal('editCandidateModal')">&times;</button>
        </div>
        <form method="POST" id="editCandidateForm" action="" data-base="{{ url('admin/candidates') }}"
            enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--red-text)">*</span></label>
                        <input type="text" name="full_name" id="edit_full_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Enrollment Date</label>
                        <input type="date" name="enrollment_date" id="edit_enrollment_date" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Sales Agent</label>
                        <input type="text" name="sales_agent" id="edit_sales_agent" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">No. of Applications <span style="color:var(--red-text)">*</span></label>
                        <input type="number" name="no_of_applications" id="edit_no_of_applications" class="form-control"
                            min="0" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Interviews Count</label>
                        <input type="number" name="interviews_count" id="edit_interviews_count" class="form-control" min="0">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span style="color:var(--red-text)">*</span></label>
                        <select name="status" id="edit_status" class="form-control" required>
                            @foreach ($statusOptions as $statusItem)
                                <option value="{{ $statusItem }}">{{ ucfirst($statusItem) }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($isAdmin)
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">Search Recruiter</label>
                            <input type="text" id="edit_recruiter_search" class="form-control"
                                placeholder="Type recruiter name..." oninput="filterSelectOptions('edit_recruiter_search','edit_recruiter_id')">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Assign to Recruiter <span style="color:var(--red-text)">*</span></label>
                            <select name="recruiter_id" id="edit_recruiter_id" class="form-control" required>
                                <option value="">Select recruiter</option>
                                @foreach ($recruiters as $rec)
                                    <option value="{{ $rec->id }}">{{ $rec->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                @endif

                <p class="text-sm text-muted mb-8" style="font-weight:600;padding-top:4px;">LinkedIn and Email Details</p>
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">LinkedIn ID</label>
                        <input type="text" name="linkedin_id" id="edit_linkedin_id" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">LinkedIn Password</label>
                        <input type="text" name="linkedin_password" id="edit_linkedin_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email ID <span style="color:var(--red-text)">*</span></label>
                        <input type="email" name="email_id" id="edit_email_id" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email Password</label>
                        <input type="text" name="email_password" id="edit_email_password" class="form-control">
                    </div>
                    <div class="form-group">
                        <label class="form-label">LinkedIn Updated At</label>
                        <input type="date" name="linkedin_updated" id="edit_linkedin_updated" class="form-control">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Address</label>
                    <textarea name="address" id="edit_address" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Profile Description</label>
                    <textarea name="profile" id="edit_profile" class="form-control" rows="3"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" id="edit_notes" class="form-control" rows="2"></textarea>
                </div>

                <div style="margin-top:8px;padding-top:16px;border-top:1px solid var(--border-color);">
                    <p class="text-sm mb-8" style="font-weight:600;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
                        <i class="bi bi-file-earmark-arrow-up-fill" style="color:var(--blue);"></i> Candidate Documents
                    </p>
                    <div class="form-grid">
                        <div class="form-group">
                            <label class="form-label">CV Upload</label>
                            <input type="file" name="cv_file" class="form-control" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="text-sm text-muted" id="edit_cv_current" style="margin-top:6px;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Candidate Details Upload</label>
                            <input type="file" name="candidate_details_file" class="form-control"
                                accept=".pdf,.doc,.docx,.jpg,.jpeg,.png">
                            <div class="text-sm text-muted" id="edit_details_current" style="margin-top:6px;"></div>
                        </div>
                    </div>
                </div>

                <div style="margin-top:8px;padding-top:16px;border-top:1px solid var(--border-color);">
                    <p class="text-sm mb-8" style="font-weight:600;color:var(--text-secondary);display:flex;align-items:center;gap:6px;">
                        <i class="bi bi-shield-lock-fill" style="color:var(--blue);"></i> Student Portal Password
                    </p>
                    <div class="form-group">
                        <label class="form-label">Current Login Password</label>
                        <div class="input-with-icon password-verify-wrap" id="candidate_password_verify_wrap">
                            <input type="password" id="edit_current_login_password" class="form-control" readonly
                                value="********" data-unlocked="0">
                            <button type="button" class="input-eye-btn" id="edit_reveal_password_btn"
                                onclick="toggleCandidateCurrentPassword()"
                                title="View current password after verification">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="password-verify-tooltip" id="candidate_password_verify_tooltip">
                                <div class="password-verify-title">Verify your account password</div>
                                <input type="password" id="candidate_verify_login_password" class="form-control"
                                    placeholder="Enter your login password"
                                    onkeydown="handleCandidateRevealPasswordKeydown(event)">
                                <div class="password-verify-actions">
                                    <button type="button" class="btn btn-outline btn-sm"
                                        onclick="cancelCandidatePasswordReveal()">Cancel</button>
                                    <button type="button" class="btn btn-primary btn-sm" id="candidate_verify_submit"
                                        onclick="confirmCandidatePasswordReveal()">Verify</button>
                                </div>
                                <div class="password-verify-error" id="candidate_verify_error"></div>
                            </div>
                        </div>
                        <div class="portal-pass-hint">
                            <i class="bi bi-info-circle"></i>
                            Enter your own login password to reveal the candidate's current portal password.
                        </div>
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label">New Login Password <span class="text-muted" style="font-weight:400;">(leave blank to keep current)</span></label>
                        <div class="input-with-icon">
                            <input type="password" name="login_password" id="edit_login_password" class="form-control"
                                placeholder="Enter new password only if you want to change it">
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editCandidateModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>
@endsection
