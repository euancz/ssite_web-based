@extends('layouts.app')

@section('title', 'Home')

{{-- Load the home-specific card layout styles for this page. --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
<div class="home-content">

    {{-- Section heading --}}
    <h1 class="home-heading">
        What's New at SSITE
    </h1>

    {{-- The featured Article is queried from approved and active records only. --}}
    @forelse ($articles as $article)
        <article class="featured-article card-hover">
            <p class="article-category">Article</p>
            <h2 class="featured-title">
                <a href="{{ route('articles.show', $article) }}" class="article-title-link">{{ $article->title }}</a>
            </h2>
            <p class="article-byline featured-byline">
                By <span class="article-author">{{ $article->author?->name ?? 'Former member' }}</span>
                @if ($article->created_at) &middot; {{ $article->created_at->format('M j, Y') }} @endif
            </p>
            <a href="{{ route('articles.show', $article) }}" class="featured-image-link card-media" aria-label="Read {{ $article->title }}">
                <img src="{{ $article->imageUrl() }}" alt="Featured article: {{ $article->title }}" class="article-image">
            </a>
        </article>
    @empty
        <section class="featured-article">
            <p class="article-category">Article</p>
            <h2 class="featured-title">No articles have been published yet.</h2>
        </section>
    @endforelse

    <hr class="home-divider">

    {{-- ===================== SECONDARY ARTICLES ===================== --}}
    <div class="secondary-articles">

        {{-- vertical divider (desktop only) --}}
        <div class="secondary-articles-divider"></div>

        {{-- Public Activity cards contain only approved and active records from the home query. --}}
        @forelse ($activities as $activity)
            <article class="secondary-article secondary-article-first card-hover">
                <a href="{{ route('activities.show', $activity) }}" class="secondary-article-image-link card-media" aria-label="Read {{ $activity->title }}">
                        <img src="{{ $activity->imageUrl() }}" alt="Activity: {{ $activity->title }}" class="article-image">
                </a>
                <p class="article-category">Activities</p>
                <h3 class="secondary-article-title">
                    <a href="{{ route('activities.show', $activity) }}" class="article-title-link">{{ $activity->title }}</a>
                </h3>
                <p class="article-byline">
                    By <span class="article-author">{{ $activity->author?->name ?? 'Former member' }}</span>
                    @if ($activity->activity_date) &middot; {{ $activity->activity_date->format('M j, Y') }} @endif
                </p>
            </article>
        @empty
            <article class="secondary-article secondary-article-first">
                <p class="article-category">Activities</p>
                <p>No activities have been published yet.</p>
            </article>
        @endforelse

        {{-- Public Achievement cards contain only approved and active records from the home query. --}}
        @forelse ($achievements as $achievement)
            <article class="secondary-article secondary-article-second card-hover">
                <a href="{{ route('achievements.show', $achievement) }}" class="secondary-article-image-link card-media" aria-label="Read {{ $achievement->title }}">
                        <img src="{{ $achievement->imageUrl() }}" alt="Achievement: {{ $achievement->title }}" class="article-image">
                </a>
                <p class="article-category">Achievements</p>
                <h3 class="secondary-article-title">
                    <a href="{{ route('achievements.show', $achievement) }}" class="article-title-link">{{ $achievement->title }}</a>
                </h3>
                <p class="article-byline">
                    By <span class="article-author">{{ $achievement->author?->name ?? 'Former member' }}</span>
                    @if ($achievement->achievement_date) &middot; {{ $achievement->achievement_date->format('M j, Y') }} @endif
                </p>
            </article>
        @empty
            <article class="secondary-article secondary-article-second">
                <p class="article-category">Achievements</p>
                <p>No achievements have been published yet.</p>
            </article>
        @endforelse
    </div>

</div>
@endsection
