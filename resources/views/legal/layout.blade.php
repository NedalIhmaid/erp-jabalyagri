<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') — {{ config('legal.company') }}</title>
    <style>
        :root { color-scheme: light dark; }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: -apple-system, "Segoe UI", Tahoma, "Noto Naskh Arabic", sans-serif;
            line-height: 1.85;
            background: #f7f7f5;
            color: #1c1c1a;
        }
        .wrap { max-width: 46rem; margin: 0 auto; padding: 3rem 1.25rem 5rem; }
        header { border-bottom: 2px solid #133316; padding-bottom: 1.25rem; margin-bottom: 2rem; }
        h1 { font-size: 1.6rem; margin: 0 0 .35rem; color: #133316; }
        h2 { font-size: 1.15rem; margin: 2.25rem 0 .5rem; color: #133316; }
        .meta { font-size: .875rem; color: #5c5c56; margin: 0; }
        ul { padding-inline-start: 1.25rem; }
        li { margin-bottom: .35rem; }
        a { color: #133316; }
        section[dir="ltr"] { text-align: left; margin-top: 4rem; border-top: 1px solid #d8d8d2; padding-top: 2rem; }
        footer { margin-top: 3rem; font-size: .875rem; color: #5c5c56; border-top: 1px solid #d8d8d2; padding-top: 1.25rem; }
        @media (prefers-color-scheme: dark) {
            body { background: #16171a; color: #e8e8e4; }
            h1, h2, a { color: #8fbf95; }
            header { border-bottom-color: #8fbf95; }
            .meta, footer { color: #a3a39c; }
            section[dir="ltr"], footer { border-top-color: #33342f; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <header>
            <h1>@yield('heading')</h1>
            <p class="meta">{{ config('legal.company') }} — @yield('heading_en')</p>
            <p class="meta">تاريخ السريان / Effective date: {{ config('legal.effective_date') }}</p>
        </header>

        @yield('content')

        <footer>
            {{ config('legal.company') }} · {{ config('legal.address') }}<br>
            <a href="mailto:{{ config('legal.email') }}">{{ config('legal.email') }}</a>
            @if (config('legal.phone'))
                · {{ config('legal.phone') }}
            @endif
        </footer>
    </div>
</body>
</html>
