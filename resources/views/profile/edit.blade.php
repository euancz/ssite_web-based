@extends('layouts.app')

@section('title', 'My Profile')

@section('content')
<div class="dashboard-page profile-page">
    <x-page-header title="My Profile" subtitle="Update your student information." />

    @if (session('status'))
        <p class="dashboard-success" role="status">{{ session('status') }}</p>
    @endif

    @if ($errors->any())
        <div class="dashboard-error" role="alert">
            <p>Please review the highlighted fields and try again.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.update') }}" class="dashboard-panel profile-form">
        @csrf
        @method('PUT')
        @include('profile.partials.fields', ['allowStudentNumber' => $user->isAdviser()])
        <div class="profile-form-actions">
            <button type="submit" class="dashboard-button">Save changes</button>
        </div>
    </form>
</div>
@endsection
