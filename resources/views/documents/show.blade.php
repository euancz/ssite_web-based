@extends('layouts.app')

@section('title', $document->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="documents">
    @if (session('status'))
        <p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert" data-flash-toast>{{ session('error') }}</p>
    @endif

    <x-posts.card
        :post="$document"
        module="Documents"
        route-prefix="documents"
        :show-url="route('documents.show', $document)"
        :content="$document->description ?? ''"
        tab="show"
        :full="true"
    >
        <x-slot:attachment>
            <x-posts.attachment
                :post="$document"
                route-prefix="documents"
                :category="$document->category ?: 'Uncategorized'"
                guest-can-see-list
                :viewer-url="$viewerUrl"
                viewer-dialog-id="document-pdf-dialog"
            />
        </x-slot:attachment>

        <x-slot:footerActions>
            @can('approve', $document)
                @if ($document->isPending())
                    <form method="POST" action="{{ route('documents.approve', $document) }}">
                        @csrf
                        <button type="submit" class="dashboard-button">Approve</button>
                    </form>
                    <button type="button" class="dashboard-button dashboard-button-secondary"
                            data-modal-open="document-show-reject-{{ $document->getKey() }}">Reject</button>
                    <x-ui.reject-modal
                        id="document-show-reject-{{ $document->getKey() }}"
                        :action="route('documents.reject', $document)"
                        subject="document"
                    />
                @endif
            @endcan
        </x-slot:footerActions>
    </x-posts.card>

    @can('viewFile', $document)
        {{-- PDF.js renders a canvas inside this native modal; its short-lived URL is never a file link. --}}
        <dialog id="document-pdf-dialog" class="document-pdf-dialog" aria-labelledby="document-pdf-heading">
            <section
                id="document-pdf-viewer"
                class="document-pdf-viewer"
                data-pdf-viewer
                data-pdf-url="{{ $viewerUrl }}"
                tabindex="0"
                aria-labelledby="document-pdf-heading"
                aria-describedby="document-pdf-note"
            >
            <div class="document-pdf-viewer__heading">
                <h2 id="document-pdf-heading">Document preview</h2>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-dialog-close aria-label="Close document preview"><x-icon name="x" size="sm" /> Close</button>
            </div>
            <p id="document-pdf-note" class="document-pdf-viewer__note">View only. Text selection is unavailable. Use the arrow keys to change pages, plus/minus to zoom, 0 to fit width, and Escape to exit full screen.</p>
            <div class="document-pdf-toolbar" role="group" aria-label="PDF controls">
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-previous disabled>Previous</button>
                <span class="document-pdf-page-status" data-pdf-page-status aria-live="polite">Loading page…</span>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-next disabled>Next</button>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-zoom-out disabled aria-label="Zoom out"><x-icon name="minus" size="sm" /></button>
                <span data-pdf-zoom-status aria-live="off">100%</span>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-zoom-in disabled aria-label="Zoom in"><x-icon name="plus" size="sm" /></button>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-fit disabled>Fit width</button>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-fullscreen aria-pressed="false">Full screen</button>
            </div>
            <div class="document-pdf-stage" data-pdf-stage>
                {{-- Canvas interactions have casual-copy deterrents only; this is not a security boundary. --}}
                <canvas class="document-pdf-canvas" data-pdf-canvas draggable="false" role="img" aria-label="PDF page 1"></canvas>
            </div>
            <p class="document-pdf-status" data-pdf-status role="status" aria-live="polite">Loading document…</p>
            <button type="button" class="dashboard-button dashboard-button-secondary document-pdf-retry" data-pdf-retry hidden>Reload page</button>
            </section>
        </dialog>
        <noscript><p class="document-pdf-noscript">Enable JavaScript to open the document viewer.</p></noscript>
    @endcan
</x-posts.feed-frame>
@endsection
