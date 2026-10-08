@extends('layouts.app')

@section('title', 'New Activity')

@section('content')
<div class="dashboard-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="New Activity" subtitle="Share an activity with the SSITE community." />
    <section class="dashboard-panel">
        {{-- SECURITY: The controller authorizes the role and sets ownership and status server-side. --}}
        @include('activities._form', ['activity' => null, 'action' => route('activities.store'), 'submitLabel' => 'Create Activity'])
    </section>
</div>
@endsection
