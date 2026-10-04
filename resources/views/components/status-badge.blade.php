@props(['status'])

{{-- Keep status coloring consistent wherever a role or post status is displayed. --}}
@php
    $normalizedStatus = strtolower((string) $status);
    $statusClass = match ($normalizedStatus) {
        'pending' => 'status-badge-pending',
        'approved' => 'status-badge-approved',
        'rejected' => 'status-badge-rejected',
        default => 'status-badge-neutral',
    };
@endphp

<span {{ $attributes->class(['status-badge', $statusClass]) }}>
    {{ ucfirst($normalizedStatus) }}
</span>
