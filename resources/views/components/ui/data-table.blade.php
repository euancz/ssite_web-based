@props(['label'])

{{-- Wraps wide tables in a keyboard-focusable horizontal scroll region. --}}
<div class="dashboard-table-scroll" role="region" aria-label="{{ $label }}" tabindex="0">
    {{ $slot }}
</div>
<p class="dashboard-table-scroll-hint">If columns run off screen, swipe or use arrow keys to view them.</p>
