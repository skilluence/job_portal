<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - Skilluence</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-square.webp') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="sp-login-body">
    <div class="sp-login-layout">
        <div class="sp-login-left">
            <div class="sp-login-brand">
                <img src="{{ asset('images/logo-white-scaled.png') }}" alt="Skilluence"
                    class="sp-login-brand-wordmark">
                <div class="sp-login-brand-tag">Student Portal</div>
            </div>

            <div class="sp-login-features">
                <div class="sp-feature-item">
                    <div class="sp-feature-icon"><i class="bi bi-graph-up-arrow"></i></div>
                    <div>
                        <div class="sp-feature-title">Track Progress</div>
                        <div class="sp-feature-desc">See your application status and interview updates in one place.</div>
                    </div>
                </div>
                <div class="sp-feature-item">
                    <div class="sp-feature-icon"><i class="bi bi-person-fill-check"></i></div>
                    <div>
                        <div class="sp-feature-title">Manage Profile</div>
                        <div class="sp-feature-desc">Keep your email, profile summary, and contact details current.</div>
                    </div>
                </div>
                <div class="sp-feature-item">
                    <div class="sp-feature-icon"><i class="bi bi-shield-fill-check"></i></div>
                    <div>
                        <div class="sp-feature-title">Secure Access</div>
                        <div class="sp-feature-desc">Your portal account is protected with a dedicated login password.</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="sp-login-right">
            <div class="sp-login-card">
                <div class="sp-login-logo-sm">
                    <img src="{{ asset('images/logo-square.webp') }}" alt="Skilluence">
                    <span>Skilluence</span>
                </div>
                <h1 class="sp-login-title">Welcome back</h1>
                <p class="sp-login-subtitle">Sign in to your student portal</p>

                @if (session('error'))
                    <div class="alert alert-error mb-16">
                        <i class="bi bi-exclamation-circle-fill"></i>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif

                <form method="POST" action="{{ route('student.login.post') }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Email Address</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-envelope sp-input-icon"></i>
                            <input type="email" name="email"
                                class="form-control sp-input @error('email') is-invalid @enderror"
                                placeholder="your.email@example.com" value="{{ old('email') }}" autofocus required>
                        </div>
                        @error('email')
                            <div class="sp-field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="form-label">Password</label>
                        <div class="sp-input-wrap">
                            <i class="bi bi-lock sp-input-icon"></i>
                            <input type="password" name="password" id="spPassword"
                                class="form-control sp-input @error('password') is-invalid @enderror"
                                placeholder="Enter your password" required>
                            <button type="button" class="sp-eye-btn" onclick="toggleSpPass()">
                                <i class="bi bi-eye" id="spEyeIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="sp-field-error">{{ $message }}</div>
                        @enderror
                    </div>

                    <button type="submit" class="sp-login-btn">
                        <i class="bi bi-box-arrow-in-right"></i>
                        Sign In to Portal
                    </button>
                </form>

                <div class="sp-login-footer-note">
                    <i class="bi bi-info-circle"></i>
                    Contact your recruiter if you do not have portal access.
                </div>
            </div>
        </div>
    </div>

    <script>
        function toggleSpPass() {
            var input = document.getElementById('spPassword');
            var icon = document.getElementById('spEyeIcon');
            if (input.type === 'password') {
                input.type = 'text';
                icon.className = 'bi bi-eye-slash';
            } else {
                input.type = 'password';
                icon.className = 'bi bi-eye';
            }
        }
    </script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
