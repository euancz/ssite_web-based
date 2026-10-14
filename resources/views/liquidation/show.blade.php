@extends('layouts.app')

@section('title', $liquidation->title)

@section('content')
<x-posts.feed-frame variant="show" stylesheet="liquidation">
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    <article class="liquidation-document-detail">
        <header class="liquidation-detail-heading">
            <div class="liquidation-report-icon" aria-hidden="true"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h6l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2zM13 3.75v4h4M8 14h8M8 17h8"/></svg><span>PDF</span></div>
            <div><h1>{{ $liquidation->title }}</h1><p>Submitted by {{ $liquidation->author?->name ?? 'Former member' }} · {{ $liquidation->created_at?->format('F j, Y') ?? 'Date unavailable' }}</p></div>
            <x-posts.row-menu :post="$liquidation" route-prefix="liquidations" />
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
                @can('viewFile', $liquidation)<a href="{{ route('liquidations.view', $liquidation) }}">Preview PDF</a>@endcan
                @can('download', $liquidation)<a href="{{ route('liquidations.download', $liquidation) }}" class="liquidation-download-link">Download PDF</a>@endcan
                @guest<a href="{{ route('liquidations.index') }}">Back to reports</a>@endguest
            </div>
        </section>
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
    </article>
</x-posts.feed-frame>
@endsection
