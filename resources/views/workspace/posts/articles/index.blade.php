@extends('layouts.app')

@section('title', 'Articles')

@section('content')
<x-workspace.posts.feed-frame
    variant="index"
    stylesheet="articles"
    title="Articles"
    description="Read and manage SSITE articles."
    route-name="editorial.articles.index"
    :allowed-tabs="$allowedTabs"
    :active-tab="$tab"
    :pending-count="$pendingCount"
    :sort="$sort"
    :sort-options="$sortOptions"
>
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    {{-- President bulk archive remains separate from each card's single-post actions. --}}
    @can('bulkArchive', \App\Models\Article::class)
        @if ($tab !== 'published' && $articles->isNotEmpty())
            <form id="article-bulk-archive" method="POST" action="{{ route('articles.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions" data-selection-bar hidden>
                <span data-selection-count>0 selected</span>
                <button type="button" class="dashboard-button dashboard-button-secondary"
                        onclick="document.getElementById('bulk-archive-article-modal').showModal()">
                    Archive selected
                </button>
            </div>
            <dialog id="bulk-archive-article-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected articles?</h2>
                    <p>Selected articles leave Published and keep their approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary"
                                onclick="document.getElementById('bulk-archive-article-modal').close()">Cancel</button>
                        <button type="submit" form="article-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    <section class="post-feed-list" aria-label="{{ ucfirst($tab) }} articles">
        @forelse ($articles as $article)
            <x-workspace.posts.card
                :post="$article"
                module="Articles"
                route-prefix="articles"
                :show-url="route('articles.show', $article)"
                :content="$article->content"
                :tab="$tab"
                :media-url="$article->imageUrl()"
                :media-alt="$article->title"
            >
                <x-slot:footerActions>
                    @can('approve', $article)
                        @if ($tab === 'pending' && $article->isPending())
                            <form method="POST" action="{{ route('articles.approve', $article) }}">
                                @csrf
                                <button type="submit" class="dashboard-button">Approve</button>
                            </form>
                            <button type="button" class="dashboard-button dashboard-button-secondary"
                                    data-modal-open="article-reject-{{ $article->getKey() }}">Reject</button>
                            <x-ui.reject-modal
                                id="article-reject-{{ $article->getKey() }}"
                                :action="route('articles.reject', $article)"
                            />
                        @endif
                    @endcan

                    @can('bulkArchive', \App\Models\Article::class)
                        @if ($tab !== 'published' && ! $article->isArchived())
                            <label class="dashboard-muted">
                                <input type="checkbox" name="article_ids[]" value="{{ $article->article_id }}" form="article-bulk-archive">
                                Select
                            </label>
                        @endif
                    @endcan
                </x-slot:footerActions>
            </x-workspace.posts.card>
        @empty
            @if ($sort !== 'recent')
                <div class="post-feed-empty">
                    <p>No articles match this sort. <a href="{{ route('editorial.articles.index', ['tab' => $tab]) }}">Reset</a></p>
                </div>
            @elseif ($tab === 'published')
                <x-workspace.posts.empty-state title="No published articles yet" message="Check back soon for the latest SSITE articles." />
            @elseif (auth()->user()?->canPostIn('articles'))
                <x-workspace.posts.empty-state title="No articles in this tab" message="Use the prompt above to create an article." />
            @else
                <x-workspace.posts.empty-state title="No articles found" message="Check back later for new articles." />
            @endif
        @endforelse
    </section>

    <div class="dashboard-pagination">{{ $articles->links('vendor.pagination.ssite') }}</div>
</x-workspace.posts.feed-frame>
@endsection
