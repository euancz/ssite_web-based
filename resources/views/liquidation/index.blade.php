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
    :search="$search"
    search-label="Search reports"
    search-id="liquidation-search"
    :pending-count="$pendingCount"
>
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    {{-- Adviser bulk archive remains separate from each card's single-post actions. --}}
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
            <x-posts.card
                :post="$liquidation"
                module="Liquidation"
                route-prefix="liquidations"
                :show-url="route('liquidations.show', $liquidation)"
                :content="$liquidation->description ?? ''"
                :tab="$tab"
            >
                <x-slot:attachment>
                    <x-posts.strips.liquidation :liquidation="$liquidation">
                        <x-posts.attachment
                            :post="$liquidation"
                            route-prefix="liquidations"
                            guest-can-see-list
                        />
                    </x-posts.strips.liquidation>
                </x-slot:attachment>

                <x-slot:footerActions>
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
                </x-slot:footerActions>
            </x-posts.card>
        @empty
            @if ($tab === 'published')
                <x-posts.empty-state title="No published reports yet" message="Check back soon for the latest liquidation reports." />
            @elseif (auth()->user()?->canPost())
                <x-posts.empty-state title="No reports in this tab" message="Use the prompt above to upload a report." />
            @else
                <x-posts.empty-state title="No reports found" message="Try another search or check back later." />
            @endif
        @endforelse
    </section>

    <div class="dashboard-pagination">{{ $liquidations->links() }}</div>
</x-posts.feed-frame>
@endsection
