@extends('layouts.app')

@section('title', 'Documents')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/record-selection.css') }}">
    <link rel="stylesheet" href="{{ asset('css/documents-gallery.css') }}">
@endpush

@section('content')
<div class="documents-page site-page">
    <section class="documents-hero site-hero" aria-labelledby="documents-title">
        <div class="documents-hero-grid" aria-hidden="true"></div>
        <div class="documents-hero-copy">
            <p class="documents-eyebrow">DOCUMENTS &bull; RECORDS &bull; ARCHIVES</p>
            <h1 id="documents-title">OFFICIAL RECORDS</h1>
            <p>Browse SSITE's official documents and organizational records in one accessible location.</p>
        </div>
    </section>

    <form class="documents-filters" method="GET" action="{{ route('documents.index') }}">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="documents-search">
            <label class="visually-hidden" for="document-search">Search title</label>
            <input id="document-search" type="search" name="q" value="{{ $searchQuery }}" placeholder="Search Title" maxlength="100">
            <button type="submit" aria-label="Search documents"><x-icon name="search" size="sm" /></button>
        </div>
        <label class="documents-select">
            <span>Sort By Semester</span>
            <select name="semester" onchange="this.form.requestSubmit()">
                <option value="">All Semesters</option>
                <option value="1" @selected($semester === '1')>1st Semester</option>
                <option value="2" @selected($semester === '2')>2nd Semester</option>
            </select>
            <x-icon name="chevron-down" aria-hidden="true" />
        </label>
        <label class="documents-select">
            <span>Categories</span>
            <select name="category" onchange="this.form.requestSubmit()">
                @foreach ($categoryOptions as $value => $label)
                    <option value="{{ $value }}" @selected($category === $value)>{{ $value === '' ? 'All Categories' : $label }}</option>
                @endforeach
            </select>
            <x-icon name="chevron-down" aria-hidden="true" />
        </label>
        <noscript><button class="site-button" type="submit">Apply filters</button></noscript>
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
                            <p>Student Society in Information Technology Education</p>
                            <time datetime="{{ $document->created_at?->toDateString() }}">{{ $document->created_at?->format('F j, Y') }}</time>
                        </div>
                    </div>
                </a>
            </article>
            @include('documents._selection', ['document' => $document])
        @empty
            <div class="documents-empty">
                <h2>{{ $searchQuery !== '' || $category !== '' || $semester !== '' ? 'No matching documents' : 'No published documents yet' }}</h2>
                <p>{{ $searchQuery !== '' || $category !== '' || $semester !== '' ? 'Try another title, semester, or category.' : 'Check back soon for official SSITE records.' }}</p>
                @if ($searchQuery !== '' || $category !== '' || $semester !== '')
                    <a href="{{ route('documents.index') }}">Clear filters</a>
                @endif
            </div>
        @endforelse
    </section>
    @if ($documents->hasPages())
        <div class="dashboard-pagination">{{ $documents->links('vendor.pagination.ssite') }}</div>
    @endif
</div>
@endsection
