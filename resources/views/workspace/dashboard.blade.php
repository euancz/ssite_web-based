@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="workspace-dashboard workspace-president-dashboard">
    <header class="workspace-dashboard-heading">
        <div>
            <h1>Dashboard</h1>
            <p>Publishing status and the work that needs your attention.</p>
        </div>
    </header>
    <p class="workspace-draft-summary"><a class="dashboard-text-button" href="{{ route('workspace.drafts.index') }}">My drafts ({{ $draftCount }})</a> &middot; Private work you have not submitted.</p>


    <section class="workspace-status-panel" aria-labelledby="workspace-status-title">
        <div class="workspace-status-heading">
            <h2 id="workspace-status-title">Content at a glance</h2>
            <span>All five content areas</span>
        </div>
        <div class="workspace-metric-grid">
            <a class="workspace-metric-card workspace-metric-pending" href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}">
                <span class="workspace-metric-icon"><x-workspace.icon name="clock" /></span>
                <span class="workspace-metric-label">Pending review</span>
                <strong class="workspace-metric-value"><x-ui.count-up :value="$dashboardStatus['pending']" /></strong>
                <small>Submissions waiting for a decision</small>
            </a>
            <article class="workspace-metric-card workspace-metric-published">
                <span class="workspace-metric-icon"><x-workspace.icon name="check" /></span>
                <span class="workspace-metric-label">Published</span>
                <strong class="workspace-metric-value"><x-ui.count-up :value="$dashboardStatus['published']" /></strong>
                <small>Visible on the student site</small>
            </article>
            <article class="workspace-metric-card workspace-metric-archived">
                <span class="workspace-metric-icon"><x-workspace.icon name="archive" /></span>
                <span class="workspace-metric-label">Archived</span>
                <strong class="workspace-metric-value"><x-ui.count-up :value="$dashboardStatus['archived']" /></strong>
                <small>Retained outside active listings</small>
            </article>
        </div>
    </section>

    <section class="workspace-officer-recent" aria-labelledby="workspace-pending-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-pending-title">Waiting longest for review</h2><p>Start with the oldest submissions across all content areas.</p></div>
            <a class="dashboard-text-button" href="{{ route('adviser.reviews.index', ['status' => 'pending', 'sort' => 'oldest']) }}">Open review queue</a>
        </div>
        <x-ui.data-table label="Oldest pending submissions">
            <table class="dashboard-table">
                <thead><tr><th scope="col">Submission</th><th scope="col">Author</th><th scope="col">Submitted</th><th scope="col">Review</th></tr></thead>
                <tbody>
                    @forelse ($pendingPosts as $post)
                        <tr>
                            <th scope="row">{{ $post->title }} <small>{{ $post->type }}</small>@if ($post->isArchived()) <x-ui.status-badge status="archived" /> @endif</th>
                            <td>{{ $post->author?->name ?? 'Former member' }}</td>
                            <td>{{ $post->created_at?->format('M j, Y') ?? 'Not recorded' }}</td>
                            <td><a class="dashboard-text-button" href="{{ $post->preview_url }}">Preview <span class="visually-hidden">{{ $post->title }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="dashboard-empty-state">The review queue is clear. New submissions will appear here.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>
    </section>

    <section class="workspace-officer-module-status" aria-labelledby="workspace-module-title">
        <div class="workspace-officer-section-heading"><div><h2 id="workspace-module-title">Content by area</h2><p>Open any count to see the matching submissions.</p></div></div>
        <x-workspace.content-summary :modules="$moduleStats" />
    </section>

    <x-workspace.dashboard-guide />

    @can('manage-officers')
        <section class="workspace-admin-shortcut" aria-labelledby="workspace-officers-title">
            <div>
                <span class="workspace-admin-shortcut-icon"><x-workspace.icon name="users" /></span>
                <div><h2 id="workspace-officers-title">Officer roster</h2><p>Update the officer names and terms shown on the About page.</p></div>
            </div>
            <a href="{{ route('adviser.officers.index') }}">Manage officers <x-workspace.icon name="chevron" /></a>
        </section>
    @endcan
</div>
@endsection
