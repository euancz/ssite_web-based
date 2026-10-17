@extends('layouts.app')

@section('title', 'Edit Activity')

@section('content')
<div class="dashboard-page workspace-composer-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Edit activity" subtitle="Update activity details or replace its image." />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Edit activity form">
        {{-- SECURITY: ActivityPolicy and the update action check ownership or President capability. --}}
        @include('activities._form', ['activity' => $activity, 'action' => route('activities.update', $activity), 'submitLabel' => 'Save Changes'])
    </section>
</div>
@endsection
