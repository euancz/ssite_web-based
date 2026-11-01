@extends('layouts.workspace')

@section('title', 'Create post')

@section('content')
<div class="dashboard-page workspace-composer-page">
    <x-ui.page-header title="Create post" subtitle="Choose the type of post you want to create." />
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
