@extends('layouts.app')

@section('title', 'Articles')

@section('content')
<div class="dashboard-page">
    {{-- The shared heading follows the existing dashboard card and page spacing. --}}
    <x-page-header title="Articles" subtitle="Read and manage SSITE articles." >
        <x-slot:actions>
            {{-- SECURITY: Posting links are shown only when ArticlePolicy permits article creation. --}}
            @can('create', \App\Models\Article::class)
                <a href="{{ route('articles.create') }}" class="dashboard-button">New Article</a>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- Role-whitelisted tabs keep private workflow states out of student and guest navigation. --}}
    @php
        $tabLabels = [
            'published' => 'Published', 'mine' => 'My Posts', 'pending' => 'Pending Review',
            'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All',
        ];
    @endphp
    <nav class="dashboard-tabs" aria-label="Article sections">
        @foreach ($allowedTabs as $allowedTab)
            <a href="{{ route('articles.index', ['tab' => $allowedTab, 'search' => $search ?: null]) }}"
               @class(['dashboard-tab', 'is-active' => $tab === $allowedTab])
               @if ($tab === $allowedTab) aria-current="page" @endif>
                {{ $tabLabels[$allowedTab] }}
            </a>
        @endforeach
    </nav>

    {{-- Search stays within the active role-approved tab and searches article titles and author names. --}}
    <form method="GET" action="{{ route('articles.index') }}" class="dashboard-filter-form">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="dashboard-form-field">
            <label for="article-search">Search articles</label>
            <input id="article-search" type="search" name="search" value="{{ $search }}" maxlength="100">
        </div>
        <button type="submit" class="dashboard-button">Search</button>
    </form>

    {{-- Flash messages report completed actions and harmless repeat archive/review attempts. --}}
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    {{-- Adviser bulk archive posts selected IDs to a validated, policy-protected endpoint. --}}
    @can('bulkArchive', \App\Models\Article::class)
        @if ($tab !== 'published' && $articles->isNotEmpty())
            <form id="article-bulk-archive" method="POST" action="{{ route('articles.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions">
                <button type="button" class="dashboard-button dashboard-button-secondary"
                        onclick="document.getElementById('bulk-archive-article-modal').showModal()">
                    Archive selected
                </button>
            </div>
            {{-- Confirmation keeps the adviser in control before archiving the selected group. --}}
            <dialog id="bulk-archive-article-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected articles?</h2>
                    <p>Selected articles leave Published and keep their current approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary"
                                onclick="document.getElementById('bulk-archive-article-modal').close()">Cancel</button>
                        <button type="submit" form="article-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    {{-- Article cards show only the rows returned for this role and its active tab. --}}
    <section class="articles-list" aria-label="{{ $tabLabels[$tab] }} articles">
        @forelse ($articles as $article)
            <article class="article-list-item">
                <a href="{{ route('articles.show', $article) }}" aria-label="Read {{ $article->title }}">
                    <img class="article-list-image" src="{{ $article->imageUrl() }}" alt="">
                </a>
                <div class="article-list-content">
                    <div class="article-badges">
                        <x-status-badge :status="$article->approval_status" />
                        @if ($article->isArchived())
                            <x-status-badge status="archived" />
                        @endif
                    </div>
                    <h2><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h2>
                    <div class="article-list-meta">
                        <span>{{ $article->author?->name ?? 'Former member' }}</span>
                        <span>{{ $article->created_at?->format('M j, Y') }}</span>
                    </div>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($article->content), 180) }}</p>
                    {{-- Rejection feedback is limited to the officer's own-posts tab. --}}
                    @if ($tab === 'mine' && $article->isRejected() && $article->rejection_reason)
                        <p class="dashboard-rejection-reason">Rejection reason: {{ $article->rejection_reason }}</p>
                    @endif
                    <div class="article-actions">
                        {{-- SECURITY: ArticlePolicy hides private details from visitors who cannot view this record. --}}
                        @can('view', $article)
                            <a class="dashboard-text-button" href="{{ route('articles.show', $article) }}">Read article</a>
                        @endcan
                        {{-- SECURITY: Only an author or adviser can edit; the controller checks the same policy. --}}
                        @can('update', $article)
                            <a class="dashboard-text-button" href="{{ route('articles.edit', $article) }}">Edit</a>
                        @endcan
                        {{-- SECURITY: Only advisers may select articles for bulk archive. --}}
                        @can('bulkArchive', \App\Models\Article::class)
                            @if (! $article->isArchived())
                                <label class="dashboard-muted">
                                    <input type="checkbox" name="article_ids[]" value="{{ $article->article_id }}" form="article-bulk-archive">
                                    Select
                                </label>
                            @endif
                        @endcan
                    </div>
                </div>
            </article>
        @empty
            <section class="dashboard-panel dashboard-empty-state">
                <h2>{{ $tabLabels[$tab] }}</h2>
                <p>
                    @switch($tab)
                        @case('published') No published articles are available right now. @break
                        @case('mine') You have not posted any articles yet. @break
                        @case('pending') No articles are waiting for review. @break
                        @case('rejected') No rejected articles were found. @break
                        @case('archived') No archived articles were found. @break
                        @default No articles were found.
                    @endswitch
                </p>
            </section>
        @endforelse
    </section>

    {{-- Keep search and tab filters while moving through ten article results per page. --}}
    <div class="dashboard-pagination">{{ $articles->links() }}</div>
</div>
@endsection
