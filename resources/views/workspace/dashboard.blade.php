@extends(request()->header('X-SSITE-Page-Data') === '1' ? 'layouts.page-data' : 'layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="workspace-dashboard workspace-president-dashboard">
    <x-workspace.welcome description="Review submissions and manage published content." />
    <x-workspace.dashboard-summary :status="$dashboardStatus ?? []" :draft-count="$draftCount ?? null" :reviewer="true" :pending="$pageDataPending ?? false" />

    <section class="workspace-officer-recent" aria-labelledby="workspace-pending-title">
        <div class="workspace-officer-section-heading">
            <div><h2 id="workspace-pending-title">Pending review</h2><p>Oldest submissions first.</p></div>
            <a class="dashboard-text-button" href="{{ route('adviser.reviews.index', ['status' => 'pending', 'sort' => 'oldest']) }}">Open review queue</a>
        </div>
        <x-ui.data-table label="Oldest pending submissions">
            <table class="dashboard-table">
                <thead><tr><th scope="col">Submission</th><th scope="col">Author</th><th scope="col">Submitted</th><th scope="col">Review</th></tr></thead>
                <tbody data-page-data="submissions" @if ($pageDataPending ?? false) aria-busy="true" @endif>
                    @if ($pageDataPending ?? false)
                        <x-ui.data-skeleton :columns="4" />
                    @else
                    @forelse ($pendingPosts as $post)
                        <tr>
                            <th scope="row">{{ $post->title }} <small>{{ $post->type }}</small>@if ($post->isArchived()) <x-ui.status-badge status="archived" /> @endif</th>
                            <td>{{ $post->author?->name ?? 'Former member' }}</td>
                            <td>{{ $post->created_at?->format('M j, Y') ?? 'Not recorded' }}</td>
                            <td><a class="dashboard-text-button" href="{{ $post->preview_url }}">Preview <span class="visually-hidden">{{ $post->title }}</span></a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="dashboard-empty-state">The review queue is clear. New submissions will appear here.</td></tr>
                    @endforelse
                    @endif
                </tbody>
            </table>
        </x-ui.data-table>
    </section>

    <x-workspace.content-areas :modules="$moduleStats" />
</div>
@endsection
