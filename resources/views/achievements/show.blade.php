@extends('layouts.app')

@section('title', $achievement->title)

@section('content')
<div class="dashboard-page">
    {{-- Flash messages remain visible after achievement actions return to this page. --}}
    @if (session('status')) <p class="dashboard-success" role="status">{{ session('status') }}</p> @endif
    @if (session('error')) <p class="dashboard-error" role="alert">{{ session('error') }}</p> @endif

    {{-- Achievement metadata uses the shared page header and status badge. --}}
    <x-ui.page-header :title="$achievement->title" :subtitle="'By ' . ($achievement->author?->name ?? 'Former member') . ' Â· ' . ($achievement->achievement_date?->format('M j, Y') ?? 'Date not specified')">
        <x-slot:actions>
            <div class="article-badges">
                <x-ui.status-badge :status="$achievement->approval_status" />
                @if ($achievement->isArchived()) <x-ui.status-badge status="archived" /> @endif
            </div>
        </x-slot:actions>
    </x-ui.page-header>

    {{-- Archived achievements remain readable to their owner and advisers. --}}
    @if ($achievement->isArchived()) <p class="dashboard-notice">This achievement is archived and does not appear in Published.</p> @endif
    @if ($achievement->isPending()) <p class="dashboard-notice">This achievement is pending adviser review.</p> @endif
    @if ($achievement->isRejected() && $achievement->rejection_reason)
        <p class="dashboard-error">Rejection reason: {{ $achievement->rejection_reason }}</p>
    @endif

    {{-- Achievement description stays escaped while preserving the line breaks entered in its form. --}}
    <section class="dashboard-panel">
        <img class="article-show-image" src="{{ $achievement->imageUrl() }}" alt="">
        <dl class="article-list-meta">
            @if ($achievement->achievement_date)<div><dt>Date</dt><dd>{{ $achievement->achievement_date->format('F j, Y') }}</dd></div>@endif
            @if ($achievement->awardee)<div><dt>Awardee</dt><dd>{{ $achievement->awardee }}</dd></div>@endif
            @if ($achievement->category)<div><dt>Category</dt><dd>{{ $achievement->category }}</dd></div>@endif
        </dl>
        <div class="article-show-content" style="white-space: pre-line">{{ $achievement->description }}</div>
    </section>

    {{-- State-changing forms use CSRF and server-side policy checks matching route middleware. --}}
    <div class="article-actions">
        {{-- SECURITY: Only the achievement owner or an adviser may change achievement content. --}}
        @can('update', $achievement)
            <a class="dashboard-button dashboard-button-secondary" href="{{ route('achievements.edit', $achievement) }}">Edit</a>
        @endcan
        {{-- SECURITY: Only the owner or an adviser may archive this achievement. --}}
        @can('archive', $achievement)
            @if (! $achievement->isArchived())
                <form id="archive-achievement-form" method="POST" action="{{ route('achievements.archive', $achievement) }}">@csrf</form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="archive-achievement-modal">Archive</button>
            @endif
        @endcan
        {{-- SECURITY: Restore is owner/adviser-only and leaves the approval state unchanged. --}}
        @can('restore', $achievement)
            @if ($achievement->isArchived())
                <form id="restore-achievement-form" method="POST" action="{{ route('achievements.restore', $achievement) }}">@csrf</form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="restore-achievement-modal">Restore</button>
            @endif
        @endcan
        {{-- SECURITY: Adviser review controls appear only while the achievement is pending. --}}
        @can('approve', $achievement)
            @if ($achievement->isPending())
                <form method="POST" action="{{ route('achievements.approve', $achievement) }}">@csrf<button class="dashboard-button" type="submit">Approve</button></form>
                <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="achievement-reject-modal">Reject</button>
            @endif
        @endcan
        {{-- SECURITY: Permanent deletion is adviser-only and requires confirmation before DELETE. --}}
        @can('delete', $achievement)
            <form id="delete-achievement-form" method="POST" action="{{ route('achievements.destroy', $achievement) }}">@csrf @method('DELETE')</form>
            <button class="dashboard-button dashboard-button-secondary" type="button" data-modal-open="delete-achievement-modal">Delete</button>
        @endcan
    </div>

    {{-- The shared modal submits a required rejection reason to the adviser-only endpoint. --}}
    @can('reject', $achievement)
        @if ($achievement->isPending())
            <x-ui.reject-modal id="achievement-reject-modal" :action="route('achievements.reject', $achievement)" subject="achievement" />
        @endif
    @endcan
    {{-- Confirmation dialogs keep archive, restore, and permanent deletion deliberate actions. --}}
    @can('archive', $achievement)
        @if (! $achievement->isArchived())
            <dialog id="archive-achievement-modal" class="reject-modal"><div class="reject-modal-content">
                <h2>Archive achievement?</h2><p>The achievement leaves Published and remains available in Archived.</p>
                <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="archive-achievement-modal">Cancel</button>
                    <button type="submit" form="archive-achievement-form" class="dashboard-button">Archive</button></div>
            </div></dialog>
        @endif
    @endcan
    @can('restore', $achievement)
        @if ($achievement->isArchived())
            <dialog id="restore-achievement-modal" class="reject-modal"><div class="reject-modal-content">
                <h2>Restore achievement?</h2><p>Its approval status will stay unchanged.</p>
                <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="restore-achievement-modal">Cancel</button>
                    <button type="submit" form="restore-achievement-form" class="dashboard-button">Restore</button></div>
            </div></dialog>
        @endif
    @endcan
    @can('delete', $achievement)
        <dialog id="delete-achievement-modal" class="reject-modal"><div class="reject-modal-content">
            <h2>Delete achievement permanently?</h2><p>This also removes its stored image and cannot be undone.</p>
            <div class="reject-modal-actions"><button type="button" class="dashboard-button dashboard-button-secondary" data-modal-close="delete-achievement-modal">Cancel</button>
                <button type="submit" form="delete-achievement-form" class="dashboard-button">Delete permanently</button></div>
        </div></dialog>
    @endcan
</div>
@endsection
