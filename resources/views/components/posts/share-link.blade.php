@props(['post', 'url'])

{{-- Public posts share only their show page; private items never receive a share affordance. --}}
@if ($post->isApproved() && ! $post->isArchived())
    <span class="post-feed-share">
        <a href="{{ $url }}"
           data-post-share
           data-share-title="{{ $post->title }}"
           aria-label="Share {{ $post->title }}">
            Share
        </a>
        <span class="post-feed-visually-hidden" aria-live="polite" data-share-status></span>
    </span>
@endif
