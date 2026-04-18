@extends('layouts.admin')
@section('title', 'Users')
@section('module-title', 'Users')
@section('module-description', 'Manage admin and recruiter accounts.')
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
            <div class="card-title">All Users</div>
            <div class="card-subtitle">{{ $users->total() }} total records</div>
        </div>
        <div class="d-flex gap-8" style="flex-wrap:wrap;align-items:center;">
            <form method="GET" action="{{ route('admin.users') }}" class="d-flex gap-8" style="flex-wrap:wrap;">
                <input type="text" name="search" class="form-control" placeholder="Search name or email..."
                    value="{{ $search }}" style="width:220px;">
                <select name="role" class="form-control" style="width:160px;" onchange="this.form.submit()">
                    <option value="">All Roles</option>
                    <option value="admin" @selected($role === 'admin')>Admin</option>
                    <option value="recruiter" @selected($role === 'recruiter')>Recruiter</option>
                </select>
                <button type="submit" class="btn btn-outline"><i class="bi bi-search"></i></button>
                @if ($search || $role)
                    <a href="{{ route('admin.users') }}" class="btn btn-outline" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                @endif
            </form>
            @if ($isAdmin)
                <button class="btn btn-primary" onclick="openModal('addUserModal')">
                    <i class="bi bi-plus-lg"></i> Add User
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
                    <th>Status</th>
                    <th>Candidates</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($users as $i => $user)
                    <tr>
                        <td class="text-muted text-sm">{{ $users->firstItem() + $i }}</td>
                        <td>
                            <div class="avatar-row">
                                <div class="avatar-sm">{{ strtoupper(substr($user->name, 0, 1)) }}</div>
                                <span class="avatar-name">{{ $user->name }}</span>
                            </div>
                        </td>
                        <td class="text-muted text-sm">{{ $user->email }}</td>
                        <td><span class="badge {{ $user->role_badge_color }}">{{ $user->role_label }}</span></td>
                        <td>
                            <span class="badge {{ $user->status === 'active' ? 'badge-success' : 'badge-danger' }}">
                                {{ ucfirst($user->status) }}
                            </span>
                        </td>
                        <td class="text-muted text-sm">{{ $user->candidates_count ?? 0 }}</td>
                        <td>
                            @php
                                $canEdit = $isAdmin || $currentUser->id === $user->id;
                                $userPayload = [
                                    'id'     => $user->id,
                                    'name'   => $user->name,
                                    'email'  => $user->email,
                                    'role'   => $user->role,
                                    'status' => $user->status,
                                    'is_self' => $currentUser->id === $user->id,
                                ];
                            @endphp
                            @if ($canEdit)
                                <button class="btn btn-outline btn-sm"
                                    data-user="{{ base64_encode(json_encode($userPayload, JSON_UNESCAPED_SLASHES)) }}"
                                    onclick="editUserFromButton(this)" title="Edit user">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            @endif
                            @if ($isAdmin && $user->role !== 'admin')
                                <form method="POST" action="{{ route('admin.users.destroy', $user->id) }}" style="display:inline;"
                                    onsubmit="return confirm('Delete {{ addslashes($user->name) }}? Their candidates will be reassigned to the admin account.')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-danger btn-sm" title="Delete user">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7">
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

{{-- Add User Modal (admin only) --}}
@if ($isAdmin)
<div class="modal-overlay" id="addUserModal">
    <div class="modal">
        <div class="modal-header">
            <div class="modal-title"><i class="bi bi-person-plus" style="margin-right:6px;"></i> Add New User</div>
            <button class="modal-close" onclick="closeModal('addUserModal')">&times;</button>
        </div>
        <form method="POST" action="{{ route('admin.users.store') }}">
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
                    <div class="form-group">
                        <label class="form-label">Role <span style="color:var(--red-text)">*</span></label>
                        <select name="role" class="form-control" required>
                            <option value="recruiter" @selected(old('role') === 'recruiter')>Recruiter</option>
                            <option value="admin" @selected(old('role') === 'admin')>Admin</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Status <span style="color:var(--red-text)">*</span></label>
                        <select name="status" class="form-control" required>
                            <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                            <option value="inactive" @selected(old('status') === 'inactive')>Inactive</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Password <span style="color:var(--red-text)">*</span></label>
                        <input type="password" name="password" class="form-control" required placeholder="Min 8 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password <span style="color:var(--red-text)">*</span></label>
                        <input type="password" name="password_confirmation" class="form-control" required>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('addUserModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Create User</button>
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
        <form method="POST" id="editUserForm" action="" data-base="{{ url('admin/users') }}">
            @csrf
            @method('PUT')
            <div class="modal-body">
                <div class="form-grid">
                    <div class="form-group">
                        <label class="form-label">Full Name <span style="color:var(--red-text)">*</span></label>
                        <input type="text" name="name" id="edit_user_name" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span style="color:var(--red-text)">*</span></label>
                        <input type="email" name="email" id="edit_user_email" class="form-control" required>
                    </div>
                    @if ($isAdmin)
                        <div class="form-group" id="edit_user_role_group">
                            <label class="form-label">Role <span style="color:var(--red-text)">*</span></label>
                            <select name="role" id="edit_user_role" class="form-control" required>
                                <option value="recruiter">Recruiter</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
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
                        <input type="password" name="password" class="form-control" placeholder="Min 8 characters">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control">
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

@endsection
