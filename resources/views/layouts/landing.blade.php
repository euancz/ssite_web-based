@inject('siteContent', 'App\Support\SiteContent')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layouts.partials.font-preloads')
    <meta name="description" content="The official SSITE website at Mabalacat City College. Sign in for articles, activities, achievements, documents, and financial reports.">
    <meta name="theme-color" content="#7bd0ed">
    <title>@yield('title', 'SSITE | Mabalacat City College')</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ssite-favicon.svg') }}">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}?v={{ filemtime(public_path('css/landing.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/interface-polish.css') }}">
    <link rel="stylesheet" href="{{ asset('css/landing-chrome.css') }}?v={{ filemtime(public_path('css/landing-chrome.css')) }}">
    @include('layouts.partials.typography')
    <script src="{{ asset('js/landing.js') }}" defer></script>
    @if (auth()->user()?->isAdviser())
        <link rel="stylesheet" href="{{ asset('css/site-editor.css') }}?v={{ filemtime(public_path('css/site-editor.css')) }}">
    @endif
</head>
@php($landingUrl = route(request()->routeIs('adviser.site.landing') ? 'adviser.site.landing' : 'landing'))
<body class="landing-page" id="top">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="landing-header">
        <div class="header-inner wrap">
            <a class="brand" href="{{ $landingUrl }}" aria-label="SSITE home"><img src="{{ $siteContent->image('landing-header', 'logo_image') }}" alt="" width="54" height="54"><span><strong class="landing-brand-full">{{ $siteContent->value('landing-header', 'brand_line_one') }}<br>{{ $siteContent->value('landing-header', 'brand_line_two') }}</strong><strong class="landing-brand-short">{{ $siteContent->value('landing-header', 'initials') }}</strong><small>{{ $siteContent->value('landing-header', 'campus') }}</small></span></a>
            <nav class="desktop-nav" aria-label="Main navigation"><a href="{{ $landingUrl }}#community">{{ $siteContent->value('landing-header', 'community_link') }}</a><a href="{{ $landingUrl }}#get-app">{{ $siteContent->value('landing-header', 'mobile_link') }}</a><a href="#questions">{{ $siteContent->value('landing-header', 'policies_link') }}</a></nav>
            <x-section-pencil section="landing-header" />
            <a class="header-signin" href="{{ route('microsoft.login') }}" aria-label="Sign in with Microsoft"><x-microsoft-mark />{{ $siteContent->value('landing-header', 'sign_in_link') }}<x-icon name="arrow-right" size="sm" /></a>
        </div>
        <nav class="mobile-nav wrap" aria-label="Mobile navigation"><a href="{{ $landingUrl }}#community">{{ $siteContent->value('landing-header', 'community_link') }}</a><a href="{{ $landingUrl }}#get-app">{{ $siteContent->value('landing-header', 'mobile_link') }}</a><a href="#questions">{{ $siteContent->value('landing-header', 'policies_link') }}</a></nav>
    </header>
    <main id="main-content" tabindex="-1">@yield('content')</main>
    <footer class="landing-site-footer">
        <div class="site-footer-content">
            {{-- Left: org info --}}
            <div class="footer-organization">
                <a class="footer-brand" href="{{ $landingUrl }}">
                    {{-- LOGO PLACEHOLDER --}}
                    <img src="{{ $siteContent->image('landing-footer', 'organization_logo_image') }}"
                         alt="SSITE Logo"
                        class="footer-logo">
                    <span class="footer-brand-name">
                        {{ $siteContent->value('landing-footer', 'organization_line_one') }}<br>
                        {{ $siteContent->value('landing-footer', 'organization_line_two') }}
                    </span>
                </a>
                <p class="footer-description">
                    {{ $siteContent->value('landing-footer', 'description') }}
                </p>

                <div class="footer-badges">
                    {{-- BADGE PLACEHOLDERS --}}
                    <img src="{{ $siteContent->image('landing-footer', 'institute_logo_image') }}" alt="ICS logo" class="footer-badge">
                    <img src="{{ $siteContent->image('landing-footer', 'college_logo_image') }}" alt="Mabalacat City College logo" class="footer-badge">
                    <img src="{{ $siteContent->image('landing-footer', 'society_logo_image') }}" alt="SSITE logo" class="footer-badge">
                </div>
            </div>

            {{-- Right: contact --}}
            <div class="footer-contact">
                <h4 class="footer-heading">{{ $siteContent->value('landing-footer', 'contact_heading') }} <x-section-pencil section="landing-footer" /></h4>
                <ul class="footer-contact-list">
                    <li class="footer-contact-item">
                        <a href="{{ $siteContent->value('landing-footer', 'map_url') }}" class="footer-contact-button" target="_blank" rel="noopener noreferrer">
                            <x-icon name="map-pin" size="md" class="footer-contact-icon" />
                            <span>{{ $siteContent->value('landing-footer', 'address') }}</span>
                        </a>
                    </li>
                    <li class="footer-contact-item">
                        <a href="{{ $siteContent->value('landing-footer', 'facebook_url') }}" class="footer-contact-button" target="_blank" rel="noopener noreferrer">
                            <x-icon name="facebook" size="md" class="footer-contact-icon" />
                            <span>{{ $siteContent->value('landing-footer', 'facebook_label') }}</span>
                        </a>
                    </li>
                    <li class="footer-contact-item">
                        <a href="mailto:{{ $siteContent->value('landing-footer', 'contact_email') }}" class="footer-contact-button">
                            <x-icon name="mail" size="md" class="footer-contact-icon" />
                            <span>{{ $siteContent->value('landing-footer', 'contact_email') }}</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-return"><a class="back-top" href="#top">{{ $siteContent->value('landing-footer', 'return_link') }} <x-icon name="arrow-up" size="sm" /></a></div>
        <div class="footer-bottom">
            <p class="footer-copyright">
                &copy; {{ date('Y') }} Student Society in Information Technology Education, Mabalacat City College
            </p>
        </div>
    </footer>
</body>
</html>
