@props(['post', 'routePrefix'])

{{-- Keep management controls policy-gated and use the existing feature routes and HTTP methods. --}}
@canany(['update', 'archive', 'restore', 'delete'], $post)
    <details class="post-card-menu" data-post-menu>
        <summary aria-label="More actions for {{ $post->title }}" data-tooltip="More actions" aria-haspopup="true" aria-expanded="false">
            <x-icon name="ellipsis" />
        </summary>
        <div class="post-card-menu-panel">
            @can('update', $post)
                <a href="{{ route($routePrefix . '.edit', $post) }}">Edit post</a>
            @endcan

            @can('archive', $post)
                @if (! $post->isArchived())
                    <form id="post-archive-{{ $routePrefix }}-{{ $post->getKey() }}" method="POST" action="{{ route($routePrefix . '.archive', $post) }}" class="post-card-action-form">
                        @csrf
                    </form>
                    <button type="button" data-post-confirm-open="post-archive-confirm-{{ $routePrefix }}-{{ $post->getKey() }}">Archive</button>
                    <x-posts.confirm-action
                        id="post-archive-confirm-{{ $routePrefix }}-{{ $post->getKey() }}"
                        title="Archive post?"
                        message="This post will leave Published and remain available in Archived."
                        form-id="post-archive-{{ $routePrefix }}-{{ $post->getKey() }}"
                        button-text="Archive"
                    />
                @endif
            @endcan

            @can('restore', $post)
                @if ($post->isArchived())
                    <form id="post-restore-{{ $routePrefix }}-{{ $post->getKey() }}" method="POST" action="{{ route($routePrefix . '.restore', $post) }}" class="post-card-action-form">
                        @csrf
                    </form>
                    <button type="button" data-post-confirm-open="post-restore-confirm-{{ $routePrefix }}-{{ $post->getKey() }}">Restore</button>
                    <x-posts.confirm-action
                        id="post-restore-confirm-{{ $routePrefix }}-{{ $post->getKey() }}"
                        title="Restore post?"
                        message="The post's approval status will stay unchanged."
                        form-id="post-restore-{{ $routePrefix }}-{{ $post->getKey() }}"
                        button-text="Restore"
                    />
                @endif
            @endcan

            @can('delete', $post)
                <form id="post-delete-{{ $routePrefix }}-{{ $post->getKey() }}" method="POST" action="{{ route($routePrefix . '.destroy', $post) }}" class="post-card-action-form">
                    @csrf
                    @method('DELETE')
                </form>
                <button type="button" data-post-confirm-open="post-delete-confirm-{{ $routePrefix }}-{{ $post->getKey() }}">Delete permanently</button>
                <x-posts.confirm-action
                    id="post-delete-confirm-{{ $routePrefix }}-{{ $post->getKey() }}"
                    title="Delete post permanently?"
                    message="This action cannot be undone."
                    form-id="post-delete-{{ $routePrefix }}-{{ $post->getKey() }}"
                    button-text="Delete permanently"
                />
            @endcan
        </div>
    </details>
@endcanany

@once
    @push('scripts')
        <script>
            const postMenus = [...document.querySelectorAll('[data-post-menu]')];
            postMenus.forEach((menu) => {
                const summary = menu.querySelector('summary');
                const syncExpanded = () => summary?.setAttribute('aria-expanded', String(menu.open));
                menu.addEventListener('toggle', syncExpanded);
                syncExpanded();
                menu.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') {
                        menu.open = false;
                        summary?.focus();
                    }
                });
            });
            document.addEventListener('click', (event) => {
                postMenus.forEach((menu) => {
                    if (!menu.contains(event.target)) menu.open = false;
                });
            });
        </script>
    @endpush
@endonce
