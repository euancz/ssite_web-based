@props(['label', 'value'])

{{-- Shared statistic card keeps dashboard metrics visually consistent. --}}
<section class="dashboard-stat-card">
    <p class="dashboard-stat-label">{{ $label }}</p>
    <p class="dashboard-stat-value">{{ $value }}</p>
</section>
