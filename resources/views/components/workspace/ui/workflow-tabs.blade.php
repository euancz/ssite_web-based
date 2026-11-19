@props(['routeName', 'allowedTabs', 'activeTab', 'pendingCount' => null])

@php
    $labels = ['mine' => 'My submissions', 'published' => 'Published', 'pending' => 'Pending review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All posts'];
    $icons = ['mine' => 'file', 'published' => 'check', 'pending' => 'clock', 'rejected' => 'x', 'archived' => 'archive', 'all' => 'grid'];
    $tabs = array_intersect(['mine', 'published', 'pending', 'archived', 'rejected', 'all'], $allowedTabs);
@endphp
<nav class="dashboard-tabs workspace-workflow-tabs workspace-list-tabs" aria-label="Submission status">
    @foreach ($tabs as $tab)
        <a href="{{ route($routeName, ['tab' => $tab]) }}" data-feed-tab
           @class(['dashboard-tab', 'is-active' => $activeTab === $tab])
           @if ($activeTab === $tab) aria-current="page" @endif>
            <x-workspace.icon :name="$icons[$tab]" /><span>{{ $labels[$tab] }}</span>
            @if ($tab === 'pending' && $pendingCount > 0)<span class="dashboard-tab-count">{{ $pendingCount }}</span>@endif
        </a>
    @endforeach
</nav>
