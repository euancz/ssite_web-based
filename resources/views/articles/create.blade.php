@extends('layouts.app')

@section('title', 'New Article')

@section('content')
<div class="dashboard-page workspace-composer-page" data-post-composer data-composer-submit="{{ auth()->user()->postsAutoApproved() ? 'Publish post' : 'Submit for review' }}" data-composer-hint="{{ auth()->user()->postsAutoApproved() ? 'Your post will be published when you submit.' : 'Your post will be sent for approval before it is published.' }}">
    {{-- The shared page heading and form panel follow existing dashboard spacing. --}}
    <x-ui.page-header title="Create article" subtitle="Write and prepare an article for review." />
    <x-workspace.composer-steps :step="2" />
    <p class="profile-field-help"><a href="{{ route('workspace.create') }}">Back to content types</a></p>
    <section class="dashboard-panel workspace-composer-panel" aria-label="Article form">
        {{-- SECURITY: The POST endpoint authorizes the role and sets ownership and status server-side. --}}
        @include('articles._form', [
            'article' => null,
            'action' => route('articles.store'),
            'submitLabel' => 'Create Article',
        ])
    </section>
</div>
@endsection
