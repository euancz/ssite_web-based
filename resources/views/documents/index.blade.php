@extends('layouts.app')

@section('title', 'Documents')

@section('content')
<x-posts.feed-frame
    variant="index"
    stylesheet="documents"
    title="Documents"
    description="Browse and manage SSITE documents."
    :policy-class="\App\Models\Document::class"
    create-route="documents.create"
    composer-prompt="Share a document…"
    route-name="documents.index"
    :allowed-tabs="$allowedTabs"
    :active-tab="$tab"
    :search="$search"
    search-label="Search document titles"
    search-id="document-search"
    :category="$category"
    :categories="$categories"
    :extra-query="['category' => $category ?: null]"
    :pending-count="$pendingCount"
>
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    {{-- President bulk archive remains separate from each card's single-post actions. --}}
    @can('bulkArchive', \App\Models\Document::class)
        @if ($tab !== 'published' && $documents->isNotEmpty())
            <form id="document-bulk-archive" method="POST" action="{{ route('documents.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions" data-selection-bar hidden>
                <span data-selection-count>0 selected</span>
                <button type="button" class="dashboard-button dashboard-button-secondary"
                        onclick="document.getElementById('bulk-archive-document-modal').showModal()">
                    Archive selected
                </button>
            </div>
            <dialog id="bulk-archive-document-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected documents?</h2>
                    <p>Selected documents leave Published and keep their approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary"
                                onclick="document.getElementById('bulk-archive-document-modal').close()">Cancel</button>
                        <button type="submit" form="document-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    @php($tabLabels = ['published' => 'Published', 'mine' => 'My Posts', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All'])
    <section class="post-feed-list" aria-label="{{ $tabLabels[$tab] }} documents">
        @forelse ($documents as $document)
            <x-posts.card
                :post="$document"
                module="Documents"
                route-prefix="documents"
                :show-url="route('documents.show', $document)"
                :content="$document->description ?? ''"
                :tab="$tab"
            >
                <x-slot:attachment>
                    <x-posts.attachment
                        :post="$document"
                        route-prefix="documents"
                        :category="$document->category ?: 'Uncategorized'"
                        guest-can-see-list
                    />
                </x-slot:attachment>

                <x-slot:footerActions>
                    @can('approve', $document)
                        @if ($tab === 'pending' && $document->isPending())
                            <form method="POST" action="{{ route('documents.approve', $document) }}">
                                @csrf
                                <button type="submit" class="dashboard-button">Approve</button>
                            </form>
                            <button type="button" class="dashboard-button dashboard-button-secondary"
                                    data-modal-open="document-reject-{{ $document->getKey() }}">Reject</button>
                            <x-ui.reject-modal
                                id="document-reject-{{ $document->getKey() }}"
                                :action="route('documents.reject', $document)"
                                subject="document"
                            />
                        @endif
                    @endcan

                    @can('bulkArchive', \App\Models\Document::class)
                        @if ($tab !== 'published' && ! $document->isArchived())
                            <label class="dashboard-muted">
                                <input type="checkbox" name="document_ids[]" value="{{ $document->document_id }}" form="document-bulk-archive">
                                Select
                            </label>
                        @endif
                    @endcan
                </x-slot:footerActions>
            </x-posts.card>
        @empty
            @if ($tab === 'published')
                <x-posts.empty-state title="No published documents yet" message="Check back soon for the latest SSITE documents." />
            @elseif (auth()->user()?->canPostIn('documents'))
                <x-posts.empty-state title="No documents in this tab" message="Use the prompt above to upload a document." />
            @else
                <x-posts.empty-state title="No documents found" message="Try another search or check back later." />
            @endif
        @endforelse
    </section>

    <div class="dashboard-pagination">{{ $documents->links() }}</div>
</x-posts.feed-frame>
@endsection
