@extends('layouts.app')

@section('title', $activity->title)

@section('content')
<div class="dashboard-page">
    {{-- Flash messages remain visible after activity actions return to this page. --}}
    @if (session('status')) <p class="dashboard-success" role="status">{{ session('status') }}</p> @endif
    @if (session('error')) <p class="dashboard-error" role="alert">{{ session('error') }}</p> @endif

    {{-- Activity metadata uses the shared page header and status badge. --}}
    <x-ui.page-header :title="$activity->title" :subtitle="'By ' . ($activity->author?->name ?? 'Former member') . ' · ' . ($activity->activity_date?->format('M j, Y') ?? 'Date not specified')">
        <x-slot:actions>
            <div class="article-badges">
                <x-ui.status-badge :status="$activity->approval_status" />
                @if ($activity->isArchived()) <x-ui.status-badge status="archived" /> @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Archived activities remain readable to their owner and advisers. --}}
    @if ($activity->isArchived()) <p class="dashboard-notice">This activity is archived and does not appear in Published.</p> @endif
    @if ($activity->isPending()) <p class="dashboard-notice">This activity is pending adviser review.</p> @endif
    @if ($activity->isRejected() && $activity->rejection_reason)
        <p class="dashboard-error">Rejection reason: {{ $activity->rejection_reason }}</p>
    @endif

    {{-- Activity description stays escaped while preserving the line breaks entered in its form. --}}
    <section class="dashboard-panel">
        <img class="article-show-image" src="{{ $activity->imageUrl() }}" alt="">
        <dl class="article-list-meta">
            @if ($activity->activity_date)<div><dt>Date</dt><dd>{{ $activity->activity_date->format('F j, Y') }}</dd></div>@endif
            @if ($activity->location)<div><dt>Location</dt><dd>{{ $activity->location }}</dd></div>@endif
        </dl>
        <div class="article-show-content" style="white-space: pre-line">{{ $activity->description }}</div>
    </section>

    {{-- State-changing forms use CSRF and server-side policy checks matching route middleware. --}}
    <div class="article-actions">
        {{-- SECURITY: Only the activity owner or an adviser may change activity content. --}}
        @can('update', $activity)
            <a class="dashboard-button dashboard-button-secondary" href="{{ route('activities.edit', $activity) }}">Edit</a>
        @endcan
        {{-- SECURITY: Only the owner or an adviser may archive this activity. --}}
        @can('archive', $activity)
            @if (! $activity->isArchived())
                <form id="archive-activity-form" method="POST" action="{{ route('activities.archive', $activity) }}">@csrf</form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="archive-activity-modal">Archive</button>
            @endif
        @endcan
        {{-- SECURITY: Restore is owner/adviser-only and leaves the approval state unchanged. --}}
        @can('restore', $activity)
            @if ($activity->isArchived())
                <form id="restore-activity-form" method="POST" action="{{ route('activities.restore', $activity) }}">@csrf</form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="restore-activity-modal">Restore</button>
            @endif
        @endcan
        {{-- SECURITY: Adviser review controls appear only while the activity is pending. --}}
        @can('approve', $activity)
            @if ($activity->isPending())
                <form method="POST" action="{{ route('activities.approve', $activity) }}">@csrf<button class="dashboard-button" type="submit">Approve</button></form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="activity-reject-modal">Reject</button>
            @endif
        @endcan
        {{-- SECURITY: Permanent deletion is adviser-only and requires confirmation before DELETE. --}}
        @can('delete', $activity)
            <form id="delete-activity-form" method="POST" action="{{ route('activities.destroy', $activity) }}">@csrf @method('DELETE')</form>
            <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="delete-activity-modal">Delete</button>
        @endcan
    </div>

    {{-- The shared modal submits a required rejection reason to the adviser-only endpoint. --}}
    @can('reject', $activity)
        @if ($activity->isPending())
            <x-ui.reject-modal id="activity-reject-modal" :action="route('activities.reject', $activity)" subject="activity" />
        @endif
    @endcan
    {{-- Confirmation dialogs keep archive, restore, and permanent deletion deliberate actions. --}}
    @can('archive', $activity)
        @if (! $activity->isArchived())
            <dialog id="archive-activity-modal" class="reject-modal"><div class="reject-modal-content">
                <h2>Archive activity?</h2><p>The activity leaves Published and remains available in Archived.</p>
                <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="archive-activity-modal">Cancel</button>
                    <button type="submit" form="archive-activity-form" class="dashboard-button">Archive</button></div>
            </div></dialog>
        @endif
    @endcan
    @can('restore', $activity)
        @if ($activity->isArchived())
            <dialog id="restore-activity-modal" class="reject-modal"><div class="reject-modal-content">
                <h2>Restore activity?</h2><p>Its approval status will stay unchanged.</p>
                <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="restore-activity-modal">Cancel</button>
                    <button type="submit" form="restore-activity-form" class="dashboard-button">Restore</button></div>
            </div></dialog>
        @endif
    @endcan
    @can('delete', $activity)
        <dialog id="delete-activity-modal" class="reject-modal"><div class="reject-modal-content">
            <h2>Delete activity permanently?</h2><p>This also removes its stored image and cannot be undone.</p>
            <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="delete-activity-modal">Cancel</button>
                <button type="submit" form="delete-activity-form" class="dashboard-button">Delete permanently</button></div>
        </div></dialog>
    @endcan
</div>
@endsection
