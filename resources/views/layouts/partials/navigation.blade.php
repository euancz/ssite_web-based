{{-- The pill stays public and role neutral; role shortcuts live in the account menu so each destination appears once. --}}
        {{-- Desktop nav links row --}}
        <nav class="desktop-navigation" @if(request()->is('/')) style="display:flex;width:fit-content;margin:0 auto -0.1rem;" @endif>
            <ul class="site-nav-list" @if(request()->is('/')) style="width:fit-content;" @endif>
                <li class="site-nav-item">
                    <a href="{{ $roleHomeUrl }}"
                       class="site-nav-link {{ request()->is('/') || request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}">
                        Home
                    </a>
                </li>
                <li class="site-nav-item"><a href="{{ url('/discover') }}" class="site-nav-link {{ request()->routeIs('discover') ? 'is-active' : '' }}" @if(request()->routeIs('discover')) aria-current="page" @endif>Discover</a></li>
                <li class="site-nav-item"><a href="{{ route('transparency') }}" class="site-nav-link {{ request()->routeIs('transparency') ? 'is-active' : '' }}" @if(request()->routeIs('transparency')) aria-current="page" @endif>Transparency</a></li>
                <li class="site-nav-item"><a href="{{ url('/documents') }}" class="site-nav-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}" @if(request()->routeIs('documents.*')) aria-current="page" @endif>Documents <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span></a></li>
                <li class="site-nav-item"><a href="{{ url('/about') }}" class="site-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif>About us</a></li>
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
                <li class="mobile-nav-item"><a href="{{ url('/discover') }}" class="mobile-nav-link {{ request()->routeIs('discover') ? 'is-active' : '' }}" @if(request()->routeIs('discover')) aria-current="page" @endif>Discover</a></li>
                <li class="mobile-nav-item"><a href="{{ route('transparency') }}" class="mobile-nav-link {{ request()->routeIs('transparency') ? 'is-active' : '' }}" @if(request()->routeIs('transparency')) aria-current="page" @endif>Transparency</a></li>
                <li class="mobile-nav-item"><a href="{{ url('/documents') }}" class="mobile-nav-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}" @if(request()->routeIs('documents.*')) aria-current="page" @endif>Documents <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span></a></li>
                <li class="mobile-nav-item"><a href="{{ url('/about') }}" class="mobile-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif>About us</a></li>
            </ul>
        </nav>
