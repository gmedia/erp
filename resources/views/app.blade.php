<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    {{-- Inline script to detect system dark mode preference and apply it immediately --}}
    <script>
        (function() {
            const appearance = '{{ $appearance ?? 'system' }}';

            if (appearance === 'system') {
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (prefersDark) {
                    document.documentElement.classList.add('dark');
                }
            }
        })();
    </script>

    {{-- Inline style to set the HTML background color based on our theme in app.css --}}
    <style>
        html {
            background-color: oklch(1 0 0);
        }

        html.dark {
            background-color: oklch(0.145 0 0);
        }
    </style>

    @php
        $appCompanyName = $companyName ?? config('app.name', 'Laravel');
    @endphp

    <title inertia>{{ $appCompanyName }}</title>

    <script>
        // Set company name in window before React loads
        window.__APP_COMPANY_NAME__ = '{{ $appCompanyName }}';
    </script>

    <link rel="icon" href="/asset-files/dokfin/favicon.ico" sizes="any">
    <link rel="icon" href="/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="/apple-touch-icon.png">

    @viteReactRefresh
    @vite(['resources/js/app.tsx'])
</head>

<body class="font-sans antialiased">
    <div id="app"></div>
</body>

</html>
