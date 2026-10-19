@extends('layouts.app')

@section('title', 'Activities')

@section('content')
<x-workspace.posts.feed-frame
    variant="index"
    stylesheet="activities"
    title="Activities"
    description="Read and manage SSITE activities."
    :route-name="request()->routeIs('workspace.posts.activities.index') ? 'workspace.posts.activities.index' : (request()->routeIs('editorial.activities.index') ? 'editorial.activities.index' : 'activities.index')"
    :allowed-tabs="$allowedTabs"
    :active-tab="$tab"
    :pending-count="$pendingCount"
    :sort="$sort"
    :sort-options="$sortOptions"
    filter-name="timeframe"
    :filter="$timeframe"
    :filter-options="$timeframeOptions"
    filter-label="Filter by timeframe"
    filter-default="all"
>
    @if (session('status'))
        <p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert" data-flash-toast>{{ session('error') }}</p>
    @endif

    {{-- President bulk archive remains separate from each card's single-post actions. --}}
    @can('bulkArchive', \App\Models\Activity::class)
        @if ($tab !== 'published' && $activities->isNotEmpty())
            <form id="activity-bulk-archive" method="POST" action="{{ route('activities.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions" data-selection-bar hidden>
                <span data-selection-count>0 selected</span>
                <button type="button" class="dashboard-button dashboard-button-secondary"
                        onclick="document.getElementById('bulk-archive-activity-modal').showModal()">
                    Archive selected
                </button>
            </div>
            <dialog id="bulk-archive-activity-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected activities?</h2>
                    <p>Selected activities leave Published and keep their current approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary"
                                onclick="document.getElementById('bulk-archive-activity-modal').close()">Cancel</button>
                        <button type="submit" form="activity-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    @php($tabLabels = ['published' => 'Published posts', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived posts', 'all' => 'All'])
    <section class="post-feed-list" aria-label="{{ $tabLabels[$tab] }} activities">
        @forelse ($activities as $activity)
            <x-workspace.posts.card
                :post="$activity"
                module="Activities"
                route-prefix="activities"
                :show-url="route('activities.show', $activity)"
                :content="$activity->description"
                :tab="$tab"
                :media-url="$activity->imageUrl()"
                :media-alt="$activity->title"
            >
                <x-slot:strip>
                    <x-posts.strips.activity :activity="$activity" />
                </x-slot:strip>

                <x-slot:footerActions>
                    @can('approve', $activity)
                        @if ($tab === 'pending' && $activity->isPending())
                            <form method="POST" action="{{ route('activities.approve', $activity) }}">
                                @csrf
                                <button type="submit" class="dashboard-button">Approve</button>
                            </form>
                            <button type="button" class="dashboard-button dashboard-button-secondary"
                                    data-modal-open="activity-reject-{{ $activity->getKey() }}">Reject</button>
                            <x-ui.reject-modal
                                id="activity-reject-{{ $activity->getKey() }}"
                                :action="route('activities.reject', $activity)"
                                subject="activity"
                            />
                        @endif
                    @endcan

                    @can('bulkArchive', \App\Models\Activity::class)
                        @if ($tab !== 'published' && ! $activity->isArchived())
                            <label class="dashboard-muted">
                                <input type="checkbox" name="activity_ids[]" value="{{ $activity->activity_id }}" form="activity-bulk-archive">
                                Select
                            </label>
                        @endif
                    @endcan
                </x-slot:footerActions>
            </x-workspace.posts.card>
        @empty
            @if ($sort !== 'recent' || $timeframe !== 'all')
                <div class="post-feed-empty">
                    <p>No activities match these settings. <a href="{{ route(request()->routeIs('workspace.posts.activities.index') ? 'workspace.posts.activities.index' : (request()->routeIs('editorial.activities.index') ? 'editorial.activities.index' : 'activities.index'), ['tab' => $tab]) }}">Reset</a></p>
                </div>
            @elseif ($tab === 'published')
                <x-workspace.posts.empty-state title="No published activities yet" message="Check back soon for the latest SSITE activities." />
            @elseif (auth()->user()?->canPostIn('activities'))
                <x-workspace.posts.empty-state title="No activities in this tab" message="Use Create activity in the workspace navigation to start a submission." />
            @else
                <x-workspace.posts.empty-state title="No activities found" message="Check back later for new activities." />
            @endif
        @endforelse
    </section>

    <div class="dashboard-pagination">{{ $activities->links('vendor.pagination.ssite') }}</div>
</x-workspace.posts.feed-frame>
@endsection
