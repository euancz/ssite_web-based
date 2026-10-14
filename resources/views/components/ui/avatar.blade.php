@props(['user', 'size' => 'md', 'fallbackName' => null, 'fallbackInitials' => '?'])

{{-- Show an available account picture, or initials when the account has no readable image. --}}
@php
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $avatarUrl = $user?->avatarUrl();
    $displayName = $user?->name ?? $fallbackName;
@endphp

<span {{ $attributes->class(['ui-avatar', 'ui-avatar-' . $size]) }}>
    @if ($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $displayName ?? 'User avatar' }}" loading="lazy">
    @else
        {{-- Preserve a useful label and initials when a post outlives its author account. --}}
        <span aria-label="{{ $displayName }}">{{ $user?->initials() ?? $fallbackInitials }}</span>
    @endif
</span>
