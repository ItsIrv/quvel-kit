<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'QuVel Kit' }}</title>
    <meta name="description" content="{{ $description ?? 'A high-performance hybrid starter kit built on Laravel & Quasar.' }}">

    @vite(['resources/css/app.css'])

    <!-- Fonts & Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;600;700&display=swap" rel="stylesheet">

    @stack('head')
</head>

<body class="hero-container text-white flex flex-col items-center justify-center min-h-screen p-6">
    <div class="max-w-3xl bg-white/10 backdrop-blur-md rounded-lg shadow-lg p-8 text-center">
        @yield('content')
    </div>

    @stack('scripts')
</body>

</html>
