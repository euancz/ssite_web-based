@extends('layouts.app')

@section('title', 'Edit Activity')

@section('content')
<div class="dashboard-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Edit Activity" subtitle="Update the activity details or replace its image." />
    <section class="dashboard-panel">
        {{-- SECURITY: ActivityPolicy and the update action both check ownership or adviser role. --}}
        @include('activities._form', ['activity' => $activity, 'action' => route('activities.update', $activity), 'submitLabel' => 'Save Changes'])
    </section>
</div>
@endsection
