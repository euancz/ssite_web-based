@extends('layouts.app')

@section('title', 'Set Up Your Profile')

@section('content')
<div class="dashboard-page profile-page">
    <x-ui.page-header
        title="Set up your profile"
        subtitle="Upload a profile photo and agree to the site policies to continue."
    />

    @error('account')
        <p class="dashboard-error" role="alert">{{ $message }}</p>
    @enderror

    @include('profile.partials.picture', ['onboarding' => true])
</div>
@endsection
