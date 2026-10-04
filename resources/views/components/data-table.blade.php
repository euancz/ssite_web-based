@props(['label'])

<div class="dashboard-table-scroll" role="region" aria-label="{{ $label }}" tabindex="0">
    {{ $slot }}
</div>
