@php
    $user = auth()->user();
    $roleLabel = $user->isPresident() ? 'President' : ($user->position() ?? match ($user->role) {
        'adviser' => 'Adviser',
        'superadmin' => 'Superadmin',
        default => ucfirst($user->role),
    });
    $roleInitials = $user->initials();
    $profileImage = $user->avatarUrl();
    $workspaceModules = [
        'articles' => ['Article', 'articles.create', 'editorial.articles.index', 'file'],
        'activities' => ['Activity', 'activities.create', 'editorial.activities.index', 'calendar'],
        'achievements' => ['Achievement', 'achievements.create', 'editorial.achievements.index', 'award'],
        'documents' => ['Document', 'documents.create', 'editorial.documents.index', 'file'],
        'liquidations' => ['Liquidation', 'liquidations.create', 'editorial.liquidations.index', 'chart'],
    ];
    $workspaceModuleOptions = collect($workspaceModules)
        ->filter(fn ($module, $key) => $user->canReview() || $user->canPostIn($key));
    $activeWorkspaceModule = $workspaceModuleOptions->keys()->first();
    foreach ($workspaceModuleOptions as $moduleKey => $module) {
        if (request()->routeIs('editorial.' . $moduleKey . '.*', $moduleKey . '.create', $moduleKey . '.edit')) {
            $activeWorkspaceModule = $moduleKey;
            break;
        }
    }
@endphp

