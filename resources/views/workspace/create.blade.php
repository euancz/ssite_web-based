@extends('layouts.workspace')

@section('title', 'Create post')

@section('content')
<div class="dashboard-page workspace-composer-page">
    <x-ui.page-header title="Create post" subtitle="Start with a content type. We will guide you through the rest." />
    <x-workspace.composer-steps :step="1" />
    <section class="workspace-create-intro" aria-labelledby="workspace-create-intro-title">
        <div><h2 id="workspace-create-intro-title">What would you like to post?</h2><p>Step 1 of 3: Choose a type to continue to its details.</p></div>
        <a class="dashboard-button dashboard-button-secondary" href="{{ route('workspace.drafts.index') }}"><x-workspace.icon name="file" /> Resume a draft</a>
    </section>
    <section class="dashboard-panel workspace-post-chooser" aria-label="Post type">
        @foreach ($modules as $key => $module)
            <a class="workspace-post-choice" href="{{ route($module['create']) }}">
                <x-workspace.icon :name="$module['icon']" />
                <span><strong>{{ $module['singular'] }}</strong><small>{{ match ($key) {
                    'articles' => 'Share news, stories, and updates.',
                    'activities' => 'Announce an activity or event.',
                    'achievements' => 'Celebrate an achievement or milestone.',
                    'documents' => 'Upload a document and its details.',
                    'liquidations' => 'Submit a financial report and supporting PDF.',
                } }}</small></span>
                <x-icon name="arrow-right" />
            </a>
        @endforeach
    </section>
</div>
@endsection
