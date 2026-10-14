@props(['href', 'prompt', 'user'])

{{-- A single, policy-gated prompt links to the existing create screen without embedding a form. --}}
<a class="post-feed-composer" href="{{ $href }}">
    <x-ui.avatar :user="$user" size="sm" />
    <span>{{ $prompt }}</span>
</a>
