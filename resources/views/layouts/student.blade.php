<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Student Portal') - Skilluence</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-square.webp') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>

<body class="sp-body">
    @php
        $sidebarCandidate = $candidate ?? \App\Models\Candidate::find(session('student_id'));
    @endphp

    <div class="sp-layout">
        <aside class="sp-sidebar">
            <div class="sp-sidebar-header">
                <img src="{{ asset('images/logo-white-scaled.png') }}" alt="Skilluence" class="sp-sidebar-wordmark">
            </div>

            <div class="sp-profile-block">
                <div class="sp-avatar-lg">
                    {{ $sidebarCandidate ? strtoupper(substr($sidebarCandidate->full_name, 0, 1)) : 'S' }}
                </div>
                <div class="sp-sidebar-name">
                    {{ $sidebarCandidate?->full_name ?? 'Student' }}
                </div>
                <div class="sp-sidebar-tag">
                    @if ($sidebarCandidate)
                        <span class="sp-status-dot {{ $sidebarCandidate->status }}"></span>
                        {{ ucfirst($sidebarCandidate->status) }}
                    @else
                        Student Portal
                    @endif
                </div>
            </div>

            <nav class="sp-nav">
                <a href="{{ route('student.dashboard') }}"
                    class="sp-nav-item {{ request()->routeIs('student.dashboard') ? 'active' : '' }}">
                    <i class="bi bi-grid-1x2-fill"></i>
                    <span>Dashboard</span>
                </a>
                <a href="{{ route('student.info') }}"
                    class="sp-nav-item {{ request()->routeIs('student.info*') ? 'active' : '' }}">
                    <i class="bi bi-person-fill"></i>
                    <span>My Profile</span>
                </a>
            </nav>

            <div class="sp-sidebar-footer">
                <form method="POST" action="{{ route('student.logout') }}">
                    @csrf
                    <button type="submit" class="sp-logout-btn">
                        <i class="bi bi-box-arrow-left"></i>
                        <span>Sign Out</span>
                    </button>
                </form>
            </div>
        </aside>

        <main class="sp-main">
            <div class="sp-content">
                @yield('content')
            </div>
        </main>
    </div>

    <script src="{{ asset('js/app.js') }}"></script>
</body>

</html>
