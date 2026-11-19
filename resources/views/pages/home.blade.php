@extends(request()->header('X-SSITE-Page-Data') === '1' ? 'layouts.page-data' : 'layouts.app')

@section('title', 'Home')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}?v={{ filemtime(public_path('css/home.css')) }}">
    <link rel="stylesheet" href="{{ asset('css/home-community-hero.css') }}?v={{ filemtime(public_path('css/home-community-hero.css')) }}">
@endpush

@section('content')
@php($defaultPostImage = asset(config('school.default_profile_picture', 'images/Wolf.png')))
<div class="home-page site-page">
    <section class="community-hero" aria-labelledby="home-hero-title">
        <div class="community-hero-inner">
            <div class="community-hero-copy">
                <h1 id="home-hero-title">SSITE</h1>
                <p class="community-hero-campus">Mabalacat City College</p>
                <p class="community-hero-description">Catch up on campus stories, student activities, and achievements.</p>
                <div class="community-hero-actions">
                    <a class="community-hero-button" href="{{ route('discover') }}">Browse posts <x-icon name="arrow-right" size="sm" /></a>
                    <a class="community-hero-more" href="#home-news-title">Latest updates <x-icon name="chevron-down" size="sm" /></a>
                </div>
            </div>
            <figure class="community-hero-identity">
                <div class="community-hero-seal">
                    <img src="{{ asset('images/SSITE LOGO.png') }}" alt="" width="240" height="240" fetchpriority="high" decoding="async">
                </div>
                <figcaption>Student Society in Information Technology Education</figcaption>
            </figure>
        </div>
    </section>

    <section class="home-intro" aria-labelledby="home-intro-title">
        <div class="home-intro-image">
            <img src="{{ asset('images/rawri-community-cutout.png') }}" alt="SSITE wolf mascot wearing a hoodie" width="360" height="316" loading="eager" decoding="async">
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

        <div class="news-grid" data-page-data="news" @if ($pageDataPending ?? false) aria-busy="true" @endif>
            @if ($pageDataPending ?? false)
                @foreach (['Article', 'Activities', 'Achievements'] as $category)
                    <article @class(['news-card', 'news-card--featured' => $loop->first])>
                        @unless ($loop->first)
                            <div class="news-card-image" aria-hidden="true"><span class="skeleton-block home-skeleton-image"></span></div>
                        @endunless
                        <div class="news-card-content">
                            <p class="article-category">{{ $category }}</p>
                            <div class="skeleton-lines home-news-skeleton-copy" aria-hidden="true">
                                <span class="skeleton-block skeleton-line skeleton-line--heading"></span>
                                <span class="skeleton-block skeleton-line skeleton-line--short"></span>
                            </div>
                        </div>
                        @if ($loop->first)
                            <div class="news-card-image" aria-hidden="true"><span class="skeleton-block home-skeleton-image"></span></div>
                        @endif
                    </article>
                @endforeach
            @else
            @forelse ($articles as $article)
                @php($imageUrl = $article->imageUrl())
                <article class="news-card news-card--featured card-hover" data-motion="reveal">
                    <div class="news-card-content">
                        <p class="article-category">Article</p>
                        <h3><a href="{{ route('articles.show', $article) }}">{{ $article->title }}</a></h3>
                        <p class="article-byline">By {{ $article->author?->name ?? 'Former member' }} @if ($article->created_at)&middot; {{ $article->created_at->format('M j, Y') }} @endif</p>
                        <a class="read-more" href="{{ route('articles.show', $article) }}">Read more <x-icon name="chevron-right" size="sm" class="card-read-more-icon" /></a>
                    </div>
                    <a href="{{ route('articles.show', $article) }}" aria-label="Read {{ $article->title }}">
                        <img class="news-card-image" src="{{ $imageUrl }}" alt="Article: {{ $article->title }}" loading="{{ $loop->first ? 'eager' : 'lazy' }}" decoding="async" @if ($imageUrl !== $defaultPostImage) data-home-image @endif>
                    </a>
                </article>
            @empty
                <article class="news-card news-card--featured"><div class="news-card-content"><p class="article-category">Article</p><h3>No articles have been published yet.</h3></div></article>
            @endforelse

            @forelse ($activities as $activity)
                @php($imageUrl = $activity->imageUrl())
                <article class="news-card card-hover" data-motion="reveal">
                    <a href="{{ route('activities.show', $activity) }}" aria-label="Read {{ $activity->title }}"><img class="news-card-image" src="{{ $imageUrl }}" alt="Activity: {{ $activity->title }}" loading="lazy" decoding="async" @if ($imageUrl !== $defaultPostImage) data-home-image @endif></a>
                    <div class="news-card-content">
                        <p class="article-category">Activities</p>
                        <h3><a href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a></h3>
                        <p class="article-byline">By {{ $activity->author?->name ?? 'Former member' }} @if ($activity->activity_date)&middot; {{ $activity->activity_date->format('M j, Y') }} @endif</p>
                        <a class="read-more" href="{{ route('activities.show', $activity) }}">Read more <x-icon name="chevron-right" size="sm" class="card-read-more-icon" /></a>
                    </div>
                </article>
            @empty
                <article class="news-card"><div class="news-card-content"><p class="article-category">Activities</p><h3>No activities have been published yet.</h3></div></article>
            @endforelse

            @forelse ($achievements as $achievement)
                @php($imageUrl = $achievement->imageUrl())
                <article class="news-card card-hover" data-motion="reveal">
                    <a href="{{ route('achievements.show', $achievement) }}" aria-label="Read {{ $achievement->title }}"><img class="news-card-image" src="{{ $imageUrl }}" alt="Achievement: {{ $achievement->title }}" loading="lazy" decoding="async" @if ($imageUrl !== $defaultPostImage) data-home-image @endif></a>
                    <div class="news-card-content">
                        <p class="article-category">Achievements</p>
                        <h3><a href="{{ route('achievements.show', $achievement) }}">{{ $achievement->title }}</a></h3>
                        <p class="article-byline">By {{ $achievement->author?->name ?? 'Former member' }} @if ($achievement->achievement_date)&middot; {{ $achievement->achievement_date->format('M j, Y') }} @endif</p>
                        <a class="read-more" href="{{ route('achievements.show', $achievement) }}">Read more <x-icon name="chevron-right" size="sm" class="card-read-more-icon" /></a>
                    </div>
                </article>
            @empty
                <article class="news-card"><div class="news-card-content"><p class="article-category">Achievements</p><h3>No achievements have been published yet.</h3></div></article>
            @endforelse
            @endif
        </div>
    </section>

    <section class="home-leaders" aria-labelledby="home-leaders-title">
        <div class="section-heading">
            <h2 id="home-leaders-title">Meet the Leaders of SSITE!</h2>
            <p>Meet the SSITE-MCC student leaders dedicated to serving the IT community, bringing ideas to life, and creating meaningful opportunities for students.</p>
        </div>
        <div class="leaders-carousel" data-leaders-carousel>
            <button type="button" class="leaders-carousel-button leaders-carousel-button--previous" data-leaders-previous aria-label="View previous leaders"><span aria-hidden="true">‹</span></button>
            <div class="leaders-grid" data-leaders-track data-page-data="officers" tabindex="0" aria-label="SSITE officers" @if ($pageDataPending ?? false) aria-busy="true" @endif>
            @if ($pageDataPending ?? false)
                @for ($card = 0; $card < 4; $card++)
                    <article class="leader-card" aria-hidden="true">
                        <div class="leader-photo-frame">
                            <span class="skeleton-block leader-portrait-skeleton"></span>
                        </div>
                        <div class="leader-nameplate skeleton-lines leader-skeleton-copy">
                            <span class="skeleton-block skeleton-line"></span>
                            <span class="skeleton-block skeleton-line skeleton-line--short"></span>
                        </div>
                    </article>
                @endfor
            @else
            @forelse ($officers as $officer)
                <article class="leader-card">
                    @php($officerPhoto = $officer->photoUrl())
                    <div class="leader-photo-frame">
                        @if ($officerPhoto)
                            <img src="{{ $officerPhoto }}" alt="Portrait of {{ $officer->name }}" loading="lazy" decoding="async" data-home-image>
                        @endif
                    </div>
                    <div class="leader-nameplate">
                        <h3>{{ $officer->name }}</h3>
                        <p>{{ $officer->position }}</p>
                    </div>
                </article>
            @empty
                <p class="home-empty-state">Our current leaders will be announced soon.</p>
            @endforelse
            @endif
            </div>
            <button type="button" class="leaders-carousel-button leaders-carousel-button--next" data-leaders-next aria-label="View next leaders"><span aria-hidden="true">›</span></button>
        </div>
    </section>

    <section class="home-sponsors home-section" aria-labelledby="home-sponsors-title">
        <div class="section-heading">
            <h2 id="home-sponsors-title">The Network Behind SSITE</h2>
            <p>Recognizing the partners and sponsors whose support, collaboration, and shared vision help SSITE create meaningful opportunities for IT students and the community.</p>
        </div>
        <div class="sponsor-grid" data-page-data="sponsors" @if ($pageDataPending ?? false) aria-busy="true" @endif>
            @if ($pageDataPending ?? false)
                @for ($card = 0; $card < 3; $card++)
                    <article class="sponsor-card" aria-hidden="true">
                        <div class="sponsor-image"><span class="skeleton-block sponsor-logo-skeleton"></span></div>
                        <div class="sponsor-content skeleton-lines">
                            <span class="skeleton-block skeleton-line skeleton-line--heading"></span>
                            <span class="skeleton-block skeleton-line"></span>
                            <span class="skeleton-block skeleton-line skeleton-line--short"></span>
                        </div>
                    </article>
                @endfor
            @else
            @forelse ($sponsors as $sponsor)
                @php($logoUrl = $sponsor->logoUrl())
                <article class="sponsor-card" data-motion="reveal">
                    <div class="sponsor-image"><img src="{{ $logoUrl ?? asset('images/ssite - revamp logo 2.png') }}" alt="" loading="lazy" decoding="async" @if ($logoUrl) data-home-image @endif></div>
                    <div class="sponsor-content">
                        <h3>{{ $sponsor->name }}</h3>
                        <p>{{ $sponsor->description }}</p>
                        <x-sponsor-link :url="$sponsor->website_url" :sponsor-name="$sponsor->name" />
                    </div>
                </article>
            @empty
                <p class="sponsors-empty">Our partners and sponsors will be announced soon.</p>
            @endforelse
            @endif
        </div>
    </section>
