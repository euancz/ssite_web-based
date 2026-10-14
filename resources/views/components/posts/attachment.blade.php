@props(['post', 'routePrefix', 'category' => null, 'guestCanSeeList' => false])

{{-- Show sanitized file metadata and authorized controller actions, never the private storage path. --}}
<section class="post-feed-attachment" aria-label="PDF attachment">
    <div class="post-feed-pdf-mark" aria-hidden="true">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7">
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h6l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2zM13 3.75v4h4M8 14h8M8 17h8"></path>
        </svg>
        <span>PDF</span>
    </div>
    <div class="post-feed-attachment-info">
        <p class="post-feed-file-name">{{ $post->original_name }}</p>
        <p class="post-feed-file-meta">
            {{ number_format($post->file_size / 1024, 1) }} KB
            @if ($category)
                <span class="post-feed-category-chip">{{ $category }}</span>
            @endif
        </p>
    </div>
    <div class="post-feed-attachment-actions">
        @can('viewFile', $post)
            <a href="{{ route($routePrefix . '.view', $post) }}">View</a>
        @endcan
        @can('download', $post)
            <a href="{{ route($routePrefix . '.download', $post) }}">Download</a>
        @endcan
        @guest
            @if ($guestCanSeeList)
                <a href="{{ route($routePrefix . '.download', $post) }}">Log in to download</a>
            @endif
        @endguest
    </div>
</section>
