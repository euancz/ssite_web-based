@props(['label', 'value', 'href' => null])

{{-- Shared statistic card keeps metrics consistent; an optional route makes its count immediately useful. --}}
@if ($href)
    <a href="{{ $href }}" class="dashboard-stat-card dashboard-stat-card-link">
        <p class="dashboard-stat-label">{{ $label }}</p>
        <p class="dashboard-stat-value">{{ $value }}</p>
    </a>
@else
    <section class="dashboard-stat-card">
        <p class="dashboard-stat-label">{{ $label }}</p>
        <p class="dashboard-stat-value">{{ $value }}</p>
    </section>
@endif
