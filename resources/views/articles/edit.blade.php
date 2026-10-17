@extends('layouts.app')

@section('title', 'Edit Article')

@section('content')
<div class="dashboard-page workspace-composer-page">
    {{-- The shared page heading and form panel follow existing dashboard spacing. --}}
    <x-ui.page-header title="Edit article" subtitle="Update the article content or replace its image." />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Edit article form">
        {{-- SECURITY: ArticlePolicy and the update endpoint check ownership or President capability. --}}
        @include('articles._form', [
            'article' => $article,
            'action' => route('articles.update', $article),
            'submitLabel' => 'Save Changes',
        ])
    </section>
</div>
@endsection
