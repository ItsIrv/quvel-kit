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

  <!-- PWA Meta Tags -->
  <link rel="manifest" href="{{ asset('build-tenantadmin/manifest.json') }}">

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
