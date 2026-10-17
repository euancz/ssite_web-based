@php
    $user = auth()->user();
    $workspaceUser = $user && ($user->canPost() || $user->canReview() || $user->isAdviser() || $user->isSuperadmin());
@endphp

@if ($workspaceUser)
    @php
        $workspaceConfig = config('workspace');
        $roleLabel = $user->position() ?? match ($user->role) {
            'adviser' => 'Adviser',
            'superadmin' => 'Superadmin',
            default => ucfirst($user->role),
        };
        $profileImage = $user->avatarUrl();
        $availableModules = collect($workspaceConfig['modules'])
            ->filter(fn ($module, $key) => $user->canReview()
                || ($module['capability'] === 'canPostIn:' . $key && $user->canPostIn($key)));
        $activeModule = $availableModules->keys()->first();

        foreach ($availableModules as $moduleKey => $module) {
            if (request()->routeIs($moduleKey . '.*', 'editorial.' . $moduleKey . '.*')) {
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
                <img src="{{ asset('images/SSITE LOGO.png') }}" alt="SSITE">
                <span class="workspace-brand-copy"><strong>{{ $workspaceConfig['brand'] }}</strong></span>
            </a>

            <section class="workspace-signed-in" aria-label="Signed in as">
                @if ($profileImage)
                    <img class="workspace-signed-avatar" src="{{ $profileImage }}" alt="">
                @else
                    <span class="workspace-signed-avatar workspace-profile-initials" aria-hidden="true">{{ $user->initials() }}</span>
                @endif
                <span class="workspace-signed-copy"><small>Signed in as</small><strong>{{ $user->name }}</strong><span>{{ $roleLabel }}</span></span>
            </section>

            <nav class="workspace-nav" aria-label="Workspace">
                <span class="workspace-nav-label">{{ $workspaceConfig['sections']['overview'] }}</span>
                <div class="workspace-nav-group">
                    <a class="workspace-nav-link {{ request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}"
                       href="{{ route($user->dashboardRoute()) }}" aria-label="Dashboard" data-tooltip="Dashboard"
                       @if (request()->routeIs('officer.dashboard', 'adviser.dashboard')) aria-current="page" @endif>
                        <x-workspace.icon name="grid" /><span>Dashboard</span>
                    </a>
                </div>

                @if ($user->canPost())
                    <span class="workspace-nav-label">{{ $workspaceConfig['sections']['create'] }}</span>
                    <div class="workspace-nav-group" aria-label="Create content">
                        @foreach ($availableModules as $moduleKey => $module)
                            @if ($module['capability'] === 'canPostIn:' . $moduleKey && $user->canPostIn($moduleKey))
                                <a class="workspace-nav-link {{ request()->routeIs($module['create'], str_replace('.create', '.edit', $module['create'])) ? 'is-active' : '' }}"
                                   href="{{ route($module['create']) }}" aria-label="Create {{ strtolower($module['singular']) }}"
                                   data-tooltip="Create {{ strtolower($module['singular']) }}"
                                   @if (request()->routeIs($module['create'], str_replace('.create', '.edit', $module['create'])) ) aria-current="page" @endif>
                                    <x-workspace.icon :name="$module['icon']" /><span>Create {{ strtolower($module['singular']) }}</span>
                                </a>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($availableModules->isNotEmpty())
                    <span class="workspace-nav-label">{{ $workspaceConfig['sections']['library'] }}</span>
                    <div class="workspace-post-library" aria-label="Published and archived posts">
                        @foreach (['published' => ['Published posts', 'check'], 'archived' => ['Archived posts', 'archive']] as $libraryTab => [$libraryLabel, $libraryIcon])
                            @php
                                $libraryRoute = $activeModule ? $availableModules[$activeModule][($libraryTab === 'archived' && $user->canReview()) ? 'editorial' : 'index'] : null;
                                $isLibraryRoute = $activeModule && request()->routeIs($activeModule . '.index', 'editorial.' . $activeModule . '.index');
                                $isLibraryActive = $isLibraryRoute && request('tab', 'published') === $libraryTab;
                            @endphp
                            @if ($libraryRoute)
                                <div class="workspace-library-button {{ $isLibraryActive ? 'is-active' : '' }}">
                                    <a class="workspace-nav-link workspace-library-link" href="{{ route($libraryRoute, ['tab' => $libraryTab]) }}"
                                       aria-label="{{ $libraryLabel }}" data-tooltip="{{ $libraryLabel }}"
                                       @if ($isLibraryActive) aria-current="page" @endif>
                                        <x-workspace.icon :name="$libraryIcon" /><span>{{ $libraryLabel }}</span>
                                    </a>
                                    <label class="workspace-library-type">
                                        <span class="visually-hidden">{{ $libraryLabel }} type</span>
                                        <select aria-label="{{ $libraryLabel }} type" data-library-type>
                                            @foreach ($availableModules as $moduleKey => $module)
                                                <option value="{{ route($module[($libraryTab === 'archived' && $user->canReview()) ? 'editorial' : 'index'], ['tab' => $libraryTab]) }}" @selected($activeModule === $moduleKey)>
                                                    {{ $module['label'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </label>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @endif

                @if ($user->canReview()
                    || $user->can(config('workspace.navigation.users.gate'))
                    || $user->can(config('workspace.navigation.officers.gate')))
                    <span class="workspace-nav-label">{{ $workspaceConfig['sections']['administration'] }}</span>
                    <div class="workspace-nav-group">
                        @can(config('workspace.navigation.review.gate'))
                            <a class="workspace-nav-link {{ request()->routeIs('adviser.reviews.*') ? 'is-active' : '' }}"
                               href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}"
                               aria-label="Review queue{{ ($pendingReviewCount ?? 0) > 0 ? ', ' . $pendingReviewCount . ' pending' : '' }}"
                               data-tooltip="Review queue" @if (request()->routeIs('adviser.reviews.*')) aria-current="page" @endif>
                                <x-workspace.icon name="check" /><span>Review queue</span>
                                @if (($pendingReviewCount ?? 0) > 0)
                                    <span class="workspace-nav-count">{{ $pendingReviewCount > 99 ? '99+' : $pendingReviewCount }}</span>
                                @endif
                            </a>
                        @endcan
                        @can(config('workspace.navigation.officers.gate'))
                            <a class="workspace-nav-link {{ request()->routeIs('adviser.officers.*') ? 'is-active' : '' }}"
                               href="{{ route('adviser.officers.index') }}" aria-label="Officer roster"
                               data-tooltip="Officer roster" @if (request()->routeIs('adviser.officers.*')) aria-current="page" @endif>
                                <x-workspace.icon name="users" /><span>Officer roster</span>
                            </a>
                        @endcan
                        @can(config('workspace.navigation.users.gate'))
                            <a class="workspace-nav-link {{ request()->routeIs('adviser.users.*') ? 'is-active' : '' }}"
                               href="{{ route('adviser.users.index') }}" aria-label="{{ $user->isSuperadmin() ? 'Advisers' : 'Officers and positions' }}"
                               data-tooltip="{{ $user->isSuperadmin() ? 'Advisers' : 'Officers and positions' }}"
                               @if (request()->routeIs('adviser.users.*')) aria-current="page" @endif>
                                <x-workspace.icon name="users" /><span>{{ $user->isSuperadmin() ? 'Advisers' : 'Officers and positions' }}</span>
                            </a>
                        @endcan
                    </div>
                @endif
            </nav>

            <div class="workspace-sidebar-footer">
                <a class="workspace-site-link" href="{{ url('/') }}" aria-label="View site" data-tooltip="View site">
                    <x-workspace.icon name="external" /><span>View site</span>
                </a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="workspace-logout-link" type="submit" aria-label="Log out" data-tooltip="Log out">
                        <svg class="workspace-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3"/><path d="M12 3h6a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-6"/></svg>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </aside>

        <div class="workspace-main">
            <header class="workspace-topbar">
                <button class="workspace-menu-toggle" type="button" aria-label="Collapse sidebar" aria-pressed="false" aria-controls="workspace-sidebar">
                    <x-workspace.icon name="menu" />
                </button>
                <div class="workspace-topbar-title"><span>SSITE Workspace</span><span aria-hidden="true">/</span><strong>@yield('title', 'Dashboard')</strong></div>
                <div class="workspace-topbar-actions">
                    <a class="workspace-view-site" href="{{ url('/') }}"><x-workspace.icon name="external" /><span>View site</span></a>
                    <div class="notification-menu-wrapper workspace-notification" id="notification-menu-wrapper"
                         data-summary-url="{{ route('notifications.summary') }}"
                         data-read-url-template="{{ route('notifications.read', '00000000-0000-4000-8000-000000000000') }}"
                         data-avatar-url-template="{{ route('notifications.avatar', '00000000-0000-4000-8000-000000000000') }}">
                        <button type="button" id="notifications-toggle" aria-label="Notifications" aria-controls="notifications-dropdown" aria-expanded="false" class="workspace-notification-toggle">
                            <x-workspace.icon name="bell" />
                            <span id="notification-badge" class="notification-badge hidden">0</span>
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
                        <button type="button" id="account-menu-toggle" aria-label="Account menu" aria-haspopup="menu" aria-controls="account-menu" aria-expanded="false" class="workspace-profile-toggle">
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
                                <x-ui.avatar :user="$user" size="md" />
                                <div class="account-menu-identity-copy"><strong>{{ $user->name }}</strong><span>{{ $roleLabel }}</span></div>
                            </div>
                            @if ($user->hasCompletedProfile())
                                <a href="{{ route('profile.edit') }}" class="account-menu-action" role="menuitem">Profile</a>
                            @endif
                            <button type="button" class="account-menu-action" role="menuitem">Settings</button>
                            <div class="account-menu-divider" role="separator"></div>
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
            sidebar.querySelectorAll('[data-library-type]').forEach((selector) => {
                selector.addEventListener('change', () => {
                    if (selector.value) window.location.assign(selector.value);
                });
            });
        })();
    </script>
@else
    <main id="main-content">@yield('content')</main>
@endif
