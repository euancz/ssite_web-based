@php
    $user = auth()->user();
    $workspaceUser = $user && ($user->canPost() || $user->canReview() || $user->isAdviser() || $user->isAdmin());
@endphp

@if ($workspaceUser)
    @php
        $workspaceConfig = config('workspace');
        $roleLabel = $user->position() ?? match ($user->role) {
            'adviser' => 'Adviser',
            'admin' => 'Admin',
            default => ucfirst($user->role),
        };
        $profileImage = $user->avatarUrl();
        $availableModules = collect($workspaceConfig['modules'])
            ->filter(fn ($module, $key) => $user->canReview()
                || ($module['capability'] === 'canPostIn:' . $key && $user->canPostIn($key)));
        $activeModule = $availableModules->keys()->first();

        foreach ($availableModules as $moduleKey => $module) {
            if (request()->routeIs($moduleKey . '.*', 'editorial.' . $moduleKey . '.*', 'workspace.posts.' . $moduleKey . '.*')) {
                $activeModule = $moduleKey;
                break;
            }
        }
    @endphp

    <a class="workspace-skip-link" href="#main-content">Skip to content</a>
    <div class="workspace-shell" data-workspace-shell>
        <button class="workspace-scrim" type="button" aria-label="Close workspace navigation" tabindex="-1" hidden></button>
        <aside class="workspace-sidebar" id="workspace-sidebar" aria-label="Workspace navigation">
            <a class="workspace-brand" href="{{ route($user->dashboardRoute()) }}">
                <img src="{{ asset('images/ssite-workspace-44.png') }}"
                     srcset="{{ asset('images/ssite-workspace-88.png') }} 2x, {{ asset('images/ssite-workspace-132.png') }} 3x"
                     width="44" height="44" alt="SSITE">
                <span class="workspace-brand-copy"><strong>{{ $workspaceConfig['brand'] }}</strong><small>Content &amp; publishing</small></span>
            </a>

            <nav class="workspace-nav" aria-label="Workspace">
                <section class="workspace-nav-section" aria-label="Overview">
                    <a class="workspace-nav-link {{ request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}"
                       href="{{ route($user->dashboardRoute()) }}" aria-label="Dashboard" data-tooltip="Dashboard"
                       @if (request()->routeIs('officer.dashboard', 'adviser.dashboard')) aria-current="page" @endif>
                        <x-workspace.icon name="grid" /><span>Dashboard</span>
                    </a>
                </section>

                @if ($user->canPost() || $availableModules->isNotEmpty())
                <section class="workspace-nav-section" aria-label="Content">
                    <span class="workspace-nav-label">Content</span>
                @if ($user->canPost())
                    @php
                        $creatingPost = request()->routeIs('workspace.create', 'articles.create', 'activities.create', 'achievements.create', 'documents.create', 'liquidations.create');
                    @endphp
                        <a class="workspace-nav-link {{ $creatingPost ? 'is-active' : '' }}"
                           href="{{ route('workspace.create') }}" aria-label="Create post" data-tooltip="Create post"
                           @if ($creatingPost) aria-current="page" @endif>
                            <x-workspace.icon name="plus" /><span>Create post</span>
                        </a>
                @endif
                @if ($availableModules->isNotEmpty())
                    @php
                        $libraryRoute = $activeModule ? $availableModules[$activeModule]['workspace'] : null;
                        $isLibraryRoute = $activeModule && request()->routeIs($activeModule . '.index', 'editorial.' . $activeModule . '.index', 'workspace.posts.' . $activeModule . '.index');
                        $activeLibraryTab = request('tab', $user->isPresident() ? 'published' : 'mine');
                    @endphp
                    @php($isMyWork = request()->routeIs('workspace.drafts.*') || ($isLibraryRoute && $activeLibraryTab === 'mine'))
                        <a class="workspace-nav-link {{ $isMyWork ? 'is-active' : '' }}" href="{{ $user->canPost() ? route('workspace.drafts.index') : route($libraryRoute, ['tab' => 'mine']) }}" aria-label="My work" data-tooltip="My work" @if($isMyWork) aria-current="page" @endif><x-workspace.icon name="file" /><span>My work</span></a>
                    @if ($libraryRoute)
                            @php($isManagePosts = $isLibraryRoute && $activeLibraryTab !== 'mine')
                            <a class="workspace-nav-link workspace-library-link {{ $isManagePosts ? 'is-active' : '' }}" href="{{ route($libraryRoute, ['tab' => 'published']) }}" aria-label="Manage posts" data-tooltip="Manage posts" @if($isManagePosts) aria-current="page" @endif><x-workspace.icon name="files" /><span>Manage posts</span></a>
                    @endif
                        <a class="workspace-nav-link {{ request()->routeIs('workspace.content-activity.*') ? 'is-active' : '' }}" href="{{ route('workspace.content-activity.index') }}" aria-label="Activity log" data-tooltip="Activity log" @if(request()->routeIs('workspace.content-activity.*')) aria-current="page" @endif><x-workspace.icon name="logs" /><span>Activity log</span></a>
                @endif
                </section>
                @endif

                @if ($user->canReview()
                    || $user->can(config('workspace.navigation.users.gate'))
                    || $user->can(config('workspace.navigation.officers.gate')))
                    <section class="workspace-nav-section" aria-label="People and review">
                        <span class="workspace-nav-label">{{ $workspaceConfig['sections']['administration'] }}</span>
                        @can(config('workspace.navigation.review.gate'))
                            <a class="workspace-nav-link {{ request()->routeIs('adviser.reviews.*') ? 'is-active' : '' }}"
                               href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}"
                               aria-label="Review queue{{ ($pendingReviewCount ?? 0) > 0 ? ', ' . $pendingReviewCount . ' pending' : '' }}"
                               data-tooltip="Review queue" @if (request()->routeIs('adviser.reviews.*')) aria-current="page" @endif>
                                <x-workspace.icon name="clipboard-check" /><span>Review queue</span>
                                @if (($pendingReviewCount ?? 0) > 0)
                                    <span class="workspace-nav-count">{{ $pendingReviewCount > 99 ? '99+' : $pendingReviewCount }}</span>
                                @endif
                            </a>
                        @endcan
                        @can(config('workspace.navigation.officers.gate'))
                            <a class="workspace-nav-link {{ request()->routeIs('adviser.officers.*') ? 'is-active' : '' }}"
                               href="{{ route('adviser.officers.index') }}" aria-label="Officer roster"
                               data-tooltip="Officer roster" @if (request()->routeIs('adviser.officers.*')) aria-current="page" @endif>
                                <x-workspace.icon name="contact-round" /><span>Officer roster</span>
                            </a>
                        @endcan
                        @can('manage-sponsors')
                            <a class="workspace-nav-link {{ request()->routeIs('workspace.sponsors.*') ? 'is-active' : '' }}"
                               href="{{ route('workspace.sponsors.index') }}" aria-label="Sponsors" data-tooltip="Sponsors"
                               @if (request()->routeIs('workspace.sponsors.*')) aria-current="page" @endif>
                                <x-workspace.icon name="handshake" /><span>Sponsors</span>
                            </a>
                        @endcan
                        @can(config('workspace.navigation.users.gate'))
                            <a class="workspace-nav-link {{ request()->routeIs('adviser.users.*') ? 'is-active' : '' }}"
                               href="{{ route('adviser.users.index') }}" aria-label="{{ $user->isAdmin() ? 'Advisers' : 'Officers and positions' }}"
                               data-tooltip="{{ $user->isAdmin() ? 'Advisers' : 'Officers and positions' }}"
                               @if (request()->routeIs('adviser.users.*')) aria-current="page" @endif>
                                <x-workspace.icon name="users" /><span>{{ $user->isAdmin() ? 'Advisers' : 'Officers and positions' }}</span>
                            </a>
                        @endcan
                    </section>
                @endif
            </nav>

        </aside>

        <div class="workspace-main">
            <header class="workspace-topbar">
                {{-- SECURITY: Keep the menu's accessible name and visible tooltip in sync with its existing responsive state. --}}
                <button class="workspace-menu-toggle" type="button" aria-label="Collapse sidebar" data-tooltip="Collapse sidebar" aria-pressed="false" aria-controls="workspace-sidebar">
                    <x-workspace.icon name="menu" />
                </button>
                <div class="workspace-topbar-actions">
                    <a class="workspace-view-site" href="{{ url('/') }}" aria-label="View site" data-tooltip="View site"><x-workspace.icon name="external" /><span>View site</span></a>
                    <div class="notification-menu-wrapper workspace-notification" id="notification-menu-wrapper"
                         data-summary-url="{{ route('notifications.summary') }}"
                         data-read-url-template="{{ route('notifications.read', '00000000-0000-4000-8000-000000000000') }}"
                         data-avatar-url-template="{{ route('notifications.avatar', '00000000-0000-4000-8000-000000000000') }}">
                        <button type="button" id="notifications-toggle" aria-label="Notifications" data-tooltip="Notifications" aria-controls="notifications-dropdown" aria-expanded="false" class="workspace-notification-toggle">
                            <x-workspace.icon name="bell" />
                            {{-- SECURITY: Start from the exact server-rendered unread value so the first poll is not animated. --}}
                            <span id="notification-badge" class="notification-badge hidden" data-motion="badge" data-unread-count="0">0</span>
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
                    <div class="account-menu-wrapper workspace-account-menu" id="account-menu-wrapper">
                        <button type="button" id="account-menu-toggle" aria-label="Account menu" data-tooltip="Account menu" aria-haspopup="menu" aria-controls="account-menu" aria-expanded="false" class="workspace-profile-toggle">
                            @if ($profileImage)
                                <img src="{{ $profileImage }}" alt="" class="workspace-profile-picture">
                            @else
                                <span class="workspace-profile-picture workspace-profile-initials" aria-hidden="true">{{ $user->initials() }}</span>
                            @endif
                            <span class="workspace-profile-copy"><strong>{{ $user->name }}</strong><small>{{ $roleLabel }}</small></span>
                            <x-workspace.icon name="chevron" />
                        </button>
                        <div id="account-menu" class="account-menu hidden" role="menu" aria-label="Account menu">
                            <div class="account-menu-identity" role="presentation">
                                @include('layouts.partials.account-avatar', ['user' => $user])
                                <div class="account-menu-identity-copy"><strong>{{ $user->name }}</strong><span>{{ $roleLabel }}</span></div>
                            </div>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="account-menu-logout" role="menuitem">Logout</button>
                            </form>
                        </div>
                    </div>
                </div>
            </header>

            <main class="workspace-content" id="main-content" tabindex="-1">
                @yield('content')
            </main>
        </div>
    </div>

    @include('layouts.partials.workspace-tools')
    <script src="{{ asset('js/workspace-ui.js') }}" defer></script>

    <script>
        (() => {
            const shell = document.querySelector('[data-workspace-shell]');
            const sidebar = document.getElementById('workspace-sidebar');
            const toggle = document.querySelector('.workspace-menu-toggle');
            const scrim = document.querySelector('.workspace-scrim');
            if (!shell || !sidebar || !toggle || !scrim) return;

            shell.classList.add('has-js');
            const phone = window.matchMedia('(max-width: 900px)');
            const storageKey = 'ssite-workspace-collapsed';
            let drawerOpen = false;
            let collapsed = false;

            try {
                collapsed = window.localStorage.getItem(storageKey) === 'true';
            } catch (_) {
                collapsed = false;
            }

            const setCollapsed = (value) => {
                collapsed = value;
                shell.classList.toggle('is-collapsed', value);
                toggle.setAttribute('aria-pressed', String(value));
                toggle.setAttribute('aria-label', value ? 'Expand sidebar' : 'Collapse sidebar');
                toggle.dataset.tooltip = value ? 'Expand sidebar' : 'Collapse sidebar';
                try {
                    window.localStorage.setItem(storageKey, String(value));
                } catch (_) {
                    // Private browsing may disable storage; the current page state still works.
                }
            };

            const setDrawerOpen = (value, restoreFocus = false) => {
                drawerOpen = value;
                sidebar.classList.toggle('is-open', value);
                sidebar.inert = phone.matches && !value;
                sidebar.setAttribute('aria-hidden', String(phone.matches && !value));
                scrim.hidden = !value;
                scrim.classList.toggle('is-visible', value);
                document.body.classList.toggle('workspace-menu-open', value);
                toggle.setAttribute('aria-expanded', String(value));
                toggle.setAttribute('aria-label', value ? 'Close workspace navigation' : 'Open workspace navigation');
                toggle.dataset.tooltip = value ? 'Close workspace navigation' : 'Open workspace navigation';
                if (value) sidebar.querySelector('a[href]')?.focus();
                if (!value && restoreFocus) toggle.focus();
            };

            const syncViewport = () => {
                if (phone.matches) {
                    shell.classList.remove('is-collapsed');
                    setDrawerOpen(false);
                    toggle.setAttribute('aria-pressed', 'false');
                } else {
                    setDrawerOpen(false);
                    sidebar.inert = false;
                    sidebar.setAttribute('aria-hidden', 'false');
                    setCollapsed(collapsed);
                }
            };

            syncViewport();
            toggle.addEventListener('click', () => {
                if (phone.matches) setDrawerOpen(!drawerOpen);
                else setCollapsed(!collapsed);
            });
            scrim.addEventListener('click', () => setDrawerOpen(false, true));
            sidebar.querySelectorAll('a[href]').forEach((link) => link.addEventListener('click', () => {
                if (phone.matches) setDrawerOpen(false);
            }));
            document.addEventListener('keydown', (event) => {
                if (!phone.matches || !drawerOpen) return;
                if (event.key === 'Escape') {
                    event.preventDefault();
                    setDrawerOpen(false, true);
                } else if (event.key === 'Tab') {
                    const focusable = [...sidebar.querySelectorAll('a[href], button:not([disabled]), select:not([disabled]), input:not([disabled])')];
                    if (!focusable.length) return;
                    const first = focusable[0];
                    const last = focusable[focusable.length - 1];
                    if (event.shiftKey && (document.activeElement === first || document.activeElement === sidebar)) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            });
            phone.addEventListener('change', syncViewport);
        })();
    </script>
@else
    <main id="main-content">@yield('content')</main>
@endif
