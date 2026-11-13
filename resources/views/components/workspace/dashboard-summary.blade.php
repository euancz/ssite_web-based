@props(['status', 'draftCount', 'reviewer' => false])
@php
    $metrics = [
        ['label' => 'Pending review', 'value' => $status['pending'], 'tone' => 'pending', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'pending']) : null],
        ['label' => 'Published', 'value' => $status['published'], 'tone' => 'published', 'url' => null],
        ['label' => 'My drafts', 'value' => $draftCount, 'tone' => 'draft', 'url' => route('workspace.drafts.index')],
        ['label' => 'Rejected', 'value' => $status['rejected'], 'tone' => 'rejected', 'url' => null],
    ];
@endphp
<section class="workspace-dashboard-summary" aria-label="{{ $reviewer ? 'Content overview' : 'Your content' }}">
    @foreach ($metrics as $metric)
        <article class="workspace-stat workspace-stat--{{ $metric['tone'] }}">
            <span class="workspace-stat-label">{{ $metric['label'] }}</span>
            <strong>{{ number_format($metric['value']) }}</strong>
            @if ($metric['url'])
                <a class="workspace-stat-link" href="{{ $metric['url'] }}" aria-label="Open {{ strtolower($metric['label']) }}"><x-workspace.icon name="arrow-right" /></a>
            @endif
        </article>
    @endforeach
</section>
