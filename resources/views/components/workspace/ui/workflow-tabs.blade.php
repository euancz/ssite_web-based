@props(['routeName', 'allowedTabs', 'activeTab', 'pendingCount' => null])

@php
    $labels = ['mine' => 'My submissions', 'published' => 'Published', 'pending' => 'Pending review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All posts'];
@endphp
<nav class="dashboard-tabs workspace-workflow-tabs" aria-label="Submission status">
    @foreach ($allowedTabs as $tab)
        <a href="{{ route($routeName, ['tab' => $tab]) }}" data-feed-tab
           @class(['dashboard-tab', 'is-active' => $activeTab === $tab])
           @if ($activeTab === $tab) aria-current="page" @endif>
            {{ $labels[$tab] }}
            @if ($tab === 'pending' && $pendingCount !== null)<span class="dashboard-tab-count">{{ $pendingCount }}</span>@endif
        </a>
    @endforeach
</nav>
