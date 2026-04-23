@extends('layouts.admin')
@section('title', 'Leave')
@section('module-title', 'Leave')
@section('module-description', 'Submit leave requests, review approval status, and track staff leave history.')
@section('content')

@if (session('success'))
    <div class="toast-container" id="flashToast">
        <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
    </div>
@endif

@if ($errors->any())
    <div class="alert alert-error mb-16">
        <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;font-size:16px;"></i>
        <div>@foreach ($errors->all() as $error)<div>{{ $error }}</div>@endforeach</div>
    </div>
@endif

<style>
.leave-grid { display:grid; grid-template-columns:minmax(320px, 420px) minmax(0, 1fr); gap:20px; align-items:start; }
.leave-card-title { font-size:15px; font-weight:700; color:var(--text-primary); margin-bottom:4px; }
.leave-card-sub { font-size:12px; color:var(--text-muted); margin-bottom:18px; }
.leave-help { font-size:11.5px; color:var(--text-muted); margin-top:8px; }
.leave-history-head { display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap; }
.leave-status-note { display:inline-flex; align-items:center; gap:6px; font-size:11px; color:var(--text-muted); }
.leave-session-chip { display:inline-flex; align-items:center; gap:6px; padding:3px 8px; border-radius:999px; background:var(--main-bg); color:var(--text-muted); font-size:11px; font-weight:600; }
.leave-actions { display:flex; gap:6px; flex-wrap:wrap; }
.leave-doc-link { font-size:12px; font-weight:600; text-decoration:none; }
.leave-doc-link:hover { text-decoration:underline; }
@media (max-width:960px) {
    .leave-grid { grid-template-columns:1fr; }
}
</style>

