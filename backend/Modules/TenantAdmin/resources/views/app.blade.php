<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport"
    content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
  <meta name="csrf-token" content="{{ csrf_token() }}">

  <!-- Primary Meta Tags -->
  <title>TenantAdmin - {{ config('app.name', 'Laravel') }}</title>
  <meta name="title" content="TenantAdmin - {{ config('app.name', 'Laravel') }}">
  <meta name="description" content="{{ config('app.description', 'Tenant Administration Portal') }}">

  <!-- Mobile-specific Meta Tags -->
  <meta name="theme-color" content="#ffffff">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="{{ config('app.name', 'Laravel') }}">
  <meta name="format-detection" content="telephone=no">

  <!-- Favicon -->
  <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('favicon-32x32.png') }}">
  <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('favicon-16x16.png') }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('apple-touch-icon.png') }}">

  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.bunny.net">
  <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />

  <!-- Vite Assets -->
  @vite(['resources/js/app.ts', 'resources/css/app.css'], 'build-tenantadmin')
</head>

<body class="font-sans antialiased">
  <div id="app"></div>
</body>

</html>
