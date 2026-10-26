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

    <section class="transparency-hero site-hero" aria-labelledby="transparency-title">
        <div class="transparency-hero-grid" aria-hidden="true"></div>
        <div class="transparency-hero-content">
            <p class="transparency-kicker">Financial transparency <span aria-hidden="true">•</span> accountability <span aria-hidden="true">•</span> trust</p>
            <h1 id="transparency-title">Funds with purpose</h1>
            <p>Explore SSITE's financial records, expense reports, and liquidation documents to see how organization funds are managed and utilized.</p>
        </div>
    </section>

    <form class="transparency-filters" method="GET" action="{{ route('transparency') }}">
        <label class="transparency-search">
            <span class="visually-hidden">Search events</span>
            <input type="search" name="q" value="{{ is_string(request()->query('q')) ? mb_substr(request()->query('q'), 0, 100) : '' }}" placeholder="Search events..." autocomplete="off">
            <x-icon name="search" size="sm" aria-hidden="true" />
        </label>
        <label class="transparency-select">
            <span>Sort by semester</span>
            <select name="sort" aria-label="Sort by semester" onchange="this.form.submit()">
                <option value="recent" @selected($sort === 'recent')>1st Semester</option>
                <option value="oldest" @selected($sort === 'oldest')>2nd Semester</option>
            </select>
            <x-icon name="chevron-down" size="sm" aria-hidden="true" />
        </label>
        <label class="transparency-select">
            <span>Sort by A.Y</span>
            <select name="academic_year" aria-label="Sort by academic year" onchange="this.form.submit()">
                <option value="2025-2026" @selected(request()->query('academic_year', '2025-2026') === '2025-2026')>A.Y. 2025–2026</option>
            </select>
            <x-icon name="chevron-down" size="sm" aria-hidden="true" />
        </label>
        <input type="hidden" name="tab" value="{{ $tab }}">
    </form>

    <section class="transparency-register" aria-labelledby="transactions-title">
        <div class="transparency-register-heading">
            <h2 id="transactions-title">Transactions</h2>
            @can('create', \App\Models\Liquidation::class)
                <a href="{{ route('liquidations.create') }}">Submit report</a>
            @endcan
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
                            <td colspan="3" class="transparency-empty">No published transactions yet. Check back soon for the latest records.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($liquidations->hasPages())
            <div class="transparency-pagination">{{ $liquidations->links('vendor.pagination.ssite') }}</div>
        @endif
    </section>
    @foreach ($liquidations as $liquidation)
        <x-posts.selection :post="$liquidation" :id="'liquidation-selection-' . $liquidation->getKey()" route-prefix="liquidations" category="Events" :date="$liquidation->report_date" :expense="true" />
    @endforeach
</div>
@endsection
