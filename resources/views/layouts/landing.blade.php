<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.font-preloads')
    <meta name="description" content="The official SSITE community at Mabalacat City College. Sign in for student news, activities, achievements, documents, and organization reports.">
    <meta name="theme-color" content="#7bd0ed">
    <title>@yield('title', 'SSITE | Mabalacat City College')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ssite-favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <link rel="stylesheet" href="{{ asset('css/interface-polish.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-chrome.css') }}">
    @include('layouts.partials.typography')
    <script src="{{ asset('js/landing.js') }}" defer></script>
</head>
<body class="landing-page" id="top">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="landing-header">
        <div class="header-inner wrap">
            <a class="brand" href="{{ route('landing') }}" aria-label="SSITE home"><img src="{{ asset('images/SSITE LOGO.png') }}" alt="" width="54" height="54"><span><strong class="landing-brand-full">STUDENT SOCIETY IN INFORMATION<br>TECHNOLOGY EDUCATION</strong><strong class="landing-brand-short">SSITE</strong><small>Mabalacat City College</small></span></a>
            <nav class="desktop-nav" aria-label="Main navigation"><a href="{{ route('landing') }}#community">Community</a><a href="{{ route('landing') }}#get-app">The app</a><a href="#questions">Policies</a></nav>
            <a class="header-signin" href="{{ route('microsoft.login') }}" aria-label="Sign in with Microsoft"><x-microsoft-mark /> Sign in <x-icon name="arrow-right" size="sm" /></a>
        </div>
        <nav class="mobile-nav wrap" aria-label="Mobile navigation"><a href="{{ route('landing') }}#community">Community</a><a href="{{ route('landing') }}#get-app">Download app</a><a href="#questions">Policies</a></nav>
    </header>
    <main id="main-content" tabindex="-1">@yield('content')</main>
    <footer class="landing-site-footer">
        <div class="site-footer-content">
            {{-- Left: org info --}}
            <div class="footer-organization">
                <a class="footer-brand" href="{{ route('landing') }}">
                    {{-- LOGO PLACEHOLDER --}}
                    <img src="{{ asset('images/SSITE LOGO.png') }}"
                         alt="SSITE Logo"
                        class="footer-logo">
                    <span class="footer-brand-name">
                        STUDENT SOCIETY IN INFORMATION<br>
                        TECHNOLOGY EDUCATION
                    </span>
                </a>
                <p class="footer-description">
                    The Official Organization of Information Technology Education in MCC
                </p>

                <div class="footer-badges">
                    {{-- BADGE PLACEHOLDERS --}}
                    <img src="{{ asset('images/ICS LOGO 1.png') }}" alt="Badge 1" class="footer-badge">
                    <img src="{{ asset('images/MCC LOGO 1.png') }}" alt="Badge 2" class="footer-badge">
                    <img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="Badge 3" class="footer-badge">
                </div>
            </div>

            {{-- Right: contact --}}
            <div class="footer-contact">
                <h4 class="footer-heading">Contact Us</h4>
                <ul class="footer-contact-list">
                    <li class="footer-contact-item">
                        <a href="https://www.google.com/maps/search/?api=1&amp;query=Mabalacat%20City%20College%20Dolores" class="footer-contact-button" target="_blank" rel="noopener noreferrer">
                            <x-icon name="map-pin" size="md" class="footer-contact-icon" />
                            <span>Dolores, Mabalacat City College</span>
                        </a>
                    </li>
                    <li class="footer-contact-item">
                        <a href="https://www.facebook.com/ICSSSITE" class="footer-contact-button" target="_blank" rel="noopener noreferrer">
                            <x-icon name="facebook" size="md" class="footer-contact-icon" />
                            <span>SSITE on Facebook</span>
                        </a>
                    </li>
                    <li class="footer-contact-item">
                        <a href="mailto:ssite@gmail.com" class="footer-contact-button">
                            <x-icon name="mail" size="md" class="footer-contact-icon" />
                            <span>ssite@gmail.com</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-return"><a class="back-top" href="#top">Back to top <x-icon name="arrow-up" size="sm" /></a></div>
        <div class="footer-bottom">
            <p class="footer-copyright">
                &copy; {{ date('Y') }} Student Society in Information Technology — Mabalacat City College
            </p>
        </div>
    </footer>
</body>
</html>