</div>
@endsection

@push('scripts')
    <script>
        const initializeHomeImages = () => {
            const homePage = document.querySelector('.home-page');
            homePage?.classList.add('home-images-enabled');
            const images = document.querySelectorAll('[data-home-image]:not([data-image-state])');

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
                const shell = document.createElement('span');
                shell.className = 'home-image-shell';
                image.before(shell);
                shell.append(image);
                setState(image, 'loading');
                image.addEventListener('load', () => markReady(image), { once: true });
                image.addEventListener('error', () => { setState(image, 'error'); }, { once: true });
                if (image.complete) markReady(image);
            });
        };
        initializeHomeImages();
        document.addEventListener('page-data-loaded', initializeHomeImages);

        (() => {
            const carousel = document.querySelector('[data-leaders-carousel]');
            const track = carousel?.querySelector('[data-leaders-track]');
            const previous = carousel?.querySelector('[data-leaders-previous]');
            const next = carousel?.querySelector('[data-leaders-next]');
            if (!carousel || !track || !previous || !next) return;

            const updateButtons = () => {
                const loading = track.getAttribute('aria-busy') === 'true';
                const maxScroll = track.scrollWidth - track.clientWidth;
                previous.disabled = loading || track.scrollLeft <= 1;
                next.disabled = loading || track.scrollLeft >= maxScroll - 1;
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
            document.addEventListener('page-data-loaded', updateButtons);
            updateButtons();
        })();
    </script>
@endpush
