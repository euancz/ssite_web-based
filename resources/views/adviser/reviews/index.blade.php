@extends('layouts.app')

@section('title', 'Review Posts')

@section('content')
<div class="dashboard-page president-review-page">
    <x-ui.page-header title="Review queue" subtitle="Check submissions, preview their details, and record a decision." />

    {{-- Keep the selected status in the URL so the President can share or refresh a filtered queue. --}}
    <nav class="dashboard-tabs" aria-label="Filter posts by status">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $status => $label)
            <a href="{{ route('adviser.reviews.index', array_merge($filters, ['status' => $status])) }}"
               @class(['dashboard-tab', 'is-active' => $activeStatus === $status])
               @if ($activeStatus === $status) aria-current="page" @endif>
                {{ $label }} ({{ number_format($statusCounts[$status] ?? 0) }})
            </a>
        @endforeach
    </nav>

    <x-ui.form-errors title="Please check your filters" message="Correct the filter values and try again." />
    <form class="workspace-review-filters" method="GET" action="{{ route('adviser.reviews.index') }}" aria-label="Filter review queue">
        <input type="hidden" name="status" value="{{ $activeStatus }}">
        <div class="dashboard-form-field workspace-review-search">
            <label for="review-search">Title or author</label>
            <input id="review-search" type="search" name="q" maxlength="100" value="{{ $filters['q'] }}" placeholder="Search submissions">
        </div>
        <div class="dashboard-form-field">
            <label for="review-module">Content area</label>
            <select id="review-module" name="module">
                <option value="">All content areas</option>
                @foreach ($modules as $key => $module)<option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>{{ $module['label'] }}</option>@endforeach
            </select>
        </div>
        <div class="dashboard-form-field">
            <label for="review-visibility">Visibility</label>
            <select id="review-visibility" name="visibility">
                <option value="">Active and archived</option>
                <option value="active" @selected(($filters['visibility'] ?? '') === 'active')>Active</option>
                <option value="archived" @selected(($filters['visibility'] ?? '') === 'archived')>Archived</option>
            </select>
        </div>
        <div class="dashboard-form-field">
            <label for="review-from">Submitted from</label>
            <input id="review-from" type="date" name="from" value="{{ $filters['from'] ?? '' }}">
        </div>
        <div class="dashboard-form-field">
            <label for="review-to">Submitted through</label>
            <input id="review-to" type="date" name="to" value="{{ $filters['to'] ?? '' }}">
        </div>
        <div class="dashboard-form-field">
            <label for="review-sort">Sort by</label>
            <select id="review-sort" name="sort">
                @foreach (['oldest' => 'Oldest submission', 'recent' => 'Newest submission', 'reviewed' => 'Latest decision'] as $key => $label)
                    <option value="{{ $key }}" @selected($filters['sort'] === $key)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="profile-form-actions">
            <button type="submit" class="dashboard-button">Apply filters</button>
            <a class="dashboard-button dashboard-button-secondary" href="{{ route('adviser.reviews.index', ['status' => $activeStatus]) }}">Clear filters</a>
        </div>
    </form>
    <p class="profile-field-help">Counts reflect these filters. Approved and rejected tabs show the latest saved decision for each submission.</p>

    <section class="dashboard-panel" aria-label="{{ ucfirst($activeStatus) }} submissions">
        <p class="workspace-review-list-summary">{{ $posts->total() }} {{ \Illuminate\Support\Str::plural('submission', $posts->total()) }} · {{ ucfirst($activeStatus) }}</p>
        <x-ui.data-table label="{{ ucfirst($activeStatus) }} posts">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Author</th>
                        <th scope="col">Type</th>
                        <th scope="col">Submitted</th>
                        <th scope="col">Status</th>
                        @if ($activeStatus !== 'pending')<th scope="col">Latest decision</th>@endif
                        @if ($activeStatus === 'rejected')<th scope="col">Rejection reason</th>@endif
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>{{ $post->title }}</td>
                            <td>{{ $post->author?->name ?? 'Unknown author' }}</td>
                            <td>{{ $post->type_label }}</td>
                            <td>{{ $post->created_at ? \Illuminate\Support\Carbon::parse($post->created_at)->format('M j, Y') : '—' }}</td>
                            <td><x-ui.status-badge :status="$post->status" /> @if ($post->content_status === 'archived') <x-ui.status-badge status="archived" /> @endif</td>
                            @if ($activeStatus !== 'pending')
                                <td>{{ $post->reviewer?->name ?? 'Reviewer not recorded' }}<br>
                                    {{ $post->reviewed_at ? \Illuminate\Support\Carbon::parse($post->reviewed_at)->format('M j, Y g:i A') : 'Decision date not recorded' }}
                                </td>
                            @endif
                            @if ($activeStatus === 'rejected')
                                <td>{{ $post->rejection_reason ?: 'No reason was recorded.' }}</td>
                            @endif
                            <td class="dashboard-table-actions">
                                {{-- Preview and review actions depend on URLs supplied by the post backend. --}}
                                <a href="{{ $post->preview_url }}" class="dashboard-text-button"><x-workspace.icon name="external" />Preview</a>
                                @if ($post->status === 'pending')
                                    <form method="POST" action="{{ $post->approve_url }}">@csrf<button type="submit" class="dashboard-button"><x-workspace.icon name="check" />Approve</button></form>
                                    <button type="button" class="dashboard-button dashboard-button-secondary" data-modal-open="reject-post-{{ $post->post_type }}-{{ $post->post_id }}">Reject</button>
                                    <x-ui.reject-modal id="reject-post-{{ $post->post_type }}-{{ $post->post_id }}" :action="$post->reject_url" :subject="strtolower($post->type_label)" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + ($activeStatus !== 'pending' ? 1 : 0) + ($activeStatus === 'rejected' ? 1 : 0) }}" class="dashboard-empty-state">
                                @if ($activeStatus === 'pending')
                                    No pending submissions match these filters. Clear the filters to check the full queue.
                                @else
                                    No {{ $activeStatus }} submissions to show.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-ui.data-table>
        <div class="dashboard-pagination">{{ $posts->links('vendor.pagination.ssite') }}</div>
    </section>
</div>
@endsection
