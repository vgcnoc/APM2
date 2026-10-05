<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title inertia>ISP Management System</title>
    <meta name="description" content="Aplikasi Manajemen Pelanggan & Infrastruktur ISP">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

    @routes
    <script>
        window.addEventListener('error', function(event) {
            alert("Terjadi Error Javascript: " + event.message + "\nFile: " + event.filename + "\nBaris: " + event.lineno);
        });
        window.addEventListener('unhandledrejection', function(event) {
            alert("Terjadi Promise Error: " + event.reason);
        });
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @inertiaHead
</head>
<body class="font-sans antialiased">
    @inertia
</body>
</html>
