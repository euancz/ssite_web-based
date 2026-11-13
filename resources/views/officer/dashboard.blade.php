@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="dashboard-page workspace-officer-dashboard">
    <x-workspace.welcome description="Track your submissions and manage your posts." :action-url="route('workspace.create')" action-label="Create post" />
    <x-workspace.dashboard-summary :status="$dashboardStatus" :draft-count="$draftCount" />

    @if ($rejectedPosts->isNotEmpty())
    <section class="workspace-officer-attention" aria-labelledby="workspace-attention-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-attention-title">Needs your attention</h2><p>Review feedback before submitting again.</p></div>
        </div>
        @forelse ($rejectedPosts as $post)
            <article class="workspace-officer-attention-row">
                <span class="workspace-officer-attention-mark"><x-workspace.icon name="clock" /></span>
                <div class="workspace-officer-attention-copy">
                    <h3>{{ $post->title }}</h3>
                    <p>{{ $post->type }} Â&middot; Rejected {{ $post->reviewed_at ? \Illuminate\Support\Carbon::parse($post->reviewed_at)->format('M j, Y') : '(date not recorded)' }} &middot; {{ $post->reviewer?->name ?? 'Reviewer not recorded' }}</p>
                    @if ($post->rejection_reason)<p class="workspace-officer-reason">{{ $post->rejection_reason }}</p>@endif
                </div>
                <a class="workspace-officer-manage-link" href="{{ $post->preview_url }}">Review feedback <x-workspace.icon name="chevron" /></a>
            </article>
        @empty
            <p class="workspace-officer-empty">Nothing needs your attention. Rejected posts will appear here.</p>
        @endforelse
    </section>

    @endif

    <section class="workspace-officer-recent" aria-labelledby="workspace-recent-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-recent-title">Recent submissions</h2></div>
        </div>
        <x-ui.data-table label="Your recent submissions">
            <table class="dashboard-table workspace-officer-table">
                <thead><tr><th scope="col">Title</th><th scope="col">Content type</th><th scope="col">Status</th><th scope="col">Submitted</th><th scope="col">Open</th></tr></thead>
                <tbody>
                    @forelse ($recentPosts->take(5) as $post)
                        <tr>
                            <th scope="row">{{ $post->title }}</th>
                            <td>{{ $post->type }}</td>
                            <td><x-ui.status-badge :status="$post->status" /> @if ($post->isArchived()) <x-ui.status-badge status="archived" /> @endif</td>
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
    <x-workspace.content-areas :modules="$moduleStats" />
</div>
@endsection
