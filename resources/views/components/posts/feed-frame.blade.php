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

    {{ $slot }}
</div>
