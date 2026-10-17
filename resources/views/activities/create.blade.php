@extends('layouts.app')

@section('title', 'New Activity')

@section('content')
<div class="dashboard-page workspace-composer-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Create activity" subtitle="Prepare an activity announcement for review." />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Activity form">
        {{-- SECURITY: The controller authorizes the role and sets ownership and status server-side. --}}
        @include('activities._form', ['activity' => null, 'action' => route('activities.store'), 'submitLabel' => 'Create Activity'])
    </section>
</div>
@endsection
