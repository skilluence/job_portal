@extends('layouts.admin')
@section('title', 'Recruiters')
@section('module-title', 'Recruiters')
@section('module-description', 'Manage admin, team manager, and recruiter accounts.')
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

<div class="card">
    <div class="card-header" style="flex-wrap:wrap;gap:12px;">
        <div>
            <div class="card-title">{{ $isManager ? 'My Team' : 'All Recruiters' }}</div>
            <div class="card-subtitle">{{ $users->total() }} total records</div>
        </div>
        <div class="d-flex gap-8" style="flex-wrap:wrap;align-items:center;">
            @if (!$isManager)
            <form method="GET" action="{{ route('admin.users') }}" class="d-flex gap-8" style="flex-wrap:wrap;">
                <input type="text" name="search" class="form-control" placeholder="Search name or email..."
                    value="{{ $search }}" style="width:220px;">
                <select name="role" class="form-control" style="width:160px;" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="admin"     @selected($role === 'admin')>Admin</option>
                    <option value="manager"   @selected($role === 'manager')>Team Manager</option>
                    <option value="recruiter" @selected($role === 'recruiter')>Recruiter</option>
                </select>
                <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
                @if ($search || $role)
                    <a href="{{ route('admin.users') }}" class="btn btn-outline" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
            @endif
            @if ($isAdmin || $isManager)
                <button class="btn btn-primary" onclick="openAddUserModal()">
                    <i class="bi bi-plus-lg"></i> {{ $isManager ? 'Add Recruiter' : 'Add User' }}
                </button>
            @endif
        </div>
    </div>

    <div class="table-wrapper">
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Team Manager</th>
                    <th>Status</th>
                    <th>Candidates</th>
                    <th>Created At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $i => $user)
                    @php
                        $canEdit = $isAdmin || $isManager || $currentUser->id === $user->id;
                        $canDelete = ($isAdmin || $isManager) && $user->role !== 'admin' && $currentUser->id !== $user->id;
                        // Report eye: admin sees all non-admin; manager sees own-team recruiters (not self)
                        $canViewReport = ($isAdmin && $user->role !== 'admin')
                            || ($isManager && $user->id !== $currentUser->id && $user->team_manager_id === $currentUser->id);
                        $candidateCount = $user->role === 'manager'
                            ? (int) (($user->direct_managed_candidates_count ?? 0) + ($user->team_candidates_count ?? 0))
                            : (int) ($user->candidates_count ?? 0);
                        $userPayload = [
                            'id'              => $user->id,
                            'name'            => $user->name,
                            'email'           => $user->email,
                            'role'            => $user->role,
                            'is_admin'        => $user->role === 'admin',
                            'status'          => $user->status,
                            'team_manager_id' => $user->team_manager_id,
                            'is_self'         => $currentUser->id === $user->id,
                        ];
                    @endphp
                    <tr>
                        <td class="text-muted text-sm">{{ $users->firstItem() + $i }}</td>
                        <td>
                            <div class="avatar-row">
                                <div class="avatar-sm">{{ $user->initials }}</div>
                                <span class="avatar-name">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-sm">{{ $user->email }}</td>
                        <td><span class="badge {{ $user->role_badge_color }}">{{ $user->role_label }}</span></td>
                        <td class="text-sm text-muted">
                            {{ $user->teamManager?->name ?? ($user->role === 'recruiter' ? '—' : '') }}
                        </td>
                        <td>
                            <span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td class="text-muted text-sm">{{ $candidateCount }}</td>
                        <td class="text-sm">
                            <div>{{ $user->created_at?->format('M d, Y') ?? '-' }}</div>
                            @if ($user->created_at)
                                <div class="text-muted" style="font-size:11px;">{{ $user->created_at->format('h:i A') }}</div>
                            @endif
                        </td>
                        <td>
                            <div class="tbl-actions">
                                @if ($canViewReport)
                                    <a href="{{ route('admin.users.report', $user) }}"
                                       class="btn btn-outline btn-sm" title="View monthly report">
                                        <i class="bi bi-eye"></i>
                                    </a>
                                @endif
                                @if ($canEdit)
                                    <button class="btn btn-outline btn-sm"
                                        data-user="{{ base64_encode(json_encode($userPayload, JSON_UNESCAPED_SLASHES)) }}"
                                        onclick="editUserFromButton(this)" title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                @endif
                                @if ($canDelete)
                                    <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" style="display:inline;margin:0;"
                                        onsubmit="return confirm('Delete {{ addslashes($user->name) }}? Their candidates will be reassigned to admin.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-danger btn-sm" title="Delete">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9">
                            <div class="page-empty mb-0">
                                <i class="bi bi-person-check"></i>
                                <p>No users found{{ ($search || $role) ? ' matching your filters' : '' }}.</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($users->hasPages())
        <div class="pagination-wrap">
            <span class="pagination-info">
                Showing {{ $users->firstItem() }}-{{ $users->lastItem() }} of {{ $users->total() }}
            </span>
            {{ $users->links('pagination.custom') }}
        </div>
    @endif
