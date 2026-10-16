@extends('layouts.app')

@section('title', 'Review Posts')

@section('content')
<div class="dashboard-page">
    <x-ui.page-header title="Review Posts" subtitle="Review submitted posts by status." />

    {{-- Keep the selected status in the URL so the President can share or refresh a filtered queue. --}}
    <nav class="dashboard-tabs" aria-label="Filter posts by status">
        @foreach (['pending' => 'Pending', 'approved' => 'Approved', 'rejected' => 'Rejected'] as $status => $label)
            <a href="{{ route('adviser.reviews.index', ['status' => $status]) }}"
               @class(['dashboard-tab', 'is-active' => $activeStatus === $status])
               @if ($activeStatus === $status) aria-current="page" @endif>
                {{ $label }}
            </a>
        @endforeach
    </nav>

    <section class="dashboard-panel">
        <x-ui.data-table label="{{ ucfirst($activeStatus) }} posts">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Author</th>
                        <th scope="col">Type</th>
                        <th scope="col">Submitted</th>
                        <th scope="col">Status</th>
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
                            <td><x-ui.status-badge :status="$post->status" /></td>
                            @if ($activeStatus === 'rejected')
                                <td>{{ $post->rejection_reason ?: 'No reason was recorded.' }}</td>
                            @endif
                            <td class="dashboard-table-actions">
                                {{-- Preview and review actions depend on URLs supplied by the post backend. --}}
                                <a href="{{ $post->preview_url }}" class="dashboard-text-button">Preview</a>
                                @if ($post->status === 'pending')
                                    <form method="POST" action="{{ $post->approve_url }}">@csrf<button type="submit" class="dashboard-button">Approve</button></form>
                                    <button type="button" class="dashboard-button dashboard-button-secondary" data-modal-open="reject-post-{{ $post->post_type }}-{{ $post->post_id }}">Reject</button>
                                    <x-ui.reject-modal id="reject-post-{{ $post->post_type }}-{{ $post->post_id }}" :action="$post->reject_url" :subject="strtolower($post->type)" />
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $activeStatus === 'rejected' ? 7 : 6 }}" class="dashboard-empty-state">
                                No {{ $activeStatus }} posts.
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
