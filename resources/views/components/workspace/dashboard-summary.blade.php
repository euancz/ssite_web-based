@props(['status', 'draftCount', 'reviewer' => false])

<section class="workspace-dashboard-summary" aria-label="{{ $reviewer ? 'Content overview' : 'Your content' }}">
    @if ($reviewer)
        <a href="{{ route('adviser.reviews.index', ['status' => 'pending']) }}"><span>Pending review</span><strong>{{ $status['pending'] }}</strong></a>
    @else
        <div><span>Pending review</span><strong>{{ $status['pending'] }}</strong></div>
    @endif
    <div><span>Published</span><strong>{{ $status['published'] }}</strong></div>
    <a href="{{ route('workspace.drafts.index') }}"><span>My drafts</span><strong>{{ $draftCount }}</strong></a>
</section>
