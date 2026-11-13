@props([
    'post',
    'module',
    'routePrefix',
    'showUrl',
    'content',
    'tab' => 'published',
    'full' => false,
    'mediaUrl' => null,
    'mediaAlt' => null,
    'showApproval' => true,
])

{{-- Shared presentation, with role-aware management actions. --}}
<x-posts.card
    :post="$post" :management="true" :module="$module" :route-prefix="$routePrefix"
    :show-url="$showUrl" :content="$content" :tab="$tab" :full="$full"
    :media-url="$mediaUrl" :media-alt="$mediaAlt" :show-approval="$showApproval"
>
    @if (isset($strip))
        <x-slot:strip>{{ $strip }}</x-slot:strip>
    @endif
    @if (isset($attachment))
        <x-slot:attachment>{{ $attachment }}</x-slot:attachment>
    @endif
    @if (isset($footerActions))
        <x-slot:footerActions>{{ $footerActions }}</x-slot:footerActions>
    @endif
    {{ $slot }}
</x-posts.card>
