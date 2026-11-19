@props(['activeCount' => 0])

<details class="site-more-filters" data-filter-popover>
    <summary>
        <svg class="site-filter-sliders" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" aria-hidden="true"><path d="M4 7h9m4 0h3M4 17h3m4 0h9"/><circle cx="15" cy="7" r="2"/><circle cx="9" cy="17" r="2"/></svg>
        <span>Filters</span>
        @if ($activeCount > 0)<span class="site-filter-count" aria-label="{{ $activeCount }} additional filters active">{{ $activeCount }}</span>@endif
        <x-icon name="chevron-down" size="sm" class="site-filter-chevron" />
    </summary>
    <div class="site-secondary-filters" role="group" aria-label="Filter options">
        <div class="site-filter-popover-heading">Filter by</div>
        {{ $slot }}
        <div class="site-filter-popover-actions">
            <button type="button" data-filter-cancel>Cancel</button>
            <button type="submit" data-filter-apply>Apply filters</button>
        </div>
    </div>
</details>

@once
    @push('scripts')
        <script src="{{ asset('js/filter-popover.js') }}?v={{ filemtime(public_path('js/filter-popover.js')) }}" defer></script>
    @endpush
@endonce
