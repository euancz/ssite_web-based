@extends('layouts.app')

@section('title', 'Documents')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/record-selection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/documents-gallery.css') }}">
@endpush

@section('content')
<div class="documents-page site-page">
    <x-ui.page-hero
        class="documents-hero"
        banner="images/heroes/hero-documents.svg"
        eyebrow="Documents · records · archives"
        title="Official records"
        description="Browse SSITE's official documents and organizational records in one accessible location."
        labelledby="documents-title"
    />

    <form @class(['documents-filters', 'documents-filters-with-year' => $academicYearsEnabled]) method="GET" action="{{ route('documents.index') }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="documents-search">
            <label class="visually-hidden" for="document-search">Search title</label>
            <input id="document-search" type="search" name="q" value="{{ $searchQuery }}" placeholder="Search document titles" maxlength="100">
            <button type="submit" aria-label="Search documents"><x-icon name="search" size="sm" /></button>
        </div>
        <label class="documents-select"><span>Sort by</span>
            <select name="sort" onchange="this.form.requestSubmit()">
                @foreach ($sortOptions as $value => $label)
                    <option value="{{ $value }}" @selected($sort === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>
        @if ($academicYearsEnabled)
            <label class="documents-select"><span>Academic year</span>
                <select name="academic_year" onchange="this.form.requestSubmit()">
                    @foreach ($academicYearOptions as $value => $label)
                        <option value="{{ $value }}" @selected($academicYear === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </label>
        @endif
        <details class="documents-more-filters" @if ($category !== '' || $semester !== '') open @endif>
            <summary>
                <span>More filters</span>
                @if ($category !== '' || $semester !== '')
                    <span class="documents-filter-count" aria-label="{{ (int) ($category !== '') + (int) ($semester !== '') }} additional filters active">{{ (int) ($category !== '') + (int) ($semester !== '') }}</span>
                @endif
                <span class="documents-filter-hint">Category &amp; semester</span>
                <x-icon name="chevron-down" size="sm" />
            </summary>
            <div class="documents-secondary-filters">
                <label class="documents-select">
                    <span>Category</span>
                    <select name="category" onchange="this.form.requestSubmit()">
                        @foreach ($categoryOptions as $value => $label)
                            <option value="{{ $value }}" @selected($category === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="documents-select">
                    <span>Semester</span>
                    <select name="semester" onchange="this.form.requestSubmit()">
                        <option value="">All semesters</option>
                        <option value="1" @selected($semester === '1')>1st Semester</option>
                        <option value="2" @selected($semester === '2')>2nd Semester</option>
                    </select>
                </label>
            </div>
        </details>
        <noscript><button class="site-button" type="submit">Apply filters</button></noscript>
        @if ($academicYear !== '' || $searchQuery !== '' || $category !== '' || $semester !== '' || $sort !== 'recent')
            <a class="site-filter-reset" href="{{ route('documents.index') }}">Clear filters</a>
        @endif
    </form>

    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    <section class="documents-grid" aria-label="Documents">
        @forelse ($documents as $document)
            <article class="document-tile">
                <a class="document-tile-link" href="{{ route('documents.show', $document) }}" data-document-selection="document-selection-{{ $document->getKey() }}" aria-haspopup="dialog" aria-controls="document-selection-{{ $document->getKey() }}">
                    <div class="document-preview"
                        @if (auth()->user()?->can('viewFile', $document))
                            data-document-preview="{{ \Illuminate\Support\Facades\URL::temporarySignedRoute('documents.view', now()->addMinutes(15), ['document' => $document]) }}"
                        @endif>
                        <div class="document-preview-fallback">
                            <img src="{{ asset('images/SSITE LOGO.png') }}" alt="" width="60" height="60">
                            <span>SSITE Official Document</span>
                            <small>{{ auth()->check() ? 'Open document for details' : 'Sign in to preview the PDF' }}</small>
                        </div>
                        <canvas hidden aria-hidden="true"></canvas>
                    </div>
                    <div class="document-tile-copy">
                        <span class="document-category">{{ $document->category ?: 'Uncategorized' }}</span>
                        <h2>{{ $document->title }}</h2>
                        <div class="document-tile-meta">
                            @if ($academicYearsEnabled)<p>Academic year: {{ $document->academic_year ?: 'Not assigned' }}</p>@endif
                            <p>Student Society in Information Technology Education</p>
                            <time datetime="{{ $document->created_at?->toDateString() }}">{{ $document->created_at?->format('F j, Y') }}</time>
                        </div>
                    </div>
                </a>
            </article>
            @include('documents._selection', ['document' => $document])
        @empty
            <div class="documents-empty">
                <h2>{{ $academicYear !== '' || $searchQuery !== '' || $category !== '' || $semester !== '' ? 'No matching documents' : 'No published documents yet' }}</h2>
                <p>{{ $academicYear !== '' || $searchQuery !== '' || $category !== '' || $semester !== '' ? 'Try another title, semester, category, or academic year.' : 'Check back soon for official SSITE records.' }}</p>
                @if ($academicYear !== '' || $searchQuery !== '' || $category !== '' || $semester !== '')
                    <a class="site-filter-reset" href="{{ route('documents.index') }}">Clear filters</a>
                @endif
            </div>
        @endforelse
    </section>
    <div class="dashboard-pagination">{{ $documents->links('vendor.pagination.ssite') }}</div>
</div>
@endsection
