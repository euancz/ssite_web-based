@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="workspace-dashboard workspace-president-dashboard">
    <header class="workspace-dashboard-heading">
        <div>
            <h1>Dashboard</h1>
            <p>Publishing status and the work that needs your attention.</p>
        </div>
        <span class="workspace-dashboard-role">{{ auth()->user()->position() ?? 'President' }}</span>
    </header>

    <section class="workspace-status-panel" aria-labelledby="workspace-status-title">
        <div class="workspace-status-heading">
            <h2 id="workspace-status-title">Content at a glance</h2>
            <span>All five content areas</span>
        </div>
        <ul class="workspace-status-list">
            <li>
                <span class="workspace-status-mark workspace-status-pending"><x-workspace.icon name="clock" /></span>
                <span class="workspace-status-copy"><strong>Pending review</strong><small>Submissions waiting for a decision</small></span>
                <strong class="workspace-status-count">{{ $dashboardStatus['pending'] }}</strong>
                <a class="workspace-status-action" href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}">Review queue</a>
            </li>
            <li>
                <span class="workspace-status-mark workspace-status-published"><x-workspace.icon name="check" /></span>
                <span class="workspace-status-copy"><strong>Published</strong><small>Visible on the student site</small></span>
                <strong class="workspace-status-count">{{ $dashboardStatus['published'] }}</strong>
                <a class="workspace-status-action" href="{{ route('articles.index', ['tab' => 'published']) }}">Open library</a>
            </li>
            <li>
                <span class="workspace-status-mark workspace-status-archived"><x-workspace.icon name="archive" /></span>
                <span class="workspace-status-copy"><strong>Archived</strong><small>Retained outside active listings</small></span>
                <strong class="workspace-status-count">{{ $dashboardStatus['archived'] }}</strong>
                <a class="workspace-status-action" href="{{ route('articles.index', ['tab' => 'archived']) }}">Open archive</a>
            </li>
        </ul>
    </section>

    <section class="workspace-quick-create" aria-labelledby="workspace-quick-create-title">
        <div class="workspace-status-heading">
            <h2 id="workspace-quick-create-title">Start something new</h2>
            <span>Choose a content type</span>
        </div>
        <div class="workspace-quick-create-links">
            @foreach ($availableModules as $module)
                <a href="{{ route($module['create']) }}">
                    <span class="workspace-create-icon"><x-workspace.icon :name="$module['icon']" /></span>
                    <span>Create {{ strtolower($module['label']) }}</span>
                    <x-workspace.icon name="chevron" />
                </a>
            @endforeach
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
