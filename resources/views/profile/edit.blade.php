@extends('layouts.app')

@section('title', 'Edit Profile Picture')

@section('content')
<div class="dashboard-page profile-page profile-edit-page">
    <x-ui.page-header title="Edit profile picture" />

    {{-- Show a confirmation after the signed-in user saves profile edits. --}}
    @if (session('status'))
        <p class="dashboard-success" role="status" data-flash-toast>{{ session('status') }}</p>
    @endif

    @include('profile.partials.picture', ['onboarding' => false, 'name' => $user->name])
</div>
@endsection
