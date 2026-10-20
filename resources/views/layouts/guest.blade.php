<!DOCTYPE html>
<html lang="id">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <title>Masuk · Perpus Aman</title>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body>
        <div class="auth-wrap">
            <div class="auth-card">
                <a class="brand" href="/">
                    <span class="brand-mark">P</span>
                    <span>Perpus<span class="brand-light">Aman</span></span>
                </a>
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
