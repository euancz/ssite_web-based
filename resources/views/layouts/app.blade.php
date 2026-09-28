<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SSITE') | Student Society in Information Technology Education</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- App styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>
<body class="site-body">

    {{-- ============================= NAVBAR ============================= --}}
    <header class="site-header">
        {{-- Top bar: logo + search + icons --}}
        <div class="site-header-main">
            <a href="{{ url('/') }}" class="site-brand">
                {{-- LOGO PLACEHOLDER --}}
                <img src="https://placehold.co/56x56/17324D/A7E1F5?text=SSITE"
                     alt="SSITE Logo"
                     class="site-logo">
                <span class="site-brand-name">
                    <span class="brand-full">STUDENT SOCIETY IN INFORMATION<br>TECHNOLOGY EDUCATION</span>
                    <span class="brand-short">SSITE</span>
                </span>
            </a>

            <div class="site-header-actions">
                <div class="site-search-desktop">
                    <input type="text"
                           placeholder="Discover something from SSITE..."
                           class="site-search-input">
                    <svg class="site-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                    </svg>
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
                @auth
                    <button type="button" aria-label="Notifications" class="icon-button account-notifications">
                        <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </button>

                    <div class="account-menu-wrapper" id="account-menu-wrapper">
                        <button type="button"
                                id="account-menu-toggle"
                                aria-label="Account menu"
                                aria-controls="account-menu"
                                aria-expanded="false"
                                class="icon-button account-toggle">
                            <svg class="icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/>
                            </svg>
                        </button>

                        <div id="account-menu" class="account-menu hidden">
                            <div class="account-menu-name">
                                {{ auth()->user()->name }}
                            </div>
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
        <div id="mobile-search" class="mobile-search hidden">
            <div class="mobile-search-field">
                <input type="text"
                       placeholder="Discover something from SSITE..."
                       class="site-search-input">
                <svg class="site-search-icon" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                </svg>
            </div>
        </div>

        {{-- Desktop nav links row --}}
        <nav class="desktop-navigation">
            <ul class="site-nav-list">
                <li class="site-nav-item">
                    <a href="{{ url('/') }}" class="site-nav-link {{ request()->is('/') ? 'is-active' : '' }}">Home</a>
                </li>
                <li class="site-nav-item"><a href="{{ url('/about') }}" class="site-nav-link">About us</a></li>

                <li class="site-nav-item site-nav-dropdown">
                    <button type="button" class="site-nav-link site-nav-dropdown-toggle">
                        Articles
                        <svg class="dropdown-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul class="site-nav-dropdown-menu">
                        <li><a href="{{ url('/articles') }}" class="site-dropdown-link">All Articles</a></li>
                    </ul>
                </li>

                <li class="site-nav-item site-nav-dropdown">
                    <button type="button" class="site-nav-link site-nav-dropdown-toggle">
                        Activities
                        <svg class="dropdown-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul class="site-nav-dropdown-menu">
                        <li><a href="{{ url('/activities') }}" class="site-dropdown-link">All Activities</a></li>
                    </ul>
                </li>

                <li class="site-nav-item"><a href="{{ url('/achievements') }}" class="site-nav-link">Achievements</a></li>
                <li class="site-nav-item"><a href="{{ url('/liquidation') }}" class="site-nav-link">Liquidation</a></li>
                <li class="site-nav-item"><a href="{{ url('/documents') }}" class="site-nav-link">Documents</a></li>
            </ul>
        </nav>

        {{-- Mobile / tablet nav menu (collapsible) --}}
        <nav id="mobile-nav" class="mobile-navigation hidden">
            <ul class="mobile-nav-list">
                <li class="mobile-nav-item">
                    <a href="{{ url('/') }}" class="mobile-nav-link {{ request()->is('/') ? 'is-active' : '' }}">Home</a>
                </li>
                <li class="mobile-nav-item"><a href="{{ url('/about') }}" class="mobile-nav-link">About us</a></li>

                <li class="mobile-nav-dropdown-item">
                    <details class="mobile-nav-dropdown">
                        <summary class="mobile-nav-summary">
                            Articles
                            <svg class="mobile-nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <ul class="mobile-nav-sublist">
                            <li><a href="{{ url('/articles') }}" class="mobile-nav-sublink">All Articles</a></li>
                        </ul>
                    </details>
                </li>

                <li class="mobile-nav-dropdown-item">
                    <details class="mobile-nav-dropdown">
                        <summary class="mobile-nav-summary">
                            Activities
                            <svg class="mobile-nav-chevron" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <ul class="mobile-nav-sublist">
                            <li><a href="{{ url('/activities') }}" class="mobile-nav-sublink">All Activities</a></li>
                        </ul>
                    </details>
                </li>

                <li class="mobile-nav-item"><a href="{{ url('/achievements') }}" class="mobile-nav-link">Achievements</a></li>
                <li class="mobile-nav-item"><a href="{{ url('/liquidation') }}" class="mobile-nav-link">Liquidation</a></li>
                <li class="mobile-nav-item"><a href="{{ url('/documents') }}" class="mobile-nav-link">Documents</a></li>
            </ul>
        </nav>
    </header>

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
                    <img src="https://placehold.co/56x56/17324D/A7E1F5?text=SSITE"
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
                    <img src="https://placehold.co/64x64/17324D/A7E1F5?text=Badge+1" alt="Badge 1" class="footer-badge">
                    <img src="https://placehold.co/64x64/17324D/A7E1F5?text=Badge+2" alt="Badge 2" class="footer-badge">
                    <img src="https://placehold.co/64x64/17324D/A7E1F5?text=Badge+3" alt="Badge 3" class="footer-badge">
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
        </script>
    @endauth
</body>
</html>