@extends('layouts.app')

@section('title', auth()->user()->isPresident() ? 'President Dashboard' : 'Officer Dashboard')

@section('content')
<div class="dashboard-page">
    @if (auth()->user()->canReview())
        <x-ui.page-header title="Welcome, {{ auth()->user()->name }}" subtitle="Review pending SSITE submissions across all modules." />
        <section class="dashboard-panel" aria-labelledby="president-needs-review-heading">
            <div class="dashboard-section-heading"><div><h2 id="president-needs-review-heading">Needs review</h2><p>Latest pending submissions across all modules.</p></div>
                <a href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}" class="dashboard-text-button">Review Posts</a>
            </div>
            @forelse ($pendingPosts as $post)
                <article class="dashboard-review-item"><div><h3>{{ $post->title }}</h3><p>{{ $post->type }} · {{ $post->author?->name ?? 'Former member' }} · Submitted {{ $post->created_at?->format('M j, Y') }}</p></div>
                    <a href="{{ $post->review_url }}" class="dashboard-text-button">Review</a>
                </article>
            @empty
                <p class="dashboard-empty-state">No posts need review right now.</p>
            @endforelse
        </section>
        <section class="dashboard-stat-grid" aria-label="Pending review counts">
            @foreach ($pendingByModule as $module)
                <x-ui.stat-card :label="$module['label']" :value="$module['count']" :href="route('adviser.reviews.index', ['status' => 'pending'])" />
            @endforeach
        </section>
    @else
        <x-ui.page-header title="Welcome, {{ auth()->user()->name }}" subtitle="Your position dashboard shows only your submissions." />
        <section class="dashboard-panel" aria-labelledby="officer-attention-heading">
            <div class="dashboard-section-heading"><h2 id="officer-attention-heading">Needs attention</h2></div>
            @forelse ($rejectedPosts as $post)
                <article class="dashboard-review-item"><div><h3>{{ $post->title }}</h3><p>Rejected · {{ $post->type }}</p>@if ($post->rejection_reason)<p class="dashboard-rejection-reason">Rejection reason: {{ $post->rejection_reason }}</p>@endif</div>
                    <a class="dashboard-text-button" href="{{ $post->preview_url }}">View</a>
                </article>
            @empty
                <p class="dashboard-empty-state">No rejected posts need your attention.</p>
            @endforelse
        </section>
        <section class="dashboard-stat-grid" aria-label="My post statistics">
            @foreach ($moduleStats as $module)
                <x-ui.stat-card :label="$module['label'] . ' · My posts'" :value="$module['total']" :href="route($module['index'], ['tab' => 'mine'])" />
            @endforeach
        </section>
        <section class="dashboard-panel" aria-labelledby="module-status-heading">
            <div class="dashboard-section-heading"><div><h2 id="module-status-heading">My posts by module</h2></div></div>
            <x-ui.data-table label="My posts by module">
                <table class="dashboard-table"><thead><tr><th>Module</th><th>Total</th><th>Pending</th><th>Approved</th><th>Rejected</th><th>Actions</th></tr></thead><tbody>
                    @foreach ($moduleStats as $module)
                        <tr><td>{{ $module['label'] }}</td><td>{{ $module['total'] }}</td><td>{{ $module['pending'] }}</td><td>{{ $module['approved'] }}</td><td>{{ $module['rejected'] }}</td><td><a class="dashboard-text-button" href="{{ route($module['create']) }}">Create</a> · <a class="dashboard-text-button" href="{{ route($module['index'], ['tab' => 'mine']) }}">My posts</a></td></tr>
                    @endforeach
                </tbody></table>
            </x-ui.data-table>
        </section>
        <section class="dashboard-panel" aria-labelledby="recent-posts-heading">
            <div class="dashboard-section-heading"><div><h2 id="recent-posts-heading">My recent posts</h2></div></div>
            <x-ui.data-table label="My recent posts">
                <table class="dashboard-table"><thead><tr><th>Title</th><th>Type</th><th>Status</th><th>Date</th><th>Action</th></tr></thead><tbody>
                    @forelse ($recentPosts as $post)
                        <tr><td>{{ $post->title }}</td><td>{{ $post->type }}</td><td><x-ui.status-badge :status="$post->status" /></td><td>{{ $post->created_at?->format('M j, Y') }}</td><td><a href="{{ $post->preview_url }}" class="dashboard-text-button">View</a></td></tr>
                    @empty
                        <tr><td colspan="5" class="dashboard-empty-state">Your submissions will appear here.</td></tr>
                    @endforelse
                </tbody></table>
            </x-ui.data-table>
        </section>
    @endif
</div>
@endsection
