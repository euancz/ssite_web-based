{{-- Desktop and mobile links keep their existing role checks. --}}
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
                    {{-- SECURITY: Only advisers can manage officer-term records. --}}
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
                    {{-- SECURITY: Only advisers can manage officer-term records. --}}
                    @can('manage-users')
                        <li class="mobile-nav-item"><a href="{{ route('adviser.officers.index') }}" class="mobile-nav-link">Manage Officers</a></li>
                    @endcan
                @endauth
            </ul>
        </nav>
