@props(['post', 'routePrefix', 'category' => null, 'guestCanSeeList' => false])

{{-- Show sanitized file metadata and authorized controller actions, never the private storage path. --}}
<section class="post-feed-attachment" aria-label="PDF attachment">
    <div class="post-feed-pdf-mark" aria-hidden="true">
        <x-icon name="file-text" />
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
