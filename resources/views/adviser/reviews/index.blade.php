@extends('layouts.app')

@section('title', 'Review queue')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/post-feed.css') }}">
@endpush

@section('content')
@php
    $statusTitle = match ($activeStatus) {
        'pending' => 'Pending review',
        'approved' => 'Approved submissions',
        'rejected' => 'Rejected submissions',
    };
    $defaultSort = $activeStatus === 'pending' ? 'oldest' : 'reviewed';
    $hasDateFilter = !empty($filters['from']) || !empty($filters['to']);
    $additionalFilterCount = (int) !empty($filters['visibility']) + (int) ($filters['sort'] !== $defaultSort);
    $hasFilters = $filters['q'] !== '' || !empty($filters['module']) || $additionalFilterCount > 0 || $hasDateFilter;
@endphp
<div class="post-feed-shell workspace-library-feed president-review-page">
    <x-workspace.posts.list-heading context="Review queue" :title="$statusTitle"
        :description="$activeStatus === 'pending' ? 'Preview submissions and record a decision.' : 'Showing the latest saved decision for each submission.'" />

    {{-- Keep the selected status in the URL so the President can share or refresh a filtered queue. --}}
    <nav class="dashboard-tabs workspace-workflow-tabs workspace-list-tabs" aria-label="Submission status">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $status => $label)
            <a href="{{ route('adviser.reviews.index', array_merge($filters, ['status' => $status])) }}"
               @class(['dashboard-tab', 'is-active' => $activeStatus === $status])
               @if ($activeStatus === $status) aria-current="page" @endif>
                <x-workspace.icon :name="['pending' => 'clock', 'approved' => 'check', 'rejected' => 'x'][$status]" />
                <span>{{ $label }}</span>
                <span class="dashboard-tab-count">{{ number_format($statusCounts[$status] ?? 0) }}</span>
            </a>
        @endforeach
    </nav>

    <x-ui.form-errors title="Please check your filters" message="Correct the filter values and try again." />
    <form class="workspace-list-toolbar" method="GET" action="{{ route('adviser.reviews.index') }}" aria-label="Filter review queue">
        <input type="hidden" name="status" value="{{ $activeStatus }}">
        @foreach (['from', 'to'] as $dateFilter)
            @if (!empty($filters[$dateFilter]))
                <input type="hidden" name="{{ $dateFilter }}" value="{{ $filters[$dateFilter] }}">
            @endif
        @endforeach
        <div class="workspace-list-field">
            <label for="review-module">Content type</label>
            <select id="review-module" name="module">
                <option value="">All content areas</option>
                @foreach ($modules as $key => $module)<option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>{{ $module['label'] }}</option>@endforeach
            </select>
        </div>
        <x-workspace.ui.list-search id="review-search" label="Search submissions" :value="$filters['q']" placeholder="Title or author" />
        <div class="workspace-list-filter-actions">
            @if ($hasFilters)
                <a class="dashboard-text-button" href="{{ route('adviser.reviews.index', ['status' => $activeStatus]) }}">Clear filters</a>
            @endif
            <x-posts.filter-panel :active-count="$additionalFilterCount">
                <div class="site-filter-field">
                    <label for="review-visibility">Visibility</label>
                    <select id="review-visibility" name="visibility">
                        <option value="">Active and archived</option>
                        <option value="active" @selected(($filters['visibility'] ?? '') === 'active')>Active</option>
                        <option value="archived" @selected(($filters['visibility'] ?? '') === 'archived')>Archived</option>
                    </select>
                </div>
                <div class="site-filter-field">
                    <label for="review-sort">Sort by</label>
                    <select id="review-sort" name="sort">
                        @foreach (['oldest' => 'Oldest submission first', 'recent' => 'Newest submission first', 'reviewed' => 'Recently reviewed'] as $value => $label)
                            <option value="{{ $value }}" @selected($filters['sort'] === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </x-posts.filter-panel>
        </div>
    </form>
    <section class="dashboard-panel" aria-label="{{ $statusTitle }}">
        <p class="workspace-review-list-summary">{{ number_format($posts->total()) }} {{ \Illuminate\Support\Str::plural('submission', $posts->total()) }}</p>
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
                            <th scope="row">{{ $post->title }}</th>
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
                            <td>
                                @if ($post->status === 'pending')
                                    <x-ui.table-actions id="review-actions-{{ $post->post_type }}-{{ $post->post_id }}" label="Actions for {{ $post->title }}">
                                        <a href="{{ $post->preview_url }}">Preview</a>
                                        <form method="POST" action="{{ $post->approve_url }}">
                                            @csrf
                                            <button type="submit">Approve</button>
                                        </form>
                                        <button type="button" data-modal-open="reject-post-{{ $post->post_type }}-{{ $post->post_id }}">Reject</button>
                                    </x-ui.table-actions>
                                    <x-ui.reject-modal id="reject-post-{{ $post->post_type }}-{{ $post->post_id }}" :action="$post->reject_url" :subject="strtolower($post->type_label)" />
                                @else
                                    <a href="{{ $post->preview_url }}" class="dashboard-text-button"><x-workspace.icon name="external" />Preview</a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ 6 + ($activeStatus !== 'pending' ? 1 : 0) + ($activeStatus === 'rejected' ? 1 : 0) }}" class="dashboard-empty-state">
                                @if ($hasFilters)
                                    No {{ $activeStatus }} submissions match these filters.
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
