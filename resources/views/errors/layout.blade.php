<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('code') — Skilluence</title>
    <link rel="icon" type="image/webp" href="{{ asset('images/logo-square.webp') }}">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            padding: 24px;
        }
        .err-card {
            background: #fff;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 8px 40px rgba(37,99,235,0.10);
            padding: 48px 40px 40px;
            max-width: 480px;
            width: 100%;
            text-align: center;
        }
        .err-icon-wrap {
            width: 80px; height: 80px;
            border-radius: 50%;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 24px;
        }
        .err-code {
            font-size: 72px; font-weight: 800; letter-spacing: -3px;
            background: linear-gradient(135deg, #1e3a6e, #2563eb);
            -webkit-background-clip: text; -webkit-text-fill-color: transparent;
            background-clip: text;
            line-height: 1; margin-bottom: 8px;
        }
        .err-title {
            font-size: 20px; font-weight: 700; color: #1e293b;
            margin-bottom: 10px; letter-spacing: -0.3px;
        }
        .err-desc {
            font-size: 14px; color: #64748b; line-height: 1.6;
            margin-bottom: 32px;
        }
        .err-actions { display: flex; justify-content: center; }
        .err-btn-primary {
            display: inline-flex; align-items: center; gap: 8px;
            padding: 12px 28px; border-radius: 10px; border: none;
            background: linear-gradient(135deg, #1e3a6e, #2563eb);
            color: #fff; font-size: 14px; font-weight: 600;
            text-decoration: none; cursor: pointer;
            box-shadow: 0 3px 14px rgba(37,99,235,0.30);
            transition: box-shadow .2s, transform .2s;
            font-family: inherit;
        }
        .err-btn-primary:hover { box-shadow: 0 6px 20px rgba(37,99,235,0.40); transform: translateY(-1px); }
        .err-divider {
            border: none; border-top: 1px solid #f1f5f9;
            margin: 32px 0 20px;
        }
        .err-brand {
            font-size: 12px; color: #94a3b8; display: flex;
            align-items: center; justify-content: center; gap: 6px;
        }
        .err-brand img { height: 18px; opacity: .5; }
        @media (max-width: 480px) {
            .err-card { padding: 36px 24px 32px; border-radius: 16px; }
            .err-code  { font-size: 56px; }
            .err-title { font-size: 18px; }
            .err-btn-primary { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body>
    @php
        if (auth()->check()) {
            $dashUrl = route('admin.dashboard');
        } elseif (session('student_id')) {
            $dashUrl = route('student.dashboard');
        } else {
            $dashUrl = route('login');
        }
    @endphp

    <div class="err-card">
        @yield('icon')

        <div class="err-code">@yield('code')</div>
        <div class="err-title">@yield('title')</div>
        <p class="err-desc">@yield('description')</p>

        <div class="err-actions">
            <a href="{{ $dashUrl }}" class="err-btn-primary">
                {{-- Inline SVG house icon — no CDN font dependency --}}
                <svg width="15" height="15" fill="currentColor" viewBox="0 0 16 16" style="flex-shrink:0;">
                    <path d="M8.707 1.5a1 1 0 0 0-1.414 0L.646 8.146a.5.5 0 0 0 .708.708L2 8.207V13.5A1.5 1.5 0 0 0 3.5 15h9a1.5 1.5 0 0 0 1.5-1.5V8.207l.646.647a.5.5 0 0 0 .708-.708L13 5.793V2.5a.5.5 0 0 0-.5-.5h-1a.5.5 0 0 0-.5.5v1.293L8.707 1.5ZM13 7.207V13.5a.5.5 0 0 1-.5.5h-2v-4a.5.5 0 0 0-.5-.5h-4a.5.5 0 0 0-.5.5v4h-2a.5.5 0 0 1-.5-.5V7.207l5-5 5 5Z"/>
                </svg>
                @if(auth()->check()) Go to Dashboard
                @elseif(session('student_id')) Go to Dashboard
                @else Go to Login
                @endif
            </a>
        </div>

        <hr class="err-divider">
        <div class="err-brand">
            <img src="{{ asset('images/logo-square.webp') }}" alt="Skilluence">
            Skilluence Portal
        </div>
    </div>
</body>
</html>
