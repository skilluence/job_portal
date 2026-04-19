<!DOCTYPE html>
<html lang="en" data-theme="light">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Dashboard') - Skilluence Admin</title>
    <script>
        (function() {
            document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light');
        })();
    </script>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-square.webp') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ filemtime(public_path('css/app.css')) }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>

<body>
    @php
        $authUser = auth()->user();
        $isAdmin = $authUser?->role === 'admin';
    @endphp

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <a href="{{ route('admin.dashboard') }}" class="sidebar-logo">
                <img src="{{ asset('images/logo-square.webp') }}" alt="Skilluence">
                <span class="sidebar-logo-text">Skilluence</span>
            </a>
            <button class="sidebar-toggle" id="sidebarToggle" title="Pin sidebar">
                <i class="bi bi-layout-sidebar icon-unlock"></i>
                <i class="bi bi-layout-sidebar-inset icon-lock"></i>
            </button>
        </div>

        <nav class="sidebar-nav">
            <span class="nav-section-label">Main Menu</span>
            <a href="{{ route('admin.dashboard') }}"
                class="nav-item {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                <i class="bi bi-grid-1x2-fill"></i><span class="nav-text">Dashboard</span>
            </a>
            <a href="{{ route('admin.candidates') }}"
                class="nav-item {{ request()->routeIs('admin.candidates*') ? 'active' : '' }}">
                <i class="bi bi-people-fill"></i><span class="nav-text">Candidates</span>
            </a>
            @if (!$authUser?->isRecruiter())
            <a href="{{ route('admin.users') }}"
                class="nav-item {{ request()->routeIs('admin.users*') ? 'active' : '' }}">
                <i class="bi bi-person-badge-fill"></i><span class="nav-text">Recruiters</span>
            </a>
            @endif

            <span class="nav-section-label">System</span>
            <a href="{{ route('admin.audit-logs') }}"
                class="nav-item {{ request()->routeIs('admin.audit-logs*') ? 'active' : '' }}">
                <i class="bi bi-journal-text"></i><span class="nav-text">Audit Logs</span>
            </a>
            @if ($isAdmin)
                <a href="{{ route('admin.import-export') }}"
                    class="nav-item {{ request()->routeIs('admin.import-export*') ? 'active' : '' }}">
                    <i class="bi bi-arrow-left-right"></i><span class="nav-text">Import / Export</span>
                </a>
            @endif
        </nav>

        <div class="sidebar-footer">
            <a href="{{ route('admin.profile') }}" class="sidebar-user" style="text-decoration:none;">
                @auth
                    <div class="user-avatar" style="text-transform:uppercase;">{{ substr(Auth::user()->name, 0, 1) }}</div>
                    <div class="user-info">
                        <div class="user-name">{{ Auth::user()->name }}</div>
                        <div class="user-email">{{ Auth::user()->email }}</div>
                    </div>
                @else
                    <div class="user-avatar">U</div>
                    <div class="user-info">
                        <div class="user-name">User</div>
                        <div class="user-email">-</div>
                    </div>
                @endauth
                <div class="user-settings-btn" title="Profile">
                    <i class="bi bi-gear-fill"></i>
                </div>
            </a>
        </div>
    </aside>

    <div class="main-wrapper">
        <header class="main-header">
            <button class="header-sidebar-toggle" id="headerSidebarToggle" title="Toggle sidebar">
                <i class="bi bi-layout-sidebar"></i>
            </button>
            <div class="header-info">
                <div class="header-title">@yield('module-title', 'Dashboard')</div>
                <div class="header-description">@yield('module-description', 'Welcome to Skilluence.')</div>
            </div>
            <div class="header-actions">
                @auth
                    <a href="{{ route('admin.profile') }}" class="header-user-chip" style="text-decoration:none;" title="Profile">
                        <div class="header-avatar">{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</div>
                        <span class="header-user-name">{{ Auth::user()->name }}</span>
                    </a>
                @endauth
                <button class="theme-toggle" title="Toggle theme">
                    <i class="bi bi-moon-stars-fill icon-moon"></i>
                    <i class="bi bi-sun-fill icon-sun"></i>
                </button>
                <form method="POST" action="{{ route('logout') }}" style="display:inline-flex;">
                    @csrf
                    <button type="submit" class="header-logout-btn" title="Sign out">
                        <i class="bi bi-box-arrow-right"></i> Sign Out
                    </button>
                </form>
            </div>
        </header>
        <main class="main-content">@yield('content')</main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="{{ asset('js/app.js') }}?v={{ filemtime(public_path('js/app.js')) }}"></script>
    @stack('scripts')
</body>

</html>
