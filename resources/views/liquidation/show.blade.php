@extends('layouts.app')

@section('title', $liquidation->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="liquidation">
    @if ($management)<x-workspace.review-summary :post="$liquidation" />@endif
    @if (session('status'))
        <p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert" data-flash-toast>{{ session('error') }}</p>
    @endif

    <article class="liquidation-document-detail">
        <header class="liquidation-detail-heading">
            <div class="liquidation-report-icon" aria-hidden="true"><x-icon name="file-text" /><span>PDF</span></div>
            <div><h1>{{ $liquidation->title }}</h1><p>Submitted by {{ $liquidation->author?->name ?? 'Former member' }} · {{ $liquidation->created_at?->format('F j, Y') ?? 'Date unavailable' }}</p></div>
            @if ($management)<x-workspace.posts.row-menu :post="$liquidation" route-prefix="liquidations" />@endif
        </header>
        <dl class="liquidation-detail-facts">
            <div><dt>Report date</dt><dd>{{ $liquidation->report_date?->format('F j, Y') ?? 'Not provided' }}</dd></div>
            <div><dt>Reported amount</dt><dd>{{ $liquidation->formattedAmount() }}</dd></div>
            <div><dt>Review status</dt><dd><x-ui.status-badge :status="$liquidation->approval_status" /> @if($liquidation->isArchived())<x-ui.status-badge status="archived" />@endif</dd></div>
        </dl>
        @if ($liquidation->description)
            <section class="liquidation-detail-description"><h2>Report notes</h2><p>{{ $liquidation->description }}</p></section>
        @endif
        <section class="liquidation-detail-file" aria-label="PDF document">
            <div><strong>{{ $liquidation->original_name }}</strong><span>{{ number_format($liquidation->file_size / 1024, 1) }} KB · PDF document</span></div>
            <div class="liquidation-report-actions">
                {{-- The controller provides a short-lived signed URL only after file-view authorization. --}}
                @can('viewFile', $liquidation)<button type="button" data-pdf-modal-open="liquidation-pdf-dialog">View</button>@endcan
                @guest<a href="{{ route('liquidations.index') }}">Back to reports</a>@endguest
            </div>
        </section>
        @if ($management)
        <div class="liquidation-detail-admin">
            @can('approve', $liquidation)
                @if ($liquidation->isPending())
                    <form method="POST" action="{{ route('liquidations.approve', $liquidation) }}">
                        @csrf
                        <button type="submit" class="dashboard-button">Approve</button>
                    </form>
                    <button type="button" class="dashboard-button dashboard-button-secondary"
                            data-modal-open="liquidation-show-reject-{{ $liquidation->getKey() }}">Reject</button>
                    <x-ui.reject-modal
                        id="liquidation-show-reject-{{ $liquidation->getKey() }}"
                        :action="route('liquidations.reject', $liquidation)"
                        subject="liquidation report"
                    />
                @endif
            @endcan
        </div>
        @endif
    </article>

    @can('viewFile', $liquidation)
        {{-- The report preview uses the shared canvas viewer in a native dialog, never native PDF navigation. --}}
        <dialog id="liquidation-pdf-dialog" class="document-pdf-dialog" aria-labelledby="liquidation-pdf-heading">
            <section
                id="liquidation-pdf-viewer"
                class="document-pdf-viewer"
                data-pdf-viewer
                data-pdf-url="{{ $viewerUrl }}"
                tabindex="0"
                aria-labelledby="liquidation-pdf-heading"
                aria-describedby="liquidation-pdf-note"
            >
            <div class="document-pdf-viewer__heading">
                <h2 id="liquidation-pdf-heading">Report preview</h2>
                <button type="button" class="dashboard-button dashboard-button-secondary" data-pdf-dialog-close aria-label="Close report preview"><x-icon name="x" size="sm" /> Close</button>
            </div>
            <p id="liquidation-pdf-note" class="document-pdf-viewer__note">View only. Text selection is unavailable. Use the arrow keys to change pages, plus/minus to zoom, 0 to fit width, and Escape to exit full screen.</p>
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
        <noscript><p class="document-pdf-noscript">Enable JavaScript to open the report viewer.</p></noscript>
    @endcan
</x-posts.feed-frame>
@endsection
