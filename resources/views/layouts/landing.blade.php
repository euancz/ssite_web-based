<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="The official SSITE community at Mabalacat City College. Sign in for student news, activities, achievements, documents, and organization reports.">
    <meta name="theme-color" content="#8ad5f4">
    <title>SSITE | Mabalacat City College</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ssite - revamp logo 2.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@500;600;700&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <script src="{{ asset('js/landing.js') }}" defer></script>
</head>
<body class="landing-page" id="top">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="landing-header">
        <div class="header-inner wrap">
            <a class="brand" href="{{ route('landing') }}" aria-label="SSITE home"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" width="48" height="48"><span><strong>SSITE</strong><small>MABALACAT CITY COLLEGE</small></span></a>
            <nav class="desktop-nav" aria-label="Main navigation"><a href="#community">Community</a><a href="#get-app">The app</a><a href="#questions">FAQs</a></nav>
            <a class="header-signin" href="{{ route('microsoft.login') }}" aria-label="Sign in with Microsoft"><x-microsoft-mark /> Sign in <x-icon name="arrow-right" size="sm" /></a>
        </div>
        <nav class="mobile-nav wrap" aria-label="Mobile navigation"><a href="#community">Community</a><a href="#get-app">Download app</a><a href="#questions">FAQs</a></nav>
    </header>
    <main id="main-content" tabindex="-1">@yield('content')</main>
    <footer class="landing-footer wrap">
        <a class="brand footer-brand" href="{{ route('landing') }}"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" width="40" height="40"><span><strong>SSITE</strong><small>Student Society in Information<br>Technology Education</small></span></a>
        <p>&copy; {{ date('Y') }} SSITE<br>Mabalacat City College</p>
        <a class="back-top" href="#top">Back to top <x-icon name="arrow-up" size="sm" /></a>
    </footer>
</body>
</html>
