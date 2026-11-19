@props(['name', 'value', 'pending'])

<strong class="workspace-metric-value" data-page-data="{{ $name }}" @if ($pending) aria-busy="true" @endif>
    @if ($pending)
        <span class="skeleton-block page-data-skeleton-value" aria-hidden="true"></span>
    @else
        <x-ui.count-up :value="$value" />
    @endif
</strong>
