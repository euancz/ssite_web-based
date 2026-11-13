@extends('layouts.app')

@section('title', 'Home')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
    <link rel="stylesheet" href="{{ asset('css/home-community-hero.css') }}">
@endpush

@section('content')
<div class="home-page site-page">
    <section class="community-hero" aria-labelledby="home-hero-title">
        <div class="community-hero-inner">
            <div class="community-hero-copy">
                <h1 id="home-hero-title">SSITE<span>Mabalacat City College</span></h1>
                <p class="community-hero-description">The Student Society in Information Technology Education. Find organization news, student activities, and achievements in one place.</p>
                <div class="community-hero-actions">
                    <a class="community-hero-button" href="{{ route('discover') }}">Browse posts <x-icon name="arrow-right" size="sm" /></a>
                    <a class="community-hero-more" href="#home-news-title">Latest updates <x-icon name="chevron-down" size="sm" /></a>
                </div>
            </div>
            <figure class="community-hero-identity">
                <div class="community-hero-seal">
                    <img src="{{ asset('images/SSITE LOGO.png') }}" alt="SSITE emblem" width="240" height="240" fetchpriority="high" decoding="async">
                </div>
                <figcaption>Student Society in<br>Information Technology Education</figcaption>
            </figure>
        </div>
    </section>

    <section class="home-intro" aria-labelledby="home-intro-title">
        <div class="home-intro-image">
            <img src="{{ asset('images/rawri-community-cutout.png') }}" alt="SSITE wolf mascot wearing a hoodie" width="360" height="316" loading="eager" fetchpriority="high" decoding="async" data-home-image>
        </div>
        <div class="home-hero-copy">
            <p class="home-eyebrow">Student Society in Information Technology Education</p>
            <h2 id="home-intro-title">Welcome to the SSITE Community!</h2>
            <p>Welcome to the official website of the Student Society in Information Technology Education (SSITE) of Mabalacat City College. Stay connected with our latest announcements, activities, articles, and achievements as we bring IT students together through collaboration, innovation, and meaningful experiences.</p>
        </div>
    </section>

    <section class="home-section home-news" aria-labelledby="home-news-title">
        <div class="section-heading">
            <h2 id="home-news-title">What's New at SSITE</h2>
        </div>

        <div class="news-grid">
            @forelse ($articles as $article)
                <article class="news-card news-card--featured card-hover" data-motion="reveal">
                    <div class="news-card-content">
                        <p class="article-category">Article</p>
                        <h3><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h3>
                        <p class="article-byline">By {{ $article->author?->name ?? 'Former member' }} @if ($article->created_at)&middot; {{ $article->created_at->format('M j, Y') }} @endif</p>
                        <a class="read-more" href="{{ route('articles.show', $article) }}">Read More <span aria-hidden="true">→</span></a>
                    </div>
                    <a href="{{ route('articles.show', $article) }}" aria-label="Read {{ $article->title }}">
                        <img class="news-card-image" src="{{ $article->imageUrl() }}" alt="Featured article: {{ $article->title }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async" data-home-image>
                    </a>
                </article>
            @empty
                <article class="news-card news-card--featured"><div class="news-card-content"><p class="article-category">Article</p><h3>No articles have been published yet.</h3></div></article>
            @endforelse

            @forelse ($activities as $activity)
                <article class="news-card card-hover" data-motion="reveal">
                    <a href="{{ route('activities.show', $activity) }}" aria-label="Read {{ $activity->title }}"><img class="news-card-image" src="{{ $activity->imageUrl() }}" alt="Activity: {{ $activity->title }}" loading="lazy" decoding="async" data-home-image></a>
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
                    <a href="{{ route('achievements.show', $achievement) }}" aria-label="Read {{ $achievement->title }}"><img class="news-card-image" src="{{ $achievement->imageUrl() }}" alt="Achievement: {{ $achievement->title }}" loading="lazy" decoding="async" data-home-image></a>
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
            <button type="button" class="leaders-carousel-button leaders-carousel-button--previous" data-leaders-previous aria-label="View previous leaders"><span aria-hidden="true">‹</span></button>
            <div class="leaders-grid" data-leaders-track tabindex="0" aria-label="SSITE officers">
            @forelse ($officers as $officer)
                <article class="leader-card">
                    @if ($officer->photoUrl())
                        <img src="{{ $officer->photoUrl() }}" alt="Portrait of {{ $officer->name }}" loading="lazy" decoding="async" data-home-image>
                    @else
                        <img src="{{ asset('images/Wolf.png') }}" alt="" loading="lazy" decoding="async" data-home-image>
                    @endif
                    <h3>{{ $officer->name }}</h3>
                    <p>{{ $officer->position }}</p>
                </article>
            @empty
                <p class="home-empty-state">Our current leaders will be announced soon.</p>
            @endforelse
            </div>
            <button type="button" class="leaders-carousel-button leaders-carousel-button--next" data-leaders-next aria-label="View next leaders"><span aria-hidden="true">›</span></button>
        </div>
    </section>

    <section class="home-sponsors home-section" aria-labelledby="home-sponsors-title">
        <div class="section-heading">
            <h2 id="home-sponsors-title">The Network Behind SSITE</h2>
            <p>Recognizing the partners and sponsors whose support, collaboration, and shared vision help SSITE create meaningful opportunities for IT students and the community.</p>
        </div>
        <div class="sponsor-grid">
            @foreach (['Sponsor 1', 'Sponsor 2', 'Sponsor 3'] as $sponsor)
                <article class="sponsor-card" data-motion="reveal">
                    <div class="sponsor-image"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" loading="lazy" decoding="async" data-home-image></div>
                    <div class="sponsor-content">
                        <h3>{{ $sponsor }}</h3>
                        <p>Supporting SSITE’s programs through generous contributions that help create meaningful experiences and opportunities for students.</p>
                        <a href="https://www.facebook.com/ICSSSITE" target="_blank" rel="noopener noreferrer">Visit Official Page <span aria-hidden="true">→</span></a>
                    </div>
                </article>
            @endforeach
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script>
        (() => {
            const homePage = document.querySelector('.home-page');
            homePage?.classList.add('home-images-enabled');
            const images = document.querySelectorAll('[data-home-image]');

            const setState = (image, state) => {
                image.dataset.imageState = state;
                if (image.parentElement?.classList.contains('home-image-shell')) {
                    image.parentElement.dataset.imageState = state;
                    image.parentElement.setAttribute('aria-busy', String(state === 'loading'));
                }
            };

            const markReady = (image) => {
                if (!image.naturalWidth) {
                    setState(image, 'error');
                    return;
                }

                const reveal = () => { setState(image, 'ready'); };
                if (typeof image.decode === 'function') image.decode().then(reveal).catch(reveal);
                else reveal();
            };

            images.forEach((image) => {
                // Keep the mascot directly on the panel for seamless blending.
                if (!image.closest('.home-intro-image')) {
                    const shell = document.createElement('span');
                    shell.className = 'home-image-shell';
                    image.before(shell);
                    shell.append(image);
                }
                setState(image, 'loading');
                image.addEventListener('load', () => markReady(image), { once: true });
                image.addEventListener('error', () => { setState(image, 'error'); }, { once: true });
                if (image.complete) markReady(image);
            });
        })();

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
                track.scrollBy({ left: direction * amount, behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'instant' : 'smooth' });
            };

            previous.addEventListener('click', () => move(-1));
            next.addEventListener('click', () => move(1));
            track.addEventListener('scroll', updateButtons, { passive: true });
            window.addEventListener('resize', updateButtons);
            updateButtons();
        })();
    </script>
@endpush
