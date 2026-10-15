@extends('layouts.app')

@section('title', 'Liquidation')

@section('content')
<x-posts.feed-frame
    variant="index"
    stylesheet="liquidation"
    title="Liquidation"
    description="Browse and manage liquidation reports."
    :policy-class="\App\Models\Liquidation::class"
    create-route="liquidations.create"
    composer-prompt="Share a liquidation report…"
    route-name="liquidations.index"
    :allowed-tabs="$allowedTabs"
    :active-tab="$tab"
    :pending-count="$pendingCount"
    :sort="$sort"
    :sort-options="$sortOptions"
>
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    {{-- President bulk archive remains separate from each card's single-post actions. --}}
    @can('bulkArchive', \App\Models\Liquidation::class)
        @if ($tab !== 'published' && $liquidations->isNotEmpty())
            <form id="liquidation-bulk-archive" method="POST" action="{{ route('liquidations.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions" data-selection-bar hidden>
                <span data-selection-count>0 selected</span>
                <button type="button" class="dashboard-button dashboard-button-secondary"
                        onclick="document.getElementById('bulk-archive-liquidation-modal').showModal()">
                    Archive selected
                </button>
            </div>
            <dialog id="bulk-archive-liquidation-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected reports?</h2>
                    <p>Selected reports leave Published and keep their approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary"
                                onclick="document.getElementById('bulk-archive-liquidation-modal').close()">Cancel</button>
                        <button type="submit" form="liquidation-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    @php($tabLabels = ['published' => 'Published', 'mine' => 'My Posts', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All'])
    <section class="post-feed-list" aria-label="{{ $tabLabels[$tab] }} liquidation reports">
        @forelse ($liquidations as $liquidation)
            <article class="liquidation-report-row">
                <div class="liquidation-report-main">
                    <div class="liquidation-report-icon" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6"><path stroke-linecap="round" stroke-linejoin="round" d="M7 3.75h6l4 4v12.5H7a2 2 0 0 1-2-2v-12.5a2 2 0 0 1 2-2zM13 3.75v4h4M8 14h8M8 17h8"/></svg>
                        <span>PDF</span>
                    </div>
                    <div class="liquidation-report-copy">
                        <h2><a href="{{ route('liquidations.show', $liquidation) }}">{{ $liquidation->title }}</a></h2>
                        <p class="liquidation-report-file">{{ $liquidation->original_name }} <span>·</span> {{ number_format($liquidation->file_size / 1024, 1) }} KB</p>
                        @if ($liquidation->description)<p class="liquidation-report-description">{{ \Illuminate\Support\Str::limit(strip_tags($liquidation->description), 180) }}</p>@endif
                        <p class="liquidation-report-byline">{{ $liquidation->author?->name ?? 'Former member' }} <span>·</span> {{ $liquidation->created_at?->diffForHumans() ?? 'Date unavailable' }}</p>
                    </div>
                    <div class="liquidation-report-facts">
                        <span>Report date</span>
                        <strong>{{ $liquidation->report_date?->format('M j, Y') ?? 'Not provided' }}</strong>
                        <span>Amount</span>
                        <strong class="liquidation-report-amount">{{ $liquidation->formattedAmount() }}</strong>
                    </div>
                    @if ($tab === 'published')
                        {{-- SECURITY: Engagement controls render only for Published rows with schema-backed attributes. --}}
                        <div class="post-card-engagement" aria-label="Post activity">
                            @if (config('school.enable_view_counts', false) && isset($liquidation->view_count) && (int) $liquidation->view_count > 0)
                                <span>{{ number_format((int) $liquidation->view_count) }} {{ \Illuminate\Support\Str::plural('view', (int) $liquidation->view_count) }}</span>
                            @endif
                            @if (config('school.enable_likes', false) && isset($liquidation->likes_count))
                                @php($liked = (int) ($liquidation->liked_by_viewer ?? 0) > 0)
                                @if ((int) $liquidation->likes_count > 0)
                                    <span>{{ number_format((int) $liquidation->likes_count) }} {{ \Illuminate\Support\Str::plural('like', (int) $liquidation->likes_count) }}</span>
                                @endif
                                <form method="POST" action="{{ route('posts.like', ['type' => $liquidation::POST_TYPE, 'post' => $liquidation->getKey()]) }}">
                                    @csrf
                                    <input type="hidden" name="liked" value="{{ $liked ? '0' : '1' }}">
                                    <button type="submit" class="post-like-button" aria-pressed="{{ $liked ? 'true' : 'false' }}">
                                        {{ $liked ? 'Unlike' : 'Like' }}
                                    </button>
                                </form>
                            @endif
                        </div>
                    @endif
                    <x-posts.row-menu :post="$liquidation" route-prefix="liquidations" />
                </div>
                <div class="liquidation-report-actions">
                    @can('viewFile', $liquidation)<a href="{{ route('liquidations.view', $liquidation) }}">Preview PDF</a>@endcan
                    @can('download', $liquidation)<a href="{{ route('liquidations.download', $liquidation) }}" class="liquidation-download-link">Download PDF</a>@endcan
                    @guest<a href="{{ route('liquidations.show', $liquidation) }}">View report</a>@endguest
                </div>
                <div class="liquidation-report-review">
                    @if ($tab !== 'published' || $liquidation->isArchived())<x-ui.status-badge :status="$liquidation->approval_status" />@endif
                    @if ($liquidation->isArchived())<x-ui.status-badge status="archived" />@endif
                </div>
                <div class="liquidation-report-admin">
                    @can('approve', $liquidation)
                        @if ($tab === 'pending' && $liquidation->isPending())
                            <form method="POST" action="{{ route('liquidations.approve', $liquidation) }}">
                                @csrf
                                <button type="submit" class="dashboard-button">Approve</button>
                            </form>
                            <button type="button" class="dashboard-button dashboard-button-secondary"
                                    data-modal-open="liquidation-reject-{{ $liquidation->getKey() }}">Reject</button>
                            <x-ui.reject-modal
                                id="liquidation-reject-{{ $liquidation->getKey() }}"
                                :action="route('liquidations.reject', $liquidation)"
                                subject="liquidation report"
                            />
                        @endif
                    @endcan

                    @can('bulkArchive', \App\Models\Liquidation::class)
                        @if ($tab !== 'published' && ! $liquidation->isArchived())
                            <label class="dashboard-muted">
                                <input type="checkbox" name="liquidation_ids[]" value="{{ $liquidation->liquidation_id }}" form="liquidation-bulk-archive">
                                Select
                            </label>
                        @endif
                    @endcan
                </div>
            </article>
        @empty
            @if ($sort !== 'recent')
                <div class="post-feed-empty">
                    <p>No reports match this sort. <a href="{{ route('liquidations.index', ['tab' => $tab]) }}">Reset</a></p>
                </div>
            @elseif ($tab === 'published')
                <x-posts.empty-state title="No published reports yet" message="Check back soon for the latest liquidation reports." />
            @elseif (auth()->user()?->canPostIn('liquidations'))
                <x-posts.empty-state title="No reports in this tab" message="Use the prompt above to upload a report." />
            @else
                <x-posts.empty-state title="No reports found" message="Check back later for new reports." />
            @endif
        @endforelse
    </section>

    <div class="dashboard-pagination">{{ $liquidations->links() }}</div>
</x-posts.feed-frame>
@endsection
