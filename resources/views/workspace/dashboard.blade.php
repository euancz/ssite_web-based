@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="workspace-dashboard">
    <header class="workspace-dashboard-heading">
        <div>
            <h1>Dashboard</h1>
            <p>A quick view of your SSITE publishing work and its current status.</p>
        </div>
        <span class="workspace-dashboard-role">{{ auth()->user()->position() }}</span>
    </header>

    <section class="workspace-status-panel" aria-labelledby="workspace-status-title">
        <div class="workspace-status-heading">
            <h2 id="workspace-status-title">Content status</h2>
            <span>{{ auth()->user()->canReview() ? 'Across SSITE content' : 'Your submissions' }}</span>
        </div>
        <ul class="workspace-status-list">
            <li>
                <span class="workspace-status-mark workspace-status-pending"><x-workspace.icon name="clock" /></span>
                <span class="workspace-status-copy"><strong>{{ auth()->user()->canReview() ? 'Pending review' : 'Awaiting review' }}</strong><small>{{ auth()->user()->canReview() ? 'Posts waiting for your decision' : 'Sent to the President for review' }}</small></span>
                <strong class="workspace-status-count">{{ $dashboardStatus['pending'] }}</strong>
            </li>
            <li>
                <span class="workspace-status-mark workspace-status-published"><x-workspace.icon name="check" /></span>
                <span class="workspace-status-copy"><strong>Published</strong><small>Visible on the main site</small></span>
                <strong class="workspace-status-count">{{ $dashboardStatus['published'] }}</strong>
            </li>
            <li>
                <span class="workspace-status-mark workspace-status-archived"><x-workspace.icon name="archive" /></span>
                <span class="workspace-status-copy"><strong>Archived</strong><small>Removed from active listings</small></span>
                <strong class="workspace-status-count">{{ $dashboardStatus['archived'] }}</strong>
            </li>
        </ul>
        @if (auth()->user()->canReview())
            <a class="workspace-review-link" href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}">
                <x-workspace.icon name="check" /><span>Open review queue</span><x-workspace.icon name="chevron" />
            </a>
        @endif
    </section>

    <section class="workspace-quick-create" aria-labelledby="workspace-quick-create-title">
        <div class="workspace-status-heading">
            <h2 id="workspace-quick-create-title">Create a post</h2>
            <span>Choose a format to get started</span>
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
</div>
@endsection
