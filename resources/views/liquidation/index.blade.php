@extends('layouts.app')

@section('title', 'Transparency')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/record-selection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/liquidation.css') }}">
@endpush

@section('content')
<div class="transparency-page site-page">
    @if (session('status'))
        <p class="dashboard-success transparency-toast" role="status" data-flash-toast>{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error transparency-toast" role="alert" data-flash-toast>{{ session('error') }}</p>
    @endif

    <x-ui.stories-banner
        eyebrow="Financial transparency • Accountability • Trust"
        title="Funds with purpose"
        description="Explore SSITE’s financial records, expense reports, and liquidation documents to see how organization funds are managed and utilized."
        labelledby="transparency-title"
    />

    <form class="transparency-filters" method="GET" action="{{ route('transparency') }}">
        <div class="transparency-search main-search-field">
            <label class="visually-hidden" for="report-search">Search reports</label>
            <input id="report-search" type="search" name="q" value="{{ $searchQuery }}" maxlength="100" placeholder="Search reports..." autocomplete="off">
            <button type="submit" class="search-submit-icon" aria-label="Search reports"><x-icon name="search" size="sm" class="site-search-icon" /></button>
        </div>
        <x-posts.filter-panel :active-count="(int) ($event !== '') + (int) ($year !== '') + (int) ($semester !== '') + (int) ($sort !== 'recent')">
        <label class="transparency-select">
            <span>Event name</span>
            <select name="event" aria-label="Event name" onchange="this.form.requestSubmit()">
                @foreach ($eventOptions as $value => $label)
                    <option value="{{ $value }}" @selected((string) $event === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" size="sm" aria-hidden="true" />
        </label>

        <label class="transparency-select">
            <span>Sort by</span>
            <select name="sort" aria-label="Sort reports" onchange="this.form.requestSubmit()">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" size="sm" aria-hidden="true" />
        </label>
        <label class="transparency-select">
            <span>Report year</span>
            <select name="year" aria-label="Filter by report year" onchange="this.form.requestSubmit()">
                @foreach ($yearOptions as $value => $label)
                    <option value="{{ $value }}" @selected((string) $year === (string) $value)>{{ $label }}</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" size="sm" aria-hidden="true" />
        </label>
        @foreach ($extraFilters as $name => $extra)
            @continue($name === 'event')
            <label class="transparency-select">
                <span>{{ $extra['label'] }}</span>
                <select name="{{ $name }}" aria-label="{{ $extra['label'] }}" onchange="this.form.requestSubmit()">
                    @foreach ($extra['options'] as $value => $label)
                        <option value="{{ $value }}" @selected((string) $extra['value'] === (string) $value)>{{ $label }}</option>
                    @endforeach
                </select>
                <x-icon name="chevron-down" size="sm" aria-hidden="true" />
            </label>
        @endforeach
        </x-posts.filter-panel>
        <noscript><button class="site-button" type="submit">Apply filters</button></noscript>
        <input type="hidden" name="tab" value="{{ $tab }}">
        @if ($searchQuery !== '' || $year !== '' || $event !== '' || $semester !== '' || $sort !== 'recent')
            <a class="site-filter-reset" href="{{ route('transparency') }}">Clear filters</a>
        @endif
    </form>

    <section class="transparency-register" aria-labelledby="transactions-title">
        <div class="transparency-register-heading">
            <h2 id="transactions-title">Transactions</h2>
        </div>

        <div class="transparency-table-wrap" role="region" aria-label="Published transactions" tabindex="0">
            <table class="transparency-table">
                <thead>
                    <tr>
                        <th scope="col">Event title</th>
                        <th scope="col">Date</th>
                        <th scope="col">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($liquidations as $liquidation)
                        <tr>
                            <td>
                                <a href="{{ route('liquidations.show', $liquidation) }}" data-document-selection="liquidation-selection-{{ $liquidation->getKey() }}" aria-haspopup="dialog" aria-controls="liquidation-selection-{{ $liquidation->getKey() }}">
                                    <x-icon name="file-text" size="sm" aria-hidden="true" />
                                    <span>{{ $liquidation->title }}</span>
                                </a>
                            </td>
                            <td>{{ $liquidation->report_date?->format('F j, Y') ?? 'Date unavailable' }}</td>
                            <td>{{ $liquidation->formattedAmount() }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="transparency-empty">{{ $searchQuery !== '' || $year !== '' || $event !== '' || $semester !== '' ? 'No matching transactions. Try another search or report year.' : 'No published transactions yet. Check back soon for the latest records.' }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="transparency-pagination">{{ $liquidations->links('vendor.pagination.ssite') }}</div>
    </section>
    @foreach ($liquidations as $liquidation)
        <x-posts.selection :post="$liquidation" :id="'liquidation-selection-' . $liquidation->getKey()" route-prefix="liquidations" category="Events" :date="$liquidation->report_date" :expense="true" />
    @endforeach
</div>
@endsection
