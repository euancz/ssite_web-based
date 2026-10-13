@extends('layouts.app')

@section('title', 'Activities')

@section('content')
<div class="dashboard-page">
    {{-- The shared page heading keeps the feature aligned with the Articles dashboard. --}}
    <x-ui.page-header title="Achievements" subtitle="Read and manage SSITE achievements.">
        <x-slot:actions>
            {{-- SECURITY: AchievementPolicy controls creation links and the endpoint repeats that check. --}}
            @can('create', \App\Models\Achievement::class)
                <a href="{{ route('achievements.create') }}" class="dashboard-button">New Achievement</a>
            @endcan
        </x-slot:actions>
    </x-ui.page-header>

    {{-- SECURITY: This display reshapes allowed tabs only; controller whitelists and Published fallback stay authoritative. --}}
    <x-ui.workflow-tabs route-name="achievements.index" :allowed-tabs="$allowedTabs" :active-tab="$tab" :search="$search" :pending-count="$pendingCount" />
    @php($tabLabels = ['published' => 'Published', 'mine' => 'My Posts', 'pending' => 'Pending Review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All'])

    {{-- Search stays within the role-approved tab and matches achievement details or author names. --}}
    <form method="GET" action="{{ route('achievements.index') }}" class="dashboard-filter-form">
        <input type="hidden" name="tab" value="{{ $tab }}">
        <div class="dashboard-form-field">
            <label for="achievement-search">Search achievements</label>
            <input id="achievement-search" type="search" name="search" value="{{ $search }}" maxlength="100">
        </div>
        <button type="submit" class="dashboard-button">Search</button>
    </form>

    {{-- Flash messages report completed actions and harmless repeated requests. --}}
    @if (session('status')) <p class="dashboard-success" role="status">{{ session('status') }}</p> @endif
    @if (session('error')) <p class="dashboard-error" role="alert">{{ session('error') }}</p> @endif

    {{-- Adviser bulk archive submits selected IDs to a validated, policy-protected endpoint. --}}
    @can('bulkArchive', \App\Models\Achievement::class)
        @if ($tab !== 'published' && $achievements->isNotEmpty())
            <form id="achievement-bulk-archive" method="POST" action="{{ route('achievements.bulk-archive') }}">
                @csrf
            </form>
            <div class="article-bulk-actions" data-selection-bar hidden>
                <span data-selection-count>0 selected</span>
                <button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-achievement-modal').showModal()">
                    Archive selected
                </button>
            </div>
            {{-- Adviser confirmation precedes archiving the selected group. --}}
            <dialog id="bulk-archive-achievement-modal" class="reject-modal">
                <div class="reject-modal-content">
                    <h2>Archive selected achievements?</h2>
                    <p>Selected achievements leave Published and keep their current approval status.</p>
                    <div class="reject-modal-actions">
                        <button type="button" class="dashboard-button dashboard-button-secondary" onclick="document.getElementById('bulk-archive-achievement-modal').close()">Cancel</button>
                        <button type="submit" form="achievement-bulk-archive" class="dashboard-button">Archive selected</button>
                    </div>
                </div>
            </dialog>
        @endif
    @endcan

    {{-- Achievement cards contain only records returned by the active role and tab query. --}}
    <section class="articles-list" aria-label="{{ $tabLabels[$tab] }} achievements">
        @forelse ($achievements as $achievement)
            <article class="article-list-item">
                <a href="{{ route('achievements.show', $achievement) }}" aria-label="Read {{ $achievement->title }}">
                    <img class="article-list-image" src="{{ $achievement->imageUrl() }}" alt="">
                </a>
                <div class="article-list-content">
                    <div class="article-badges">
                        <x-ui.status-badge :status="$achievement->approval_status" />
                        @if ($achievement->isArchived()) <x-ui.status-badge status="archived" /> @endif
                    </div>
                    <h2><a href="{{ route('achievements.show', $achievement) }}">{{ $achievement->title }}</a></h2>
                    <div class="article-list-meta">
                        <span>{{ $achievement->author?->name ?? 'Former member' }}</span>
                        @if ($achievement->achievement_date) <span>{{ $achievement->achievement_date->format('M j, Y') }}</span> @endif
                        @if ($achievement->awardee) <span>{{ $achievement->awardee }}</span> @endif
                        @if ($achievement->category) <span>{{ $achievement->category }}</span> @endif
                    </div>
                    <p>{{ \Illuminate\Support\Str::limit(strip_tags($achievement->description), 180) }}</p>
                    {{-- Rejection details appear only in an officer's own-posts tab. --}}
                    @if ($tab === 'mine' && $achievement->isRejected() && $achievement->rejection_reason)
                        <p class="dashboard-rejection-reason">Rejection reason: {{ $achievement->rejection_reason }}</p>
                    @endif
                    <div class="article-actions">
                        {{-- SECURITY: AchievementPolicy hides private details from users who cannot view this record. --}}
                        @can('view', $achievement)
                            <a class="dashboard-text-button" href="{{ route('achievements.show', $achievement) }}">Read achievement</a>
                        @endcan
                        {{-- SECURITY: Only the achievement owner or an adviser can edit; the endpoint repeats this check. --}}
                        @can('update', $achievement)
                            <details class="article-row-menu">
                                <summary aria-label="More actions for {{ $achievement->title }}">&#8942;</summary>
                                <a class="dashboard-text-button" href="{{ route('achievements.edit', $achievement) }}">Edit</a>
                            </details>
                        @endcan
                        {{-- SECURITY: Only advisers can select achievements for bulk archive. --}}
                        @can('bulkArchive', \App\Models\Achievement::class)
                            @if (! $achievement->isArchived())
                                <label class="dashboard-muted">
                                    <input type="checkbox" name="achievement_ids[]" value="{{ $achievement->achievement_id }}" form="achievement-bulk-archive">
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
                        @case('published') No published achievements are available right now. @break
                        @case('mine') You have not posted any achievements yet. @break
                        @case('pending') No achievements are waiting for review. @break
                        @case('rejected') No rejected achievements were found. @break
                        @case('archived') No archived achievements were found. @break
                        @default No achievements were found.
                    @endswitch
                </p>
            </section>
        @endforelse
    </section>

    {{-- Keep search and tab filters while moving through ten achievement results per page. --}}
    <div class="dashboard-pagination">{{ $achievements->links() }}</div>
</div>
@endsection
