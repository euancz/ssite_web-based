@props(['user', 'size' => 'md'])

{{-- Show an available account picture, or initials when the account has no readable image. --}}
@php
    $size = in_array($size, ['sm', 'md', 'lg'], true) ? $size : 'md';
    $avatarUrl = $user?->avatarUrl();
@endphp

<span {{ $attributes->class(['ui-avatar', 'ui-avatar-' . $size]) }}>
    @if ($avatarUrl)
        <img src="{{ $avatarUrl }}" alt="{{ $user->name }}" loading="lazy">
    @else
        <span aria-label="{{ $user?->name }}">{{ $user?->initials() ?? '?' }}</span>
    @endif
</span>
