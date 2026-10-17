@extends('layouts.app')

@section('title', 'Edit Achievement')

@section('content')
<div class="dashboard-page workspace-composer-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Edit achievement" subtitle="Update achievement details or replace its image." />
    <section class="dashboard-panel workspace-composer-panel" aria-label="Edit achievement form">
        {{-- SECURITY: AchievementPolicy and the update action check ownership or President capability. --}}
        @include('achievements._form', ['achievement' => $achievement, 'action' => route('achievements.update', $achievement), 'submitLabel' => 'Save Changes'])
    </section>
</div>
@endsection
