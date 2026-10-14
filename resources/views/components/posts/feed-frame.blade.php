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
    'search' => '',
    'searchLabel' => 'Search posts',
    'searchId' => 'post-search',
    'category' => null,
    'categories' => [],
    'extraQuery' => [],
    'pendingCount' => null,
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

        <x-ui.workflow-tabs
            :route-name="$routeName"
            :allowed-tabs="$allowedTabs"
            :active-tab="$activeTab"
            :search="$search"
            :extra-query="$extraQuery"
            :pending-count="$pendingCount"
        />

        <x-posts.search-row
            :action="route($routeName)"
            :tab="$activeTab"
            :search="$search"
            :label="$searchLabel"
            :id="$searchId"
            :category="$category"
            :categories="$categories"
        />
    @endif

    {{ $slot }}
</div>
