@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="dashboard-page profile-page">
    <x-ui.page-header title="My Profile" subtitle="Update your student information." />

    {{-- Show a confirmation after the signed-in user saves profile edits. --}}
    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif

    {{-- Keep field-specific validation feedback visible on a failed update. --}}
    @if ($errors->any())
        <div class="dashboard-error" role="alert">
            <p>Please review the highlighted fields and try again.</p>
        </div>
    @endif

    @include('profile.partials.picture', ['user' => $user])

    <form method="POST" action="{{ route('profile.update') }}" class="dashboard-panel profile-form">
        {{-- SECURITY: The request is scoped to the authenticated user; role and email are not submitted. --}}
        @csrf
        @method('PUT')
        @include('profile.partials.fields', ['allowStudentNumber' => $user->isAdviser()])
        <div class="profile-form-actions">
            <button type="submit" class="dashboard-button">Save changes</button>
        </div>
    </form>
</div>
@endsection