</div>

{{-- Add User Modal --}}
@if ($isAdmin || $isManager)
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title">
                <i class="bi bi-person-plus" style="margin-right:6px;"></i>
                {{ $isManager ? 'Add Recruiter' : 'Add New User' }}
            </div>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}" autocomplete="off">
            @csrf
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--red-text)">*</span></label>
                        <input type="text" name="name" class="form-control" required value="{{ old('name') }}">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:var(--red-text)">*</span></label>
                        <input type="email" name="email" class="form-control" required value="{{ old('email') }}">
                    </div>

                    @if ($isAdmin)
                    {{-- Admin can create manager/recruiter only --}}
                    <div class="form-group">
                        <label class="form-label">Role <span style="color:var(--red-text)">*</span></label>
                        <select name="role" id="add_user_role" class="form-control" required onchange="handleAddRoleChange(this)">
                            <option value="recruiter" @selected(old('role','recruiter') === 'recruiter')>Recruiter</option>
                            <option value="manager"   @selected(old('role') === 'manager')>Team Manager</option>
                        </select>
                    </div>
                    @else
                    {{-- Manager always creates recruiter --}}
                    <input type="hidden" name="role" value="recruiter">
                    @endif

                    <div class="form-group">
                        <label class="form-label">Status <span style="color:var(--red-text)">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="active"   @selected(old('status','active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>

                    {{-- Team Manager assignment — shown when role=recruiter --}}
                    @if ($isAdmin)
                    <div class="form-group" id="add_team_manager_group" style="display:{{ old('role','recruiter') === 'recruiter' ? 'block' : 'none' }};">
                        <label class="form-label">Assign to Team Manager <span style="color:var(--red-text)">*</span></label>
                        @if ($managers->isEmpty())
                        <div class="alert alert-error" style="margin:0;padding:10px 14px;font-size:12.5px;">
                            <i class="bi bi-exclamation-triangle-fill"></i>
                            No team managers found. Please add a Team Manager first before creating recruiters.
                        </div>
                        <input type="hidden" name="team_manager_id" value="">
                        @else
                        <select name="team_manager_id" id="add_team_manager_id" class="form-control"
                            {{ old('role','recruiter') === 'recruiter' ? 'required' : '' }}>
                            <option value="">— Select Team Manager —</option>
                            @foreach ($managers as $mgr)
                                <option value="{{ $mgr->id }}" @selected(old('team_manager_id') == $mgr->id)>{{ $mgr->name }}</option>
                            @endforeach
                        </select>
                        @endif
                    </div>
                    @elseif ($isManager)
                    {{-- Manager's recruiters are auto-assigned, no field needed --}}
                    <input type="hidden" name="team_manager_id" value="{{ $currentUser->id }}">
                    @endif

                    <div class="form-group">
                        <label class="form-label">Password <span style="color:var(--red-text)">*</span></label>
                        <div class="input-with-icon">
                            <input type="password" name="password" class="form-control" required placeholder="Min 8 characters" autocomplete="new-password">
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password <span style="color:var(--red-text)">*</span></label>
                        <div class="input-with-icon">
                            <input type="password" name="password_confirmation" class="form-control" required autocomplete="new-password">
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="add_user_submit">
                    <i class="bi bi-check-lg"></i> Create User
                </button>
            </div>
        </form>
    </div>
</div>
@endif

{{-- Edit User Modal --}}
<div class="modal-overlay" id="editUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-pencil-square" style="margin-right:6px;"></i> Edit User</div>
            <button class="modal-close" onclick="closeModal('editUserModal')">&times;</button>
        </div>
        <form method="POST" id="editUserForm" action="" data-base="{{ url('admin/users') }}" autocomplete="off">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--red-text)">*</span></label>
                        <input type="text" name="name" id="edit_user_name" class="form-control" required>
                    </div>
                    <div class="form-group" id="edit_user_email_group">
                        <label class="form-label">Email <span style="color:var(--red-text)">*</span></label>
                        <input type="email" name="email" id="edit_user_email" class="form-control" required>
                    </div>
                    @if ($isAdmin)
                        <div class="form-group" id="edit_user_email_locked_group" style="display:none;">
                            <label class="form-label">Email</label>
                            <input type="text" id="edit_user_email_locked_text" class="form-control" value="" disabled>
                        </div>
                    @endif
                    @if ($isAdmin)
                        <div class="form-group" id="edit_user_role_group">
                            <label class="form-label">Role <span style="color:var(--red-text)">*</span></label>
                            <select name="role" id="edit_user_role" class="form-control" required onchange="handleEditRoleChange()">
                                <option value="recruiter">Recruiter</option>
                                <option value="manager">Team Manager</option>
                            </select>
                        </div>
                        <div class="form-group" id="edit_user_role_locked_group" style="display:none;">
                            <label class="form-label">Role</label>
                            <input type="text" id="edit_user_role_locked_text" class="form-control" value="Admin" disabled>
                            <input type="hidden" id="edit_user_role_hidden" value="">
                        </div>
                        <div class="form-group" id="edit_user_status_group">
                            <label class="form-label">Status <span style="color:var(--red-text)">*</span></label>
                            <select name="status" id="edit_user_status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="form-group" id="edit_user_status_locked_group" style="display:none;">
                            <label class="form-label">Status</label>
                            <input type="text" id="edit_user_status_locked_text" class="form-control" value="" disabled>
                        </div>
                        <div class="form-group" id="edit_team_manager_group" style="display:none;">
                            <label class="form-label">Assign to Team Manager <span style="color:var(--red-text)">*</span></label>
                            <select name="team_manager_id" id="edit_user_team_manager" class="form-control">
                                <option value="">— Select Team Manager —</option>
                                @foreach ($managers as $mgr)
                                    <option value="{{ $mgr->id }}">{{ $mgr->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @elseif ($isManager)
                        <div class="form-group">
                            <label class="form-label">Status <span style="color:var(--red-text)">*</span></label>
                            <select name="status" id="edit_user_status" class="form-control" required>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                    @endif
                    <div class="form-group">
                        <label class="form-label">New Password <span class="text-muted text-sm">(optional)</span></label>
                        <div class="input-with-icon">
                            <input type="password" name="password" class="form-control" placeholder="Min 8 characters" autocomplete="new-password">
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <div class="input-with-icon">
                            <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password">
                            <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('editUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
var hasManagers = {{ $managers->isNotEmpty() ? 'true' : 'false' }};

function openAddUserModal() {
    @if ($isAdmin && $managers->isEmpty())
    // Warn admin if no managers when recruiter is the default
    @endif
    openModal('addUserModal');
}

function handleAddRoleChange(select) {
    var role = select.value;
    var tmGroup = document.getElementById('add_team_manager_group');
    var tmSelect = document.getElementById('add_team_manager_id');

    if (role === 'recruiter') {
        if (tmGroup) tmGroup.style.display = 'block';
        if (tmSelect) tmSelect.required = true;
    } else {
        if (tmGroup) tmGroup.style.display = 'none';
        if (tmSelect) { tmSelect.required = false; tmSelect.value = ''; }
    }
}

function handleEditRoleChange() {
    var roleSelect = document.getElementById('edit_user_role');
    if (!roleSelect || roleSelect.disabled) return;
    var role = roleSelect.value;
    var tmGroup  = document.getElementById('edit_team_manager_group');
    var tmSelect = document.getElementById('edit_user_team_manager');

    if (role === 'recruiter') {
        if (tmGroup) tmGroup.style.display = 'block';
        if (tmSelect) tmSelect.required = true;
    } else {
        if (tmGroup) tmGroup.style.display = 'none';
        if (tmSelect) { tmSelect.required = false; }
    }
}
</script>

@endsection
