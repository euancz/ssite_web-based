@extends('layouts.app')

@section('title', 'Adviser Dashboard')

@section('content')
<div class="dashboard-page">
    <x-ui.page-header
        title="Welcome, {{ auth()->user()->name }}"
        subtitle="Adviser dashboard - Review and manage the SSITE community."
    >
        <x-slot:actions>
            <a href="{{ route('adviser.users.index') }}" class="dashboard-button dashboard-button-secondary">Manage Users</a>
            <a href="{{ route('adviser.reviews.index') }}" class="dashboard-button">Review Posts</a>
        </x-slot:actions>
    </x-ui.page-header>

    <section class="dashboard-stat-grid" aria-label="Adviser statistics">
        <x-ui.stat-card label="Pending Reviews" :value="$pendingPosts->count()" />
        <x-ui.stat-card label="Approved This Month" :value="$approvedThisMonth" />
        <x-ui.stat-card label="Total Officers" :value="$totalOfficers" />
        <x-ui.stat-card label="Total Students" :value="$totalStudents" />
    </section>

    <section class="dashboard-panel">
        <div class="dashboard-section-heading">
            <div>
                <h2>Needs review</h2>
                <p>The latest five pending submissions will appear here.</p>
            </div>
            <a href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}" class="dashboard-text-button">
                Review Posts
            </a>
        </div>

        {{-- TODO: Populate with the five newest pending posts after the approval model exists. --}}
        @forelse ($pendingPosts as $post)
            <article class="dashboard-review-item">
                <div>
                    <h3>{{ $post->title }}</h3>
                    <p>{{ ucfirst($post->type) }} &middot; Submitted {{ $post->created_at?->format('M j, Y') }}</p>
                </div>
                {{-- A review link exists only when the post backend supplies a preview destination. --}}
                @if ($post->review_url)
                    <a href="{{ $post->review_url }}" class="dashboard-text-button">Review</a>
                @else
                    <span class="dashboard-muted">Review link unavailable</span>
                @endif
            </article>
        @empty
            <p class="dashboard-empty-state">
                There are no pending reviews. Post storage and review actions are not implemented yet.
            </p>
        @endforelse
    </section>

    <div class="dashboard-quick-links">
        <a href="{{ route('adviser.users.index') }}" class="dashboard-panel dashboard-quick-link">
            <strong>Manage Users</strong>
            <span>Search accounts and assign student or officer roles.</span>
        </a>
        <a href="{{ route('adviser.reviews.index') }}" class="dashboard-panel dashboard-quick-link">
            <strong>Review Posts</strong>
            <span>Open the post review queue.</span>
        </a>
    </div>
</div>
@endsection