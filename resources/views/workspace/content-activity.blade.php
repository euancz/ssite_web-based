@extends('layouts.app')
@section('title', 'Activity log')
@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Activity log" subtitle="See who posted, approved, and edited content you can manage." />
    <x-ui.form-errors />
    <form method="GET" action="{{ route('workspace.content-activity.index') }}" class="workspace-review-filters">
        <div class="dashboard-form-field"><label for="activity-search">Content title</label><input id="activity-search" type="search" name="q" maxlength="100" value="{{ $filters['q'] }}" placeholder="Search titles"></div>
        <div class="dashboard-form-field"><label for="activity-module">Content area</label>
            <select id="activity-module" name="module"><option value="">All assigned areas</option>@foreach ($modules as $key => $module)<option value="{{ $key }}" @selected($filters['module'] === $key)>{{ $module['label'] }}</option>@endforeach</select>
        </div>
        <div class="dashboard-form-field"><label for="activity-action">Action</label>
            <select id="activity-action" name="action"><option value="">All actions</option>@foreach (['posted' => 'Posted', 'approved' => 'Approved', 'edited' => 'Edited', 'rejected' => 'Rejected'] as $key => $label)<option value="{{ $key }}" @selected($filters['action'] === $key)>{{ $label }}</option>@endforeach</select>
        </div>
        <div class="dashboard-form-field"><label for="activity-days">Period</label>
            <select id="activity-days" name="days">@foreach ([7, 30] as $days)<option value="{{ $days }}" @selected($filters['days'] === $days)>Last {{ $days }} days</option>@endforeach</select>
        </div>
        <div class="dashboard-form-field"><label for="activity-sort">Sort by</label>
            <select id="activity-sort" name="sort"><option value="newest" @selected($filters['sort'] === 'newest')>Newest first</option><option value="oldest" @selected($filters['sort'] === 'oldest')>Oldest first</option></select>
        </div>
        <div class="profile-form-actions"><button class="dashboard-button" type="submit">Apply filters</button><a class="dashboard-text-button" href="{{ route('workspace.content-activity.index') }}">Clear filters</a></div>
    </form>
    <section class="dashboard-panel">
        <h2>Recent activity</h2>
        <p class="profile-field-help">Times use {{ config('app.timezone') }}. Posted means a submission was created; edits and reviews appear from when tracking began.</p>
        @if (! $log['historyAvailable'])<p>Review tracking is not available on this installation.</p>@endif
        @if (! $log['editsAvailable'])<p>Edit tracking is not available on this installation.</p>@endif
        <x-ui.data-table label="Content activity log">
            <table class="dashboard-table review-history-table">
                <thead><tr><th scope="col">Who</th><th scope="col">Action</th><th scope="col">Content</th><th scope="col">When</th><th scope="col">Reason</th></tr></thead>
                <tbody>
                    @forelse ($log['events'] as $event)
                        <tr>
                            <td>{{ $event->actor ?: 'Unknown user' }}</td>
                            <td>{{ ucfirst($event->action) }}</td>
                            <th scope="row"><a href="{{ route('workspace.posts.'.$event->module.'.show', $event->post_id) }}">{{ $event->title }}</a><span class="review-history-area">{{ $modules[$event->module]['label'] }}</span></th>
                            <td><time datetime="{{ \Illuminate\Support\Carbon::parse($event->occurred_at)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($event->occurred_at)->format('M j, Y g:i A') }}</time></td>
                            <td>{{ $event->reason ?: '—' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5">No activity matches these filters. Try another period or content area.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>
        {{ $log['events']->links('vendor.pagination.ssite') }}
    </section>
</div>
@endsection
