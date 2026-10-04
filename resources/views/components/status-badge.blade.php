@props(['status'])

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
