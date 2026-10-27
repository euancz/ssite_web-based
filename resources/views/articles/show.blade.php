@extends('layouts.app')

@section('title', $article->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="articles">
    @if ($management)<x-workspace.review-summary :post="$article" />@endif
    @if ($management)<x-workspace.article-feature :article="$article" />@endif
    @if (session('status'))
        <p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert" data-flash-toast>{{ session('error') }}</p>
    @endif

    @if ($article->isApproved() && ! $article->isArchived())
        {{-- SECURITY: The read-only progress indicator appears only for approved, active article details. --}}
        <div class="article-reading-progress" data-motion="reading-progress" aria-hidden="true" hidden>
            <span></span>
        </div>
    @endif

    <x-posts.card
        :management="$management"
        :post="$article"
        module="Articles"
        route-prefix="articles"
        :show-url="route('articles.show', $article)"
        :content="$article->content"
        tab="show"
        :full="true"
        :media-url="$article->imageUrl()"
        :media-alt="$article->title"
        :reading-progress="$article->isApproved() && ! $article->isArchived()"
    >
        <x-slot:footerActions>
            @if ($management)
            @can('approve', $article)
                @if ($article->isPending())
                    <form method="POST" action="{{ route('articles.approve', $article) }}">
                        @csrf
                        <button type="submit" class="dashboard-button">Approve</button>
                    </form>
                    <button type="button" class="dashboard-button dashboard-button-secondary"
                            data-modal-open="article-show-reject-{{ $article->getKey() }}">Reject</button>
                    <x-ui.reject-modal
                        id="article-show-reject-{{ $article->getKey() }}"
                        :action="route('articles.reject', $article)"
                    />
                @endif
            @endcan
                    @endif
        </x-slot:footerActions>
    </x-posts.card>
</x-posts.feed-frame>
@endsection
