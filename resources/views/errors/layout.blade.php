<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>@yield('title') · {{ config('app.name') }}</title>
    {{-- Self-contained on purpose: error pages must render even when assets or the database are unavailable. --}}
    <style>
        :root { --bg: #f8fafc; --card: #fff; --fg: #0f172a; --muted: #475569; --accent: #4f46e5; --accent-fg: #fff; --border: #e2e8f0; color-scheme: light dark; }
        @media (prefers-color-scheme: dark) { :root { --bg: #0b1120; --card: #111827; --fg: #f1f5f9; --muted: #94a3b8; --accent: #818cf8; --accent-fg: #0b1120; --border: #1f2937; } }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 24px 16px; background: var(--bg); color: var(--fg);
               font: 16px/1.6 ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }
        main { width: 100%; max-width: 30rem; background: var(--card); border: 1px solid var(--border); border-radius: 12px; padding: 32px; }
        .code { font: 600 0.8rem/1 ui-monospace, Menlo, monospace; letter-spacing: .08em; color: var(--muted); text-transform: uppercase; }
        h1 { font-size: 1.6rem; line-height: 1.25; margin: 10px 0 8px; }
        p { margin: 0; color: var(--muted); }
        .actions { display: flex; flex-wrap: wrap; gap: 12px; margin-top: 24px; }
        a { display: inline-flex; align-items: center; padding: 10px 16px; border-radius: 8px; font-weight: 600; text-decoration: none; border: 1px solid var(--border); color: var(--fg); }
        a.primary { background: var(--accent); border-color: var(--accent); color: var(--accent-fg); }
        a:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
    </style>
</head>
<body>
    <main>
        <div class="code">Error @yield('code')</div>
        <h1>@yield('title')</h1>
        <p>@yield('message')</p>
        <div class="actions">
            @hasSection('actions')
                @yield('actions')
            @else
                <a class="primary" href="javascript:history.back()">Go back</a>
                <a href="{{ url('/') }}">Home page</a>
            @endif
        </div>
    </main>
</body>
</html>