<div class="workspace-shell">
    <button class="workspace-scrim" type="button" aria-label="Close workspace navigation" tabindex="-1"></button>
    <aside class="workspace-sidebar" id="workspace-sidebar" aria-label="Workspace navigation">
        <a class="workspace-brand" href="{{ route($user->dashboardRoute()) }}">
            <img src="{{ asset('images/SSITE LOGO.png') }}" alt="">
            <span class="workspace-brand-copy"><strong>SSITE</strong><small>Editorial workspace</small></span>
        </a>

        <nav class="workspace-nav" aria-label="Publishing desk">
            <span class="workspace-nav-label">Overview</span>
            <div class="workspace-nav-group">
                <a class="workspace-nav-link {{ request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'is-active' : '' }}" href="{{ route($user->dashboardRoute()) }}">
                    <x-workspace.icon name="grid" /><span>Dashboard</span>
                </a>
            </div>

            @if ($user->canPost() || $user->canReview())
                <span class="workspace-nav-label">Create</span>
                <div class="workspace-nav-group" role="group" aria-label="Create content">
                    @foreach ($workspaceModules as $module => [$label, $createRoute, $manageRoute, $icon])
                        @if ($user->canPostIn($module))
                            <a class="workspace-nav-link {{ request()->routeIs($module . '.create', $module . '.store', $module . '.edit', $module . '.update') ? 'is-active' : '' }}" href="{{ route($createRoute) }}">
                                <x-workspace.icon :name="$icon" /><span>Create {{ strtolower($label) }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>

                @if ($workspaceModuleOptions->isNotEmpty())
                    <span class="workspace-nav-label">Post library</span>
                    <div class="workspace-post-library" role="group" aria-label="Published and archived posts">
                        <div class="workspace-library-button {{ request('tab', 'published') === 'published' && request()->routeIs('editorial.*') ? 'is-active' : '' }}">
                            <a class="workspace-nav-link workspace-library-link" href="{{ route($workspaceModules[$activeWorkspaceModule][2], ['tab' => 'published']) }}">
                                <x-workspace.icon name="check" /><span>Published posts</span>
                            </a>
                            <label class="workspace-library-type">
                                <span class="visually-hidden">Published post type</span>
                                <select aria-label="Published post type" data-library-type>
                                    @foreach ($workspaceModuleOptions as $moduleKey => [$label, $createRoute, $indexRoute, $icon])
                                        <option value="{{ route($indexRoute, ['tab' => 'published']) }}" @selected($activeWorkspaceModule === $moduleKey)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                        <div class="workspace-library-button {{ request('tab') === 'archived' && request()->routeIs('editorial.*') ? 'is-active' : '' }}">
                            <a class="workspace-nav-link workspace-library-link" href="{{ route($workspaceModules[$activeWorkspaceModule][2], ['tab' => 'archived']) }}">
                                <x-workspace.icon name="archive" /><span>Archived posts</span>
                            </a>
                            <label class="workspace-library-type">
                                <span class="visually-hidden">Archived post type</span>
                                <select aria-label="Archived post type" data-library-type>
                                    @foreach ($workspaceModuleOptions as $moduleKey => [$label, $createRoute, $indexRoute, $icon])
                                        <option value="{{ route($indexRoute, ['tab' => 'archived']) }}" @selected($activeWorkspaceModule === $moduleKey)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </label>
                        </div>
                    </div>
                @endif
            @endif

            @if ($user->canReview() || $user->isAdviser() || $user->isSuperadmin())
                <span class="workspace-nav-label">Administration</span>
                <div class="workspace-nav-group" role="group" aria-label="Administration">
                    @if ($user->canReview())
                        <a class="workspace-nav-link {{ request()->routeIs('adviser.reviews.*') ? 'is-active' : '' }}" href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}">
                            <x-workspace.icon name="check" /><span>Review queue</span>
                        </a>
                        <a class="workspace-nav-link {{ request()->routeIs('adviser.officers.*') ? 'is-active' : '' }}" href="{{ route('adviser.officers.index') }}">
                            <x-workspace.icon name="users" /><span>Officer roster</span>
                        </a>
                    @endif
                    @if ($user->isAdviser() || $user->isSuperadmin())
                        <a class="workspace-nav-link {{ request()->routeIs('adviser.users.*') ? 'is-active' : '' }}" href="{{ route('adviser.users.index') }}">
                            <x-workspace.icon name="users" /><span>{{ $user->isSuperadmin() ? 'Adviser access' : 'User access' }}</span>
                        </a>
                    @endif
                </div>
            @endif
        </nav>

        <div class="workspace-sidebar-footer">
            <a class="workspace-site-link" href="{{ url('/') }}"><x-workspace.icon name="external" /><span>Back to main site</span></a>
        </div>
    </aside>

    <div class="workspace-main">
        <header class="workspace-topbar">
            <button class="workspace-menu-toggle" type="button" aria-label="Open workspace navigation" aria-expanded="false" aria-controls="workspace-sidebar">
                <x-workspace.icon name="menu" />
            </button>
            <span class="workspace-topbar-label">SSITE <span aria-hidden="true">/</span> {{ request()->routeIs('officer.dashboard', 'adviser.dashboard') ? 'Dashboard' : 'Publishing workspace' }}</span>
            <div class="workspace-topbar-actions">
                <a class="workspace-notification-link" href="{{ route('notifications.index') }}" aria-label="Notifications{{ ($notificationUnreadCount ?? 0) > 0 ? ', ' . $notificationUnreadCount . ' unread' : '' }}">
                    <x-workspace.icon name="bell" />
                    @if (($notificationUnreadCount ?? 0) > 0)
                        <span class="workspace-notification-count">{{ $notificationUnreadCount > 9 ? '9+' : $notificationUnreadCount }}</span>
                    @endif
                </a>
                <a class="workspace-profile-link" href="{{ route('profile.edit') }}" aria-label="Profile for {{ $user->name }}">
                    @if ($profileImage)
                        <img src="{{ $profileImage }}" alt="" class="workspace-profile-picture">
                    @else
                        <span class="workspace-profile-picture workspace-profile-initials" aria-hidden="true">{{ $roleInitials }}</span>
                    @endif
                    <span class="workspace-profile-copy"><strong>{{ $user->name }}</strong><small>{{ $roleLabel }}</small></span>
                    <x-workspace.icon name="chevron" />
                </a>
            </div>
        </header>

        <main class="workspace-content" id="main-content">
            @yield('content')
        </main>
    </div>
</div>

<script>
    (() => {
        const sidebar = document.getElementById('workspace-sidebar');
        const toggle = document.querySelector('.workspace-menu-toggle');
        const scrim = document.querySelector('.workspace-scrim');
        if (!sidebar || !toggle || !scrim) return;

        const setOpen = (open) => {
            sidebar.classList.toggle('is-open', open);
            scrim.classList.toggle('is-visible', open);
            toggle.setAttribute('aria-expanded', String(open));
            toggle.setAttribute('aria-label', open ? 'Close workspace navigation' : 'Open workspace navigation');
            document.body.classList.toggle('workspace-menu-open', open);
        };

        toggle.addEventListener('click', () => setOpen(toggle.getAttribute('aria-expanded') !== 'true'));
        scrim.addEventListener('click', () => setOpen(false));
        sidebar.querySelectorAll('a').forEach((link) => link.addEventListener('click', () => setOpen(false)));
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && sidebar.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
        window.matchMedia('(min-width: 901px)').addEventListener('change', (event) => {
            if (event.matches) setOpen(false);
        });

        sidebar.querySelectorAll('[data-library-type]').forEach((selector) => {
            selector.addEventListener('change', () => {
                if (selector.value) window.location.assign(selector.value);
            });
        });

    })();
</script>
