<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'SSITE') | Student Society in Information Technology Education</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Pixelify+Sans:wght@400;500;600;700&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- App styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    @vite('resources/js/app.js')
    @php
        $workspaceUser = auth()->user();
        $workspaceRole = $workspaceUser && (
            $workspaceUser->canPost()
            || $workspaceUser->canReview()
            || $workspaceUser->can('manage-users')
            || $workspaceUser->can('manage-officers')
        );
        $workspaceRoute = request()->routeIs(
            'officer.*', 'adviser.*', 'editorial.*', 'workspace.posts.*',
            'notifications.index', 'profile.edit',
            'articles.create', 'articles.edit', 'activities.create', 'activities.edit',
            'achievements.create', 'achievements.edit', 'documents.create', 'documents.edit',
            'liquidations.create', 'liquidations.edit'
        );
        $workspaceEnabled = $workspaceRole && $workspaceRoute;
    @endphp
    @if ($workspaceEnabled)
        <link rel="stylesheet" href="{{ asset('css/workspace.css') }}">
    @endif

    @stack('styles')
    @unless ($workspaceEnabled)
        <link rel="stylesheet" href="{{ asset('css/site-navigation.css') }}">
        <link rel="stylesheet" href="{{ asset('css/main-site.css') }}">
    @endunless
    <link rel="stylesheet" href="{{ asset('css/ui-foundation.css') }}">
