<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.font-preloads')
    <title>@yield('title', 'Workspace') | SSITE</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ssite-favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}">
    @vite('resources/js/app.js')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/ui-foundation.css') }}">
    <link rel="stylesheet" href="{{ asset('css/ssite-upgrade.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}?v={{ filemtime(public_path('css/mobile-responsive.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/workspace-experience.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace-refresh.css') }}?v={{ filemtime(public_path('css/workspace-refresh.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/main-site-dropdowns.css') }}?v={{ filemtime(public_path('css/main-site-dropdowns.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/interface-polish.css') }}">
    @include('layouts.partials.typography')
    <script src="{{ asset('js/dropdown-position.js') }}" defer></script>
    @include('layouts.partials.search-assets')
</head>
<body class="site-body workspace-site-body">
    @php($showAccountPhotoDialog = auth()->user()?->hasCompletedProfile())
    @include('layouts.partials.workspace')
    @include('layouts.partials.account-photo-dialog', ['user' => auth()->user()])
    @stack('scripts')
    @include('layouts.partials.account-scripts')
</body>
</html>
