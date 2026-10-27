@extends('layouts.app')
@section('title', 'Review history')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Review history" subtitle="Browse recorded approval and rejection decisions for posts you can currently manage." />
    <x-ui.form-errors />
    <form method="GET" action="{{ route('workspace.review-history.index') }}" class="workspace-review-filters">
        <div class="dashboard-form-field">
            <label for="history-search">Title at review</label>
            <input id="history-search" type="search" name="q" maxlength="100" value="{{ $filters['q'] }}" placeholder="Search recorded titles">
        </div>
        <div class="dashboard-form-field">
            @if ($filters['post'] ?? null)
                <span>Content area</span><strong>{{ $modules[$filters['module']]['label'] }} · Post #{{ $filters['post'] }}</strong>
                <input type="hidden" name="module" value="{{ $filters['module'] }}">
                <input type="hidden" name="post" value="{{ $filters['post'] }}">
            @else
                <label for="history-module">Content area</label>
                <select id="history-module" name="module"><option value="">All assigned areas</option>
                    @foreach ($modules as $key => $module)<option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>{{ $module['label'] }}</option>@endforeach
                </select>
            @endif
        </div>
        <div class="dashboard-form-field"><label for="history-decision">Decision</label>
            <select id="history-decision" name="decision"><option value="">All decisions</option>
                <option value="approved" @selected(($filters['decision'] ?? '') === 'approved')>Approved</option>
                <option value="rejected" @selected(($filters['decision'] ?? '') === 'rejected')>Rejected</option>
            </select>
        </div>
        <div class="dashboard-form-field"><label for="history-from">From</label><input id="history-from" name="from" type="date" value="{{ $filters['from'] ?? '' }}"></div>
        <div class="dashboard-form-field"><label for="history-to">Through</label><input id="history-to" name="to" type="date" value="{{ $filters['to'] ?? '' }}"></div>
        <div class="dashboard-form-field"><label for="history-sort">Order</label>
            <select id="history-sort" name="sort"><option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option><option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option></select>
        </div>
        <div class="profile-form-actions"><button class="dashboard-button" type="submit">Apply filters</button><a class="dashboard-text-button" href="{{ route('workspace.review-history.index') }}">Clear filters</a></div>
    </form>
    <section class="dashboard-panel">
        <h2>{{ $events->total() }} {{ \Illuminate\Support\Str::plural('recorded decision', $events->total()) }}</h2>
        <p class="profile-field-help">History starts when tracking was enabled. Names and titles are snapshots from each decision. Deleted posts and posts you can no longer manage are not shown.</p>
        <x-ui.data-table label="Recorded review decisions">
            <table class="dashboard-table review-history-table">
                <thead><tr><th scope="col">Decision</th><th scope="col">Title at review</th><th scope="col">Reviewer at review</th><th scope="col">Time</th><th scope="col">Reason</th></tr></thead>
                <tbody>@forelse ($events as $event)
                    <tr>
                        <td><x-ui.status-badge :status="$event->decision" /></td>
                        <th scope="row"><a href="{{ route('workspace.posts.'.$types[$event->post_type]['key'].'.show', $event->post_id) }}">{{ $event->post_title }}</a><span class="review-history-area">{{ $types[$event->post_type]['label'] }}</span></th>
                        <td>{{ $event->reviewer_name }}</td>
                        <td><time datetime="{{ \Illuminate\Support\Carbon::parse($event->created_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($event->created_at)->format('M j, Y g:i A') }}</time></td>
                        <td>{{ $event->reason ?: '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5">No recorded decisions match this view. Try clearing filters or return after a post has been reviewed.</td></tr>
                @endforelse</tbody>
            </table>
        </x-ui.data-table>
        {{ $events->links('vendor.pagination.ssite') }}
    </section>
</div>
@endsection
