@extends('layouts.app')

@section('title', 'New Article')

@section('content')
<div class="dashboard-page">
    {{-- The shared page heading and form panel follow existing dashboard spacing. --}}
    <x-ui.page-header title="New Article" subtitle="Write an article for the SSITE community." />
    <section class="dashboard-panel">
        {{-- SECURITY: The POST endpoint authorizes the role and sets ownership and status server-side. --}}
        @include('articles._form', [
            'article' => null,
            'action' => route('articles.store'),
            'submitLabel' => 'Create Article',
        ])
    </section>
</div>
@endsection
