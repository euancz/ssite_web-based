@extends('layouts.app')

@section('title', 'Home')

{{-- Load the home-specific card layout styles for this page. --}}
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
<div class="home-page site-page">
    <section class="home-hero" aria-labelledby="home-hero-title">
        <div class="home-hero-art" aria-hidden="true">
            <span class="hero-note hero-note--welcome">WELCOME</span>
            <span class="hero-note hero-note--to">TO</span>
            <span class="hero-note hero-note--ssite">SSITE</span>
            <span class="hero-window"><img src="{{ asset('images/SSITE LOGO.png') }}" alt=""></span>
            <span class="hero-laptop"><span>{...}</span></span>
        </div>
    </section>

    <section class="home-intro" aria-labelledby="home-hero-title">
        <div class="home-intro-image">
            <img src="{{ asset('images/Wolf.png') }}" alt="SSITE community placeholder illustration">
        </div>
        <div class="home-hero-copy">
            <p class="home-eyebrow">Student Society in Information Technology Education</p>
            <h1 id="home-hero-title">Welcome to the SSITE Community!</h1>
            <p>Welcome to the official website of the Student Society in Information Technology Education (SSITE) of Mabalacat City College. Stay connected with our latest announcements, activities, articles, and achievements as we bring IT students together through collaboration, innovation, and meaningful experiences.</p>
        </div>
    </section>

    <section class="home-section home-news" aria-labelledby="home-news-title">
        <div class="section-heading">
            <h2 id="home-news-title">What's New at SSITE</h2>
        </div>

        <div class="news-grid">
            {{-- Public home queries contain approved and active records only. --}}
            @forelse ($articles as $article)
                <article class="news-card news-card--featured card-hover" data-motion="reveal">
                    <div class="news-card-content">
                        <p class="article-category">Article</p>
                        <h3><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h3>
                        <p class="article-byline">By {{ $article->author?->name ?? 'Former member' }} @if ($article->created_at)&middot; {{ $article->created_at->format('M j, Y') }} @endif</p>
                        <a class="read-more" href="{{ route('articles.show', $article) }}">Read More <span aria-hidden="true">→</span></a>
                    </div>
                    <a href="{{ route('articles.show', $article) }}" aria-label="Read {{ $article->title }}">
                        <img class="news-card-image" src="{{ $article->imageUrl() }}" alt="Featured article: {{ $article->title }}">
                    </a>
                </article>
            @empty
                <article class="news-card news-card--featured"><div class="news-card-content"><p class="article-category">Article</p><h3>No articles have been published yet.</h3></div></article>
            @endforelse

            @forelse ($activities as $activity)
                <article class="news-card card-hover" data-motion="reveal">
                    <a href="{{ route('activities.show', $activity) }}" aria-label="Read {{ $activity->title }}"><img class="news-card-image" src="{{ $activity->imageUrl() }}" alt="Activity: {{ $activity->title }}"></a>
                    <div class="news-card-content">
                        <p class="article-category">Activities</p>
                        <h3><a href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a></h3>
                        <p class="article-byline">By {{ $activity->author?->name ?? 'Former member' }} @if ($activity->activity_date)&middot; {{ $activity->activity_date->format('M j, Y') }} @endif</p>
                        <a class="read-more" href="{{ route('activities.show', $activity) }}">Read More <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            @empty
                <article class="news-card"><div class="news-card-content"><p class="article-category">Activities</p><h3>No activities have been published yet.</h3></div></article>
            @endforelse

            @forelse ($achievements as $achievement)
                <article class="news-card card-hover" data-motion="reveal">
                    <a href="{{ route('achievements.show', $achievement) }}" aria-label="Read {{ $achievement->title }}"><img class="news-card-image" src="{{ $achievement->imageUrl() }}" alt="Achievement: {{ $achievement->title }}"></a>
                    <div class="news-card-content">
                        <p class="article-category">Achievements</p>
                        <h3><a href="{{ route('achievements.show', $achievement) }}">{{ $achievement->title }}</a></h3>
                        <p class="article-byline">By {{ $achievement->author?->name ?? 'Former member' }} @if ($achievement->achievement_date)&middot; {{ $achievement->achievement_date->format('M j, Y') }} @endif</p>
                        <a class="read-more" href="{{ route('achievements.show', $achievement) }}">Read More <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            @empty
                <article class="news-card"><div class="news-card-content"><p class="article-category">Achievements</p><h3>No achievements have been published yet.</h3></div></article>
            @endforelse
        </div>
    </section>

    <section class="home-leaders" aria-labelledby="home-leaders-title">
        <div class="section-heading">
            <h2 id="home-leaders-title">Meet the Leaders of SSITE!</h2>
            <p>Meet the SSITE-MCC student leaders dedicated to serving the IT community, bringing ideas to life, and creating meaningful opportunities for students.</p>
        </div>
        <div class="leaders-carousel" data-leaders-carousel>
            <button type="button" class="leaders-carousel-button leaders-carousel-button--previous" data-leaders-previous aria-label="View previous leaders">
                <span aria-hidden="true">‹</span>
            </button>
            <div class="leaders-grid" data-leaders-track tabindex="0" aria-label="SSITE officers">
            @forelse ($officers as $officer)
                <article class="leader-card">
                    @if ($officer->photoUrl())
                        <img src="{{ $officer->photoUrl() }}" alt="Portrait of {{ $officer->name }}">
                    @else
                        <img src="{{ asset('images/Wolf.png') }}" alt="">
                    @endif
                    <h3>{{ $officer->name }}</h3>
                    <p>{{ $officer->position }}</p>
                </article>
            @empty
                <p class="home-empty-state">Our current leaders will be announced soon.</p>
            @endforelse
            </div>
            <button type="button" class="leaders-carousel-button leaders-carousel-button--next" data-leaders-next aria-label="View next leaders">
                <span aria-hidden="true">›</span>
            </button>
        </div>
    </section>

    <section class="home-sponsors home-section" aria-labelledby="home-sponsors-title">
        <div class="section-heading">
            <h2 id="home-sponsors-title">The Network Behind SSITE</h2>
            <p>Recognizing the partners and sponsors whose support, collaboration, and shared vision help SSITE create meaningful opportunities for IT students and the community.</p>
        </div>
        <div class="sponsor-grid">
            @foreach (['Sponsor 1', 'Sponsor 2', 'Sponsor 3'] as $sponsor)
                <article class="sponsor-card">
                    <div class="sponsor-image"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt=""></div>
                    <div class="sponsor-content">
                        <h3>{{ $sponsor }}</h3>
                        <p>Supporting SSITE’s programs through generous contributions that help create meaningful experiences and opportunities for students.</p>
                        <a href="https://www.facebook.com/ICSSSITE" target="_blank" rel="noopener noreferrer">Visit Official Page <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
@endsection

@push('scripts')
    <script>
        (() => {
            const carousel = document.querySelector('[data-leaders-carousel]');
            const track = carousel?.querySelector('[data-leaders-track]');
            const previous = carousel?.querySelector('[data-leaders-previous]');
            const next = carousel?.querySelector('[data-leaders-next]');
            if (!carousel || !track || !previous || !next) return;

            const updateButtons = () => {
                const maxScroll = track.scrollWidth - track.clientWidth;
                previous.disabled = track.scrollLeft <= 1;
                next.disabled = track.scrollLeft >= maxScroll - 1;
            };

            const move = (direction) => {
                const card = track.querySelector('.leader-card');
                const gap = parseFloat(getComputedStyle(track).gap) || 0;
                const amount = (card?.getBoundingClientRect().width || track.clientWidth) + gap;
                track.scrollBy({ left: direction * amount, behavior: 'smooth' });
            };

            previous.addEventListener('click', () => move(-1));
            next.addEventListener('click', () => move(1));
            track.addEventListener('scroll', updateButtons, { passive: true });
            window.addEventListener('resize', updateButtons);
            updateButtons();
        })();
    </script>
@endpush
