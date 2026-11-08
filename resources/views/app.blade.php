<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>ISP Management System</title>
    <meta name="description" content="Aplikasi Manajemen Pelanggan & Infrastruktur ISP">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate" />
    <meta http-equiv="Pragma" content="no-cache" />
    <meta http-equiv="Expires" content="0" />

    @php
        $favicon = \App\Models\Setting::get('app_favicon');
    @endphp
    @if($favicon)
        <link rel="icon" href="{{ asset('storage/' . $favicon) }}">
        <link rel="shortcut icon" href="{{ asset('storage/' . $favicon) }}">
    @else
        <link rel="icon" href="/favicon.ico">
    @endif

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- Dark Mode & Background Pattern Init Script (Anti-FOUC) -->
    <script>
        (function() {
            try {
                var theme = localStorage.getItem('apm_theme');
                if (theme === 'dark' || (!theme && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                    document.documentElement.classList.add('dark');
                } else {
                    document.documentElement.classList.remove('dark');
                }
                var pattern = localStorage.getItem('apm_bg_pattern') || 'royal-batik';
                document.documentElement.setAttribute('data-bg-pattern', pattern);
            } catch (e) {}
        })();

        // Auto-recovery jika chunk Vite lama 404 (mencegah layar putih saat browser membuka sesi lama)
        window.addEventListener('error', function(e) {
            if (e && e.target && (e.target.tagName === 'SCRIPT' || e.target.tagName === 'LINK')) {
                var src = e.target.src || e.target.href || '';
                if (src.indexOf('/build/') !== -1) {
                    var lastReload = sessionStorage.getItem('apm_chunk_reload');
                    var now = Date.now();
                    if (!lastReload || now - parseInt(lastReload, 10) > 8000) {
                        sessionStorage.setItem('apm_chunk_reload', String(now));
                        window.location.reload();
                    }
                }
            }
        }, true);
    </script>

    @routes
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased @guest auth-device-scale @endguest" @auth style="zoom: 80%;" @endauth>
    @inertia
</body>
</html>
