<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SSITE') | Student Society in Information Technology Education</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- App styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>
<body class="site-body">
    {{-- Role shortcuts live in the account menu; the shared Home destination stays public for every role. --}}
    @php($roleHomeUrl = url('/'))

    {{-- ============================= NAVBAR ============================= --}}
    {{-- The completion screen hides site navigation so an incomplete account cannot browse away. --}}
    @if (request()->routeIs('profile.complete'))
        <header class="site-header profile-minimal-header">
            <div class="site-header-main">
                <img src="{{ asset('images/SSITE LOGO.png') }}" alt="SSITE Logo" class="site-logo">
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="site-button">Logout</button>
                </form>
            </div>
        </header>
    @else
    <header class="site-header">
        {{-- Top bar: logo + search + icons --}}
        <div class="site-header-main">
            <a href="{{ $roleHomeUrl }}" class="site-brand">
                {{-- LOGO PLACEHOLDER --}}
                <img src="{{ asset('images/SSITE LOGO.png') }}"
                     alt="SSITE Logo"
                     class="site-logo">
                <span class="site-brand-name">
                    <span class="brand-full">STUDENT SOCIETY IN INFORMATION<br>TECHNOLOGY EDUCATION</span>
                    <span class="brand-short">SSITE</span>
                </span>
            </a>

            <div class="site-header-actions">
                {{-- SECURITY: Both search forms use the public suggestions endpoint; suggestions contain visible content only. --}}
                <div class="site-search-desktop search-shell">
                    <form method="GET" action="{{ route('search.index') }}" class="search-form">
                        <input type="text" name="q" value="{{ request()->routeIs('search.index') && isset($query) ? $query : (is_string(request()->query('q')) ? mb_substr(request()->query('q'), 0, 100) : '') }}" placeholder="Discover something from SSITE..."
                               class="site-search-input" role="combobox" aria-autocomplete="list" aria-expanded="false"
                               aria-controls="desktop-search-list" autocomplete="off">
                        <button type="submit" class="search-submit-icon" aria-label="Search">
                            <svg class="site-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                            </svg>
                        </button>
                    </form>
                    <div id="desktop-search-list" class="search-suggestions hidden" role="listbox" aria-label="Search suggestions"></div>
                </div>

                {{-- search icon (mobile/tablet only, opens the mobile search field below) --}}
                <button type="button"
                        aria-label="Search"
                        onclick="document.getElementById('mobile-search').classList.toggle('hidden')"
                        class="icon-button mobile-search-toggle">
                    <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                    </svg>
                </button>

                {{-- LOGGED OUT: Login button --}}
                {{-- Guests see sign-in controls; authenticated users instead see their account menu below. --}}
                @guest
                    <div class="login-area">
                        <button type="button"
                                id="login-toggle"
                                aria-controls="login-popover"
                                aria-expanded="{{ $errors->any() || session()->has('error') ? 'true' : 'false' }}"
                                class="site-button login-toggle-button">
                            Login
                        </button>

                        <section id="login-popover"
                                 aria-label="Log in to your account"
                                 class="login-popover {{ $errors->any() || session()->has('error') ? '' : 'hidden' }}">
                            <div class="login-popover-image" aria-hidden="true"></div>
                            <div class="login-popover-content">
                                <h2>Welcome</h2>
                                <p class="login-popover-subtitle">Log in to your account to continue</p>
                                {{-- Show login or Microsoft callback errors inside the open sign-in panel. --}}
                                @if(session('error'))
                                    <p class="login-popover-error" role="alert">{{ session('error') }}</p>
                                @endif

                                <form method="POST" action="{{ route('login') }}">
                                    @csrf
                                    <div class="login-popover-field">
                                        <label for="popover-email">Email</label>
                                        <input type="email"
                                               id="popover-email"
                                               name="email"
                                               value="{{ old('email') }}"
                                               autocomplete="email"
                                               required
                                               autofocus>
                                        @error('email')
                                            <span class="login-popover-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <div class="login-popover-field">
                                        <label for="popover-password">Password</label>
                                        <input type="password"
                                               id="popover-password"
                                               name="password"
                                               autocomplete="current-password"
                                               required>
                                        @error('password')
                                            <span class="login-popover-error">{{ $message }}</span>
                                        @enderror
                                    </div>

                                    <label class="login-popover-remember">
                                        <input type="checkbox" name="remember">
                                        <span>Remember me</span>
                                    </label>

                                    <button type="submit" class="login-popover-submit">Login</button>
                                </form>

                                <div class="login-popover-divider" aria-hidden="true">
                                    <span>OR</span>
                                </div>

                                <a href="{{ route('microsoft.login') }}" class="login-popover-microsoft">
                                    <span class="microsoft-mark" aria-hidden="true">
                                        <span></span><span></span><span></span><span></span>
                                    </span>
                                    <span>Continue with Microsoft</span>
                                </a>
                            </div>
                        </section>
                    </div>
                @endguest

                {{-- LOGGED IN: notifications + account icons --}}
                {{-- Account controls are available to every signed-in role. --}}
                @auth
                    <div class="notification-menu-wrapper" id="notification-menu-wrapper"
                         data-summary-url="{{ route('notifications.summary') }}"
                         data-read-url-template="{{ route('notifications.read', '00000000-0000-4000-8000-000000000000') }}"
                         data-avatar-url-template="{{ route('notifications.avatar', '00000000-0000-4000-8000-000000000000') }}">
                        <button type="button" id="notifications-toggle" aria-label="Notifications"
                                aria-controls="notifications-dropdown" aria-expanded="false" class="icon-button account-notifications">
                            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                            </svg>
                            <span id="notification-badge" class="notification-badge {{ $notificationUnreadCount ? '' : 'hidden' }}">{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>
                        </button>
                        <section id="notifications-dropdown" class="notification-dropdown hidden" aria-label="Notifications">
                            <div class="notification-dropdown-heading">
                                <strong>Notifications</strong>
                                <form method="POST" action="{{ route('notifications.read-all') }}">
                                    @csrf
                                    <button type="submit" class="notification-mark-all">Mark all as read</button>
                                </form>
                            </div>
                            <div id="notification-list" class="notification-list" aria-live="polite">
                                <p class="notification-empty">Open notifications to load your latest updates.</p>
                            </div>
                            <a href="{{ route('notifications.index') }}" class="notification-view-all">View all</a>
                        </section>
                    </div>

                    <div class="account-menu-wrapper" id="account-menu-wrapper">
                        <button type="button"
                                id="account-menu-toggle"
                                aria-label="Account menu"
                                aria-haspopup="menu"
                                aria-controls="account-menu"
                                aria-expanded="false"
                                class="icon-button account-toggle">
                            {{-- Keep the existing circular account button styling while showing the signed-in user's avatar. --}}
                            <x-ui.avatar :user="auth()->user()" size="sm" />
                        </button>

                        <div id="account-menu" class="account-menu hidden" role="menu" aria-label="Account menu">
                            <div class="account-menu-identity" role="presentation">
                                <x-ui.avatar :user="auth()->user()" size="md" />
                                <div class="account-menu-identity-copy">
                                    <strong>{{ auth()->user()->name }}</strong>
                                    <span>{{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}</span>
                                    @if (auth()->user()->position())
                                        <span>{{ auth()->user()->position() }}</span>
                                    @endif
                                </div>
                            </div>
                            @if (auth()->user()->hasCompletedProfile())
                                <a href="{{ route('profile.edit') }}" class="account-menu-action" role="menuitem">
                                    <svg aria-hidden="true" class="icon-small" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0A17.9 17.9 0 0112 21.75c-2.7 0-5.25-.6-7.5-1.65z"/>
                                    </svg>
                                    <span>My Profile</span>
                                </a>
                            @endif
                            <button type="button" class="account-menu-action" role="menuitem">
                                <svg aria-hidden="true" class="icon-small" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06-1.5 2.6-.08-.02a1.65 1.65 0 00-1.78.76l-.04.07h-3l-.02-.08a1.65 1.65 0 00-1.4-1.14 1.65 1.65 0 00-1.73.92l-.04.08-2.84-1.02.02-.08a1.65 1.65 0 00-.76-1.78l-.07-.04v-3l.08-.02a1.65 1.65 0 001.14-1.4 1.65 1.65 0 00-.92-1.73l-.08-.04 1.02-2.84.08.02a1.65 1.65 0 001.78-.76l.04-.07h3l.02.08a1.65 1.65 0 001.4 1.14 1.65 1.65 0 001.73-.92l.04-.08 2.84 1.02-.02.08a1.65 1.65 0 00.76 1.78l.07.04v3l-.08.02A1.65 1.65 0 0019.4 15z"/>
                                </svg>
                                <span>Settings</span>
                            </button>
                            @if (auth()->user()->canPost() || auth()->user()->can('review-posts') || auth()->user()->can('manage-users') || auth()->user()->can('manage-officers'))
                                <div class="account-menu-divider" role="separator"></div>
                                <p class="account-menu-section-label" role="presentation">Manage</p>
                                @if (auth()->user()->canPost() || auth()->user()->isAdviser() || auth()->user()->isSuperadmin())
                                    <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="account-menu-action" role="menuitem">Dashboard</a>
                                @endif
                                @can('review-posts')
                                    <a href="{{ route('adviser.reviews.index') }}" class="account-menu-action" role="menuitem">Review Posts ({{ $pendingReviewCount }})</a>
                                @endcan
                                @can('manage-users')
                                    <a href="{{ route('adviser.users.index') }}" class="account-menu-action" role="menuitem">{{ auth()->user()->isSuperadmin() ? 'Manage Advisers' : 'Manage Users' }}</a>
                                @endcan
                                @can('manage-officers')
                                    <a href="{{ route('adviser.officers.index') }}" class="account-menu-action" role="menuitem">Manage About Us</a>
                                @endcan
                            @endif
                            {{-- Keep logout last and visually separate for every signed-in role. --}}
                            <div class="account-menu-divider" role="separator"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="account-menu-logout" role="menuitem">
                                    <svg aria-hidden="true" class="icon-small" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3-3H9m0 0l3-3m-3 3l3 3"/>
                                    </svg>
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                {{-- hamburger (mobile/tablet only) --}}
                <button type="button"
                        aria-label="Open menu"
                        aria-expanded="false"
                        aria-controls="mobile-nav"
                        class="icon-button mobile-menu-toggle">
                    <svg id="icon-open" class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="icon-close" class="icon hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- mobile search field --}}
        {{-- Blade comment: the mobile search row reuses the desktop suggestion behavior and touch-sized results. --}}
        <div id="mobile-search" class="mobile-search hidden search-shell">
            <div class="mobile-search-field">
                <form method="GET" action="{{ route('search.index') }}" class="search-form">
                    <input type="text" name="q" value="{{ request()->routeIs('search.index') && isset($query) ? $query : (is_string(request()->query('q')) ? mb_substr(request()->query('q'), 0, 100) : '') }}" placeholder="Discover something from SSITE..."
                           class="site-search-input" role="combobox" aria-autocomplete="list" aria-expanded="false"
                           aria-controls="mobile-search-list" autocomplete="off">
                    <button type="submit" class="search-submit-icon" aria-label="Search">
                        <svg class="site-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                        </svg>
                    </button>
                </form>
                <div id="mobile-search-list" class="search-suggestions hidden" role="listbox" aria-label="Search suggestions"></div>
            </div>
        </div>

    </header>
    {{-- The nav stays in normal page flow so it scrolls away while the site header remains sticky. --}}
    @include('layouts.partials.navigation')
    @endif

    <script>
        (() => {
            const menuToggle = document.querySelector('.mobile-menu-toggle');
            const mobileNav = document.getElementById('mobile-nav');
            const openIcon = document.getElementById('icon-open');
            const closeIcon = document.getElementById('icon-close');

            if (!menuToggle || !mobileNav || !openIcon || !closeIcon) return;

            const setMenuOpen = (open) => {
                menuToggle.setAttribute('aria-expanded', String(open));
                menuToggle.setAttribute('aria-label', open ? 'Close menu' : 'Open menu');
                mobileNav.classList.toggle('hidden', !open);
                openIcon.classList.toggle('hidden', open);
                closeIcon.classList.toggle('hidden', !open);
            };

            menuToggle.addEventListener('click', () => {
                setMenuOpen(menuToggle.getAttribute('aria-expanded') !== 'true');
            });

            document.addEventListener('click', (event) => {
                if (mobileNav.classList.contains('hidden')
                    || menuToggle.contains(event.target)
                    || mobileNav.contains(event.target)) return;

                setMenuOpen(false);
            });

            document.addEventListener('keydown', (event) => {
                if (event.key !== 'Escape' || mobileNav.classList.contains('hidden')) return;

                setMenuOpen(false);
                menuToggle.focus();
            });

            window.matchMedia('(min-width: 768px)').addEventListener('change', (event) => {
                if (event.matches) setMenuOpen(false);
            });
        })();
    </script>

    {{-- Keep anchor offsets matched to the rendered header and add its shadow only after scrolling. --}}
    <script>
        (() => {
            const siteHeader = document.querySelector('.site-header');

            if (!siteHeader) return;

            const updateHeaderHeight = () => {
                document.documentElement.style.setProperty('--site-header-height', `${siteHeader.offsetHeight}px`);
            };
            const updateHeaderShadow = () => {
                siteHeader.classList.toggle('is-scrolled', window.scrollY > 8);
            };

            updateHeaderHeight();
            updateHeaderShadow();
            window.addEventListener('scroll', updateHeaderShadow, { passive: true });

            if ('ResizeObserver' in window) {
                new ResizeObserver(updateHeaderHeight).observe(siteHeader);
            } else {
                window.addEventListener('resize', updateHeaderHeight, { passive: true });
            }
        })();
    </script>

    @include('layouts.partials.search-scripts')

    {{-- ============================= PAGE CONTENT ============================= --}}
    <main>
        @yield('content')
    </main>

    {{-- ============================= FOOTER ============================= --}}
    <footer class="site-footer">
        <div class="site-footer-content">
            {{-- Left: org info --}}
            <div class="footer-organization">
                <div class="footer-brand">
                    {{-- LOGO PLACEHOLDER --}}
                    <img src="{{ asset('images/SSITE LOGO.png') }}"
                         alt="SSITE Logo"
                        class="footer-logo">
                    <span class="footer-brand-name">
                        STUDENT SOCIETY IN INFORMATION<br>
                        TECHNOLOGY EDUCATION
                    </span>
                </div>
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
                        <svg class="footer-contact-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Dolores, Mabalacat City College</span>
                    </li>
                    <li class="footer-contact-item">
                        <svg class="footer-contact-icon" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22 12a10 10 0 10-11.5 9.87v-6.99H7.9V12h2.6V9.8c0-2.57 1.53-3.99 3.87-3.99 1.12 0 2.3.2 2.3.2v2.53h-1.3c-1.28 0-1.68.8-1.68 1.62V12h2.86l-.46 2.88h-2.4v6.99A10 10 0 0022 12z"/>
                        </svg>
                        <a href="https://www.facebook.com/ICSSSITE" class="footer-facebook-link" target="_blank" rel="noopener noreferrer">Student Society in Information Technology Education</a>
                    </li>
                    <li class="footer-contact-item">
                        <svg class="footer-contact-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:ssite@gmail.com" class="footer-email-link">ssite@gmail.com</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="footer-bottom">
            <p class="footer-copyright">
                &copy; {{ date('Y') }} Student Society in Information Technology — Mabalacat City College
            </p>
        </div>
    </footer>

    <button type="button" id="back-to-top" class="back-to-top hidden" aria-label="Back to top">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m-7 7 7-7 7 7"/>
        </svg>
    </button>

    <div id="page-loading-skeleton" class="page-loading-skeleton hidden" role="status" aria-label="Loading page">
        <div class="page-loading-skeleton-header" aria-hidden="true">
            <span class="skeleton-block skeleton-brand-mark"></span>
            <span class="skeleton-block skeleton-brand-name"></span>
            <span class="skeleton-block skeleton-header-action"></span>
        </div>
        <div class="page-loading-skeleton-content" aria-hidden="true">
            <span class="skeleton-block skeleton-title"></span>
            <span class="skeleton-block skeleton-subtitle"></span>
            <div class="skeleton-card-grid">
                <span class="skeleton-block skeleton-card"></span>
                <span class="skeleton-block skeleton-card"></span>
                <span class="skeleton-block skeleton-card"></span>
            </div>
            <span class="skeleton-block skeleton-row"></span>
            <span class="skeleton-block skeleton-row"></span>
            <span class="skeleton-block skeleton-row"></span>
        </div>
    </div>

    @stack('scripts')
    @include('layouts.partials.account-scripts')
    <script>
        (() => {
            const backToTop = document.getElementById('back-to-top');
            if (!backToTop) return;

            const updateBackToTop = () => {
                backToTop.classList.toggle('hidden', window.scrollY <= 300);
            };

            updateBackToTop();
            window.addEventListener('scroll', updateBackToTop, { passive: true });
            backToTop.addEventListener('click', () => {
                window.scrollTo({
                    top: 0,
                    behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth',
                });
            });
        })();
    </script>
    <script>
        (() => {
            const skeleton = document.getElementById('page-loading-skeleton');
            if (!skeleton) return;

            let showTimer;
            const show = () => {
                window.clearTimeout(showTimer);
                showTimer = window.setTimeout(() => skeleton.classList.remove('hidden'), 140);
            };
            const hide = () => {
                window.clearTimeout(showTimer);
                skeleton.classList.add('hidden');
            };

            document.addEventListener('click', (event) => {
                if (event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
                const clickedElement = event.target instanceof Element ? event.target : null;
                const link = clickedElement?.closest('a[href]');
                if (!link || link.hasAttribute('download') || (link.target && link.target !== '_self')) return;

                const destination = new URL(link.href, window.location.href);
                if (destination.origin !== window.location.origin) return;
                if (destination.pathname === window.location.pathname && destination.search === window.location.search && destination.hash) return;
                if (destination.href !== window.location.href) show();
            });

            document.addEventListener('submit', (event) => {
                if (event.defaultPrevented) return;
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || form.target && form.target !== '_self' || form.method.toLowerCase() === 'dialog') return;
                const destination = new URL(form.action || window.location.href, window.location.href);
                if (destination.origin === window.location.origin) show();
            });

            window.addEventListener('pageshow', hide);
            window.addEventListener('pagehide', () => window.clearTimeout(showTimer));
        })();
    </script>
</body>
</html>
