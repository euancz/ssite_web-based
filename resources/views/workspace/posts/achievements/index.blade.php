@extends('layouts.app')

@section('title', 'Achievements')

@section('content')
<x-workspace.posts.feed-frame
    variant="index"
    stylesheet="achievements"
    title="Achievements"
    description="Read and manage SSITE achievements."
    :route-name="request()->routeIs('editorial.achievements.index') ? 'editorial.achievements.index' : 'achievements.index'"
    :allowed-tabs="$allowedTabs"
    :active-tab="$tab"
    :pending-count="$pendingCount"
    :sort="$sort"
    :sort-options="$sortOptions"
    filter-name="category"
    :filter="$category"
    :filter-options="$categoryOptions"
    filter-label="Filter by category"
    filter-default=""
>
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif
    @if (session('error'))
        <p class="dashboard-error" role="alert">{{ session('error') }}</p>
    @endif

    {{-- President bulk archive remains separate from each card's single-post actions. --}}
    @can('bulkArchive', \App\Models\Achievement::class)
        @if ($tab !== 'published' && $achievements->isNotEmpty())
            <form id="achievement-bulk-archive" method="POST" action="{{ route('achievements.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions" data-selection-bar hidden>
                <span data-selection-count>0 selected</span>
                <button type="button" class="dashboard-button dashboard-button-secondary"
                        onclick="document.getElementById('bulk-archive-achievement-modal').showModal()">
                    Archive selected
                </button>
            </div>
            <dialog id="bulk-archive-achievement-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected achievements?</h2>
                    <p>Selected achievements leave Published and keep their current approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary"
                                onclick="document.getElementById('bulk-archive-achievement-modal').close()">Cancel</button>
                        <button type="submit" form="achievement-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    @php($tabLabels = ['published' => 'Published posts', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived posts', 'all' => 'All'])
    <section class="post-feed-list" aria-label="{{ $tabLabels[$tab] }} achievements">
        @forelse ($achievements as $achievement)
            <x-workspace.posts.card
                :post="$achievement"
                module="Achievements"
                route-prefix="achievements"
                :show-url="route('achievements.show', $achievement)"
                :content="$achievement->description"
                :tab="$tab"
                :media-url="$achievement->imageUrl()"
                :media-alt="$achievement->title"
            >
                <x-slot:strip>
                    <x-posts.strips.achievement :achievement="$achievement" />
                </x-slot:strip>

                <x-slot:footerActions>
                    @can('approve', $achievement)
                        @if ($tab === 'pending' && $achievement->isPending())
                            <form method="POST" action="{{ route('achievements.approve', $achievement) }}">
                                @csrf
                                <button type="submit" class="dashboard-button">Approve</button>
                            </form>
                            <button type="button" class="dashboard-button dashboard-button-secondary"
                                    data-modal-open="achievement-reject-{{ $achievement->getKey() }}">Reject</button>
                            <x-ui.reject-modal
                                id="achievement-reject-{{ $achievement->getKey() }}"
                                :action="route('achievements.reject', $achievement)"
                                subject="achievement"
                            />
                        @endif
                    @endcan

                    @can('bulkArchive', \App\Models\Achievement::class)
                        @if ($tab !== 'published' && ! $achievement->isArchived())
                            <label class="dashboard-muted">
                                <input type="checkbox" name="achievement_ids[]" value="{{ $achievement->achievement_id }}" form="achievement-bulk-archive">
                                Select
                            </label>
                        @endif
                    @endcan
                </x-slot:footerActions>
            </x-workspace.posts.card>
        @empty
            @if ($sort !== 'recent' || $category !== '')
                <div class="post-feed-empty">
                    <p>No achievements match these settings. <a href="{{ route(request()->routeIs('editorial.achievements.index') ? 'editorial.achievements.index' : 'achievements.index', ['tab' => $tab]) }}">Reset</a></p>
                </div>
            @elseif ($tab === 'published')
                <x-workspace.posts.empty-state title="No published achievements yet" message="Check back soon for the latest SSITE achievements." />
            @elseif (auth()->user()?->canPostIn('achievements'))
                <x-workspace.posts.empty-state title="No achievements in this tab" message="Use Create achievement in the workspace navigation to start a submission." />
            @else
                <x-workspace.posts.empty-state title="No achievements found" message="Check back later for new achievements." />
            @endif
        @endforelse
    </section>

    <div class="dashboard-pagination">{{ $achievements->links('vendor.pagination.ssite') }}</div>
</x-workspace.posts.feed-frame>
@endsection
