@extends('layouts.app')

@section('title', $article->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="articles">
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    <x-posts.card
        :post="$article"
        module="Articles"
        route-prefix="articles"
        :show-url="route('articles.show', $article)"
        :content="$article->content"
        tab="show"
        :full="true"
        :media-url="$article->imageUrl()"
        :media-alt="$article->title"
    >
        <x-slot:footerActions>
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
        </x-slot:footerActions>
    </x-posts.card>
</x-posts.feed-frame>
@endsection
