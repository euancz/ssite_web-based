@extends('layouts.app')

@section('title', 'Complete Your Profile')

@section('content')
<div class="dashboard-page profile-page">
    <x-ui.page-header
        title="Welcome, {{ $user->name }}! Please complete your information to continue"
        subtitle="Your details are stored securely on your SSITE account."
    />

    {{-- Keep the required-profile gate clear while field-level errors appear beside their inputs. --}}
    @if ($errors->any())
        <div class="dashboard-error" role="alert">
            <p>Please review the highlighted fields and try again.</p>
        </div>
    @endif

    <form method="POST" action="{{ route('profile.complete.store') }}" class="dashboard-panel profile-form">
        {{-- SECURITY: The endpoint updates only the signed-in user and validates the allowed profile fields. --}}
        @csrf
        @include('profile.partials.fields', ['allowStudentNumber' => true])
        <div class="profile-form-actions">
            <button type="submit" class="dashboard-button">Save information and continue</button>
        </div>
    </form>
</div>
@endsection
