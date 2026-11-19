@props([
    'variant' => 'index',
    'initialLoading' => false,
    'backUrl' => null,
    'backLabel' => 'Back to posts',
    'stylesheet' => null,
    'title' => null,
    'description' => null,
    'policyClass' => null,
    'createRoute' => null,
    'composerPrompt' => null,
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
])

{{-- Keep one centered feed frame while each page supplies its existing role-filtered content. --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/post-feed.css') }}">
    @if ($stylesheet)
        <link rel="stylesheet" href="{{ asset('css/' . $stylesheet . '.css') }}">
    @endif
@endpush

<div class="site-page post-feed-shell post-feed-{{ $variant }} {{ $stylesheet ? 'post-feed-' . $stylesheet : '' }}">
    @if ($variant === 'show' && $backUrl)
        <nav class="post-feed-back" aria-label="Post navigation">
            <a href="{{ $backUrl }}" class="dashboard-button dashboard-button-secondary">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m12 19-7-7 7-7M5 12h14" /></svg>
                {{ $backLabel }}
            </a>
        </nav>
    @endif
    @if ($variant === 'index')
        <x-ui.page-hero
            :title="$title"
            :description="$description"
            class="post-feed-heading"
            labelledby="post-feed-title"
        />


        @if ($sortOptions)
            <div class="post-feed-tab-row">
                <x-posts.sort-row
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
                />
            </div>
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
