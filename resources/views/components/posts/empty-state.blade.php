@props(['title', 'message'])

{{-- Keep an empty feed useful without adding a duplicate page-level card. --}}
<section class="post-feed-empty" aria-label="{{ $title }}">
    <h2>{{ $title }}</h2>
    <p>{{ $message }}</p>
</section>
