<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Access Denied · {{ config('app.name', 'Liberty LPG Center') }}</title>
    {{-- Self-contained styles so this page renders even if the asset build is unavailable --}}
    <style>
        body { margin: 0; min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 16px;
               background: #F1F5F9; color: #1C2430; font-family: Inter, ui-sans-serif, system-ui, -apple-system, "Segoe UI", sans-serif; }
        .card { background: #fff; border: 1px solid #E5E9EF; border-radius: 16px; padding: 32px; max-width: 420px; width: 100%; text-align: center; }
        .icon { width: 48px; height: 48px; border-radius: 9999px; background: #F7E9E8; color: #B5504B; display: flex;
                align-items: center; justify-content: center; margin: 0 auto 16px; font-size: 22px; font-weight: 700; }
        h1 { font-size: 20px; margin: 0 0 8px; }
        p { font-size: 14px; color: #5B6472; margin: 0 0 24px; line-height: 1.5; }
        a.button { display: inline-block; width: 100%; box-sizing: border-box; padding: 11px 16px; border-radius: 8px;
                   background: #0B3B70; color: #fff; font-size: 14px; font-weight: 600; text-decoration: none; }
        a.button:hover { background: #082A52; }
        a.button:focus-visible { outline: 3px solid #93C5FD; outline-offset: 2px; }
    </style>
</head>
<body>
    <main class="card" role="main" aria-labelledby="error-title">
        <div class="icon" aria-hidden="true">!</div>
        <h1 id="error-title">Access Denied</h1>
        <p>{{ $exception->getMessage() ?: 'You do not have permission to view this page or perform this action.' }}</p>
        @auth
            <a class="button" href="{{ route('dashboard') }}">Return to Dashboard</a>
        @else
            <a class="button" href="{{ route('login') }}">Go to Sign In</a>
        @endauth
    </main>
</body>
</html>
