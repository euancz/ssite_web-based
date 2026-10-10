@extends('layouts.app')

@section('title', 'Documents')

@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Documents" subtitle="Browse and manage SSITE documents.">
        <x-slot:actions>
            {{-- SECURITY: Upload links are shown only when the document policy permits creation. --}}
            @can('create', \App\Models\Document::class)
                <a href="{{ route('documents.create') }}" class="dashboard-button">Upload Document</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- SECURITY: Only tabs allowed for this role are rendered; unknown query values fall back in the controller. --}}
    @php($tabLabels = ['published' => 'Published', 'mine' => 'My Uploads', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All'])
    <nav class="dashboard-tabs" aria-label="Document sections">
        @foreach ($allowedTabs as $allowedTab)
            <a href="{{ route('documents.index', ['tab' => $allowedTab, 'search' => $search ?: null, 'category' => $category ?: null]) }}"
               @class(['dashboard-tab', 'is-active' => $tab === $allowedTab]) @if ($tab === $allowedTab) aria-current="page" @endif>{{ $tabLabels[$allowedTab] }}</a>
        @endforeach
    </nav>

    <form method="GET" action="{{ route('documents.index') }}" class="dashboard-filter-form">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="dashboard-form-field"><label for="document-search">Search title</label><input id="document-search" type="search" name="search" value="{{ $search }}" maxlength="100"></div>
        <div class="dashboard-form-field"><label for="document-category">Category</label><select id="document-category" name="category"><option value="">All categories</option>@foreach ($categories as $option)<option value="{{ $option }}" @selected($category === $option)>{{ $option }}</option>@endforeach</select></div>
        <button type="submit" class="dashboard-button">Filter</button>
    </form>

    @if (session('status')) <p class="dashboard-success" role="status">{{ session('status') }}</p> @endif
    @if (session('error')) <p class="dashboard-error" role="alert">{{ session('error') }}</p> @endif

    @can('bulkArchive', \App\Models\Document::class)
        @if ($tab !== 'published' && $documents->isNotEmpty())
            <form id="document-bulk-archive" method="POST" action="{{ route('documents.bulk-archive') }}">@csrf</form>
            <div class="article-bulk-actions"><button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-document-modal').showModal()">Archive selected</button></div>
            <dialog id="bulk-archive-document-modal" class="reject-modal"><div class="reject-modal-content"><h2>Archive selected documents?</h2><p>Selected documents leave Published and keep their approval status.</p><div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-document-modal').close()">Cancel</button><button type="submit" form="document-bulk-archive" class="dashboard-button">Archive selected</button></div></div></dialog>
        @endif
    @endcan

    <section class="articles-list" aria-label="{{ $tabLabels[$tab] }} documents">
        @forelse ($documents as $document)
            <article class="article-list-item">
                <div class="article-list-image" aria-hidden="true" style="display:grid;place-items:center"><svg class="icon" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h6l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2zM13 3.75v4h4M8 14h8M8 17h8"/></svg><span>PDF</span></div>
                <div class="article-list-content">
                    <div class="article-badges"><x-ui.status-badge :status="$document->approval_status" />@if ($document->isArchived()) <x-ui.status-badge status="archived" /> @endif</div>
                    <h2><a href="{{ route('documents.show', $document) }}">{{ $document->title }}</a></h2>
                    <div class="article-list-meta"><span>{{ $document->category ?: 'Uncategorized' }}</span><span>{{ number_format($document->file_size / 1024, 1) }} KB</span><span>{{ $document->author?->name ?? 'Former member' }}</span><span>{{ $document->created_at?->format('M j, Y') }}</span></div>
                    @if ($document->description)<p>{{ \Illuminate\Support\Str::limit(strip_tags($document->description), 180) }}</p>@endif
                    {{-- Officers receive rejection feedback only for their own uploads. --}}
                    @if ($tab === 'mine' && $document->isRejected() && $document->rejection_reason)<p class="dashboard-rejection-reason">Rejection reason: {{ $document->rejection_reason }}</p>@endif
                    <div class="article-actions">
                        @can('viewFile', $document)<a class="dashboard-text-button" href="{{ route('documents.view', $document) }}">View PDF</a>@endcan
                        @can('download', $document)<a class="dashboard-text-button" href="{{ route('documents.download', $document) }}">Download</a>@endcan
                        {{-- Guests may follow the authorized file routes, which send them through login first. --}}
                        @guest<a class="dashboard-text-button" href="{{ route('documents.view', $document) }}">View PDF</a><a class="dashboard-text-button" href="{{ route('documents.download', $document) }}">Download</a>@endguest
                        @can('update', $document)<a class="dashboard-text-button" href="{{ route('documents.edit', $document) }}">Edit</a>@endcan
                        @can('bulkArchive', \App\Models\Document::class) @if (! $document->isArchived())<label class="dashboard-muted"><input type="checkbox" name="document_ids[]" value="{{ $document->document_id }}" form="document-bulk-archive"> Select</label>@endif @endcan
                    </div>
                </div>
            </article>
        @empty
            <section class="dashboard-panel dashboard-empty-state"><h2>{{ $tabLabels[$tab] }}</h2><p>@switch($tab) @case('published') No published documents are available right now. @break @case('mine') You have not uploaded any documents yet. @break @case('pending') No documents are waiting for review. @break @case('rejected') No rejected documents were found. @break @case('archived') No archived documents were found. @break @default No documents were found. @endswitch</p></section>
        @endforelse
    </section>
    <div class="dashboard-pagination">{{ $documents->links() }}</div>
</div>
@endsection
