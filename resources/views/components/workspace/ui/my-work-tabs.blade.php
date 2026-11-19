@props(['activeTab' => 'drafts', 'module' => null])

@php
    $workModules = collect(config('workspace.modules'))->filter(fn ($item, $key) => auth()->user()->canPostIn($key) || auth()->user()->canReview());
    $workModule = $workModules->has($module) ? $module : $workModules->keys()->first();
@endphp
<nav class="dashboard-tabs workspace-workflow-tabs workspace-list-tabs" aria-label="My work pages">
    @if (auth()->user()->canPost())
        <a href="{{ route('workspace.drafts.index', ['module' => $module]) }}" @class(['dashboard-tab', 'is-active' => $activeTab === 'drafts']) @if($activeTab === 'drafts') aria-current="page" @endif>
            <x-workspace.icon name="file" /><span>My drafts</span>
        </a>
    @endif
    @if ($workModule)
        <a href="{{ route($workModules[$workModule]['workspace'], ['tab' => 'mine']) }}" @class(['dashboard-tab', 'is-active' => $activeTab === 'mine']) @if($activeTab === 'mine') aria-current="page" @endif>
            <x-workspace.icon name="file" /><span>My submissions</span>
        </a>
    @endif
</nav>
