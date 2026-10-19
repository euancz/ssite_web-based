@props(['status'])

{{-- Keep status coloring consistent wherever a role or post status is displayed. --}}
@php
    $normalizedStatus = strtolower((string) $status);
    $statusClass = match ($normalizedStatus) {
        'pending' => 'status-badge-pending',
        'approved' => 'status-badge-approved',
        'rejected' => 'status-badge-rejected',
        'archived' => 'status-badge-archived',
        default => 'status-badge-neutral',
    };
@endphp

<span {{ $attributes->class(['status-badge', $statusClass]) }}>
    @if ($normalizedStatus === 'pending')
        <x-icon name="clock" size="sm" />
    @elseif ($normalizedStatus === 'approved')
        <x-icon name="circle-check" size="sm" />
    @elseif ($normalizedStatus === 'rejected')
        <x-icon name="circle-x" size="sm" />
    @elseif ($normalizedStatus === 'archived')
        <x-icon name="archive" size="sm" />
    @endif
    {{ ucfirst($normalizedStatus) }}
</span>
