@props(['status' => [], 'draftCount' => null, 'reviewer' => false, 'pending' => false, 'metrics' => null, 'label' => null])
@php
    $metrics ??= [
        'stat-pending' => ['label' => 'Pending review', 'value' => $status['pending'] ?? null, 'icon' => 'clock', 'hint' => 'Awaiting review', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'pending']) : null],
        'stat-published' => ['label' => 'Published', 'value' => $status['published'] ?? null, 'icon' => 'check', 'hint' => 'Visible on the student site', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'approved', 'visibility' => 'active']) : null],
        'stat-draft' => ['label' => 'My drafts', 'value' => $draftCount, 'icon' => 'file', 'hint' => 'Continue writing', 'url' => route('workspace.drafts.index')],
        'stat-rejected' => ['label' => 'Rejected', 'value' => $status['rejected'] ?? null, 'icon' => 'x', 'hint' => 'Check reviewer feedback', 'url' => $reviewer ? route('adviser.reviews.index', ['status' => 'rejected']) : null],
    ];
@endphp
<section {{ $attributes->class('workspace-dashboard-summary') }} aria-label="{{ $label ?? ($reviewer ? 'Content overview' : 'Your content') }}">
    @foreach ($metrics as $name => $metric)
        <article class="workspace-stat">
            <span class="workspace-stat-icon" aria-hidden="true"><x-workspace.icon :name="$metric['icon']" /></span>
            <span class="workspace-stat-label">{{ $metric['label'] }}</span>
            <strong data-page-data="{{ $name }}" @if ($pending) aria-busy="true" @endif>
                @if ($pending)
                    <span class="skeleton-block page-data-skeleton-value" aria-hidden="true"></span>
                @else
                    {{ number_format($metric['value']) }}
                @endif
            </strong>
            @isset($metric['hint'])
                <small class="workspace-stat-hint">{{ $metric['hint'] }}</small>
            @endisset
            @isset($metric['url'])
                <a class="workspace-stat-link" href="{{ $metric['url'] }}" aria-label="Open {{ strtolower($metric['label']) }}"><x-workspace.icon name="arrow-right" /></a>
            @endisset
        </article>
    @endforeach
</section>
