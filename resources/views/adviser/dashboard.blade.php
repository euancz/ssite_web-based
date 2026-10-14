@extends('layouts.app')

@section('title', 'Adviser Dashboard')

@section('content')
<div class="dashboard-page">
    <x-ui.page-header
        title="Welcome, {{ auth()->user()->name }}"
        subtitle="Adviser dashboard - Review and manage the SSITE community."
    />

    <section class="dashboard-stat-grid" aria-label="Adviser statistics">
        <x-ui.stat-card label="Pending Reviews" :value="$pendingPosts->count()" :href="route('adviser.reviews.index', ['status' => 'pending'])" />
        <x-ui.stat-card label="Total Officers" :value="$totalOfficers" :href="route('adviser.users.index', ['role' => 'officer'])" />
        <x-ui.stat-card label="Total Students" :value="$totalStudents" :href="route('adviser.users.index', ['role' => 'student'])" />
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
            <p class="dashboard-empty-state">No posts need your review right now.</p>
        @endforelse
    </section>

</div>
@endsection
