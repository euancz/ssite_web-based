@extends('layouts.app')

@section('title', 'New Achievement')

@section('content')
<div class="dashboard-page">
    {{-- The shared heading and form panel follow Articles dashboard spacing. --}}
    <x-ui.page-header title="New Achievement" subtitle="Share an achievement with the SSITE community." />
    <section class="dashboard-panel">
        {{-- SECURITY: The controller authorizes the role and sets ownership and status server-side. --}}
        @include('achievements._form', ['achievement' => null, 'action' => route('achievements.store'), 'submitLabel' => 'Create Achievement'])
    </section>
</div>
@endsection
