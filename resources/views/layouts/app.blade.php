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
    {{-- Officers/advisers use their role dashboard as Home; students and guests keep the public home link. --}}
    @php
        $roleHomeUrl = auth()->check() && auth()->user()->canPost()
            ? route(auth()->user()->dashboardRoute())
            : url('/');
    @endphp

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
                {{-- SECURITY: Both search forms submit the same public query; suggestions contain visible content only. --}}
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
                                aria-controls="account-menu"
                                aria-expanded="false"
                                class="icon-button account-toggle">
                            {{-- Keep the existing circular account button styling while showing the signed-in user's avatar. --}}
                            <x-ui.avatar :user="auth()->user()" size="sm" />
                        </button>

                        <div id="account-menu" class="account-menu hidden">
                            <div class="account-menu-name">
                                {{ auth()->user()->name }}
                            </div>
                            <button type="button" class="account-menu-action">
                                <svg aria-hidden="true" class="icon-small" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8.25a3.75 3.75 0 100 7.5 3.75 3.75 0 000-7.5z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06-1.5 2.6-.08-.02a1.65 1.65 0 00-1.78.76l-.04.07h-3l-.02-.08a1.65 1.65 0 00-1.4-1.14 1.65 1.65 0 00-1.73.92l-.04.08-2.84-1.02.02-.08a1.65 1.65 0 00-.76-1.78l-.07-.04v-3l.08-.02a1.65 1.65 0 001.14-1.4 1.65 1.65 0 00-.92-1.73l-.08-.04 1.02-2.84.08.02a1.65 1.65 0 001.78-.76l.04-.07h3l.02.08a1.65 1.65 0 001.4 1.14 1.65 1.65 0 001.73-.92l.04-.08 2.84 1.02-.02.08a1.65 1.65 0 00.76 1.78l.07.04v3l-.08.02A1.65 1.65 0 0019.4 15z"/>
                                </svg>
                                <span>Settings</span>
                            </button>
                            @if (auth()->user()->hasCompletedProfile())
                                <a href="{{ route('profile.edit') }}" class="account-menu-action">
                                    <svg aria-hidden="true" class="icon-small" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.1a7.5 7.5 0 0115 0A17.9 17.9 0 0112 21.75c-2.7 0-5.25-.6-7.5-1.65z"/>
                                    </svg>
                                    <span>My Profile</span>
                                </a>
                            @endif
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="account-menu-logout">
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
                        onclick="document.getElementById('mobile-nav').classList.toggle('hidden'); document.getElementById('icon-open').classList.toggle('hidden'); document.getElementById('icon-close').classList.toggle('hidden');"
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

        {{-- Desktop nav links row --}}
        <nav class="desktop-navigation">
            <ul class="site-nav-list">
                <li class="site-nav-item">
                    <a href="{{ $roleHomeUrl }}"
                       class="site-nav-link {{ request()->is('/') || request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}">
                        Home
                    </a>
                </li>
                <li class="site-nav-item"><a href="{{ url('/about') }}" class="site-nav-link">About us</a></li>

                <li class="site-nav-item site-nav-dropdown">
                    {{-- SECURITY: Dots represent only unread published items; review notices stay in the bell. --}}
                    <button type="button" class="site-nav-link site-nav-dropdown-toggle">
                        Articles
                        <span class="nav-unread-dot {{ ($notificationNavCounts['article'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="article" aria-label="Unread published articles"></span>
                        <svg class="dropdown-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul class="site-nav-dropdown-menu articles-dropdown-menu">
                        <li><a href="{{ route('articles.index') }}" class="site-dropdown-link">Published Articles</a></li>
                    </ul>
                </li>

                <li class="site-nav-item site-nav-dropdown">
                    <button type="button" class="site-nav-link site-nav-dropdown-toggle">
                        Activities
                        <span class="nav-unread-dot {{ ($notificationNavCounts['activity'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="activity" aria-label="Unread published activities"></span>
                        <svg class="dropdown-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul class="site-nav-dropdown-menu activities-dropdown-menu">
                        <li><a href="{{ url('/activities') }}" class="site-dropdown-link">All Activities</a></li>
                        <li><a href="{{ url('/activities') }}" class="site-dropdown-link">Latest Activities</a></li>
                        <li><a href="{{ url('/activities') }}" class="site-dropdown-link">Featured Activities</a></li>
                        <li><a href="{{ url('/activities') }}" class="site-dropdown-link">Most Viewed</a></li>
                    </ul>
                </li>

                <li class="site-nav-item"><a href="{{ url('/achievements') }}" class="site-nav-link">Achievements <span class="nav-unread-dot {{ ($notificationNavCounts['achievement'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="achievement" aria-label="Unread published achievements"></span></a></li>
                <li class="site-nav-item"><a href="{{ url('/liquidation') }}" class="site-nav-link">Liquidation <span class="nav-unread-dot {{ ($notificationNavCounts['liquidation'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="liquidation" aria-label="Unread published liquidations"></span></a></li>
                <li class="site-nav-item"><a href="{{ url('/documents') }}" class="site-nav-link">Documents <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span></a></li>
                @auth
                    {{-- SECURITY: This shared dashboard link is limited by the canPost ability to officers and advisers. --}}
                    @can('view-post-dashboard')
                        <li class="site-nav-item">
                            <a href="{{ route(auth()->user()->dashboardRoute()) }}"
                               class="site-nav-link {{ request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}">
                                Dashboard
                            </a>
                        </li>
                        <li class="site-nav-item">
                            <a href="{{ route('articles.create') }}" class="site-nav-link">New Article</a>
                        </li>
                    @endcan
                    {{-- SECURITY: Review tools are shown only to advisers; route middleware enforces the same rule. --}}
                    @can('review-posts')
                        <li class="site-nav-item">
                            <a href="{{ route('adviser.reviews.index') }}" class="site-nav-link">Review Posts</a>
                        </li>
                    @endcan
                    {{-- SECURITY: User-role management is adviser-only and protected again on its routes. --}}
                    @can('manage-users')
                        <li class="site-nav-item">
                            <a href="{{ route('adviser.users.index') }}" class="site-nav-link">Manage Users</a>
                        </li>
                    @endcan
                    @can('manage-users')
                        <li class="site-nav-item"><a href="{{ route('adviser.officers.index') }}" class="site-nav-link">Manage Officers</a></li>
                    @endcan
                @endauth
            </ul>
        </nav>

        {{-- Mobile / tablet nav menu (collapsible) --}}
        <nav id="mobile-nav" class="mobile-navigation hidden">
            <ul class="mobile-nav-list">
                <li class="mobile-nav-item">
                    <a href="{{ $roleHomeUrl }}"
                       class="mobile-nav-link {{ request()->is('/') || request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}">
                        Home
                    </a>
                </li>
                <li class="mobile-nav-item"><a href="{{ url('/about') }}" class="mobile-nav-link">About us</a></li>

                <li class="mobile-nav-dropdown-item">
                    <details class="mobile-nav-dropdown">
                        <summary class="mobile-nav-summary">
                            Articles
                            <span class="nav-unread-dot {{ ($notificationNavCounts['article'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="article" aria-label="Unread published articles"></span>
                            <svg class="mobile-nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <ul class="mobile-nav-sublist">
                            <li><a href="{{ route('articles.index') }}" class="mobile-nav-sublink">Published Articles</a></li>
                        </ul>
                    </details>
                </li>

                <li class="mobile-nav-dropdown-item">
                    <details class="mobile-nav-dropdown">
                        <summary class="mobile-nav-summary">
                            Activities
                            <span class="nav-unread-dot {{ ($notificationNavCounts['activity'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="activity" aria-label="Unread published activities"></span>
                            <svg class="mobile-nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <ul class="mobile-nav-sublist">
                            <li><a href="{{ url('/activities') }}" class="mobile-nav-sublink">All Activities</a></li>
                            <li><a href="{{ url('/activities') }}" class="mobile-nav-sublink">Latest Activities</a></li>
                            <li><a href="{{ url('/activities') }}" class="mobile-nav-sublink">Featured Activities</a></li>
                            <li><a href="{{ url('/activities') }}" class="mobile-nav-sublink">Most Viewed</a></li>
                        </ul>
                    </details>
                </li>

                <li class="mobile-nav-item"><a href="{{ url('/achievements') }}" class="mobile-nav-link">Achievements <span class="nav-unread-dot {{ ($notificationNavCounts['achievement'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="achievement" aria-label="Unread published achievements"></span></a></li>
                <li class="mobile-nav-item"><a href="{{ url('/liquidation') }}" class="mobile-nav-link">Liquidation <span class="nav-unread-dot {{ ($notificationNavCounts['liquidation'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="liquidation" aria-label="Unread published liquidations"></span></a></li>
                <li class="mobile-nav-item"><a href="{{ url('/documents') }}" class="mobile-nav-link">Documents <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span></a></li>
                @auth
                    {{-- SECURITY: The mobile dashboard link uses the same officer/adviser Gate as desktop navigation. --}}
                    @can('view-post-dashboard')
                        <li class="mobile-nav-item">
                            <a href="{{ route(auth()->user()->dashboardRoute()) }}"
                               class="mobile-nav-link {{ request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}">
                                Dashboard
                            </a>
                        </li>
                        <li class="mobile-nav-item">
                            <a href="{{ route('articles.create') }}" class="mobile-nav-link">New Article</a>
                        </li>
                    @endcan
                    {{-- SECURITY: Only advisers see the review queue shortcut. --}}
                    @can('review-posts')
                        <li class="mobile-nav-item">
                            <a href="{{ route('adviser.reviews.index') }}" class="mobile-nav-link">Review Posts</a>
                        </li>
                    @endcan
                    {{-- SECURITY: Only advisers see user-role management. --}}
                    @can('manage-users')
                        <li class="mobile-nav-item">
                            <a href="{{ route('adviser.users.index') }}" class="mobile-nav-link">Manage Users</a>
                        </li>
                    @endcan
                    @can('manage-users')
                        <li class="mobile-nav-item"><a href="{{ route('adviser.officers.index') }}" class="mobile-nav-link">Manage Officers</a></li>
                    @endcan
                @endauth
            </ul>
        </nav>
    </header>
    @endif

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

    {{-- SECURITY: Suggestions use plain-text DOM nodes, short queries are rejected, and the endpoint is throttled. --}}
    <script>
        (() => {
            const inputs = [...document.querySelectorAll('.search-form [role="combobox"]')];
            const suggestionsUrl = @json(route('search.suggest'));
            const resultsUrl = @json(route('search.index'));
            const typeLabels = { articles: 'Articles', activities: 'Activities', achievements: 'Achievements', documents: 'Documents', liquidation: 'Liquidation', officers: 'Officers' };
            const typeIcons = { articles: '▤', activities: '◷', achievements: '★', documents: '▧', liquidation: '◫', officers: '♙' };
            let timer = null;
            let request = null;
            let sequence = 0;
            let activeIndex = -1;

            const close = (shell) => {
                const input = shell.querySelector('[role="combobox"]');
                const list = shell.querySelector('[role="listbox"]');
                window.clearTimeout(timer);
                sequence++;
                list.classList.add('hidden');
                list.replaceChildren();
                input.setAttribute('aria-expanded', 'false');
                input.removeAttribute('aria-activedescendant');
                activeIndex = -1;
                if (request) request.abort();
            };

            const appendHighlighted = (node, value, words) => {
                const terms = [...new Set(words.filter(Boolean))].sort((a, b) => b.length - a.length);
                if (!terms.length) { node.textContent = value; return; }
                const pattern = new RegExp(`(${terms.map((term) => term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&')).join('|')})`, 'giu');
                let offset = 0;
                for (const match of value.matchAll(pattern)) {
                    const position = match.index;
                    if (position > offset) node.append(document.createTextNode(value.slice(offset, position)));
                    const mark = document.createElement('mark');
                    mark.textContent = match[0];
                    node.append(mark);
                    offset = position + match[0].length;
                }
                node.append(document.createTextNode(value.slice(offset)));
            };

            const optionLink = (item, words) => {
                const link = document.createElement('a');
                link.className = 'search-suggestion-option';
                link.href = item.url;
                link.setAttribute('role', 'option');
                link.id = `search-option-${sequence}-${Math.random().toString(36).slice(2)}`;
                link.setAttribute('aria-selected', 'false');
                const copy = document.createElement('span');
                copy.className = 'search-suggestion-copy';
                const title = document.createElement('strong');
                appendHighlighted(title, item.title, words);
                const excerpt = document.createElement('span');
                excerpt.className = 'search-suggestion-excerpt';
                appendHighlighted(excerpt, item.excerpt || item.meta || '', words);
                copy.append(title, excerpt);
                link.append(copy);
                if (item.meta) {
                    const meta = document.createElement('small');
                    meta.className = 'search-suggestion-meta';
                    appendHighlighted(meta, item.meta, words);
                    link.append(meta);
                }
                return link;
            };

            const showResults = (shell, list, items, query) => {
                list.replaceChildren();
                const words = query.trim().split(/\s+/).slice(0, 5);
                const groups = new Map();
                items.forEach((item) => {
                    if (!groups.has(item.type)) groups.set(item.type, []);
                    groups.get(item.type).push(item);
                });
                groups.forEach((entries, type) => {
                    const heading = document.createElement('div');
                    heading.className = 'search-suggestion-group';
                    const icon = document.createElement('span');
                    icon.className = 'search-suggestion-icon';
                    icon.setAttribute('aria-hidden', 'true');
                    icon.textContent = typeIcons[type] || '•';
                    const label = document.createElement('span');
                    label.textContent = typeLabels[type] || 'Results';
                    heading.append(icon, label);
                    list.append(heading);
                    entries.slice(0, 3).forEach((item) => list.append(optionLink(item, words)));
                });

                if (!items.length) {
                    const empty = document.createElement('p');
                    empty.className = 'search-suggestion-message';
                    empty.textContent = `No suggestions for “${query}”.`;
                    list.append(empty);
                }
                const all = document.createElement('a');
                all.className = 'search-suggestion-all';
                all.href = `${resultsUrl}?${new URLSearchParams({ q: query })}`;
                all.setAttribute('role', 'option');
                all.id = `search-option-${sequence}-all`;
                all.setAttribute('aria-selected', 'false');
                all.textContent = `See all results for “${query}”`;
                list.append(all);
                list.classList.remove('hidden');
                shell.querySelector('[role="combobox"]').setAttribute('aria-expanded', 'true');
                activeIndex = -1;
            };

            const fetchSuggestions = (input) => {
                const shell = input.closest('.search-shell');
                const list = shell.querySelector('[role="listbox"]');
                const query = input.value.trim().replace(/\s+/g, ' ');
                window.clearTimeout(timer);
                sequence++;
                const currentSequence = sequence;
                if (request) request.abort();
                if (query.length < 2) { close(shell); return; }

                list.replaceChildren();
                const loading = document.createElement('p');
                loading.className = 'search-suggestion-message';
                loading.setAttribute('role', 'status');
                loading.textContent = 'Searching…';
                list.append(loading);
                list.classList.remove('hidden');
                input.setAttribute('aria-expanded', 'true');
                timer = window.setTimeout(async () => {
                    request = new AbortController();
                    try {
                        const response = await fetch(`${suggestionsUrl}?${new URLSearchParams({ q: query })}`, {
                            headers: { Accept: 'application/json' }, credentials: 'same-origin', signal: request.signal,
                        });
                        if (!response.ok) {
                            if (currentSequence === sequence) close(shell);
                            return;
                        }
                        const payload = await response.json();
                        if (currentSequence !== sequence || input.value.trim().replace(/\s+/g, ' ') !== query) return;
                        showResults(shell, list, payload.results || [], query);
                    } catch (_) {
                        // SECURITY: Network and session failures leave the form usable and expose no response data.
                        if (currentSequence === sequence) close(shell);
                    }
                }, 250);
            };

            inputs.forEach((input) => {
                input.addEventListener('input', () => fetchSuggestions(input));
                input.addEventListener('keydown', (event) => {
                    const shell = input.closest('.search-shell');
                    const list = shell.querySelector('[role="listbox"]');
                    const options = [...list.querySelectorAll('[role="option"]')];
                    if (event.key === 'Escape') { close(shell); return; }
                    if (event.key === 'ArrowDown' && !list.classList.contains('hidden')) {
                        event.preventDefault();
                        activeIndex = Math.min(activeIndex + 1, options.length - 1);
                    } else if (event.key === 'ArrowUp' && !list.classList.contains('hidden')) {
                        event.preventDefault();
                        activeIndex = Math.max(activeIndex - 1, 0);
                    } else if (event.key === 'Enter' && activeIndex >= 0 && options[activeIndex]) {
                        event.preventDefault();
                        window.location.assign(options[activeIndex].href);
                    } else return;

                    options.forEach((option, index) => option.setAttribute('aria-selected', String(index === activeIndex)));
                    const active = options[activeIndex];
                    if (active) {
                        input.setAttribute('aria-activedescendant', active.id);
                        active.scrollIntoView({ block: 'nearest' });
                    } else input.removeAttribute('aria-activedescendant');
                });
            });

            document.addEventListener('click', (event) => {
                document.querySelectorAll('.search-shell').forEach((shell) => {
                    if (!shell.contains(event.target)) close(shell);
                });
            });
            window.addEventListener('popstate', () => document.querySelectorAll('.search-shell').forEach(close));
            window.addEventListener('pagehide', () => { if (request) request.abort(); });
        })();
    </script>

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
                        <span>Student Society in Information Technology Education</span>
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

    @stack('scripts')
    @guest
        <script>
            const loginToggle = document.getElementById('login-toggle');
            const loginPopover = document.getElementById('login-popover');

            if (loginToggle && loginPopover) {
                loginToggle.addEventListener('click', () => {
                    const isOpen = loginToggle.getAttribute('aria-expanded') === 'true';
                    loginToggle.setAttribute('aria-expanded', String(!isOpen));
                    loginPopover.classList.toggle('hidden', isOpen);
                });

                document.addEventListener('click', (event) => {
                    if (!loginPopover.contains(event.target) && !loginToggle.contains(event.target)) {
                        loginToggle.setAttribute('aria-expanded', 'false');
                        loginPopover.classList.add('hidden');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        loginToggle.setAttribute('aria-expanded', 'false');
                        loginPopover.classList.add('hidden');
                    }
                });
            }
        </script>
    @endguest
    @auth
        <script>
            const accountMenuToggle = document.getElementById('account-menu-toggle');
            const accountMenu = document.getElementById('account-menu');

            if (accountMenuToggle && accountMenu) {
                accountMenuToggle.addEventListener('click', () => {
                    const isOpen = accountMenuToggle.getAttribute('aria-expanded') === 'true';
                    accountMenuToggle.setAttribute('aria-expanded', String(!isOpen));
                    accountMenu.classList.toggle('hidden', isOpen);
                });

                document.addEventListener('click', (event) => {
                    if (!accountMenu.contains(event.target) && !accountMenuToggle.contains(event.target)) {
                        accountMenuToggle.setAttribute('aria-expanded', 'false');
                        accountMenu.classList.add('hidden');
                    }
                });

                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        accountMenuToggle.setAttribute('aria-expanded', 'false');
                        accountMenu.classList.add('hidden');
                    }
                });
            }

            const notificationWrapper = document.getElementById('notification-menu-wrapper');
            const notificationToggle = document.getElementById('notifications-toggle');
            const notificationDropdown = document.getElementById('notifications-dropdown');
            const notificationList = document.getElementById('notification-list');
            const notificationBadge = document.getElementById('notification-badge');
            const notificationCsrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
            const postLabels = { article: 'Article', activity: 'Activity', achievement: 'Achievement', document: 'Document', liquidation: 'Liquidation' };
            document.querySelectorAll('.notification-actor-avatar').forEach((avatar) => {
                avatar.addEventListener('error', () => avatar.remove(), { once: true });
            });

            const relativeTime = (value) => {
                const seconds = Math.max(0, Math.floor((Date.now() - new Date(value).getTime()) / 1000));
                if (seconds < 60) return 'just now';
                if (seconds < 3600) return `${Math.floor(seconds / 60)}m ago`;
                if (seconds < 86400) return `${Math.floor(seconds / 3600)}h ago`;
                return `${Math.floor(seconds / 86400)}d ago`;
            };

            const updateNotificationSummary = async (forceOpen = false) => {
                if (!notificationWrapper || document.hidden || (!forceOpen && notificationToggle?.getAttribute('aria-expanded') === 'true')) return;
                try {
                    const response = await fetch(notificationWrapper.dataset.summaryUrl, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                    if (!response.ok) return;
                    const summary = await response.json();
                    const count = Number(summary.unread_count || 0);
                    notificationBadge.textContent = count > 99 ? '99+' : String(count);
                    notificationBadge.classList.toggle('hidden', count === 0);

                    document.querySelectorAll('[data-notification-post-type]').forEach((dot) => {
                        const unread = Number(summary.nav_counts?.[dot.dataset.notificationPostType] || 0) > 0;
                        dot.classList.toggle('hidden', !unread);
                    });

                    if (!forceOpen && notificationToggle?.getAttribute('aria-expanded') === 'true') return;
                    notificationList.replaceChildren();
                    if (!summary.notifications?.length) {
                        const empty = document.createElement('p');
                        empty.className = 'notification-empty';
                        empty.textContent = "You're all caught up";
                        notificationList.append(empty);
                        return;
                    }

                    summary.notifications.forEach((item) => {
                        const data = item.data || {};
                        const form = document.createElement('form');
                        form.method = 'POST';
                        form.action = notificationWrapper.dataset.readUrlTemplate.replace('00000000-0000-4000-8000-000000000000', encodeURIComponent(item.id));
                        form.className = 'notification-entry-form';
                        const csrf = document.createElement('input');
                        csrf.type = 'hidden'; csrf.name = '_token'; csrf.value = notificationCsrf;
                        const button = document.createElement('button');
                        button.type = 'submit'; button.className = `notification-entry ${item.read_at ? '' : 'is-unread'}`;
                        const typeIcon = document.createElement('span');
                        typeIcon.className = `notification-type-icon notification-type-${data.type || 'published'}`;
                        typeIcon.setAttribute('aria-hidden', 'true');
                        typeIcon.textContent = (data.type || 'published').slice(0, 1).toUpperCase();
                        const postIcon = document.createElement('small');
                        postIcon.textContent = (postLabels[data.post_type] || 'P').slice(0, 1);
                        typeIcon.append(postIcon);
                        const avatar = document.createElement('img');
                        avatar.className = 'notification-actor-avatar';
                        avatar.alt = '';
                        avatar.src = notificationWrapper.dataset.avatarUrlTemplate.replace('00000000-0000-4000-8000-000000000000', encodeURIComponent(item.id));
                        avatar.addEventListener('error', () => avatar.remove(), { once: true });
                        typeIcon.append(avatar);
                        const copy = document.createElement('span'); copy.className = 'notification-entry-copy';
                        const message = document.createElement('span'); message.className = 'notification-entry-message'; message.textContent = data.message || 'You have a new notification.';
                        const title = document.createElement('span'); title.className = 'notification-entry-title'; title.textContent = data.title || '';
                        const meta = document.createElement('span'); meta.className = 'notification-entry-meta';
                        meta.textContent = `${data.actor_name || 'SSITE'} · ${postLabels[data.post_type] || 'Post'} · ${item.created_at ? relativeTime(item.created_at) : ''}`;
                        copy.append(message, title, meta);
                        if (!item.read_at) { const unreadDot = document.createElement('span'); unreadDot.className = 'notification-entry-unread-dot'; unreadDot.setAttribute('aria-label', 'Unread'); button.append(typeIcon, copy, unreadDot); }
                        else button.append(typeIcon, copy);
                        form.append(csrf, button); notificationList.append(form);
                    });
                } catch (_) {
                    // Polling failures after logout or session expiry stay silent and leave the page usable.
                }
            };

            if (notificationWrapper && notificationToggle && notificationDropdown) {
                notificationToggle.addEventListener('click', () => {
                    const open = notificationToggle.getAttribute('aria-expanded') !== 'true';
                    notificationToggle.setAttribute('aria-expanded', String(open));
                    notificationDropdown.classList.toggle('hidden', !open);
                    if (open) updateNotificationSummary(true);
                });
                document.addEventListener('click', (event) => {
                    if (!notificationWrapper.contains(event.target)) {
                        notificationToggle.setAttribute('aria-expanded', 'false');
                        notificationDropdown.classList.add('hidden');
                    }
                });
                document.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        notificationToggle.setAttribute('aria-expanded', 'false');
                        notificationDropdown.classList.add('hidden');
                    }
                });
                document.addEventListener('visibilitychange', () => {
                    if (!document.hidden) updateNotificationSummary();
                });
                // SECURITY: Hidden tabs and an open dropdown pause polling to avoid wasted requests and flicker.
                window.setInterval(updateNotificationSummary, 60000);
                updateNotificationSummary();
            }
        </script>
    @endauth
</body>
</html>
