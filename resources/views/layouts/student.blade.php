<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Student Portal') - Skilluence</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-square.webp') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.2/build/css/intlTelInput.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="stp-body">
    @php
        $topCandidate = $candidate ?? \App\Models\Candidate::find(session('student_id'));
    @endphp

    <header class="stp-topbar">
        <div class="stp-topbar-inner">
            <div class="stp-topbar-brand">
                <img src="{{ asset('images/logo-white-scaled.png') }}" alt="Skilluence" class="stp-topbar-logo">
            </div>

            <nav class="stp-topnav">
                <a href="{{ route('student.dashboard') }}"
                    class="stp-nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('student.info') }}"
                    class="stp-nav-link {{ request()->routeIs('student.info*') ? 'active' : '' }}">
                    <i class="bi bi-person-fill"></i>
                    <span>My Profile</span>
                </a>
            </nav>

            <div class="stp-topbar-right">
                @if ($topCandidate)
                    <div class="stp-user-pill">
                        <div class="stp-user-avatar">{{ $topCandidate->initials }}</div>
                        <div class="stp-user-info">
                            <div class="stp-user-name">{{ $topCandidate->full_name }}</div>
                            <div class="stp-user-status">
                                <span class="stp-status-dot {{ $topCandidate->status }}"></span>
                                {{ ucfirst($topCandidate->status) }}
                            </div>
                        </div>
                    </div>
                @endif
                <form method="POST" action="{{ route('student.logout') }}">
                    @csrf
                    <button type="submit" class="stp-logout-btn">
                        <i class="bi bi-box-arrow-right"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </div>
    </header>

    <main class="stp-main">
        <div class="stp-content">
            @yield('content')
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js" integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo=" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/intl-tel-input@25.3.2/build/js/intlTelInput.min.js"></script>
    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
