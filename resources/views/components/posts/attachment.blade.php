@props(['post', 'routePrefix', 'category' => null, 'guestCanSeeList' => false, 'viewerUrl' => null, 'viewerDialogId' => null])

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
            @if ($viewerUrl && $viewerDialogId)
                <button type="button" data-pdf-modal-open="{{ $viewerDialogId }}">View</button>
            @else
                <a href="{{ route($routePrefix . '.show', $post) }}">View</a>
            @endif
        @endcan
        @guest
            @if ($guestCanSeeList)
                <a href="{{ route($routePrefix . '.show', $post) }}">View details</a>
            @endif
        @endguest
    </div>
</section>
