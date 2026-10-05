@extends('layouts.app')

@section('title', $article->title)

@section('content')
<div class="dashboard-page">
    {{-- Flash messages stay visible after article actions return to this page. --}}
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    {{-- Article metadata uses the shared heading and status badge components. --}}
    <x-ui.page-header :title="$article->title" subtitle="By {{ $article->author?->name ?? 'Former member' }} · {{ $article->created_at?->format('M j, Y') }}">
        <x-slot:actions>
            <div class="article-badges">
                <x-ui.status-badge :status="$article->approval_status" />
                @if ($article->isArchived()) <x-ui.status-badge status="archived" /> @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Archived content remains readable to its owner and advisers with a clear visibility notice. --}}
    @if ($article->isArchived())
        <p class="dashboard-notice">This article is archived and does not appear in Published.</p>
    @endif

    {{-- Missing stored images resolve to the established placeholder through Article::imageUrl(). --}}
    <section class="dashboard-panel">
        <img class="article-show-image" src="{{ $article->imageUrl() }}" alt="">
        <div class="article-show-content">{{ $article->content }}</div>
    </section>

    {{-- A pending article remains reviewable when archived because approval and content status are separate. --}}
    @if ($article->isPending())
        <p class="dashboard-notice">This article is pending adviser review.</p>
    @endif
    @if ($article->isRejected() && $article->rejection_reason)
        <p class="dashboard-error">Rejection reason: {{ $article->rejection_reason }}</p>
    @endif

    {{-- Action forms use POST, CSRF, and policy checks matching their route middleware. --}}
    <div class="article-actions">
        {{-- SECURITY: Only the author or an adviser may change article content. --}}
        @can('update', $article)
            <a class="dashboard-button dashboard-button-secondary" href="{{ route('articles.edit', $article) }}">Edit</a>
        @endcan

        {{-- SECURITY: Only the author or an adviser may archive this article. --}}
        @can('archive', $article)
            @if (! $article->isArchived())
                <form id="archive-article-form" method="POST" action="{{ route('articles.archive', $article) }}">
                    @csrf
                </form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="archive-article-modal">Archive</button>
            @endif
        @endcan

        {{-- SECURITY: Restore is limited to the author or an adviser and preserves approval state. --}}
        @can('restore', $article)
            @if ($article->isArchived())
                <form id="restore-article-form" method="POST" action="{{ route('articles.restore', $article) }}">
                    @csrf
                </form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="restore-article-modal">Restore</button>
            @endif
        @endcan

        {{-- SECURITY: Only advisers can review an article and only pending items expose these controls. --}}
        @can('approve', $article)
            @if ($article->isPending())
                <form method="POST" action="{{ route('articles.approve', $article) }}">
                    @csrf
                    <button class="dashboard-button" type="submit">Approve</button>
                </form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="article-reject-modal">Reject</button>
            @endif
        @endcan

        {{-- SECURITY: Permanent deletion is adviser-only and confirmed before its DELETE request. --}}
        @can('delete', $article)
            <form id="delete-article-form" method="POST" action="{{ route('articles.destroy', $article) }}">
                @csrf
                @method('DELETE')
            </form>
            <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="delete-article-modal">Delete</button>
        @endcan
    </div>

    {{-- The shared rejection modal posts a required reason to the adviser-only review route. --}}
    @can('reject', $article)
        @if ($article->isPending())
            <x-ui.reject-modal id="article-reject-modal" :action="route('articles.reject', $article)" />
        @endif
    @endcan

    {{-- Confirmation dialogs keep archive, restore, and permanent deletion deliberate POST/DELETE actions. --}}
    @can('archive', $article)
        @if (! $article->isArchived())
            <dialog id="archive-article-modal" class="reject-modal"><div class="reject-modal-content">
                <h2>Archive article?</h2><p>The article will leave Published and remain available in Archived.</p>
                <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="archive-article-modal">Cancel</button>
                    <button type="submit" form="archive-article-form" class="dashboard-button">Archive</button></div>
            </div></dialog>
        @endif
    @endcan
    @can('restore', $article)
        @if ($article->isArchived())
            <dialog id="restore-article-modal" class="reject-modal"><div class="reject-modal-content">
                <h2>Restore article?</h2><p>Its approval status will stay unchanged.</p>
                <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="restore-article-modal">Cancel</button>
                    <button type="submit" form="restore-article-form" class="dashboard-button">Restore</button></div>
            </div></dialog>
        @endif
    @endcan
    @can('delete', $article)
        <dialog id="delete-article-modal" class="reject-modal"><div class="reject-modal-content">
            <h2>Delete article permanently?</h2><p>This also removes its stored image and cannot be undone.</p>
            <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="delete-article-modal">Cancel</button>
                <button type="submit" form="delete-article-form" class="dashboard-button">Delete permanently</button></div>
        </div></dialog>
    @endcan
</div>
@endsection
