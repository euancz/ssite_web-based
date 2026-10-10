@extends('layouts.app')

@section('title', 'Liquidation')

@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Liquidation" subtitle="Browse and manage liquidation reports.">
        <x-slot:actions>
            {{-- SECURITY: Upload controls follow the per-record creation policy. --}}
            @can('create', \App\Models\Liquidation::class)<a href="{{ route('liquidations.create') }}" class="dashboard-button">Upload Report</a>@endcan
        </x-slot:actions>
    </x-ui.page-header>
    {{-- SECURITY: The controller whitelists tabs by role and forces Published for unknown values. --}}
    @php($tabLabels = ['published' => 'Published', 'mine' => 'My Uploads', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All'])
    <nav class="dashboard-tabs" aria-label="Liquidation sections">
        @foreach ($allowedTabs as $allowedTab)
            <a href="{{ route('liquidations.index', ['tab' => $allowedTab, 'search' => $search ?: null]) }}" @class(['dashboard-tab', 'is-active' => $tab === $allowedTab]) @if ($tab === $allowedTab) aria-current="page" @endif>{{ $tabLabels[$allowedTab] }}</a>
        @endforeach
    </nav>
    <form method="GET" action="{{ route('liquidations.index') }}" class="dashboard-filter-form"><input type="hidden" name="tab" value="{{ $tab }}"><div class="dashboard-form-field"><label for="liquidation-search">Search reports</label><input id="liquidation-search" type="search" name="search" value="{{ $search }}" maxlength="100"></div><button type="submit" class="dashboard-button">Search</button></form>
    @if (session('status'))<p class="dashboard-success" role="status">{{ session('status') }}</p>@endif
    @if (session('error'))<p class="dashboard-error" role="alert">{{ session('error') }}</p>@endif

    @can('bulkArchive', \App\Models\Liquidation::class)
        @if ($tab !== 'published' && $liquidations->isNotEmpty())
            <form id="liquidation-bulk-archive" method="POST" action="{{ route('liquidations.bulk-archive') }}">@csrf</form>
            <div class="article-bulk-actions"><button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-liquidation-modal').showModal()">Archive selected</button></div>
            <dialog id="bulk-archive-liquidation-modal" class="reject-modal"><div class="reject-modal-content"><h2>Archive selected reports?</h2><p>Selected reports leave Published and keep their approval status.</p><div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-liquidation-modal').close()">Cancel</button><button type="submit" form="liquidation-bulk-archive" class="dashboard-button">Archive selected</button></div></div></dialog>
        @endif
    @endcan

    <section class="articles-list" aria-label="{{ $tabLabels[$tab] }} liquidation reports">
        @forelse ($liquidations as $liquidation)
            <article class="article-list-item"><div class="article-list-image" aria-hidden="true" style="display:grid;place-items:center"><svg class="icon" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h6l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2zM13 3.75v4h4M8 14h8M8 17h8"/></svg><span>PDF</span></div><div class="article-list-content">
                <div class="article-badges"><x-ui.status-badge :status="$liquidation->approval_status" />@if ($liquidation->isArchived())<x-ui.status-badge status="archived" />@endif</div>
                <h2><a href="{{ route('liquidations.show', $liquidation) }}">{{ $liquidation->title }}</a></h2>
                <div class="article-list-meta"><span>{{ $liquidation->formattedAmount() }}</span><span>{{ $liquidation->report_date?->format('M j, Y') ?? 'No report date' }}</span><span>{{ number_format($liquidation->file_size / 1024, 1) }} KB</span><span>{{ $liquidation->author?->name ?? 'Former member' }}</span><span>{{ $liquidation->created_at?->format('M j, Y') }}</span></div>
                @if ($liquidation->description)<p>{{ \Illuminate\Support\Str::limit(strip_tags($liquidation->description), 180) }}</p>@endif
                {{-- Rejection feedback is shown on the officer's own-upload tab. --}}
                @if ($tab === 'mine' && $liquidation->isRejected() && $liquidation->rejection_reason)<p class="dashboard-rejection-reason">Rejection reason: {{ $liquidation->rejection_reason }}</p>@endif
                <div class="article-actions">@can('viewFile', $liquidation)<a class="dashboard-text-button" href="{{ route('liquidations.view', $liquidation) }}">View PDF</a>@endcan @can('download', $liquidation)<a class="dashboard-text-button" href="{{ route('liquidations.download', $liquidation) }}">Download</a>@endcan {{-- Guest file routes redirect to login before delivering a PDF. --}} @guest<a class="dashboard-text-button" href="{{ route('liquidations.view', $liquidation) }}">View PDF</a><a class="dashboard-text-button" href="{{ route('liquidations.download', $liquidation) }}">Download</a>@endguest @can('update', $liquidation)<a class="dashboard-text-button" href="{{ route('liquidations.edit', $liquidation) }}">Edit</a>@endcan
                    @can('bulkArchive', \App\Models\Liquidation::class) @if (! $liquidation->isArchived())<label class="dashboard-muted"><input type="checkbox" name="liquidation_ids[]" value="{{ $liquidation->liquidation_id }}" form="liquidation-bulk-archive"> Select</label>@endif @endcan
                </div>
            </div></article>
        @empty
            <section class="dashboard-panel dashboard-empty-state"><h2>{{ $tabLabels[$tab] }}</h2><p>@switch($tab) @case('published') No published reports are available right now. @break @case('mine') You have not uploaded any reports yet. @break @case('pending') No reports are waiting for review. @break @case('rejected') No rejected reports were found. @break @case('archived') No archived reports were found. @break @default No reports were found. @endswitch</p></section>
        @endforelse
    </section>
    <div class="dashboard-pagination">{{ $liquidations->links() }}</div>
</div>
@endsection
