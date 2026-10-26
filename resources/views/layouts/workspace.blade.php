<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Workspace') | SSITE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace.css') }}">
    @vite('resources/js/app.js')
    @stack('styles')
    <link rel="stylesheet" href="{{ asset('css/ui-foundation.css') }}">
</head>
<body class="site-body workspace-site-body">
    @include('layouts.partials.workspace')
    @stack('scripts')
    @include('layouts.partials.account-scripts')
</body>
</html>
