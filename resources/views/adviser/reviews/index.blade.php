@extends('layouts.app')

@section('title', 'Review Posts')

@section('content')
<div class="dashboard-page">
    <x-page-header title="Review Posts" subtitle="Review submitted posts by status." />

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
        <x-data-table label="{{ ucfirst($activeStatus) }} posts">
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th scope="col">Title</th>
                        <th scope="col">Author</th>
                        <th scope="col">Type</th>
                        <th scope="col">Submitted</th>
                        <th scope="col">Status</th>
                        <th scope="col">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($posts as $post)
                        <tr>
                            <td>{{ $post->title }}</td>
                            <td>{{ $post->author?->name ?? 'Unknown author' }}</td>
                            <td>{{ ucfirst($post->type) }}</td>
                            <td>{{ $post->created_at?->format('M j, Y') }}</td>
                            <td><x-status-badge :status="$post->status" /></td>
                            <td class="dashboard-table-actions">
                                @if ($post->preview_url)
                                    <a href="{{ $post->preview_url }}" class="dashboard-text-button">Preview</a>
                                @else
                                    <button type="button" class="dashboard-text-button" disabled>Preview</button>
                                @endif
                                @if ($post->status === 'pending')
                                    <button type="button" class="dashboard-text-button" disabled>Approve</button>
                                    <button type="button" class="dashboard-text-button"
                                            data-modal-open="reject-post-modal">Reject</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="dashboard-empty-state">
                                No {{ $activeStatus }} posts. Post storage and review actions are not implemented yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </x-data-table>
    </section>

    <x-reject-modal id="reject-post-modal" />
</div>
@endsection
