@extends('layouts.auth')
@section('title', 'Sign In')
@section('content')
<div class="auth-card" id="authCard" data-initial-tab="{{ $initialAuthTab ?? 'signin' }}">
    <div class="auth-logo">
        <span class="brand-logo-rect auth-wordmark">
            <img src="{{ asset('images/logo-colored-scaled.png') }}" alt="Skilluence Logo" class="logo-light">
            <img src="{{ asset('images/logo-white-scaled.png') }}" alt="Skilluence Logo" class="logo-dark">
        </span>
    </div>

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

    <div class="auth-tabs">
        <button type="button" class="auth-tab active" data-auth-tab="signin">Sign In</button>
        <button type="button" class="auth-tab" data-auth-tab="signup" @if (!($canSelfRegister ?? false)) disabled title="Sign up is disabled after first account setup." @endif>
            Sign Up
        </button>
    </div>

    <form method="POST" action="{{ route('login.post') }}" class="auth-form active" data-auth-form="signin">
        @csrf
        <input type="hidden" name="auth_mode" value="signin">
        <div class="form-group">
            <label class="form-label" for="signin-email">Email Address</label>
            <input id="signin-email" type="email" name="email" class="form-control" value="{{ old('email') }}"
                placeholder="you@example.com" required autofocus>
        </div>
        <div class="form-group">
            <label class="form-label" for="signin-password">Password</label>
            <div class="input-with-icon">
                <input id="signin-password" type="password" name="password" class="form-control"
                    placeholder="Enter your password" required>
                <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
            </div>
        </div>
        <div class="d-flex align-center justify-between mb-16" style="margin-top:-4px;">
            <label class="d-flex align-center gap-8 text-sm" style="cursor:pointer;">
                <input type="checkbox" name="remember" style="accent-color:var(--blue);">
                <span style="color:var(--text-secondary);">Remember me</span>
            </label>
        </div>
        <button type="submit" class="auth-submit">Sign In</button>
    </form>

    <form method="POST" action="{{ route('register.post') }}" class="auth-form" data-auth-form="signup">
        @csrf
        <input type="hidden" name="auth_mode" value="signup">

        @if ($canSelfRegister ?? false)
            <div class="form-group">
                <label class="form-label" for="signup-name">Full Name</label>
                <input id="signup-name" type="text" name="name" class="form-control"
                    value="{{ old('name') }}" placeholder="e.g. Admin User" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="signup-email">Email Address</label>
                <input id="signup-email" type="email" name="email" class="form-control"
                    value="{{ old('email') }}" placeholder="admin@example.com" required>
            </div>
            <div class="form-group">
                <label class="form-label" for="signup-password">Password</label>
                <div class="input-with-icon">
                    <input id="signup-password" type="password" name="password" class="form-control"
                        placeholder="Minimum 8 characters" required>
                    <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label" for="signup-password-confirmation">Confirm Password</label>
                <div class="input-with-icon">
                    <input id="signup-password-confirmation" type="password" name="password_confirmation" class="form-control"
                        placeholder="Re-enter password" required>
                    <button type="button" class="input-eye-btn password-toggle"><i class="bi bi-eye"></i></button>
                </div>
            </div>
            <button type="submit" class="auth-submit">Create Admin Account</button>
        @else
            <div class="alert mb-0" style="background:var(--blue-light);color:var(--blue-text);border:1px solid rgba(37,99,235,0.2);">
                <i class="bi bi-info-circle-fill"></i>
                <span>Sign up is disabled because an account already exists. Please sign in.</span>
            </div>
        @endif
    </form>
</div>
@endsection
