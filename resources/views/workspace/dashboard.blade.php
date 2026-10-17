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

    <section class="workspace-status-panel" aria-labelledby="workspace-status-title">
        <div class="workspace-status-heading">
            <h2 id="workspace-status-title">Content at a glance</h2>
            <span>All five content areas</span>
        </div>
        <div class="workspace-metric-grid">
            <a class="workspace-metric-card workspace-metric-pending" href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}">
                <span class="workspace-metric-icon"><x-workspace.icon name="clock" /></span>
                <span class="workspace-metric-label">Pending review</span>
                <strong class="workspace-metric-value">{{ $dashboardStatus['pending'] }}</strong>
                <small>Submissions waiting for a decision</small>
            </a>
            <article class="workspace-metric-card workspace-metric-published">
                <span class="workspace-metric-icon"><x-workspace.icon name="check" /></span>
                <span class="workspace-metric-label">Published</span>
                <strong class="workspace-metric-value">{{ $dashboardStatus['published'] }}</strong>
                <small>Visible on the student site</small>
            </article>
            <article class="workspace-metric-card workspace-metric-archived">
                <span class="workspace-metric-icon"><x-workspace.icon name="archive" /></span>
                <span class="workspace-metric-label">Archived</span>
                <strong class="workspace-metric-value">{{ $dashboardStatus['archived'] }}</strong>
                <small>Retained outside active listings</small>
            </article>
        </div>
    </section>

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
