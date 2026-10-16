@props([
    'variant' => 'index',
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

<div class="post-feed-shell {{ $stylesheet ? 'post-feed-' . $stylesheet : '' }}">
    @if ($variant === 'index')
        <header class="post-feed-heading">
            <h1>{{ $title }}</h1>
            @if ($description)
                <p>{{ $description }}</p>
            @endif
        </header>

        @can('create', $policyClass)
            <x-posts.composer :href="route($createRoute)" :prompt="$composerPrompt" :user="auth()->user()" />
        @endcan

        @if ($sortOptions)
            <div class="post-feed-tab-row">
                <x-ui.workflow-tabs
                    :route-name="$routeName"
                    :allowed-tabs="$allowedTabs"
                    :active-tab="$activeTab"
                    :pending-count="$pendingCount"
                />
                <x-posts.sort-row
                    :action="route($routeName)"
                    :route-name="$routeName"
                    :tab="$activeTab"
                    :sort="$sort"
                    :options="$sortOptions"
                    :filter-name="$filterName"
                    :filter="$filter"
                    :filter-options="$filterOptions"
                    :filter-label="$filterLabel"
                    :filter-default="$filterDefault"
                />
            </div>
        @else
            <x-ui.workflow-tabs
                :route-name="$routeName"
                :allowed-tabs="$allowedTabs"
                :active-tab="$activeTab"
                :pending-count="$pendingCount"
            />
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