</head>
<body class="{{ $workspaceEnabled ? 'site-body workspace-site-body' : 'site-body main-site-body' }} {{ request()->routeIs('discover') ? 'discover-site-body' : '' }}">
    <template id="search-icon-articles"><x-icon name="file-text" size="sm" /></template>
    <template id="search-icon-activities"><x-icon name="calendar-days" size="sm" /></template>
    <template id="search-icon-achievements"><x-icon name="award" size="sm" /></template>
    <template id="search-icon-documents"><x-icon name="file-text" size="sm" /></template>
    <template id="search-icon-liquidation"><x-icon name="chart-column-increasing" size="sm" /></template>
    <template id="search-icon-officers"><x-icon name="users" size="sm" /></template>
    <template id="search-icon-default"><x-icon name="info" size="sm" /></template>
    <template id="notification-icon-submitted"><x-icon name="clock" size="sm" /></template>
    <template id="notification-icon-approved"><x-icon name="circle-check" size="sm" /></template>
    <template id="notification-icon-published"><x-icon name="circle-check" size="sm" /></template>
    <template id="notification-icon-rejected"><x-icon name="circle-x" size="sm" /></template>
    <template id="notification-icon-default"><x-icon name="info" size="sm" /></template>
    <template id="notification-post-article"><x-icon name="file-text" size="sm" /></template>
    <template id="notification-post-activity"><x-icon name="calendar-days" size="sm" /></template>
    <template id="notification-post-achievement"><x-icon name="award" size="sm" /></template>
    <template id="notification-post-document"><x-icon name="file-text" size="sm" /></template>
    <template id="notification-post-liquidation"><x-icon name="chart-column-increasing" size="sm" /></template>
    @if ($workspaceEnabled)
        @include('layouts.partials.workspace')
    @else
    <a class="site-skip-link" href="#main-content">Skip to content</a>
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
    <header class="site-header public-home-header">
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
                        <button type="submit" class="search-submit-icon" aria-label="Search" data-tooltip="Search">
                            <x-icon name="search" size="sm" class="site-search-icon" />
                        </button>
                    </form>
                    <div id="desktop-search-list" class="search-suggestions hidden" role="listbox" aria-label="Search suggestions"></div>
                </div>

                {{-- search icon (mobile/tablet only, opens the mobile search field below) --}}
                <button type="button"
                        id="mobile-search-toggle"
                        aria-label="Open search"
                        aria-controls="mobile-search"
                        aria-expanded="false"
                        class="icon-button mobile-search-toggle" data-tooltip="Open search">
                    <x-icon name="search" class="icon" />
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
                                aria-controls="notifications-dropdown" aria-expanded="false" class="icon-button account-notifications" data-tooltip="Notifications">
                            <x-icon name="bell" class="icon" />
                            {{-- SECURITY: Seed count-change detection with the server-rendered unread total; visible text stays capped. --}}
                            <span id="notification-badge" class="notification-badge {{ $notificationUnreadCount ? '' : 'hidden' }}" data-motion="badge" data-unread-count="{{ $notificationUnreadCount }}">{{ $notificationUnreadCount > 99 ? '99+' : $notificationUnreadCount }}</span>
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
                                class="icon-button account-toggle" data-tooltip="Account menu">
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
                                    <x-icon name="user-round" size="sm" class="icon-small" />
                                    <span>My Profile</span>
                                </a>
                            @endif
                            <button type="button" class="account-menu-action" role="menuitem">
                                <x-icon name="settings" size="sm" class="icon-small" />
                                <span>Settings</span>
                            </button>
                            @if (auth()->user()->canPost() || auth()->user()->can('review-posts') || auth()->user()->can('manage-users') || auth()->user()->can('manage-officers'))
                                <div class="account-menu-divider" role="separator"></div>
                                <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="account-menu-action" role="menuitem">Open workspace</a>
                            @endif
                            {{-- Keep logout last and visually separate for every signed-in role. --}}
                            <div class="account-menu-divider" role="separator"></div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="account-menu-logout" role="menuitem">
                                    <x-icon name="log-out" size="sm" class="icon-small" />
                                    <span>Logout</span>
                                </button>
                            </form>
                        </div>
                    </div>
                @endauth

                {{-- hamburger (mobile/tablet only) --}}
                <button type="button"
                        aria-label="Open menu"
                        data-tooltip="Open menu"
                        aria-expanded="false"
                        aria-controls="mobile-nav"
                        class="icon-button mobile-menu-toggle">
                    <x-icon name="menu" class="icon" id="icon-open" />
                    <x-icon name="x" class="icon hidden" id="icon-close" />
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
                    <button type="submit" class="search-submit-icon" aria-label="Search" data-tooltip="Search">
                        <x-icon name="search" size="sm" class="site-search-icon" />
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
            const searchToggle = document.getElementById('mobile-search-toggle');
            const mobileSearch = document.getElementById('mobile-search');

            {{-- Keep the mobile search toggle's accessible state aligned with the existing visibility toggle. --}}
            if (searchToggle && mobileSearch) {
                searchToggle.addEventListener('click', () => {
                    const open = searchToggle.getAttribute('aria-expanded') !== 'true';
                    searchToggle.setAttribute('aria-expanded', String(open));
                    searchToggle.setAttribute('aria-label', open ? 'Close search' : 'Open search');
                    searchToggle.dataset.tooltip = open ? 'Close search' : 'Open search';
                    mobileSearch.classList.toggle('hidden', !open);
                });

                window.matchMedia('(min-width: 1024px)').addEventListener('change', (event) => {
                    if (!event.matches) return;
                    mobileSearch.classList.add('hidden');
                    searchToggle.setAttribute('aria-expanded', 'false');
                    searchToggle.setAttribute('aria-label', 'Open search');
                    searchToggle.dataset.tooltip = 'Open search';
                });
            }

            if (!menuToggle || !mobileNav || !openIcon || !closeIcon) return;

            const setMenuOpen = (open) => {
                menuToggle.setAttribute('aria-expanded', String(open));
                const label = open ? 'Close menu' : 'Open menu';
                menuToggle.setAttribute('aria-label', label);
                menuToggle.dataset.tooltip = label;
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
    <main id="main-content" tabindex="-1">
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
                        <x-icon name="map-pin" size="md" class="footer-contact-icon" />
                        <span>Dolores, Mabalacat City College</span>
                    </li>
                    <li class="footer-contact-item">
                        <x-icon name="external-link" size="md" class="footer-contact-icon" />
                        <a href="https://www.facebook.com/ICSSSITE" class="footer-facebook-link" target="_blank" rel="noopener noreferrer">Student Society in Information Technology Education</a>
                    </li>
                    <li class="footer-contact-item">
                        <x-icon name="mail" size="md" class="footer-contact-icon" />
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
    @endif

    <button type="button" id="back-to-top" class="back-to-top hidden" aria-label="Back to top" data-tooltip="Back to top">
        <x-icon name="arrow-up" size="md" />
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
                // Feed tabs and pagination replace only their own result region and show its local skeleton.
                if (link.closest('.post-feed-shell .dashboard-tabs, .post-feed-shell .dashboard-pagination')) return;

                const destination = new URL(link.href, window.location.href);
                if (destination.origin !== window.location.origin) return;
                if (destination.pathname === window.location.pathname && destination.search === window.location.search && destination.hash) return;
                if (destination.href !== window.location.href) show();
            });

            document.addEventListener('submit', (event) => {
                if (event.defaultPrevented) return;
                const form = event.target;
                if (!(form instanceof HTMLFormElement) || form.target && form.target !== '_self' || form.method.toLowerCase() === 'dialog') return;
                // Feed sort and filter controls use the same result-only loading state as tab changes.
                if (form.matches('.post-feed-sort')) return;
                const destination = new URL(form.action || window.location.href, window.location.href);
                if (destination.origin === window.location.origin) show();
            });

            window.addEventListener('pageshow', hide);
            window.addEventListener('pagehide', () => window.clearTimeout(showTimer));
        })();
    </script>
</body>
</html>
