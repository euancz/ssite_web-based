@extends('layouts.app')

@section('title', 'Discover')

@push('styles')
    <style>
        .discover-site-body {
            background: #f8f8f8;
        }

        .discover-page {
            background: #f8f8f8;
            min-height: 100vh;
        }

        .discover-main {
            max-width: 1180px;
            margin: 0 auto;
            padding: 24px 20px 0;
        }

        .discover-hero {
            position: relative;
            overflow: hidden;
            background: linear-gradient(180deg, rgba(17, 171, 216, 0.84), rgba(107, 202, 222, 0.88));
            border-radius: 26px;
            padding: 34px 30px 32px;
            box-shadow: 0 10px 18px rgba(17, 83, 119, 0.08);
            min-height: 210px;
        }

        .discover-hero::before {
            content: "";
            position: absolute;
            inset: 0;
            background:
                linear-gradient(140deg, rgba(255,255,255,0.12), transparent 32%),
                linear-gradient(130deg, transparent 44%, rgba(3, 66, 92, 0.08) 47%, transparent 50%),
                repeating-linear-gradient(
                    90deg,
                    transparent,
                    transparent 34px,
                    rgba(255,255,255,0.10) 34px,
                    rgba(255,255,255,0.10) 35px
                );
            pointer-events: none;
        }

        .discover-hero-inner {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 820px;
            margin: 0 auto;
        }

        .discover-kicker {
            margin: 0 0 10px;
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.28rem;
            color: rgba(12, 55, 73, 0.95);
            text-transform: uppercase;
        }

        .discover-title {
            margin: 0;
            font-size: clamp(2.4rem, 5vw, 5rem);
            line-height: 0.96;
            letter-spacing: -0.08em;
            font-weight: 800;
            color: #0c2d3d;
        }

        .discover-subtitle {
            margin: 16px auto 0;
            max-width: 640px;
            font-size: 1.05rem;
            color: rgba(8, 45, 59, 0.8);
            line-height: 1.55;
        }

        .discover-filters {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
            margin-top: 26px;
        }

        .discover-field {
            display: flex;
            flex-direction: column;
            gap: 10px;
            font-weight: 600;
            color: #0f3550;
        }

        .discover-field label {
            font-size: 0.9rem;
            line-height: 1;
        }

        .discover-field select {
            appearance: none;
            width: 100%;
            border: 1px solid rgba(17, 114, 132, 0.55);
            border-radius: 12px;
            background: rgba(206, 244, 255, 0.72);
            color: #0c2d3d;
            padding: 0.9rem 2.8rem 0.9rem 1rem;
            font: inherit;
            font-weight: 600;
            box-shadow: inset 0 1px 0 rgba(255,255,255,0.25);
            background-image: linear-gradient(45deg, transparent 50%, #0f3550 50%), linear-gradient(135deg, #0f3550 50%, transparent 50%);
            background-position: calc(100% - 18px) calc(50% - 2px), calc(100% - 12px) calc(50% - 2px);
            background-size: 6px 6px, 6px 6px;
            background-repeat: no-repeat;
        }

        .discover-feed {
            margin-top: 28px;
        }

        .discover-featured {
            display: grid;
            grid-template-columns: 1.38fr 1fr;
            background: rgba(255, 255, 255, 0.96);
            border: 1px solid rgba(64, 120, 148, 0.15);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 14px 28px rgba(10, 61, 82, 0.08);
        }

        .discover-featured-image {
            min-height: 320px;
        }

        .discover-featured-image img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .discover-featured-copy {
            display: flex;
            flex-direction: column;
            justify-content: center;
            padding: 24px 26px;
            background: rgba(255, 255, 255, 0.96);
        }

        .discover-tag-row {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .discover-tag {
            display: inline-flex;
            align-items: center;
            padding: 0.38rem 0.7rem;
            border-radius: 999px;
            background: rgba(101, 190, 226, 0.15);
            color: #0e6f8e;
            font-size: 0.76rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .discover-featured-copy h2 {
            margin: 0;
            color: #0f2d3d;
            font-size: clamp(1.8rem, 2.5vw, 3rem);
            line-height: 1.15;
            letter-spacing: -0.05em;
        }

        .discover-featured-copy p {
            margin: 16px 0 0;
            color: rgba(18, 49, 65, 0.8);
            font-size: 1rem;
            line-height: 1.7;
        }

        .discover-author {
            margin-top: 20px;
            color: #496a7d;
            font-size: 0.92rem;
            font-weight: 600;
        }

        .discover-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 22px;
            margin-top: 24px;
        }

        .discover-story-card {
            background: rgba(255, 255, 255, 0.98);
            border: 1px solid rgba(64, 120, 148, 0.15);
            border-radius: 18px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(17, 79, 97, 0.06);
        }

        .discover-story-card img {
            display: block;
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .discover-story-body {
            padding: 18px 16px 16px;
        }

        .discover-story-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            margin-bottom: 10px;
        }

        .discover-story-meta span {
            display: inline-flex;
            align-items: center;
            padding: 0.34rem 0.65rem;
            border-radius: 999px;
            background: rgba(71, 164, 200, 0.14);
            color: #0c6479;
            font-size: 0.72rem;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-transform: uppercase;
        }

        .discover-story-card h3 {
            margin: 0;
            color: #0f2d3d;
            font-size: clamp(1.2rem, 1.8vw, 2rem);
            line-height: 1.3;
            letter-spacing: -0.04em;
        }

        .discover-story-author {
            margin-top: 20px;
            color: #496a7d;
            font-size: 0.88rem;
            font-weight: 600;
        }

        .discover-panel {
            margin-top: 28px;
        }

        .discover-panel[hidden] {
            display: none;
        }

        .discover-placeholder {
            background: linear-gradient(145deg, rgba(223, 244, 248, 0.9), rgba(255, 255, 255, 0.98));
        }

        .discover-placeholder img {
            object-fit: contain;
            padding: 32px;
            opacity: 0.68;
        }

        .discover-placeholder h2,
        .discover-placeholder h3 {
            color: #315b6b;
        }

        .discover-empty {
            color: #315b6b;
            font-weight: 600;
        }

        @media (max-width: 960px) {

            .discover-filters {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .discover-featured {
                grid-template-columns: 1fr;
            }

            .discover-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 640px) {

            .discover-main {
                padding-left: 14px;
                padding-right: 14px;
            }

            .discover-hero {
                padding: 26px 18px 24px;
            }

            .discover-kicker {
                letter-spacing: 0.16rem;
                font-size: 0.72rem;
            }

            .discover-subtitle {
                font-size: 0.94rem;
            }

            .discover-grid {
                grid-template-columns: 1fr;
            }

            .discover-featured-copy {
                padding: 20px 18px;
            }

            .discover-story-card img {
                height: 200px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="discover-page">
        <div class="discover-main site-page">
            <section class="discover-hero site-hero" aria-labelledby="discover-title">
                <div class="discover-hero-inner">
                    <p class="discover-kicker">Articles • Activities • Achievements</p>
                    <h1 class="discover-title" id="discover-title">Stories &amp; Highlights</h1>
                    <p class="discover-subtitle">Discover the stories, activities, and achievements that highlight student creativity, experience, and accomplishments.</p>
                </div>
            </section>

            <section class="discover-filters" aria-label="Discover filters">
                <div class="discover-field">
                    <label for="discover-category">Category</label>
                    <select id="discover-category" aria-label="Category filter">
                        <option value="all">All Categories</option>
                        <option value="articles">Articles</option>
                        <option value="activities">Activities</option>
                        <option value="achievements">Achievements</option>
                    </select>
                </div>
                <div class="discover-field">
                    <label for="discover-topic">Topic</label>
                    <select id="discover-topic" aria-label="Topic filter">
                        <option>All Topics</option>
                    </select>
                </div>
                <div class="discover-field">
                    <label for="discover-date">Date Published</label>
                    <select id="discover-date" aria-label="Date published filter">
                        <option>Any Time</option>
                    </select>
                </div>
                <div class="discover-field">
                    <label for="discover-sort">Sort By</label>
                    <select id="discover-sort" aria-label="Sort by filter">
                        <option>All</option>
                    </select>
                </div>
            </section>

            @php
                $featured = $articles->first() ?? $activities->first() ?? $achievements->first();
                $featuredType = $articles->isNotEmpty() ? 'Articles' : ($activities->isNotEmpty() ? 'Activities' : 'Achievements');
                $featuredRoute = $articles->isNotEmpty() ? 'articles.show' : ($activities->isNotEmpty() ? 'activities.show' : 'achievements.show');
                $discoverSections = [
                    'articles' => ['label' => 'Articles', 'items' => $articles, 'route' => 'articles.show'],
                    'activities' => ['label' => 'Activities', 'items' => $activities, 'route' => 'activities.show'],
                    'achievements' => ['label' => 'Achievements', 'items' => $achievements, 'route' => 'achievements.show'],
                ];
            @endphp

            <section class="discover-panel" id="discover-all" role="tabpanel" aria-label="All discover stories">
                @if ($featured)
                    <article class="discover-featured">
                        <div class="discover-featured-image">
                            <img src="{{ $featured->imageUrl() }}" alt="{{ $featured->title }}">
                        </div>
                        <div class="discover-featured-copy">
                            <div class="discover-tag-row"><span class="discover-tag">{{ $featuredType }}</span></div>
                            <h2><a href="{{ route($featuredRoute, $featured) }}">{{ $featured->title }}</a></h2>
                            <p>{{ \Illuminate\Support\Str::limit($featured instanceof \App\Models\Article ? $featured->displayText() : ($featured->description ?? ''), 180) }}</p>
                            <div class="discover-author">By {{ $featured->author?->name ?? 'Former member' }} • {{ $featured->created_at?->format('F j, Y') }}</div>
                        </div>
                    </article>
                @else
                    <article class="discover-featured discover-placeholder">
                        <div class="discover-featured-image">
                            <img src="{{ asset('images/Wolf.png') }}" alt="">
                        </div>
                        <div class="discover-featured-copy">
                            <div class="discover-tag-row"><span class="discover-tag">Stories &amp; Highlights</span></div>
                            <h2>No published stories yet</h2>
                            <p>New articles, activities, and achievements will appear here after they are published.</p>
                            <div class="discover-author">Check back soon for the latest SSITE updates.</div>
                        </div>
                    </article>
                @endif

                <div class="discover-grid">
                    @foreach ($discoverSections as $section)
                        @php($item = $section['items']->first())
                        @if ($item)
                            <article class="discover-story-card">
                                <a href="{{ route($section['route'], $item) }}">
                                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}">
                                </a>
                                <div class="discover-story-body">
                                    <div class="discover-story-meta"><span>{{ $section['label'] }}</span></div>
                                    <h3><a href="{{ route($section['route'], $item) }}">{{ $item->title }}</a></h3>
                                    <div class="discover-story-author">By {{ $item->author?->name ?? 'Former member' }} • {{ $item->created_at?->format('F j, Y') }}</div>
                                </div>
                            </article>
                        @else
                            <article class="discover-story-card discover-placeholder">
                                <img src="{{ asset('images/Wolf.png') }}" alt="">
                                <div class="discover-story-body">
                                    <div class="discover-story-meta"><span>{{ $section['label'] }}</span></div>
                                    <h3>No published {{ strtolower($section['label']) }} yet</h3>
                                    <div class="discover-story-author">New {{ strtolower($section['label']) }} will appear here soon.</div>
                                </div>
                            </article>
                        @endif
                    @endforeach
                </div>
            </section>

            @foreach ($discoverSections as $key => $section)
                <section class="discover-panel" id="discover-{{ $key }}" role="tabpanel" aria-label="{{ $section['label'] }}" hidden>
                    <div class="discover-grid">
                        @forelse ($section['items'] as $item)
                            <article class="discover-story-card">
                                <a href="{{ route($section['route'], $item) }}">
                                    <img src="{{ $item->imageUrl() }}" alt="{{ $item->title }}">
                                </a>
                                <div class="discover-story-body">
                                    <div class="discover-story-meta"><span>{{ $section['label'] }}</span></div>
                                    <h3><a href="{{ route($section['route'], $item) }}">{{ $item->title }}</a></h3>
                                    <div class="discover-story-author">By {{ $item->author?->name ?? 'Former member' }} • {{ $item->created_at?->format('F j, Y') }}</div>
                                </div>
                            </article>
                        @empty
                            @for ($placeholder = 0; $placeholder < 3; $placeholder++)
                                <article class="discover-story-card discover-placeholder">
                                    <img src="{{ asset('images/Wolf.png') }}" alt="">
                                    <div class="discover-story-body">
                                        <div class="discover-story-meta"><span>{{ $section['label'] }}</span></div>
                                        <h3>No published {{ strtolower($section['label']) }} yet</h3>
                                        <div class="discover-story-author">New {{ strtolower($section['label']) }} will appear here soon.</div>
                                    </div>
                                </article>
                            @endfor
                        @endforelse
                    </div>
                </section>
            @endforeach
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const discoverCategory = document.getElementById('discover-category');

        discoverCategory?.addEventListener('change', (event) => {
            const target = event.target.value;

            document.querySelectorAll('.discover-panel').forEach((panel) => {
                panel.hidden = target !== 'all' && panel.id !== `discover-${target}`;
            });
        });
    </script>
@endpush
