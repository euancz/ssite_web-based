@props(['post'])

<section class="workspace-review-summary" aria-label="Submission status">
    <h2>Submission status</h2>
    <p><x-ui.status-badge :status="$post->approval_status" /> @if ($post->isArchived()) <x-ui.status-badge status="archived" /> @endif</p>
    <dl>
        <div><dt>Submitted</dt><dd>{{ $post->created_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</dd></div>
        <div><dt>Last updated</dt><dd>{{ $post->updated_at?->format('M j, Y g:i A') ?? 'Not recorded' }}</dd></div>
        @unless ($post->isPending())
            <div><dt>Reviewer</dt><dd>{{ $post->reviewer?->name ?? 'Not recorded' }}</dd></div>
            <div><dt>Latest decision</dt><dd>{{ $post->reviewed_at ? \Illuminate\Support\Carbon::parse($post->reviewed_at)->format('M j, Y g:i A') : 'Not recorded' }}</dd></div>
        @endunless
    </dl>
    @if ($post->isPending())
        <p>Waiting for the President to review this submission.</p>
    @elseif ($post->isRejected())
        <p><strong>Rejection reason:</strong> {{ $post->rejection_reason ?: 'No reason was recorded.' }}</p>
    @endif
    @if ($post->isArchived())<p>This submission is archived and is not visible on the public site.</p>@endif
    <x-workspace.review-history :post="$post" />
    @can('update', $post)
        @if (app(\App\Services\ContentVersions::class)->available())
            <p><a href="{{ route('workspace.content-versions.index', ['module' => $post->getTable(), 'post' => $post->getKey()]) }}">View content version history</a></p>
        @endif
    @endcan
</section>
