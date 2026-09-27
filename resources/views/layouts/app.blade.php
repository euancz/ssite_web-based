<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'SSITE') | Student Society in Information Technology Education</title>

    <!-- Tailwind (CDN, swap for the compiled build.css whenever you set up Vite) -->
    <script src="https://cdn.tailwindcss.com"></script>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Lora:wght@500;600;700&family=Inter:wght@400;500;600&display=swap" rel="stylesheet">

    <!-- App styles -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">

    @stack('styles')
</head>
<body class="bg-white antialiased">

    {{-- ============================= NAVBAR ============================= --}}
    <header class="ssite-bg">
        {{-- Top bar: logo + search + icons --}}
        <div class="max-w-7xl mx-auto px-4 sm:px-6 py-3 sm:py-4 flex items-center justify-between gap-3 sm:gap-6">
            <a href="{{ url('/') }}" class="flex items-center gap-2 sm:gap-3 shrink-0 min-w-0">
                {{-- LOGO PLACEHOLDER --}}
                <img src="https://placehold.co/56x56/17324D/A7E1F5?text=SSITE"
                     alt="SSITE Logo"
                     class="h-10 w-10 sm:h-14 sm:w-14 rounded-full object-cover shrink-0">
                <span class="font-display font-bold leading-tight text-[11px] xs:text-xs sm:text-[15px] md:text-base ssite-navy truncate sm:whitespace-normal">
                    <span class="hidden sm:inline">STUDENT SOCIETY IN INFORMATION<br>TECHNOLOGY EDUCATION</span>
                    <span class="sm:hidden">SSITE</span>
                </span>
            </a>

            <div class="flex items-center gap-2 sm:gap-4 shrink-0">
                <div class="relative hidden lg:block">
                    <input type="text"
                           placeholder="Discover something from SSITE..."
                           class="w-64 xl:w-80 rounded-full border-0 bg-white/90 py-2.5 pl-5 pr-10 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-white">
                    <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                    </svg>
                </div>

                {{-- search icon (mobile/tablet only, opens the mobile search field below) --}}
                <button type="button"
                        aria-label="Search"
                        onclick="document.getElementById('mobile-search').classList.toggle('hidden')"
                        class="lg:hidden h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-[var(--ssite-navy)] text-white flex items-center justify-center hover:opacity-90">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                    </svg>
                </button>

                <button type="button" aria-label="Notifications" class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-[var(--ssite-navy)] text-white flex items-center justify-center hover:opacity-90">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 10-12 0v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                    </svg>
                </button>

                <a href="{{ url('/login') }}" aria-label="Account" class="h-9 w-9 sm:h-10 sm:w-10 rounded-full bg-[var(--ssite-navy)] text-white flex items-center justify-center hover:opacity-90">
                    <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 11-7.5 0 3.75 3.75 0 017.5 0zM4.5 20.25a8.25 8.25 0 0115 0"/>
                    </svg>
                </a>

                {{-- hamburger (mobile/tablet only) --}}
                <button type="button"
                        aria-label="Open menu"
                        aria-expanded="false"
                        aria-controls="mobile-nav"
                        onclick="document.getElementById('mobile-nav').classList.toggle('hidden'); document.getElementById('icon-open').classList.toggle('hidden'); document.getElementById('icon-close').classList.toggle('hidden');"
                        class="md:hidden h-9 w-9 rounded-full bg-[var(--ssite-navy)] text-white flex items-center justify-center hover:opacity-90">
                    <svg id="icon-open" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="icon-close" class="h-5 w-5 hidden" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>

        {{-- mobile search field --}}
        <div id="mobile-search" class="hidden lg:hidden px-4 sm:px-6 pb-3">
            <div class="relative">
                <input type="text"
                       placeholder="Discover something from SSITE..."
                       class="w-full rounded-full border-0 bg-white/90 py-2.5 pl-5 pr-10 text-sm text-slate-700 placeholder:text-slate-400 focus:outline-none focus:ring-2 focus:ring-white">
                <svg class="absolute right-3.5 top-1/2 -translate-y-1/2 h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M18 10.5a7.5 7.5 0 11-15 0 7.5 7.5 0 0115 0z"/>
                </svg>
            </div>
        </div>

        {{-- Desktop nav links row --}}
        <nav class="ssite-bg border-t border-white/40 hidden md:block">
            <ul class="max-w-7xl mx-auto px-6 flex items-center gap-8 text-[15px] font-medium ssite-navy overflow-x-auto">
                <li class="py-3">
                    <a href="{{ url('/') }}" class="hover:opacity-70 {{ request()->is('/') ? 'font-semibold' : '' }}">Home</a>
                </li>
                <li class="py-3"><a href="{{ url('/about') }}" class="hover:opacity-70">About us</a></li>

                <li class="py-3 relative group">
                    <button type="button" class="flex items-center gap-1 hover:opacity-70">
                        Articles
                        <svg class="h-4 w-4 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul class="absolute left-0 top-full hidden group-hover:block bg-white text-slate-700 rounded-lg shadow-lg border border-[var(--ssite-line)] min-w-[180px] py-2 z-20">
                        <li><a href="{{ url('/articles') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">All Articles</a></li>
                    </ul>
                </li>

                <li class="py-3 relative group">
                    <button type="button" class="flex items-center gap-1 hover:opacity-70">
                        Activities
                        <svg class="h-4 w-4 mt-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <ul class="absolute left-0 top-full hidden group-hover:block bg-white text-slate-700 rounded-lg shadow-lg border border-[var(--ssite-line)] min-w-[180px] py-2 z-20">
                        <li><a href="{{ url('/activities') }}" class="block px-4 py-2 text-sm hover:bg-slate-50">All Activities</a></li>
                    </ul>
                </li>

                <li class="py-3"><a href="{{ url('/achievements') }}" class="hover:opacity-70">Achievements</a></li>
                <li class="py-3"><a href="{{ url('/liquidation') }}" class="hover:opacity-70">Liquidation</a></li>
                <li class="py-3"><a href="{{ url('/documents') }}" class="hover:opacity-70">Documents</a></li>
            </ul>
        </nav>

        {{-- Mobile / tablet nav menu (collapsible) --}}
        <nav id="mobile-nav" class="hidden md:hidden ssite-bg border-t border-white/40">
            <ul class="px-4 sm:px-6 py-2 flex flex-col text-[15px] font-medium ssite-navy divide-y divide-white/40">
                <li class="py-3">
                    <a href="{{ url('/') }}" class="block {{ request()->is('/') ? 'font-semibold' : '' }}">Home</a>
                </li>
                <li class="py-3"><a href="{{ url('/about') }}" class="block">About us</a></li>

                <li class="py-1">
                    <details class="group/details">
                        <summary class="list-none flex items-center justify-between py-2 cursor-pointer">
                            Articles
                            <svg class="h-4 w-4 transition-transform group-open/details:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <ul class="pl-3 pb-2 text-slate-700/90">
                            <li><a href="{{ url('/articles') }}" class="block py-1.5 text-sm">All Articles</a></li>
                        </ul>
                    </details>
                </li>

                <li class="py-1">
                    <details class="group/details">
                        <summary class="list-none flex items-center justify-between py-2 cursor-pointer">
                            Activities
                            <svg class="h-4 w-4 transition-transform group-open/details:rotate-180" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </summary>
                        <ul class="pl-3 pb-2 text-slate-700/90">
                            <li><a href="{{ url('/activities') }}" class="block py-1.5 text-sm">All Activities</a></li>
                        </ul>
                    </details>
                </li>

                <li class="py-3"><a href="{{ url('/achievements') }}" class="block">Achievements</a></li>
                <li class="py-3"><a href="{{ url('/liquidation') }}" class="block">Liquidation</a></li>
                <li class="py-3"><a href="{{ url('/documents') }}" class="block">Documents</a></li>
            </ul>
        </nav>
    </header>

    {{-- ============================= PAGE CONTENT ============================= --}}
    <main>
        @yield('content')
    </main>

    {{-- ============================= FOOTER ============================= --}}
    <footer class="ssite-bg mt-16">
        <div class="max-w-7xl mx-auto px-6 py-12 grid grid-cols-1 md:grid-cols-2 gap-10">
            {{-- Left: org info --}}
            <div>
                <div class="flex items-center gap-3 mb-4">
                    {{-- LOGO PLACEHOLDER --}}
                    <img src="https://placehold.co/56x56/17324D/A7E1F5?text=SSITE"
                         alt="SSITE Logo"
                         class="h-14 w-14 rounded-full object-cover">
                    <span class="font-display font-bold leading-tight text-[15px] ssite-navy">
                        STUDENT SOCIETY IN INFORMATION<br>
                        TECHNOLOGY EDUCATION
                    </span>
                </div>
                <p class="ssite-navy/90 text-sm mb-6 max-w-sm">
                    The Official Organization of Information Technology Education in MCC
                </p>

                <div class="flex items-center gap-4">
                    {{-- BADGE PLACEHOLDERS --}}
                    <img src="https://placehold.co/64x64/17324D/A7E1F5?text=Badge+1" alt="Badge 1" class="h-16 w-16 rounded-full object-cover">
                    <img src="https://placehold.co/64x64/17324D/A7E1F5?text=Badge+2" alt="Badge 2" class="h-16 w-16 rounded-full object-cover">
                    <img src="https://placehold.co/64x64/17324D/A7E1F5?text=Badge+3" alt="Badge 3" class="h-16 w-16 rounded-full object-cover">
                </div>
            </div>

            {{-- Right: contact --}}
            <div class="md:justify-self-end">
                <h4 class="font-display font-semibold text-lg mb-4 ssite-navy">Contact Us</h4>
                <ul class="space-y-3 text-sm ssite-navy">
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17.657 16.657L13.414 20.9a2 2 0 01-2.828 0l-4.243-4.243a8 8 0 1111.314 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        <span>Dolores, Mabalacat City College</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 shrink-0" fill="currentColor" viewBox="0 0 24 24">
                            <path d="M22 12a10 10 0 10-11.5 9.87v-6.99H7.9V12h2.6V9.8c0-2.57 1.53-3.99 3.87-3.99 1.12 0 2.3.2 2.3.2v2.53h-1.3c-1.28 0-1.68.8-1.68 1.62V12h2.86l-.46 2.88h-2.4v6.99A10 10 0 0022 12z"/>
                        </svg>
                        <span>Student Society in Information Technology Education</span>
                    </li>
                    <li class="flex items-center gap-3">
                        <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <a href="mailto:ssite@gmail.com" class="underline hover:opacity-80">ssite@gmail.com</a>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-white/40">
            <p class="max-w-7xl mx-auto px-6 py-5 text-center text-xs font-medium ssite-navy">
                &copy; {{ date('Y') }} Student Society in Information Technology Education — Mabalacat City College
            </p>
        </div>
    </footer>

    @stack('scripts')
</body>
</html>