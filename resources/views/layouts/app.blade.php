@inject('siteContent', 'App\Support\SiteContent')
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.partials.font-preloads')
    <title>@yield('title', 'SSITE') | Student Society in Information Technology Education</title>
    <link rel="icon" type="image/svg+xml" href="{{ asset('images/ssite-favicon.svg') }}">

    @php
        $showLoginSuccess = auth()->check() && session()->pull('show_login_success', false);
    @endphp
    @if ($showLoginSuccess)
        <link rel="stylesheet" href="{{ asset('css/login-success.css') }}">
        <script src="{{ asset('js/login-success.js') }}" defer></script>
    @endif
    <!-- Fonts -->

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
        $workspaceRoutePatterns = [
            'officer.*', 'adviser.*', 'editorial.*', 'workspace.posts.*', 'workspace.drafts.*', 'workspace.content-activity.*', 'workspace.content-versions.*', 'workspace.sponsors.*',
            'notifications.index', 'profile.edit',
            'articles.create', 'articles.edit', 'activities.create', 'activities.edit',
            'achievements.create', 'achievements.edit', 'documents.create', 'documents.edit',
            'liquidations.create', 'liquidations.edit'
        ];
        $workspaceRoute = request()->routeIs(...$workspaceRoutePatterns);
        $workspaceEnabled = $workspaceRole && $workspaceRoute;
        $isPublicLanding = request()->getPathInfo() === '/';
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
    <link rel="stylesheet" href="{{ asset('css/ssite-upgrade.css') }}">
    <link rel="stylesheet" href="{{ asset('css/mobile-responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('css/workspace-experience.css') }}">
    @if ($workspaceEnabled)
        <link rel="stylesheet" href="{{ asset('css/workspace-refresh.css') }}">
    @endif
    <link rel="stylesheet" href="{{ asset('css/main-site-dropdowns.css') }}?v={{ filemtime(public_path('css/main-site-dropdowns.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/interface-polish.css') }}">
    <link rel="stylesheet" href="{{ asset('css/pagination.css') }}?v={{ filemtime(public_path('css/pagination.css')) }}">
    @unless ($workspaceEnabled)
        <link rel="stylesheet" href="{{ asset('css/content-cards.css') }}?v={{ filemtime(public_path('css/content-cards.css')) }}">
    @endunless
    @include('layouts.partials.typography')
    <link rel="stylesheet" href="{{ asset('css/stories-banner.css') }}?v={{ filemtime(public_path('css/stories-banner.css')) }}">
    <script src="{{ asset('js/dropdown-position.js') }}" defer></script>
    @include('layouts.partials.search-assets')
    @if ($pageDataPending ?? false)
        <noscript><meta http-equiv="refresh" content="0;url={{ request()->fullUrlWithQuery(['_full' => 1]) }}"></noscript>
        <script src="{{ asset('js/initial-page-data.js') }}" defer></script>
    @endif
</head>
    @if (auth()->user()?->isAdviser())
        <link rel="stylesheet" href="{{ asset('css/site-editor.css') }}?v={{ filemtime(public_path('css/site-editor.css')) }}">
    @endif
