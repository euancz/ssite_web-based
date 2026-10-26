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

{{-- One escaped, role-aware card keeps list and detail anatomy consistent across all post types. --}}
<article class="post-card {{ $full ? 'post-card-full' : '' }}">
    <header class="post-card-header">
        <x-ui.avatar
            :user="$post->author"
            size="sm"
            fallback-name="Former member"
            fallback-initials="FM"
        />
        <div class="post-card-author">
            <p class="post-card-author-name">{{ $post->author?->name ?? 'Former member' }}</p>
            @if ($post->author?->role === 'adviser')
                <p class="post-card-author-role">Adviser</p>
            @elseif ($post->author?->officer_position)
                <p class="post-card-author-role">{{ $post->author->officer_position }}</p>
            @endif
            <p class="post-card-meta">
                @if ($post->created_at)
                    <time datetime="{{ $post->created_at->toIso8601String() }}" title="{{ $post->created_at->format('F j, Y g:i A T') }}">{{ $post->created_at->diffForHumans() }}</time>
                @else
                    <span>Time unavailable</span>
                @endif
                <span aria-hidden="true">·</span>
                <span>{{ $module }}</span>
                @if ($showApproval && ($tab !== 'published' || ($full && (! $post->isApproved() || $post->isArchived()))))
                    <x-ui.status-badge :status="$post->approval_status" />
                @endif
                @if ($post->isArchived())
                    <x-ui.status-badge status="archived" />
                @endif
            </p>
        </div>
        <x-workspace.posts.row-menu :post="$post" :route-prefix="$routePrefix" />
    </header>

    <div class="post-card-copy">
        @if ($full && $post->isArchived())
            <p class="post-card-state">This post is archived and does not appear in Published.</p>
        @endif
        @if ($full && $post->isPending())
            <p class="post-card-state">This post is awaiting President review.</p>
        @endif
        @can('update', $post)
            @if ($post->isRejected() && $post->rejection_reason)
                <p class="post-card-rejection">Rejection reason: {{ $post->rejection_reason }}</p>
            @endif
        @endcan
        @if ($full)
            <h1 class="post-card-title">{{ $post->title }}</h1>
            <div class="post-card-body post-card-body-full">{{ $content }}</div>
        @else
            <h2 class="post-card-title"><a href="{{ $showUrl }}">{{ $post->title }}</a></h2>
            <div class="post-card-body">
                <p data-post-excerpt>{{ \Illuminate\Support\Str::limit(strip_tags((string) $content), 220) }}</p>
                @if (\Illuminate\Support\Str::length(strip_tags((string) $content)) > 220)
                    <p id="post-{{ $routePrefix }}-{{ $post->getKey() }}-full" data-post-full hidden>{{ $content }}</p>
                    <a class="post-card-expand" href="{{ $showUrl }}" aria-expanded="false" aria-controls="post-{{ $routePrefix }}-{{ $post->getKey() }}-full" data-post-expand>See more</a>
                @endif
            </div>
        @endif
    </div>

    @if ($mediaUrl)
        <a class="post-card-media" href="{{ $showUrl }}" aria-label="Open {{ $post->title }}">
            <img
                src="{{ $mediaUrl }}"
                alt="{{ $mediaAlt ?? $post->title }}"
                width="1200"
                height="675"
                loading="{{ $full ? 'eager' : 'lazy' }}"
            >
        </a>
    @endif

    @if (isset($strip) && trim((string) $strip) !== '')
        <div class="post-card-strip">{{ $strip }}</div>
    @endif

    @if (isset($attachment))
        <div class="post-card-strip">{{ $attachment }}</div>
    @endif

    <footer class="post-card-footer">
        @unless ($full)
            <a class="post-card-read-link" href="{{ $showUrl }}">Read full post</a>
        @endunless
        @if ((! $full && $tab === 'published') || ($full && $post->isApproved() && ! $post->isArchived()))
            {{-- SECURITY: Engagement renders only for public content with schema-backed attributes. --}}
            <div class="post-card-engagement" aria-label="Post activity">
                @if (config('school.enable_view_counts', false) && isset($post->view_count))
                    <span>{{ number_format((int) $post->view_count) }} {{ \Illuminate\Support\Str::plural('view', (int) $post->view_count) }}</span>
                @endif
                @if (config('school.enable_likes', false) && isset($post->like_count))
                    @php($liked = (int) ($post->liked_by_viewer ?? 0) > 0)
                    <span>{{ number_format((int) $post->like_count) }} {{ \Illuminate\Support\Str::plural('like', (int) $post->like_count) }}</span>
                    @if (isset($post->likes_count))
                        <form method="POST" action="{{ route('posts.like', ['type' => $post::POST_TYPE, 'post' => $post->getKey()]) }}">
                            @csrf
                            <input type="hidden" name="liked" value="{{ $liked ? '0' : '1' }}">
                            <button type="submit" class="post-like-button" aria-pressed="{{ $liked ? 'true' : 'false' }}">
                                {{ $liked ? 'Unlike' : 'Like' }}
                            </button>
                        </form>
                    @endif
                @endif
            </div>
        @endif
        <x-posts.share-link :post="$post" :url="route($routePrefix . '.show', $post)" />
        @if (isset($footerActions))
            <div class="post-card-footer-actions">{{ $footerActions }}</div>
        @endif
    </footer>
</article>

@once
    @push('scripts')
        <script>
            document.querySelectorAll('[data-post-expand]').forEach((control) => {
                control.addEventListener('click', (event) => {
                    event.preventDefault();
                    const expanded = control.getAttribute('aria-expanded') === 'true';
                    const fullText = document.getElementById(control.getAttribute('aria-controls'));
                    const excerpt = control.closest('.post-card-body')?.querySelector('[data-post-excerpt]');
                    if (!fullText || !excerpt) return;
                    fullText.hidden = expanded;
                    excerpt.hidden = !expanded;
                    control.setAttribute('aria-expanded', String(!expanded));
                    control.textContent = expanded ? 'See more' : 'See less';
                });
            });

            document.querySelectorAll('[data-post-share]').forEach((link) => {
                link.addEventListener('click', (event) => {
                    if (navigator.share) {
                        event.preventDefault();
                        navigator.share({ title: link.dataset.shareTitle, url: link.href })
                            .then(() => {
                                const status = link.parentElement?.querySelector('[data-share-status]');
                                if (status) status.textContent = 'Link shared';
                            })
                            .catch((error) => {
                                if (error.name !== 'AbortError') window.location.assign(link.href);
                            });
                        return;
                    }

                    if (navigator.clipboard?.writeText) {
                        event.preventDefault();
                        navigator.clipboard.writeText(link.href)
                            .then(() => {
                                const status = link.parentElement?.querySelector('[data-share-status]');
                                if (status) status.textContent = 'Link copied';
                            })
                            .catch(() => window.location.assign(link.href));
                    }
                });
            });
        </script>
    @endpush
@endonce
