@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-page workspace-officer-dashboard">
    <header class="workspace-dashboard-heading">
        <div>
            <h1>Dashboard</h1>
            <p>Welcome, {{ auth()->user()->name }}. Track your submissions and pick up where you left off.</p>
        </div>
        <span class="workspace-dashboard-role">{{ auth()->user()->position() ?? 'Officer' }}</span>
    </header>

    <section class="workspace-officer-create" aria-labelledby="workspace-officer-create-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-officer-create-title">Create content</h2><p>Available for your assigned position.</p></div>
        </div>
        <div class="workspace-officer-create-links">
            @foreach ($moduleStats as $key => $module)
                <a href="{{ route($module['create']) }}">
                    <span class="workspace-officer-create-icon"><x-workspace.icon :name="$modules[$key]['icon'] ?? 'file'" /></span>
                    <span>Create {{ $modules[$key]['singular'] }}</span>
                    <x-workspace.icon name="chevron" />
                </a>
            @endforeach
        </div>
    </section>

    <section class="workspace-officer-module-status" aria-labelledby="workspace-module-status-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-module-status-title">Your content</h2><p>Counts include only submissions created by your account.</p></div>
        </div>
        <x-ui.data-table label="Your submission totals by content type">
            <table class="dashboard-table workspace-officer-table workspace-officer-module-table">
                <thead><tr><th scope="col">Content type</th><th scope="col">Total</th><th scope="col">Pending</th><th scope="col">Approved</th><th scope="col">Rejected</th><th scope="col">Manage</th></tr></thead>
                <tbody>
                    @foreach ($moduleStats as $module)
                        <tr>
                            <th scope="row">{{ $module['label'] }}</th>
                            <td>{{ $module['total'] }}</td>
                            <td>{{ $module['pending'] }}</td>
                            <td>{{ $module['approved'] }}</td>
                            <td>{{ $module['rejected'] }}</td>
                            <td><a class="workspace-officer-manage-link" href="{{ route($module['index'], ['tab' => 'mine']) }}">Submissions <x-workspace.icon name="chevron" /></a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </x-ui.data-table>
    </section>

    <section class="workspace-officer-attention" aria-labelledby="workspace-attention-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-attention-title">Needs your attention</h2><p>Rejected submissions include feedback when a reason was recorded.</p></div>
        </div>
        @forelse ($rejectedPosts as $post)
            <article class="workspace-officer-attention-row">
                <span class="workspace-officer-attention-mark"><x-workspace.icon name="clock" /></span>
                <div class="workspace-officer-attention-copy">
                    <h3>{{ $post->title }}</h3>
                    <p>{{ $post->type }} · Rejected {{ $post->created_at?->format('M j, Y') }}</p>
                    @if ($post->rejection_reason)<p class="workspace-officer-reason">{{ $post->rejection_reason }}</p>@endif
                </div>
                <a class="workspace-officer-manage-link" href="{{ $post->preview_url }}">Review feedback <x-workspace.icon name="chevron" /></a>
            </article>
        @empty
            <p class="workspace-officer-empty">Nothing needs your attention. Rejected posts will appear here.</p>
        @endforelse
    </section>

    <section class="workspace-officer-recent" aria-labelledby="workspace-recent-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-recent-title">Recent submissions</h2><p>Your latest posts across the content types available to you.</p></div>
        </div>
        <x-ui.data-table label="Your recent submissions">
            <table class="dashboard-table workspace-officer-table">
                <thead><tr><th scope="col">Title</th><th scope="col">Content type</th><th scope="col">Status</th><th scope="col">Submitted</th><th scope="col">Open</th></tr></thead>
                <tbody>
                    @forelse ($recentPosts as $post)
                        <tr>
                            <th scope="row">{{ $post->title }}</th>
                            <td>{{ $post->type }}</td>
                            <td><x-ui.status-badge :status="$post->status" /></td>
                            <td>{{ $post->created_at?->format('M j, Y') }}</td>
                            <td><a class="workspace-officer-manage-link" href="{{ $post->preview_url }}">View <x-workspace.icon name="external" /></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="workspace-officer-empty">Your submissions will appear here once you create content.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>
    </section>
</div>
@endsection
