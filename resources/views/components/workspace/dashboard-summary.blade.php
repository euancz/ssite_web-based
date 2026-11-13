@props(['status', 'draftCount', 'reviewer' => false])
@php
    $metrics = [
        ['label' => 'Pending review', 'value' => $status['pending'], 'icon' => 'clock', 'tone' => 'pending', 'hint' => $reviewer ? 'Ready for your review' : 'Awaiting a review decision', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'pending']) : null],
        ['label' => 'Published', 'value' => $status['published'], 'icon' => 'check', 'tone' => 'published', 'hint' => 'Approved and visible on the site', 'url' => null],
        ['label' => 'My drafts', 'value' => $draftCount, 'icon' => 'file', 'tone' => 'draft', 'hint' => 'Pick up where you left off', 'url' => route('workspace.drafts.index')],
        ['label' => 'Rejected', 'value' => $status['rejected'], 'icon' => 'circle-alert', 'tone' => 'rejected', 'hint' => $reviewer ? 'Returned with review feedback' : 'Check feedback before resubmitting', 'url' => null],
    ];
@endphp
<section class="workspace-dashboard-summary" aria-label="{{ $reviewer ? 'Content overview' : 'Your content' }}">
    @foreach ($metrics as $metric)
        <article class="workspace-stat workspace-stat--{{ $metric['tone'] }}">
            <span class="workspace-stat-icon"><x-workspace.icon :name="$metric['icon']" /></span>
            <span class="workspace-stat-label">{{ $metric['label'] }}</span>
            <strong>{{ number_format($metric['value']) }}</strong>
            <small>{{ $metric['hint'] }}</small>
            @if ($metric['url'])
                <a class="workspace-stat-link" href="{{ $metric['url'] }}" aria-label="Open {{ strtolower($metric['label']) }}"><x-workspace.icon name="arrow-right" /></a>
            @endif
        </article>
    @endforeach
</section>
