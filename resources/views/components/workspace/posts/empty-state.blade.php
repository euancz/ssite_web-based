@props(['title', 'message'])

@php
    if (request('tab') === 'archived') {
        $title = 'No archived posts';
        $message = 'Posts you archive will appear here. You can restore them at any time.';
    }
@endphp

<x-posts.empty-state :title="$title" :message="$message" />
