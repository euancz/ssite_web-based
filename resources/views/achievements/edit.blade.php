@extends('layouts.app')

@section('title', 'Edit Achievement')

@section('content')
<div class="dashboard-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="Edit Achievement" subtitle="Update the achievement details or replace its image." />
    <section class="dashboard-panel">
        {{-- SECURITY: AchievementPolicy and the update action both check ownership or adviser role. --}}
        @include('achievements._form', ['achievement' => $achievement, 'action' => route('achievements.update', $achievement), 'submitLabel' => 'Save Changes'])
    </section>
</div>
@endsection
