@php
    $guideUser = auth()->user();
    $guideModules = collect(config('workspace.modules'));
    $creatableModules = $guideModules->filter(fn ($module, $key) => $guideUser->canPostIn($key));
    $visibleModules = $guideModules->filter(fn ($module, $key) => $guideUser->canReview() || $guideUser->canPostIn($key));
    $libraryModule = $visibleModules->first();
@endphp

<details class="workspace-dashboard-guide">
    <summary class="workspace-dashboard-guide-heading">
        <div>
            <h2>Workspace guide</h2>
            <p>How to create, submit, and manage posts.</p>
        </div>
        <x-workspace.icon name="chevron-down" />
    </summary>

    <div class="workspace-dashboard-guide-grid">
        @if ($creatableModules->isNotEmpty())
            <article class="workspace-guide-card">
                <span class="workspace-guide-icon"><x-workspace.icon name="file" /></span>
                <div class="workspace-guide-copy">
                    <h3>Create content</h3>
                    <p>Draft and submit content for review. Your available forms are:</p>
                    <ul>
                        @foreach ($creatableModules as $module)
                            <li><a href="{{ route($module['create']) }}">{{ $module['singular'] }}</a></li>
                        @endforeach
                    </ul>
                </div>
            </article>
        @endif

        @if ($libraryModule)
            <article class="workspace-guide-card">
                <span class="workspace-guide-icon"><x-workspace.icon name="archive" /></span>
                <div class="workspace-guide-copy">
                    <h3>Post library</h3>
                    <p>Browse {{ $visibleModules->pluck('label')->join(', ') }}. Published items appear on the student site; archived items stay available for reference.</p>
                    <div class="workspace-guide-links">
                        <a href="{{ route($libraryModule['workspace'], ['tab' => 'published']) }}">Published posts <x-workspace.icon name="chevron" /></a>
                        <a href="{{ route($libraryModule['workspace'], ['tab' => 'archived']) }}">Archived posts <x-workspace.icon name="chevron" /></a>
                    </div>
                </div>
            </article>
        @endif

        @if ($guideUser->can(config('workspace.navigation.review.gate'))
            || $guideUser->can(config('workspace.navigation.officers.gate'))
            || $guideUser->can(config('workspace.navigation.users.gate')))
            <article class="workspace-guide-card">
                <span class="workspace-guide-icon"><x-workspace.icon name="users" /></span>
                <div class="workspace-guide-copy">
                    <h3>Administration</h3>
                    <p>{{ $guideUser->canReview() ? 'Review submissions and manage officer information.' : 'Manage organization accounts and access for the people you support.' }}</p>
                    <div class="workspace-guide-links">
                        @can(config('workspace.navigation.review.gate'))
                            <a href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}">Review queue <x-workspace.icon name="chevron" /></a>
                        @endcan
                        @can(config('workspace.navigation.officers.gate'))
                            <a href="{{ route('adviser.officers.index') }}">Officer roster <x-workspace.icon name="chevron" /></a>
                        @endcan
                        @can(config('workspace.navigation.users.gate'))
                            <a href="{{ route('adviser.users.index') }}">{{ $guideUser->isSuperadmin() ? 'Adviser accounts' : 'User accounts' }} <x-workspace.icon name="chevron" /></a>
                        @endcan
                    </div>
                </div>
            </article>
        @endif
    </div>
</details>
