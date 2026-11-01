@props(['title', 'message'])

@php
    if (request('tab') === 'archived') {
        $title = 'No archived posts';
        $message = 'Posts you archive will appear here. You can restore them at any time.';
    }
@endphp

{{-- Keep an empty feed useful without adding a duplicate page-level card. --}}
<section class="post-feed-empty" aria-label="{{ $title }}">
    <h2>{{ $title }}</h2>
    <p>{{ $message }}</p>
</section>
