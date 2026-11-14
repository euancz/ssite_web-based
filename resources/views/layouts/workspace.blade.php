<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Workspace') | SSITE</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ssite-favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}">
    @vite('resources/js/app.js')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/ui-foundation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ssite-upgrade.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace-experience.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace-refresh.css') }}">
    <link rel="stylesheet" href="{{ asset('css/main-site-dropdowns.css') }}">
    <link rel="stylesheet" href="{{ asset('css/interface-polish.css') }}">
    <script src="{{ asset('js/dropdown-position.js') }}" defer></script>
</head>
<body class="site-body workspace-site-body">
    @include('layouts.partials.workspace')
    @stack('scripts')
    @include('layouts.partials.account-scripts')
</body>
</html>
