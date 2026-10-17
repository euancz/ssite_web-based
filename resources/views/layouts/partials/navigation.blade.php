{{-- The pill stays public and role neutral; role shortcuts live in the account menu so each destination appears once. --}}
        {{-- Desktop nav links row --}}
        <nav class="desktop-navigation">
            <ul class="site-nav-list">
                <li class="site-nav-item">
                    <a href="{{ $roleHomeUrl }}"
                       class="site-nav-link {{ request()->is('/') || request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}">
                        Home
                    </a>
                </li>
                <li class="site-nav-item"><a href="{{ url('/about') }}" class="site-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif>About us</a></li>

                <li class="site-nav-item"><a href="{{ route('articles.index') }}" class="site-nav-link {{ request()->routeIs('articles.*') ? 'is-active' : '' }}" @if(request()->routeIs('articles.*')) aria-current="page" @endif>Articles <span class="nav-unread-dot {{ ($notificationNavCounts['article'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="article" aria-label="Unread published articles"></span></a></li>
                <li class="site-nav-item"><a href="{{ route('activities.index') }}" class="site-nav-link {{ request()->routeIs('activities.*') ? 'is-active' : '' }}" @if(request()->routeIs('activities.*')) aria-current="page" @endif>Activities <span class="nav-unread-dot {{ ($notificationNavCounts['activity'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="activity" aria-label="Unread published activities"></span></a></li>

                <li class="site-nav-item"><a href="{{ url('/achievements') }}" class="site-nav-link {{ request()->routeIs('achievements.*') ? 'is-active' : '' }}" @if(request()->routeIs('achievements.*')) aria-current="page" @endif>Achievements <span class="nav-unread-dot {{ ($notificationNavCounts['achievement'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="achievement" aria-label="Unread published achievements"></span></a></li>
                <li class="site-nav-item"><a href="{{ url('/liquidation') }}" class="site-nav-link {{ request()->routeIs('liquidations.*') ? 'is-active' : '' }}" @if(request()->routeIs('liquidations.*')) aria-current="page" @endif>Liquidation <span class="nav-unread-dot {{ ($notificationNavCounts['liquidation'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="liquidation" aria-label="Unread published liquidations"></span></a></li>
                <li class="site-nav-item"><a href="{{ url('/documents') }}" class="site-nav-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}" @if(request()->routeIs('documents.*')) aria-current="page" @endif>Documents <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span></a></li>
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
                <li class="mobile-nav-item"><a href="{{ url('/about') }}" class="mobile-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif>About us</a></li>

                <li class="mobile-nav-item"><a href="{{ route('articles.index') }}" class="mobile-nav-link {{ request()->routeIs('articles.*') ? 'is-active' : '' }}" @if(request()->routeIs('articles.*')) aria-current="page" @endif>Articles <span class="nav-unread-dot {{ ($notificationNavCounts['article'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="article" aria-label="Unread published articles"></span></a></li>
                <li class="mobile-nav-item"><a href="{{ route('activities.index') }}" class="mobile-nav-link {{ request()->routeIs('activities.*') ? 'is-active' : '' }}" @if(request()->routeIs('activities.*')) aria-current="page" @endif>Activities <span class="nav-unread-dot {{ ($notificationNavCounts['activity'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="activity" aria-label="Unread published activities"></span></a></li>

                <li class="mobile-nav-item"><a href="{{ url('/achievements') }}" class="mobile-nav-link {{ request()->routeIs('achievements.*') ? 'is-active' : '' }}" @if(request()->routeIs('achievements.*')) aria-current="page" @endif>Achievements <span class="nav-unread-dot {{ ($notificationNavCounts['achievement'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="achievement" aria-label="Unread published achievements"></span></a></li>
                <li class="mobile-nav-item"><a href="{{ url('/liquidation') }}" class="mobile-nav-link {{ request()->routeIs('liquidations.*') ? 'is-active' : '' }}" @if(request()->routeIs('liquidations.*')) aria-current="page" @endif>Liquidation <span class="nav-unread-dot {{ ($notificationNavCounts['liquidation'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="liquidation" aria-label="Unread published liquidations"></span></a></li>
                <li class="mobile-nav-item"><a href="{{ url('/documents') }}" class="mobile-nav-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}" @if(request()->routeIs('documents.*')) aria-current="page" @endif>Documents <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span></a></li>
            </ul>
        </nav>
