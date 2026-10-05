@extends('layouts.app')

@section('title', 'Officer Dashboard')

@section('content')
<div class="dashboard-page">
    {{-- Shared page header identifies the signed-in officer and keeps the unavailable create action visibly disabled. --}}
    <x-ui.page-header
        title="Welcome, {{ auth()->user()->name }}"
        subtitle="Officer dashboard · Your role is {{ ucfirst(auth()->user()->role) }}."
    >
        <x-slot:actions>
            <button type="button" class="dashboard-button" disabled title="Post creation is not implemented yet">
                New Post
            </button>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="dashboard-stat-grid" aria-label="My post statistics">
        <x-ui.stat-card label="My Posts" :value="$postCounts['total']" />
        <x-ui.stat-card label="Pending" :value="$postCounts['pending']" />
        <x-ui.stat-card label="Approved" :value="$postCounts['approved']" />
        <x-ui.stat-card label="Rejected" :value="$postCounts['rejected']" />
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-section-heading">
            <div>
                <h2>My recent posts</h2>
                <p>Posts submitted by your account will appear here.</p>
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
                                No posts yet. Post creation and storage are not implemented.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>
    </section>
</div>
@endsection
