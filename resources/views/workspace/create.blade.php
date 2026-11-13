@extends('layouts.workspace')

@section('title', 'Create post')

@section('content')
<div class="dashboard-page workspace-composer-page">
    <x-ui.page-header title="Create post" subtitle="Choose the type of post you want to create." />
    <section class="workspace-create-intro" aria-labelledby="workspace-create-intro-title">
        <div><h2 id="workspace-create-intro-title">Post type</h2><p>Choose a format. You can save a draft and finish it later.</p></div>
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
    <ol class="workspace-publishing-steps" aria-label="Publishing workflow">
        <li><b aria-hidden="true">01</b><div><strong>Add content</strong><p>Add your text, details, and supporting files in the editor.</p></div></li>
        <li><b aria-hidden="true">02</b><div><strong>Check the details</strong><p>Preview your content and verify titles, dates, and attachments.</p></div></li>
        <li><b aria-hidden="true">03</b><div><strong>{{ auth()->user()->canReview() ? 'Publish' : 'Submit for review' }}</strong><p>{{ auth()->user()->canReview() ? 'Publish your content when it is ready for the community.' : 'Follow its status and feedback in My submissions.' }}</p></div></li>
    </ol>
</div>
@endsection
