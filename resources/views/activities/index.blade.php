@extends('layouts.app')

@section('title', 'Activities')

@section('content')
<div class="dashboard-page">
    {{-- The shared page heading keeps the feature aligned with the Articles dashboard. --}}
    <x-ui.page-header title="Activities" subtitle="Read and manage SSITE activities.">
        <x-slot:actions>
            {{-- SECURITY: ActivityPolicy controls creation links and the endpoint repeats that check. --}}
            @can('create', \App\Models\Activity::class)
                <a href="{{ route('activities.create') }}" class="dashboard-button">New Activity</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Role-whitelisted tabs keep private workflow states out of student and guest navigation. --}}
    @php
        $tabLabels = [
            'published' => 'Published', 'mine' => 'My Posts', 'pending' => 'Pending Review',
            'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All',
        ];
    @endphp
    <nav class="dashboard-tabs" aria-label="Activity sections">
        @foreach ($allowedTabs as $allowedTab)
            <a href="{{ route('activities.index', ['tab' => $allowedTab, 'search' => $search ?: null]) }}"
               @class(['dashboard-tab', 'is-active' => $tab === $allowedTab])
               @if ($tab === $allowedTab) aria-current="page" @endif>
                {{ $tabLabels[$allowedTab] }}
            </a>
        @endforeach
    </nav>

    {{-- Search stays within the role-approved tab and matches activity details or author names. --}}
    <form method="GET" action="{{ route('activities.index') }}" class="dashboard-filter-form">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="dashboard-form-field">
            <label for="activity-search">Search activities</label>
            <input id="activity-search" type="search" name="search" value="{{ $search }}" maxlength="100">
        </div>
        <button type="submit" class="dashboard-button">Search</button>
    </form>

    {{-- Flash messages report completed actions and harmless repeated requests. --}}
    @if (session('status')) <p class="dashboard-success" role="status">{{ session('status') }}</p> @endif
    @if (session('error')) <p class="dashboard-error" role="alert">{{ session('error') }}</p> @endif

    {{-- Adviser bulk archive submits selected IDs to a validated, policy-protected endpoint. --}}
    @can('bulkArchive', \App\Models\Activity::class)
        @if ($tab !== 'published' && $activities->isNotEmpty())
            <form id="activity-bulk-archive" method="POST" action="{{ route('activities.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions">
                <button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-activity-modal').showModal()">
                    Archive selected
                </button>
            </div>
            {{-- Adviser confirmation precedes archiving the selected group. --}}
            <dialog id="bulk-archive-activity-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected activities?</h2>
                    <p>Selected activities leave Published and keep their current approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-activity-modal').close()">Cancel</button>
                        <button type="submit" form="activity-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    {{-- Activity cards contain only records returned by the active role and tab query. --}}
    <section class="articles-list" aria-label="{{ $tabLabels[$tab] }} activities">
        @forelse ($activities as $activity)
            <article class="article-list-item">
                <a href="{{ route('activities.show', $activity) }}" aria-label="Read {{ $activity->title }}">
                    <img class="article-list-image" src="{{ $activity->imageUrl() }}" alt="">
                </a>
                <div class="article-list-content">
                    <div class="article-badges">
                        <x-ui.status-badge :status="$activity->approval_status" />
                        @if ($activity->isArchived()) <x-ui.status-badge status="archived" /> @endif
                    </div>
                    <h2><a href="{{ route('activities.show', $activity) }}">{{ $activity->title }}</a></h2>
                    <div class="article-list-meta">
                        <span>{{ $activity->author?->name ?? 'Former member' }}</span>
                        @if ($activity->activity_date) <span>{{ $activity->activity_date->format('M j, Y') }}</span> @endif
                        @if ($activity->location) <span>{{ $activity->location }}</span> @endif
                    </div>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($activity->description), 180) }}</p>
                    {{-- Rejection details appear only in an officer's own-posts tab. --}}
                    @if ($tab === 'mine' && $activity->isRejected() && $activity->rejection_reason)
                        <p class="dashboard-rejection-reason">Rejection reason: {{ $activity->rejection_reason }}</p>
                    @endif
                    <div class="article-actions">
                        {{-- SECURITY: ActivityPolicy hides private details from users who cannot view this record. --}}
                        @can('view', $activity)
                            <a class="dashboard-text-button" href="{{ route('activities.show', $activity) }}">Read activity</a>
                        @endcan
                        {{-- SECURITY: Only the activity owner or an adviser can edit; the endpoint repeats this check. --}}
                        @can('update', $activity)
                            <a class="dashboard-text-button" href="{{ route('activities.edit', $activity) }}">Edit</a>
                        @endcan
                        {{-- SECURITY: Only advisers can select activities for bulk archive. --}}
                        @can('bulkArchive', \App\Models\Activity::class)
                            @if (! $activity->isArchived())
                                <label class="dashboard-muted">
                                    <input type="checkbox" name="activity_ids[]" value="{{ $activity->activity_id }}" form="activity-bulk-archive">
                                    Select
                                </label>
                            @endif
                        @endcan
                    </div>
                </div>
            </article>
        @empty
            <section class="dashboard-panel dashboard-empty-state">
                <h2>{{ $tabLabels[$tab] }}</h2>
                <p>
                    @switch($tab)
                        @case('published') No published activities are available right now. @break
                        @case('mine') You have not posted any activities yet. @break
                        @case('pending') No activities are waiting for review. @break
                        @case('rejected') No rejected activities were found. @break
                        @case('archived') No archived activities were found. @break
                        @default No activities were found.
                    @endswitch
                </p>
            </section>
        @endforelse
    </section>

    {{-- Keep search and tab filters while moving through ten activity results per page. --}}
    <div class="dashboard-pagination">{{ $activities->links() }}</div>
</div>
@endsection
