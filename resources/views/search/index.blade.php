@extends('layouts.app')

@section('title', 'Search')

@section('content')
{{-- SECURITY: Search renders only the published result set prepared by SiteSearch; the viewer never receives workflow records. --}}
<div class="dashboard-page search-page">
    <x-ui.page-header title="Search SSITE" :subtitle="$validQuery ? 'Results for “' . $query . '”' : 'Find articles, activities, achievements, and more.'" />

    @if (! $validQuery)
        <section class="dashboard-panel search-state" role="status">
            <h2>Search needs at least 2 characters</h2>
            <p>Enter a longer keyword to see matching SSITE content.</p>
        </section>
    @else
        <p class="search-total" role="status">{{ $results->total() }} {{ $results->total() === 1 ? 'result' : 'results' }} for “{{ $query }}”</p>

        <nav class="search-filters" aria-label="Filter search results">
            <a href="{{ route('search.index', ['q' => $query]) }}" class="search-filter {{ $activeType === 'all' ? 'is-active' : '' }}" @if ($activeType === 'all') aria-current="page" @endif>
                All <span>{{ array_sum($counts) }}</span>
            </a>
            @foreach ($types as $type)
                <a href="{{ route('search.index', ['q' => $query, 'type' => $type]) }}" class="search-filter {{ $activeType === $type ? 'is-active' : '' }}" @if ($activeType === $type) aria-current="page" @endif>
                    {{ $labels[$type] }} <span>{{ $counts[$type] ?? 0 }}</span>
                </a>
            @endforeach
        </nav>

        @if ($results->isEmpty())
            <section class="dashboard-panel search-state">
                <h2>No results for “{{ $query }}”.</h2>
                <p>Try different keywords.</p>
            </section>
        @else
            <section class="search-results" aria-label="Search results">
                @foreach ($results as $result)
                    <article class="dashboard-panel search-result">
                        <div class="search-result-heading">
                            <span class="search-type-badge search-type-{{ $result['type'] }}">{{ $labels[$result['type']] }}</span>
                            @if ($result['date']) <time datetime="{{ $result['date'] }}">{{ \Illuminate\Support\Carbon::parse($result['date'])->format('M j, Y') }}</time> @endif
                        </div>
                        {{-- SECURITY: Raw fragments here come only from a helper that escapes text before adding mark tags. --}}
                        <h2><a href="{{ $result['url'] }}">{!! $search->highlight($result['title'], $words) !!}</a></h2>
                        @if ($result['meta']) <p class="search-result-meta">{!! $search->highlight($result['meta'], $words) !!}</p> @endif
                        <p class="search-result-excerpt">{!! $search->highlight($result['excerpt'], $words) !!}</p>
                    </article>
                @endforeach
            </section>

            <div class="dashboard-pagination search-pagination">{{ $results->withQueryString()->links() }}</div>
        @endif
    @endif
</div>
@endsection
