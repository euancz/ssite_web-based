@extends('layouts.app')

@section('title', 'Edit Article')

@section('content')
<div class="dashboard-page">
    {{-- The shared page heading and form panel follow existing dashboard spacing. --}}
    <x-page-header title="Edit Article" subtitle="Update the article content or replace its image." />
    <section class="dashboard-panel">
        {{-- SECURITY: ArticlePolicy and the update endpoint both check ownership or adviser role. --}}
        @include('articles._form', [
            'article' => $article,
            'action' => route('articles.update', $article),
            'submitLabel' => 'Save Changes',
        ])
    </section>
</div>
@endsection
