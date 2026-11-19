@props([
    'variant' => 'index',
    'initialLoading' => false,
    'stylesheet' => null,
    'title' => null,
    'description' => null,
    'routeName' => null,
    'allowedTabs' => [],
    'activeTab' => 'published',
    'pendingCount' => null,
    'sort' => 'recent',
    'sortOptions' => [],
    'extraFilters' => [],
    'filterName' => null,
    'filter' => null,
    'filterOptions' => [],
    'filterLabel' => 'Filter posts',
    'filterDefault' => null,
    'academicYearOptions' => [],
    'academicYear' => '',
])

@php
    $filterUser = auth()->user();
    $filterModules = collect(config('workspace.modules'))
        ->filter(fn ($module, $key) => $filterUser
            && ($filterUser->canReview()
                || ($module['capability'] === 'canPostIn:' . $key && $filterUser->canPostIn($key))));
    $filterModuleKey = $filterModules->search(fn ($module) => in_array($routeName, [$module['workspace'], $module['editorial']], true));
    $statusTitle = match ($activeTab) {
        'mine' => 'My submissions',
        'published' => 'Published posts',
        'archived' => 'Archived posts',
        'pending' => 'Pending review',
        'rejected' => 'Rejected posts',
        default => 'All posts',
    };
@endphp

{{-- Keep one centered feed frame while each page supplies its existing role-filtered content. --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/post-feed.css') }}">
    @if ($stylesheet)
        <link rel="stylesheet" href="{{ asset('css/' . $stylesheet . '.css') }}">
    @endif
@endpush

<div class="post-feed-shell workspace-library-feed {{ $stylesheet ? 'post-feed-' . $stylesheet : '' }}">
    @if ($variant === 'index')
        <x-workspace.posts.list-heading
            :context="($activeTab === 'mine' ? 'My work' : 'Manage posts').' · '.$title"
            :title="$statusTitle"
            :description="$description.($activeTab === 'archived' ? ' Archived posts are hidden from the public site.' : '')"
        >
            @if ($filterModuleKey !== false && $filterModuleKey !== 'articles' && $filterUser?->canPostIn($filterModuleKey))
                <a class="dashboard-button" href="{{ route($filterModules[$filterModuleKey]['create']) }}"><x-workspace.icon name="plus" /> Create {{ strtolower($filterModules[$filterModuleKey]['singular']) }}</a>
            @endif
        </x-workspace.posts.list-heading>

        @if ($activeTab === 'mine')
            <x-workspace.ui.my-work-tabs active-tab="mine" :module="$filterModuleKey !== false ? $filterModuleKey : null" />
        @elseif ($routeName && $allowedTabs)
            <x-workspace.ui.workflow-tabs :route-name="$routeName" :allowed-tabs="array_values(array_diff($allowedTabs, ['mine', 'rejected', 'all']))" :active-tab="$activeTab" :pending-count="$pendingCount" />
        @endif

        @if ($sortOptions)
            <x-workspace.posts.sort-row
                :action="route($routeName)"
                :route-name="$routeName"
                :tab="$activeTab"
                :sort="$sort"
                :options="$sortOptions"
                :extra-filters="$extraFilters"
                :filter-name="$filterName"
                :filter="$filter"
                :filter-options="$filterOptions"
                :filter-label="$filterLabel"
                :filter-default="$filterDefault"
                :academic-year-options="$academicYearOptions"
                :academic-year="$academicYear"
            >
                @if ($filterModules->count() > 1 && $filterModuleKey !== false)
                    <div class="workspace-list-field">
                        <label for="workspace-post-module">Content type</label>
                        <select id="workspace-post-module" data-post-feed-module-filter>
                            @foreach ($filterModules as $moduleKey => $module)
                                @php($moduleRoute = $routeName === $module['editorial'] ? $module['editorial'] : $module['workspace'])
                                <option value="{{ route($moduleRoute, ['tab' => $activeTab]) }}" @selected($filterModuleKey === $moduleKey)>
                                    {{ $module['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
            </x-workspace.posts.sort-row>
        @endif
        @if ($activeTab === 'mine')
            <p class="workspace-feed-scope-note">Showing submissions created by your account.</p>
        @endif
    @endif

    <p class="dashboard-error post-feed-update-error" role="alert" data-feed-error hidden>
        Posts could not be updated. Check your connection and try again.
        @if ($initialLoading)
            <a href="{{ request()->fullUrlWithQuery(['_full' => 1]) }}">Reload the posts</a>.
        @endif
    </p>
    <p class="visually-hidden" role="status" aria-live="polite" data-feed-status></p>
    <div data-feed-results aria-busy="{{ $initialLoading ? 'true' : 'false' }}" @if ($initialLoading) data-feed-initial @endif>
        <x-posts.feed-skeleton :stylesheet="$stylesheet" :visible="$initialLoading" />
        <div data-feed-content @if ($initialLoading) hidden @endif>@unless ($initialLoading){{ $slot }}@endunless</div>
    </div>
</div>