<div class="leave-grid">
    <div class="card">
        @php
            $formLeave = $editableLeave;
            $isEditMode = (bool) $formLeave;
            $selectedType = old('leave_type', $formLeave?->leave_type ?? 'full_day');
        @endphp
        <div class="leave-card-title">{{ $isEditMode ? 'Edit Leave' : 'Add Leave' }}</div>
        <div class="leave-card-sub">
            @if ($isAdmin)
                {{ $isEditMode ? 'Admin can update staff leave details here.' : 'Admin-added leave is approved immediately.' }}
            @else
                Submit your leave request for approval.
            @endif
        </div>

        <form method="POST"
              action="{{ $isEditMode ? route('admin.leaves.update', $formLeave) : route('admin.leaves.store') }}"
              enctype="multipart/form-data">
            @csrf
            @if ($isEditMode)
                @method('PUT')
            @endif

            @if ($isAdmin)
                <div class="form-group">
                    <label class="form-label">Staff <span style="color:var(--red-text)">*</span></label>
                    <select name="user_id" class="form-control" required>
                        <option value="">Select Staff</option>
                        @foreach ($staffMembers as $staff)
                            <option value="{{ $staff->id }}" @selected((string) old('user_id', $formLeave?->user_id) === (string) $staff->id)>
                                {{ $staff->name }} ({{ $staff->role_label }})
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="form-group">
                <label class="form-label">Leave Date <span style="color:var(--red-text)">*</span></label>
                <input type="text"
                       name="leave_date"
                       class="form-control js-leave-date"
                       value="{{ old('leave_date', $formLeave?->leave_date?->format('Y-m-d')) }}"
                       placeholder="Select a future date"
                       autocomplete="off"
                       required>
                <div class="leave-help">Past dates and the current date are disabled.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Leave Type <span style="color:var(--red-text)">*</span></label>
                <select name="leave_type" id="leaveTypeSelect" class="form-control" required>
                    <option value="full_day" @selected($selectedType === 'full_day')>Full Day</option>
                    <option value="half_day" @selected($selectedType === 'half_day')>Half Day</option>
                </select>
            </div>

            <div class="form-group" id="halfDayGroup" style="display:{{ $selectedType === 'half_day' ? 'block' : 'none' }};">
                <label class="form-label">Half Day Session <span style="color:var(--red-text)">*</span></label>
                <select name="half_day_session" id="halfDaySessionSelect" class="form-control" {{ $selectedType === 'half_day' ? 'required' : '' }}>
                    <option value="">Select Session</option>
                    <option value="first_half" @selected(old('half_day_session', $formLeave?->half_day_session) === 'first_half')>First Half</option>
                    <option value="second_half" @selected(old('half_day_session', $formLeave?->half_day_session) === 'second_half')>Second Half</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Reason <span style="color:var(--red-text)">*</span></label>
                <textarea name="reason"
                          class="form-control"
                          rows="3"
                          maxlength="255"
                          placeholder="Add a short leave reason..."
                          required>{{ old('reason', $formLeave?->reason) }}</textarea>
                <div class="leave-help">Maximum 255 characters.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Document <span class="text-muted text-sm">(optional)</span></label>
                <input type="file" name="document" class="form-control" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">
                @if ($formLeave?->document_path)
                    <div class="leave-help">
                        Current document:
                        <a class="leave-doc-link" href="{{ route('admin.leaves.document', $formLeave) }}">View / Download</a>
                    </div>
                @endif
            </div>

            <div class="d-flex gap-8" style="justify-content:flex-end; flex-wrap:wrap;">
                @if ($isEditMode)
                    <a href="{{ route('admin.leaves') }}" class="btn btn-outline">Cancel</a>
                @endif
                <button type="submit" class="btn btn-primary">
                    <i class="bi {{ $isEditMode ? 'bi-check-lg' : 'bi-plus-lg' }}"></i>
                    {{ $isEditMode ? 'Save Changes' : 'Submit Leave' }}
                </button>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="leave-history-head">
            <div>
                <div class="leave-card-title">Leave History</div>
                <div class="leave-card-sub">
                    @if ($isAdmin)
                        Review all leave requests and manage approvals.
                    @else
                        View your leave requests and approval status.
                    @endif
                </div>
            </div>

            @if ($isAdmin)
                <form method="GET" action="{{ route('admin.leaves') }}" class="d-flex gap-8" style="flex-wrap:wrap;align-items:flex-end;">
                    <div class="form-group mb-0">
                        <label class="form-label">Staff Name</label>
                        <input type="text" name="staff" class="form-control" value="{{ $staffSearch }}" placeholder="Search staff name..." style="width:220px;">
                    </div>
                    <div class="form-group mb-0">
                        <label class="form-label" style="visibility:hidden;">x</label>
                        <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
                    </div>
                    @if ($staffSearch !== '')
                        <div class="form-group mb-0">
                            <label class="form-label" style="visibility:hidden;">x</label>
                            <a href="{{ route('admin.leaves') }}" class="btn btn-outline"><i class="bi bi-x-lg"></i></a>
                        </div>
                    @endif
                </form>
            @endif
        </div>

        <div class="table-wrapper">
            <table>
                <thead>
                    <tr>
                        @if ($isAdmin)<th>Staff</th>@endif
                        <th>Date</th>
                        <th>Type</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Document</th>
                        <th>Requested</th>
                        <th>Approval</th>
                        @if ($isAdmin)<th>Actions</th>@endif
                    </tr>
                </thead>
                <tbody>
                    @forelse ($leaves as $leave)
                        <tr>
                            @if ($isAdmin)
                                <td>
                                    <div style="font-weight:600;">{{ $leave->user?->name ?? 'Unknown' }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $leave->user?->role_label ?? '-' }}</div>
                                </td>
                            @endif
                            <td>
                                <div style="font-weight:600;">{{ $leave->leave_date?->format('M d, Y') }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $leave->leave_date?->format('D') }}</div>
                            </td>
                            <td>
                                <div style="font-weight:600;">{{ $leave->leave_type_label }}</div>
                                @if ($leave->half_day_session_label)
                                    <div class="leave-session-chip">{{ $leave->half_day_session_label }}</div>
                                @endif
                            </td>
                            <td style="max-width:220px;">
                                <div style="font-size:12px;color:var(--text-secondary);white-space:pre-wrap;">{{ $leave->reason }}</div>
                            </td>
                            <td>
                                <span class="badge {{ $leave->status_badge_class }}">{{ ucfirst($leave->status) }}</span>
                            </td>
                            <td>
                                @if ($leave->document_path)
                                    <a class="leave-doc-link" href="{{ route('admin.leaves.document', $leave) }}">Document</a>
                                @else
                                    <span class="text-muted text-sm">-</span>
                                @endif
                            </td>
                            <td>
                                <div class="text-sm">{{ $leave->creator?->name ?? '-' }}</div>
                                <div class="text-muted" style="font-size:11px;">{{ $leave->created_at?->format('M d, Y h:i A') }}</div>
                            </td>
                            <td>
                                @if ($leave->status === 'approved')
                                    <div class="text-sm">{{ $leave->approver?->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $leave->approved_at?->format('M d, Y h:i A') ?? '-' }}</div>
                                @elseif ($leave->status === 'rejected')
                                    <div class="text-sm">{{ $leave->rejector?->name ?? '-' }}</div>
                                    <div class="text-muted" style="font-size:11px;">{{ $leave->rejected_at?->format('M d, Y h:i A') ?? '-' }}</div>
                                @else
                                    <span class="leave-status-note"><i class="bi bi-hourglass-split"></i> Awaiting admin approval</span>
                                @endif
                            </td>
                            @if ($isAdmin)
                                <td>
                                    <div class="leave-actions">
                                        <a href="{{ route('admin.leaves', array_filter(['staff' => $staffSearch, 'edit' => $leave->id])) }}"
                                           class="btn btn-outline btn-sm" title="Edit leave">
                                            <i class="bi bi-pencil"></i>
                                        </a>
                                        @if ($leave->status !== 'approved')
                                            <form method="POST" action="{{ route('admin.leaves.approve', $leave) }}" style="display:inline;">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-primary btn-sm" title="Approve">
                                                    <i class="bi bi-check-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if ($leave->isRejectable($dashboardToday) && $leave->status !== 'rejected')
                                            <form method="POST" action="{{ route('admin.leaves.reject', $leave) }}" style="display:inline;"
                                                  onsubmit="return confirm('Reject this leave request?')">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="btn btn-danger btn-sm" title="Reject">
                                                    <i class="bi bi-x-lg"></i>
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                </td>
                            @endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $isAdmin ? 9 : 7 }}">
                                <div class="page-empty mb-0">
                                    <i class="bi bi-calendar-x"></i>
                                    <p>No leave records found yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($leaves->hasPages())
            <div class="pagination-wrap">
                <span class="pagination-info">
                    Showing {{ $leaves->firstItem() }}-{{ $leaves->lastItem() }} of {{ $leaves->total() }}
                </span>
                {{ $leaves->links('pagination.custom') }}
            </div>
        @endif
    </div>
</div>

<script>
function toggleHalfDayField() {
    var leaveType = document.getElementById('leaveTypeSelect');
    var halfDayGroup = document.getElementById('halfDayGroup');
    var halfDaySelect = document.getElementById('halfDaySessionSelect');
    var isHalfDay = leaveType && leaveType.value === 'half_day';

    if (halfDayGroup) {
        halfDayGroup.style.display = isHalfDay ? 'block' : 'none';
    }

    if (halfDaySelect) {
        halfDaySelect.required = isHalfDay;
        if (!isHalfDay) {
            halfDaySelect.value = '';
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    toggleHalfDayField();

    var leaveType = document.getElementById('leaveTypeSelect');
    if (leaveType) {
        leaveType.addEventListener('change', toggleHalfDayField);
    }

    flatpickr('.js-leave-date', {
        dateFormat: 'Y-m-d',
        minDate: new Date().fp_incr(1),
        disableMobile: true
    });
});
</script>

@endsection