<body class="{{ $workspaceEnabled ? 'site-body workspace-site-body' : 'site-body main-site-body' }} {{ request()->routeIs('discover') ? 'discover-site-body' : '' }} {{ $isPublicLanding ? 'home-landing-body' : '' }}">
    @if ($pageDataPending ?? false)
        <p data-page-data-fallback role="status" class="dashboard-error" hidden>
            The page data could not be loaded. <a href="{{ request()->fullUrlWithQuery(['_full' => 1]) }}">Reload the full page</a>.
        </p>
    @endif
    @if ($showLoginSuccess)
        @include('layouts.partials.login-success')
    @endif
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
    <template id="notification-icon-liked"><x-icon name="heart" size="sm" /></template>
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
    {{-- The public landing page is omitted from CMS navigation; authenticated logo links return to CMS Home. --}}
    @php($roleHomeUrl = auth()->check() ? route('home') : route('landing'))

    {{-- ============================= NAVBAR ============================= --}}
    {{-- The completion screen hides site navigation so an incomplete account cannot browse away. --}}
    @if (request()->routeIs('profile.complete'))
        <header class="site-header profile-minimal-header">
            <div class="site-header-main">
                <img src="{{ asset('images/SSITE LOGO.png') }}" alt="SSITE Logo" class="site-logo">
            </div>
        </header>
    @else
    <header class="site-header public-home-header">
        {{-- Top bar: logo + search + icons --}}
        <div class="site-header-main">
            <a href="{{ $roleHomeUrl }}" class="site-brand">
                {{-- LOGO PLACEHOLDER --}}
                <img src="{{ $siteContent->image('app-header', 'logo_image') }}"
                     alt="SSITE Logo"
                     class="site-logo">
                <span class="site-brand-name">
                    <span class="brand-full">{{ $siteContent->value('app-header', 'brand_line_one') }}<br>{{ $siteContent->value('app-header', 'brand_line_two') }}</span>
                    <span class="brand-short">{{ $siteContent->value('app-header', 'initials') }}</span><span class="brand-campus">{{ $siteContent->value('app-header', 'campus') }}</span>
                </span>
            </a>

            @unless ($isPublicLanding)
            <div class="site-header-actions">
                {{-- SECURITY: Both search forms use the public suggestions endpoint; suggestions contain visible content only. --}}
                @if (auth()->user()?->isAdviser())
                    <details class="site-edit-menu" @if (in_array(session('saved_section'), ['navigation', 'app-header'])) open @endif>
                        <summary aria-label="Edit header or navigation" title="Edit header or navigation"><x-icon name="pencil" size="sm" /></summary>
                        <div class="site-edit-menu-links">
                            <x-section-pencil section="app-header" labelled />
                            <x-section-pencil section="navigation" labelled />
                        </div>
                    </details>
                @endif
                <div class="site-search-desktop search-shell">
                    <form method="GET" action="{{ route('search.index') }}" class="search-form">
                        <input type="text" name="q" value="{{ request()->routeIs('search.index') && isset($query) ? $query : '' }}" placeholder="Discover something from SSITE..."
                               class="site-search-input" aria-label="Search SSITE" maxlength="100" role="combobox" aria-autocomplete="list" aria-expanded="false"
                               aria-controls="desktop-search-list" autocomplete="off">
                        <button type="submit" class="search-submit-icon" aria-label="Search">
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
                        class="icon-button mobile-search-toggle">
                    <x-icon name="search" class="icon" />
                </button>

                {{-- Only Microsoft sign-in is available to guests. --}}
                @guest
                    <a href="{{ route('microsoft.login') }}" class="site-button login-toggle-button"><span class="login-label-full">Sign in with Microsoft</span><span class="login-label-short">Sign in</span></a>
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
                                    <x-icon name="user-round" size="sm" class="icon-small" />
                                    <span>My Profile</span>
                                </a>
                            @endif
                            @if (auth()->user()->canPost() || auth()->user()->can('review-posts') || auth()->user()->can('manage-users') || auth()->user()->can('manage-officers'))
                                <div class="account-menu-divider" role="separator"></div>
                                <a href="{{ route(auth()->user()->dashboardRoute()) }}" class="account-menu-action" role="menuitem">
                                    <x-icon name="layout-dashboard" size="sm" class="icon-small" />
                                    <span>Open workspace</span>
                                </a>
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

            </div>
            @endunless
        </div>

        @unless ($isPublicLanding)
        {{-- Mobile search field reuses the public suggestions endpoint. --}}
        <div id="mobile-search" class="mobile-search hidden search-shell">
            <div class="mobile-search-field">
                <form method="GET" action="{{ route('search.index') }}" class="search-form">
                    <input type="text" name="q" value="{{ request()->routeIs('search.index') && isset($query) ? $query : '' }}" placeholder="Discover something from SSITE..."
                           class="site-search-input" aria-label="Search SSITE" maxlength="100" role="combobox" aria-autocomplete="list" aria-expanded="false"
                           aria-controls="mobile-search-list" autocomplete="off">
                    <button type="submit" class="search-submit-icon" aria-label="Search">
                        <x-icon name="search" size="sm" class="site-search-icon" />
                    </button>
                </form>
                <div id="mobile-search-list" class="search-suggestions hidden" role="listbox" aria-label="Search suggestions"></div>
            </div>
        </div>
        @endunless

    </header>
    @unless ($isPublicLanding)
        @include('layouts.partials.navigation')
    @endunless
    @endif

    <script>
        (() => {
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
    @unless ($isPublicLanding)
    <footer class="site-footer">
        <div class="site-footer-content">
            {{-- Left: org info --}}
            <div class="footer-organization">
                <div class="footer-brand">
                    {{-- LOGO PLACEHOLDER --}}
                    <img src="{{ $siteContent->image('app-footer', 'organization_logo_image') }}"
                         alt="SSITE Logo"
                        class="footer-logo">
                    <span class="footer-brand-name">
                        {{ $siteContent->value('app-footer', 'organization_line_one') }}<br>
                        {{ $siteContent->value('app-footer', 'organization_line_two') }}
                    </span>
                </div>
                <p class="footer-description">
                    {{ $siteContent->value('app-footer', 'description') }}
                </p>

                <div class="footer-badges">
                    {{-- BADGE PLACEHOLDERS --}}
                    <img src="{{ $siteContent->image('app-footer', 'institute_logo_image') }}" alt="Badge 1" class="footer-badge">
                    <img src="{{ $siteContent->image('app-footer', 'college_logo_image') }}" alt="Badge 2" class="footer-badge">
                    <img src="{{ $siteContent->image('app-footer', 'society_logo_image') }}" alt="Badge 3" class="footer-badge">
                </div>
            </div>

            {{-- Right: contact --}}
            <div class="footer-contact">
                <h4 class="footer-heading">{{ $siteContent->value('app-footer', 'contact_heading') }} <x-section-pencil section="app-footer" /></h4>
                <ul class="footer-contact-list">
                    <li class="footer-contact-item">
                        <a href="{{ $siteContent->value('app-footer', 'map_url') }}" class="footer-contact-button" target="_blank" rel="noopener noreferrer">
                            <x-icon name="map-pin" size="md" class="footer-contact-icon" />
                            <span>{{ $siteContent->value('app-footer', 'address') }}</span>
                        </a>
                    </li>
                    <li class="footer-contact-item">
                        <a href="{{ $siteContent->value('app-footer', 'facebook_url') }}" class="footer-contact-button" target="_blank" rel="noopener noreferrer">
                            <x-icon name="facebook" size="md" class="footer-contact-icon" />
                            <span>{{ $siteContent->value('app-footer', 'facebook_label') }}</span>
                        </a>
                    </li>
                    <li class="footer-contact-item">
                        <a href="mailto:{{ $siteContent->value('app-footer', 'contact_email') }}" class="footer-contact-button">
                            <x-icon name="mail" size="md" class="footer-contact-icon" />
                            <span>{{ $siteContent->value('app-footer', 'contact_email') }}</span>
                        </a>
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
    @endunless
    @endif

    <button type="button" id="back-to-top" class="back-to-top hidden" aria-label="Back to top">
        <x-icon name="arrow-up" size="md" />
    </button>

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
</body>
</html>
