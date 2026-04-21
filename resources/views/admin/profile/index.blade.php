@extends('layouts.admin')
@section('title', 'Profile & Settings')
@section('module-title', 'Profile & Settings')
@section('module-description', 'Update your personal information, change password, and manage preferences.')
@section('content')

{{-- Flash --}}
@if(session('success'))
<div class="toast-container" id="flashToast">
    <div class="toast toast-success"><i class="bi bi-check-circle-fill"></i><span>{{ session('success') }}</span></div>
</div>
@endif
@if(session('error'))
<div class="toast-container" id="errorToast">
    <div class="toast toast-error"><i class="bi bi-exclamation-circle-fill"></i><span>{{ session('error') }}</span></div>
</div>
@endif

@php
    $canEditEmail = (bool) ($user?->isAdmin());
@endphp

{{-- Profile Hero --}}
<div class="profile-hero-card mb-24">
    <div class="profile-avatar-xl">
        {{ $user ? strtoupper(substr($user->name, 0, 1)) : 'A' }}
    </div>
    <div>
        <div class="profile-hero-name">{{ $user?->name ?? 'Admin User' }}</div>
        <div class="profile-hero-email">{{ $user?->email ?? 'admin@skilluence.com' }}</div>
        <span class="badge {{ $user?->role_badge_color ?? 'badge-primary' }}" style="margin-top:6px;display:inline-flex;">
            <i class="bi bi-shield-fill" style="margin-right:4px;"></i>
            {{ ucfirst($user?->role ?? 'admin') }}
        </span>
    </div>
</div>

<div class="content-grid">

    {{-- Edit Profile --}}
    <div class="card">
        <div class="card-header">
            <div>
                <div class="card-title"><i class="bi bi-person-fill" style="color:var(--blue);margin-right:6px;"></i>Edit Profile</div>
                <div class="card-subtitle">
                    @if ($canEditEmail)
                        Update your name and email address
                    @else
                        Update your name. Email can be changed by admin only.
                    @endif
                </div>
            </div>
        </div>

        @if($errors->hasAny(['name','email']))
        <div class="alert alert-error mb-16">
            <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;"></i>
            <div>
                @foreach (['name', 'email'] as $errorField)
                    @foreach ($errors->get($errorField) as $message)
                        <div>{{ $message }}</div>
                    @endforeach
                @endforeach
            </div>
        </div>
        @endif

        <form method="POST" action="{{ route('admin.profile.update') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Full Name <span style="color:var(--red-text)">*</span></label>
                <input type="text" name="name" class="form-control"
                       value="{{ old('name', $user?->name) }}" required placeholder="Your full name">
            </div>
            <div class="form-group">
                <label class="form-label">Email Address <span style="color:var(--red-text)">*</span></label>
                <input type="email" name="email" class="form-control"
                       value="{{ $canEditEmail ? old('email', $user?->email) : ($user?->email) }}"
                       placeholder="your@email.com"
                       {{ $canEditEmail ? 'required' : 'readonly' }}
                       style="{{ $canEditEmail ? '' : 'background:var(--main-bg);cursor:not-allowed;' }}">
                @if (!$canEditEmail)
                    <div class="text-sm text-muted" style="margin-top:6px;">
                        <i class="bi bi-lock-fill"></i> Only admin can change email address.
                    </div>
                @endif
            </div>
            <div class="form-group">
                <label class="form-label">Role</label>
                <input type="text" class="form-control"
                       value="{{ ucfirst($user?->role ?? 'admin') }}"
                       readonly style="background:var(--main-bg);cursor:not-allowed;">
            </div>
            <div class="form-group mb-0">
                <label class="form-label">Status</label>
                <input type="text" class="form-control"
                       value="{{ ucfirst($user?->status ?? 'active') }}"
                       readonly style="background:var(--main-bg);cursor:not-allowed;">
            </div>
            <div style="margin-top:20px;">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg"></i> Save Profile
                </button>
            </div>
        </form>
    </div>

    <div>
        {{-- Change Password --}}
        <div class="card mb-16">
            <div class="card-header">
                <div>
                    <div class="card-title"><i class="bi bi-shield-lock-fill" style="color:var(--blue);margin-right:6px;"></i>Change Password</div>
                    <div class="card-subtitle">Use a strong, unique password</div>
                </div>
            </div>

            @if($errors->has('current_password'))
            <div class="alert alert-error mb-16">
                <i class="bi bi-exclamation-circle-fill" style="flex-shrink:0;"></i>
                <span>{{ $errors->first('current_password') }}</span>
            </div>
            @endif

            <form method="POST" action="{{ route('admin.profile.password') }}">
                @csrf
                <div class="form-group">
                    <label class="form-label">Current Password</label>
                    <div class="input-with-icon">
                        <input type="password" name="current_password" class="form-control" placeholder="Current password">
                        <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">New Password</label>
                    <div class="input-with-icon">
                        <input type="password" name="password" class="form-control" placeholder="Min. 8 characters">
                        <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="form-label">Confirm New Password</label>
                    <div class="input-with-icon">
                        <input type="password" name="password_confirmation" class="form-control" placeholder="Re-enter new password">
                        <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                    </div>
                </div>
                <div style="margin-top:20px;">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-shield-check"></i> Update Password
                    </button>
                </div>
            </form>
        </div>

        {{-- Preferences --}}
        <div class="card">
            <div class="card-header mb-0">
                <div class="card-title"><i class="bi bi-sliders" style="color:var(--blue);margin-right:6px;"></i>Preferences</div>
            </div>
            <div style="margin-top:16px;" class="d-flex align-center justify-between">
                <div>
                    <div style="font-size:13px;font-weight:600;color:var(--text-primary);">Dark Mode</div>
                    <div class="text-sm text-muted">Toggle light or dark interface</div>
                </div>
                <button class="theme-toggle">
                    <i class="bi bi-moon-stars-fill icon-moon"></i>
                    <i class="bi bi-sun-fill icon-sun"></i>
                </button>
            </div>
        </div>
    </div>

</div>

@endsection
