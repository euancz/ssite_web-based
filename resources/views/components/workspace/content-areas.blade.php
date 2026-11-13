@props(['modules'])
<section class="workspace-content-areas" aria-labelledby="workspace-areas-title">
    <div class="workspace-section-heading">
        <div><span class="workspace-eyebrow">THE BIG PICTURE</span><h2 id="workspace-areas-title">Your content areas</h2></div>
        <a class="dashboard-text-button" href="{{ route('workspace.content-activity.index') }}">Activity log <x-workspace.icon name="arrow-right" /></a>
    </div>
    <p class="workspace-section-description">{{ auth()->user()->canReview() ? 'Track publishing across the organization.' : 'Track your submissions in each assigned area.' }}</p>
    <div class="workspace-area-grid">
        @foreach ($modules as $key => $module)
            <article class="workspace-area-card">
                <div class="workspace-area-heading"><span class="workspace-area-icon"><x-workspace.icon :name="config('workspace.modules.'.$key.'.icon')" /></span><h3>{{ $module['label'] }}</h3></div>
                <dl>
                    <div><dt>Published</dt><dd>{{ number_format($module['published']) }}</dd></div>
                    <div><dt>Pending</dt><dd>{{ number_format($module['pending']) }}</dd></div>
                    <div><dt>Rejected</dt><dd>{{ number_format($module['rejected']) }}</dd></div>
                </dl>
                <div class="workspace-area-actions">
                    <a href="{{ route($module['index'], ['tab' => auth()->user()->canReview() ? 'published' : 'mine']) }}">Open {{ strtolower($module['label']) }} <x-workspace.icon name="arrow-right" /></a>
                    <a href="{{ route($module['create']) }}" aria-label="Create {{ strtolower(config('workspace.modules.'.$key.'.singular')) }}"><x-workspace.icon name="plus" /></a>
                </div>
            </article>
        @endforeach
    </div>
</section>
