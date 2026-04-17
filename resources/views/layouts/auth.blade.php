<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Sign In') — Skilluence</title>
    <script>(function(){ document.documentElement.setAttribute('data-theme', localStorage.getItem('theme') || 'light'); })();</script>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-square.webp') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
</head>
<body>
    <button class="auth-theme-btn theme-toggle" title="Toggle theme">
        <i class="bi bi-moon-stars-fill icon-moon"></i>
        <i class="bi bi-sun-fill icon-sun"></i>
    </button>
    <div class="auth-page">
        <div class="auth-wrap">@yield('content')</div>
    </div>
    <script src="{{ asset('js/app.js') }}"></script>
</body>
</html>
