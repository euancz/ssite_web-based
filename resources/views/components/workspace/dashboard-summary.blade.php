@props(['status' => [], 'draftCount' => null, 'reviewer' => false, 'pending' => false])
@php
    $metrics = [
        ['label' => 'Pending review', 'value' => $status['pending'] ?? null, 'tone' => 'pending', 'icon' => 'clock', 'hint' => 'Awaiting review', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'pending']) : null],
        ['label' => 'Published', 'value' => $status['published'] ?? null, 'tone' => 'published', 'icon' => 'check', 'hint' => 'Visible on the student site', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'approved', 'visibility' => 'active']) : null],
        ['label' => 'My drafts', 'value' => $draftCount, 'tone' => 'draft', 'icon' => 'file', 'hint' => 'Continue writing', 'url' => route('workspace.drafts.index')],
        ['label' => 'Rejected', 'value' => $status['rejected'] ?? null, 'tone' => 'rejected', 'icon' => 'x', 'hint' => 'Check reviewer feedback', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'rejected']) : null],
    ];
@endphp
<section class="workspace-dashboard-summary" aria-label="{{ $reviewer ? 'Content overview' : 'Your content' }}">
    @foreach ($metrics as $metric)
        <article class="workspace-stat workspace-stat--{{ $metric['tone'] }}">
            <span class="workspace-stat-icon" aria-hidden="true"><x-workspace.icon :name="$metric['icon']" /></span>
            <span class="workspace-stat-label">{{ $metric['label'] }}</span>
            <strong data-page-data="stat-{{ $metric['tone'] }}" @if ($pending) aria-busy="true" @endif>
                @if ($pending)
                    <span class="skeleton-block page-data-skeleton-value" aria-hidden="true"></span>
                @else
                    {{ number_format($metric['value']) }}
                @endif
            </strong>
            <small class="workspace-stat-hint">{{ $metric['hint'] }}</small>
            @if ($metric['url'])
                <a class="workspace-stat-link" href="{{ $metric['url'] }}" aria-label="Open {{ strtolower($metric['label']) }}"><x-workspace.icon name="arrow-right" /></a>
            @endif
        </article>
    @endforeach
</section>
