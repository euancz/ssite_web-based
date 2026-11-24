@inject('siteContent', 'App\Support\SiteContent')
@extends(request()->header('X-SSITE-Page-Data') === '1' ? 'layouts.page-data' : 'layouts.app')

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

        .discover-field select,
        .discover-field input {
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

        .discover-field input { background-image: none; padding-right: 1rem; }
        .discover-filters .site-button { min-height: 44px; padding: 12px 24px; }
        .discover-card-link { display: block; height: 100%; color: inherit; text-decoration: none; }
        .discover-card-link:focus-visible { outline: 3px solid #167eaa; outline-offset: -3px; }
        .discover-card-link:hover h3 { text-decoration: underline; }
        .discover-card-summary { display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; line-height: 1.5; }
        .discover-read-story { display: inline-block; margin-top: 16px; font-weight: 700; }

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

        .discover-story-card img,
        .discover-story-card > .skeleton-block {
            display: block;
            width: 100%;
            height: 220px;
            object-fit: cover;
        }

        .discover-featured-image.skeleton-block,
        .discover-story-card > .skeleton-block { border-radius: 0; }
        .discover-featured-copy .skeleton-lines { gap: 1rem; }
        .discover-story-body.skeleton-lines { min-height: 9rem; }

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
            .discover-filters { grid-template-columns: 1fr; }

            .discover-main {
                padding-left: 14px;
                padding-right: 14px;
            }

            .discover-grid {
                grid-template-columns: 1fr;
            }

            .discover-featured-copy {
                padding: 20px 18px;
            }

            .discover-story-card img,
            .discover-story-card > .skeleton-block {
                height: 200px;
            }
        }
    </style>
@endpush

@section('content')
    <div class="discover-page">
        <div class="discover-main site-page">
            <x-ui.stories-banner
            edit-section="discover"
                :eyebrow="$siteContent->value('discover', 'eyebrow')"
                :title="$siteContent->value('discover', 'title')"
                :description="$siteContent->value('discover', 'description')"
                labelledby="discover-title"
            />

            <form class="discover-filters" aria-label="Discover filters" method="GET" action="{{ route('discover') }}">
                <div class="discover-field">
                    <label for="discover-search">Search stories</label>
                    <div class="main-search-field">
                        <input id="discover-search" type="search" name="q" maxlength="100" value="{{ $filters['q'] }}" placeholder="Title or description">
                        <button type="submit" class="search-submit-icon" aria-label="Search stories"><x-icon name="search" size="sm" class="site-search-icon" /></button>
                    </div>
                </div>
                <x-posts.filter-panel :active-count="(int) ($filters['type'] !== 'all') + (int) ($filters['category'] !== '') + (int) ($filters['period'] !== 'all') + (int) ($filters['sort'] !== 'recent')">
                <div class="discover-field">
                    <label for="discover-type">Content type</label>
                    <select id="discover-type" name="type" onchange="if (this.form.elements.category) this.form.elements.category.value = ''; this.form.requestSubmit()">
                        @foreach (['all' => 'All content', 'articles' => 'Articles', 'activities' => 'Activities', 'achievements' => 'Achievements'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['type'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="page-data-contents" data-page-data="categories" @if ($pageDataPending ?? false) aria-busy="true" @endif>
                @if (($pageDataPending ?? false) && $filters['type'] !== 'all')
                    <div class="discover-field">
                        <label for="discover-category">Category</label>
                        <select id="discover-category" disabled><option>Loading categories</option></select>
                        <input type="hidden" name="category" value="{{ $filters['category'] }}">
                    </div>
                @elseif ($filters['type'] !== 'all' && ! empty($categoryGroups))
                <div class="discover-field">
                    <label for="discover-category">Category</label>
                    <select id="discover-category" name="category" onchange="this.form.requestSubmit()">
                        <option value="">All categories</option>
                        @foreach ($categoryGroups as $options)
                            @foreach ($options as $value => $label)
                                <option value="{{ $value }}" @selected($filters['category'] === $value)>{{ $label }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                @endif
                </div>

                <div class="discover-field">
                    <label for="discover-date">Date added</label>
                    <select id="discover-date" name="period" onchange="this.form.requestSubmit()">
                        @foreach (['all' => 'Any time', 'week' => 'Last 7 days', 'month' => 'Last 30 days', 'year' => 'Last year'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['period'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="discover-field">
                    <label for="discover-sort">Sort by</label>
                    <select id="discover-sort" name="sort" onchange="this.form.requestSubmit()">
                        @foreach (['recent' => 'Newest added', 'oldest' => 'Oldest added', 'title' => 'Title (A to Z)'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                </x-posts.filter-panel>
                <noscript><button class="site-button" type="submit">Apply filters</button></noscript>
                @if ($filters['q'] !== '' || $filters['type'] !== 'all' || $filters['period'] !== 'all' || $filters['sort'] !== 'recent' || $filters['category'] !== '')
                    <a class="site-filter-reset" href="{{ route('discover') }}">Clear filters</a>
                @endif
            </form>

            <section class="discover-panel" id="discover-all" aria-label="Discover stories" data-page-data="stories" @if ($pageDataPending ?? false) aria-busy="true" @endif>
            @if ($pageDataPending ?? false)
                <article class="discover-featured" aria-hidden="true">
                    <div class="discover-featured-image skeleton-block"></div>
                    <div class="discover-featured-copy">
                        <div class="skeleton-lines">
                            <span class="skeleton-block skeleton-line skeleton-line--short"></span>
                            <span class="skeleton-block skeleton-line skeleton-line--heading"></span>
                            <span class="skeleton-block skeleton-line"></span>
                            <span class="skeleton-block skeleton-line skeleton-line--short"></span>
                        </div>
                    </div>
                </article>
                <div class="discover-grid" aria-hidden="true">
                    @for ($card = 0; $card < 3; $card++)
                        <article class="discover-story-card">
                            <div class="skeleton-block"></div>
                            <div class="discover-story-body skeleton-lines">
                                <span class="skeleton-block skeleton-line skeleton-line--heading"></span>
                                <span class="skeleton-block skeleton-line"></span>
                                <span class="skeleton-block skeleton-line skeleton-line--short"></span>
                            </div>
                        </article>
                    @endfor
                </div>
            @else
            @php
                $featured = $stories->first();
                $labels = ['articles' => 'Articles', 'activities' => 'Activities', 'achievements' => 'Achievements'];
                $featuredType = $featured ? $labels[$featured->getTable()] : '';
                $featuredRoute = $featured ? $featured->getTable().'.show' : '';
            @endphp

                @if ($featured)
                    <article class="discover-featured" data-motion="reveal">
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
                            <h2>{{ $filters['q'] !== '' || $filters['period'] !== 'all' ? 'No matching stories' : 'No published stories yet' }}</h2>
                            <p>Try another search or date range, or browse a different content type.</p>
                            <div class="discover-author">Check back soon for the latest SSITE updates.</div>
                        </div>
                    </article>
                @endif

                <div class="discover-grid">
                    @foreach ($stories->getCollection()->skip(1) as $item)
                            <article class="discover-story-card">
                                <a class="discover-card-link" href="{{ route($item->getTable().'.show', $item) }}"><img src="{{ $item->imageUrl() }}" alt="">
                                <div class="discover-story-body">
                                    <div class="discover-story-meta"><span>{{ $labels[$item->getTable()] }}</span></div>
                                    <h3>{{ $item->title }}</h3>
                                    <p class="discover-card-summary">{{ \Illuminate\Support\Str::limit($item instanceof \App\Models\Article ? $item->displayText() : strip_tags($item->description ?? ''), 180) }}</p>
                                    <div class="discover-story-author">By {{ $item->author?->name ?? 'Former member' }} &middot; {{ $item->created_at?->format('F j, Y') }}</div>
                                    <span class="discover-read-story"><span>Read more</span><x-icon name="chevron-right" size="sm" class="card-read-more-icon" /></span>
                                </div>
                                </a>
                            </article>
                    @endforeach
                </div>
                {{ $stories->links() }}
            @endif
            </section>
        </div>
    </div>
@endsection
