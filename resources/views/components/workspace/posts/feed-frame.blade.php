@props([
    'variant' => 'index',
    'stylesheet' => null,
    'title' => null,
    'description' => null,
    'routeName' => null,
    'allowedTabs' => [],
    'activeTab' => 'published',
    'pendingCount' => null,
    'sort' => 'recent',
    'sortOptions' => [],
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
        <header class="post-feed-heading">
            <div><span class="workspace-eyebrow">CONTENT LIBRARY / {{ $title }}</span><h1>{{ $statusTitle }}</h1>
            <p>{{ $description }}@if ($activeTab === 'archived') Archived posts are hidden from the public site.@endif</p></div>
            @if ($filterModuleKey !== false && $filterUser?->canPostIn($filterModuleKey))
                <a class="dashboard-button" href="{{ route($filterModules[$filterModuleKey]['create']) }}"><x-workspace.icon name="plus" /> Create {{ strtolower($filterModules[$filterModuleKey]['singular']) }}</a>
            @endif
        </header>

        @if ($routeName && $allowedTabs)
            <x-workspace.ui.workflow-tabs :route-name="$routeName" :allowed-tabs="$allowedTabs" :active-tab="$activeTab" :pending-count="$pendingCount" />
        @endif

        @if ($sortOptions)
            <div class="post-feed-tab-row">
                @if ($filterModules->count() > 1 && $filterModuleKey !== false)
                    <label class="post-feed-module-filter">
                        <span>Content type</span>
                        <select aria-label="Filter by content type" data-post-feed-module-filter>
                            @foreach ($filterModules as $moduleKey => $module)
                                @php($moduleRoute = $routeName === $module['editorial'] ? $module['editorial'] : $module['workspace'])
                                <option value="{{ route($moduleRoute, ['tab' => $activeTab]) }}" @selected($filterModuleKey === $moduleKey)>
                                    {{ $module['label'] }}
                                </option>
                            @endforeach
                        </select>
                    </label>
                @endif
                <x-workspace.posts.sort-row
                    :action="route($routeName)"
                    :route-name="$routeName"
                    :tab="$activeTab"
                    :allowed-tabs="$allowedTabs"
                    :sort="$sort"
                    :options="$sortOptions"
                    :filter-name="$filterName"
                    :filter="$filter"
                    :filter-options="$filterOptions"
                    :filter-label="$filterLabel"
                    :filter-default="$filterDefault"
                    :academic-year-options="$academicYearOptions"
                    :academic-year="$academicYear"
                />
            </div>
        @endif
        @if ($activeTab === 'mine')
            <p class="workspace-feed-scope-note">Showing submissions created by your account.</p>
        @endif
    @endif

    <p class="dashboard-error post-feed-update-error" role="alert" data-feed-error hidden>
        Posts could not be updated. Check your connection and try again.
    </p>
    <p class="visually-hidden" role="status" aria-live="polite" data-feed-status></p>
    <div data-feed-results aria-busy="false">
        <div class="post-feed-skeleton" data-feed-skeleton aria-hidden="true" hidden>
            @for ($skeletonCard = 0; $skeletonCard < 3; $skeletonCard++)
                <article class="post-feed-skeleton-card">
                    <div class="post-feed-skeleton-author">
                        <span class="post-feed-skeleton-block post-feed-skeleton-avatar"></span>
                        <span class="post-feed-skeleton-lines">
                            <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-name"></span>
                            <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-meta"></span>
                        </span>
                    </div>
                    <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-title"></span>
                    <span class="post-feed-skeleton-block post-feed-skeleton-line"></span>
                    <span class="post-feed-skeleton-block post-feed-skeleton-line post-feed-skeleton-copy"></span>
                    <span class="post-feed-skeleton-block post-feed-skeleton-footer"></span>
                </article>
            @endfor
        </div>
        <div data-feed-content>{{ $slot }}</div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            document.addEventListener('change', (event) => {
                const selector = event.target instanceof Element
                    ? event.target.closest('[data-post-feed-module-filter]')
                    : null;
                if (selector?.value) window.location.assign(selector.value);
            });
        </script>
    @endpush
@endonce
