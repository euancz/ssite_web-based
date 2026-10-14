@props([
    'action',
    'tab',
    'search',
    'label',
    'id',
    'category' => null,
    'categories' => [],
])

{{-- Keep the same GET parameters while making search and the existing document category filter compact. --}}
<form class="post-feed-search" method="GET" action="{{ $action }}">
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="post-feed-visually-hidden" for="{{ $id }}">{{ $label }}</label>
    <div class="post-feed-search-input">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="10.8" cy="10.8" r="6.8"></circle>
            <path stroke-linecap="round" d="m16 16 5 5"></path>
        </svg>
        <input id="{{ $id }}" type="search" name="search" value="{{ $search }}" maxlength="100" placeholder="{{ $label }}">
    </div>
    @if ($categories)
        <label class="post-feed-visually-hidden" for="{{ $id }}-category">Filter by category</label>
        <select id="{{ $id }}-category" name="category" class="post-feed-category">
            <option value="">All categories</option>
            @foreach ($categories as $option)
                <option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>
            @endforeach
        </select>
    @endif
    <button class="post-feed-search-button" type="submit">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
            <circle cx="10.8" cy="10.8" r="6.8"></circle>
            <path stroke-linecap="round" d="m16 16 5 5"></path>
        </svg>
        <span class="post-feed-visually-hidden">Search</span>
    </button>
</form>
