@extends('layouts.app')

@section('title', 'Officer Dashboard')

@section('content')
<div class="dashboard-page">
    {{-- Role shortcuts stay in the account menu; the page keeps its one primary post action. --}}
    <x-ui.page-header
        title="Welcome, {{ auth()->user()->name }}"
    >
        <x-slot:actions>
            <a href="{{ route('articles.create') }}" class="dashboard-button">
                New Post
            </a>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="dashboard-stat-grid" aria-label="My post statistics">
        <x-ui.stat-card label="My Posts" :value="$postCounts['total']" :href="route('articles.index', ['tab' => 'mine'])" />
    </section>

    {{-- SECURITY: The dashboard receives only the signed-in officer's records; rejected details stay private. --}}
    @php($rejectedPosts = $posts->where('status', 'rejected'))
    <section class="dashboard-panel" aria-labelledby="officer-attention-heading">
        <div class="dashboard-section-heading"><h2 id="officer-attention-heading">Needs attention</h2></div>
        @forelse ($rejectedPosts as $post)
            <article class="dashboard-review-item"><div><h3>{{ $post->title }}</h3><p>Rejected · {{ ucfirst($post->type) }}</p><p class="dashboard-rejection-reason">Rejection reason: {{ $post->rejection_reason }}</p></div>
                @if ($post->preview_url)<a class="dashboard-text-button" href="{{ $post->preview_url }}">View</a>@endif
            </article>
        @empty
            <p class="dashboard-empty-state">No rejected posts need your attention.</p>
        @endforelse
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-section-heading">
            <div>
                <h2>My recent posts</h2>
            </div>
        </div>

        <x-ui.data-table label="My recent posts">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Type</th>
                        <th scope="col">Status</th>
                        <th scope="col">Date</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    {{-- TODO: Populate from the officer's own posts when post storage and authorization are added. --}}
                    @forelse ($posts as $post)
                        <tr>
                            <td>
                                <strong>{{ $post->title }}</strong>
                                {{-- Rejection feedback belongs only to rejected submissions. --}}
                                @if ($post->status === 'rejected' && $post->rejection_reason)
                                    <p class="dashboard-rejection-reason">
                                        Rejection reason: {{ $post->rejection_reason }}
                                    </p>
                                @endif
                            </td>
                            <td>{{ ucfirst($post->type) }}</td>
                            <td><x-ui.status-badge :status="$post->status" /></td>
                            <td>{{ $post->created_at?->format('M j, Y') }}</td>
                            <td class="dashboard-table-actions">
                                <button type="button" class="dashboard-text-button" disabled>Edit</button>
                                <button type="button" class="dashboard-text-button" disabled>View</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="dashboard-empty-state">
                                No posts yet. Your submissions will appear here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>
    </section>
</div>
@endsection
