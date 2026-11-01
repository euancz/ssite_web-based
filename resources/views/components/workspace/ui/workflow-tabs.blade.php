@props(['routeName', 'allowedTabs', 'activeTab', 'pendingCount' => null])

@php
    $labels = ['mine' => 'My submissions', 'published' => 'Published', 'pending' => 'Pending review', 'rejected' => 'Rejected', 'archived' => 'Archived', 'all' => 'All posts'];
    $icons = ['mine' => 'file', 'published' => 'check', 'pending' => 'clock', 'rejected' => 'x', 'archived' => 'archive', 'all' => 'grid'];
    $primaryTabs = array_values(array_intersect($allowedTabs, ['mine', 'published', 'pending', 'archived']));
    $secondaryTabs = array_values(array_diff($allowedTabs, $primaryTabs));
    $selectedSecondary = in_array($activeTab, $secondaryTabs, true);
@endphp
<nav class="dashboard-tabs workspace-workflow-tabs" aria-label="Submission status">
    @foreach ($primaryTabs as $tab)
        <a href="{{ route($routeName, ['tab' => $tab]) }}" data-feed-tab
           @class(['dashboard-tab', 'is-active' => $activeTab === $tab])
           @if ($activeTab === $tab) aria-current="page" @endif>
            <x-workspace.icon :name="$icons[$tab]" /><span>{{ $labels[$tab] }}</span>
            @if ($tab === 'pending' && $pendingCount > 0)<span class="dashboard-tab-count">{{ $pendingCount }}</span>@endif
        </a>
    @endforeach
    @if ($secondaryTabs)
        <details class="dashboard-tabs-more workspace-status-menu">
            <summary @class(['dashboard-tab', 'is-active' => $selectedSecondary]) aria-expanded="false">
                @if ($selectedSecondary)
                    <x-workspace.icon :name="$icons[$activeTab]" /><span>{{ $labels[$activeTab] }}</span>
                @else
                    <x-workspace.icon name="grid" /><span>More</span>
                @endif
            </summary>
            <div class="dashboard-tabs-menu">
                @foreach ($secondaryTabs as $tab)
                    <a href="{{ route($routeName, ['tab' => $tab]) }}" data-feed-tab
                       @if ($activeTab === $tab) aria-current="page" @endif>{{ $labels[$tab] }}</a>
                @endforeach
            </div>
        </details>
    @endif
</nav>
