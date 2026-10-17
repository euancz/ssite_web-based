@extends('layouts.app')

@section('title', 'New Article')

@section('content')
<div class="dashboard-page workspace-composer-page">
    {{-- The shared page heading and form panel follow existing dashboard spacing. --}}
    <x-ui.page-header title="Create article" subtitle="Write and prepare an article for review." />
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
