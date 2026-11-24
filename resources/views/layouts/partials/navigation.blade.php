{{-- The pill stays public and role neutral; role shortcuts live in the account menu so each destination appears once. --}}
        {{-- Desktop nav links row --}}
        <nav aria-label="Main site" class="desktop-navigation">
            <ul class="site-nav-list">
                <li class="site-nav-item">
                    <a href="{{ $roleHomeUrl }}"
                       class="site-nav-link {{ request()->routeIs('home') ? 'is-active' : '' }}" @if(request()->routeIs('home')) aria-current="page" @endif>{{ $siteContent->value('navigation', 'home') }}
                    </a>
                </li>
                <li class="site-nav-item"><a href="{{ url('/discover') }}" class="site-nav-link {{ request()->routeIs('discover', 'articles.*', 'activities.*', 'achievements.*') ? 'is-active' : '' }}" @if(request()->routeIs('discover', 'articles.*', 'activities.*', 'achievements.*')) aria-current="page" @endif>{{ $siteContent->value('navigation', 'discover') }}</a></li>
                <li class="site-nav-item"><a href="{{ route('transparency') }}" class="site-nav-link {{ request()->routeIs('transparency', 'liquidations.*') ? 'is-active' : '' }}" @if(request()->routeIs('transparency', 'liquidations.*')) aria-current="page" @endif>{{ $siteContent->value('navigation', 'transparency') }}</a></li>
                <li class="site-nav-item"><a href="{{ url('/documents') }}" class="site-nav-link {{ request()->routeIs('documents.*') ? 'is-active' : '' }}" @if(request()->routeIs('documents.*')) aria-current="page" @endif>{{ $siteContent->value('navigation', 'documents') }} <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span></a></li>
                <li class="site-nav-item"><a href="{{ url('/about') }}" class="site-nav-link {{ request()->routeIs('about') ? 'is-active' : '' }}" @if(request()->routeIs('about')) aria-current="page" @endif>{{ $siteContent->value('navigation', 'about') }}</a></li>
            </ul>
        </nav>

        {{-- Same five destinations and order as the native mobile app. --}}
        @php
            $mobileDestinations = [
                ['label' => $siteContent->value('navigation', 'home'), 'url' => $roleHomeUrl, 'icon' => 'home', 'active' => request()->routeIs('home')],
                ['label' => $siteContent->value('navigation', 'discover'), 'url' => url('/discover'), 'icon' => 'compass', 'active' => request()->routeIs('discover', 'articles.*', 'activities.*', 'achievements.*')],
                ['label' => $siteContent->value('navigation', 'transparency'), 'url' => route('transparency'), 'icon' => 'receipt', 'active' => request()->routeIs('transparency', 'liquidations.*')],
                ['label' => $siteContent->value('navigation', 'documents'), 'url' => url('/documents'), 'icon' => 'folder', 'active' => request()->routeIs('documents.*')],
                ['label' => $siteContent->value('navigation', 'about'), 'url' => url('/about'), 'icon' => 'info', 'active' => request()->routeIs('about')],
            ];
        @endphp
        <nav aria-label="Main site" class="mobile-tab-navigation">
            <ul class="mobile-tab-list">
                @foreach ($mobileDestinations as $destination)
                    <li>
                        <a href="{{ $destination['url'] }}" class="mobile-tab-link {{ $destination['active'] ? 'is-active' : '' }}" @if($destination['active']) aria-current="page" @endif>
                            <span class="mobile-tab-icon">
                                <x-icon :name="$destination['icon']" />
                                @if ($destination['icon'] === 'folder')
                                    <span class="nav-unread-dot {{ ($notificationNavCounts['document'] ?? 0) ? '' : 'hidden' }}" data-notification-post-type="document" aria-label="Unread published documents"></span>
                                @endif
                            </span>
                            <span>{{ $destination['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </nav>
