@extends('layouts.app')

@section('title', 'New Achievement')

@section('content')
<div class="dashboard-page workspace-composer-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Create achievement" subtitle="Prepare an achievement for the SSITE community." />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Achievement form">
        {{-- SECURITY: The controller authorizes the role and sets ownership and status server-side. --}}
        @include('achievements._form', ['achievement' => null, 'action' => route('achievements.store'), 'submitLabel' => 'Create Achievement'])
    </section>
</div>
@endsection
