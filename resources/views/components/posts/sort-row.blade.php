{{-- SECURITY: Displayed options are server-whitelisted and tab links omit filter state to reset it. --}}
@props([
    'action', 'routeName', 'tab', 'sort', 'options',
    'filterName' => null, 'filter' => null, 'filterOptions' => [],
    'filterLabel' => 'Filter posts', 'filterDefault' => null,
])

{{-- Keep sort controls compact; tab changes discard sort state and the form omits pagination. --}}
<form class="post-feed-sort" method="GET" action="{{ $action }}" data-auto-submit>
    <input type="hidden" name="tab" value="{{ $tab }}">
    <label class="post-feed-visually-hidden" for="post-sort-{{ str_replace('.', '-', $routeName) }}">Sort posts</label>
    <select id="post-sort-{{ str_replace('.', '-', $routeName) }}" name="sort" aria-label="Sort posts">
        @foreach ($options as $value => $label)
            <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
        @endforeach
    </select>
    @if ($filterName && $filterOptions)
        <label class="post-feed-visually-hidden" for="post-filter-{{ str_replace('.', '-', $routeName) }}">{{ $filterLabel }}</label>
        <select id="post-filter-{{ str_replace('.', '-', $routeName) }}" name="{{ $filterName }}" aria-label="{{ $filterLabel }}">
            @foreach ($filterOptions as $value => $label)
                <option value="{{ $value }}" @selected((string) $filter === (string) $value)>{{ $label }}</option>
            @endforeach
        </select>
    @endif
    <button class="post-feed-sort-apply" type="submit">Apply</button>
    @if ($sort !== 'recent' || ($filterName && $filter !== $filterDefault))
        <a class="post-feed-sort-reset" href="{{ route($routeName, ['tab' => $tab]) }}">Reset</a>
    @endif
</form>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-auto-submit]').forEach((form) => {
                form.querySelectorAll('select').forEach((select) => select.addEventListener('change', () => form.requestSubmit()));
                if (form.querySelector('select')) form.querySelector('.post-feed-sort-apply')?.setAttribute('hidden', '');
            });
        </script>
    @endpush
@endonce
