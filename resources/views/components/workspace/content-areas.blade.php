@props(['modules'])
<section class="workspace-content-areas" aria-labelledby="workspace-areas-title">
    <div class="workspace-section-heading">
        <h2 id="workspace-areas-title">Your content areas</h2>
        <a class="dashboard-text-button" href="{{ route('workspace.content-activity.index') }}">Activity log <x-workspace.icon name="arrow-right" /></a>
    </div>
    <div class="workspace-area-shortcuts">
        @foreach ($modules as $key => $module)
            <div class="workspace-area-shortcut">
                <a href="{{ route($module['index'], ['tab' => auth()->user()->canReview() ? 'published' : 'mine']) }}">
                    <x-workspace.icon :name="config('workspace.modules.'.$key.'.icon')" />
                    <span>{{ $module['label'] }}</span>
                </a>
                @if (auth()->user()->canPostIn($key))
                    <a href="{{ route($module['create']) }}" aria-label="Create {{ strtolower(config('workspace.modules.'.$key.'.singular')) }}"><x-workspace.icon name="plus" /></a>
                @endif
            </div>
        @endforeach
    </div>
</section>
