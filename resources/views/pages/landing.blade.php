@extends('layouts.app')

@section('title', 'Welcome to SSITE')

@push('styles')
    <link rel="stylesheet" href="{{ asset('css/home.css') }}">
@endpush

@section('content')
<div class="ssite-landing">
    <section class="ssite-landing-hero" aria-labelledby="landing-title">
        <div class="ssite-landing-copy">
            <h1 id="landing-title">Your SSITE community, all in one place.</h1>
            <p>Stay close to the people, projects, and moments shaping Information Technology Education at Mabalacat City College.</p>

            @if (session('error'))
                <p class="ssite-landing-error" role="alert">{{ session('error') }}</p>
            @endif

            <div class="ssite-landing-actions">
                <a class="ssite-landing-button ssite-landing-button--primary" href="{{ route('microsoft.login') }}">
                    <span class="microsoft-mark" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
                    <span>Sign in with Microsoft</span>
                </a>
                {{-- Replace this placeholder with the published app store or direct download URL. --}}
                <a class="ssite-landing-button ssite-landing-button--download" href="https://example.com/replace-with-mobile-app-download">
                    Download the mobile app
                    <x-icon name="arrow-right" size="sm" aria-hidden="true" />
                </a>
            </div>

            <p class="ssite-landing-proof">Official updates <span aria-hidden="true">·</span> Activities <span aria-hidden="true">·</span> Student achievements</p>
        </div>

        <div class="ssite-landing-art">
            <span class="ssite-landing-orbit ssite-landing-orbit--one" aria-hidden="true"></span>
            <span class="ssite-landing-orbit ssite-landing-orbit--two" aria-hidden="true"></span>
            <img src="{{ asset('images/rawri-26-27-community.jpg') }}" alt="The SSITE wolf mascot" width="360" height="300" fetchpriority="high">
            <span class="ssite-landing-stamp" aria-hidden="true">SSITE<br><small>MCC</small></span>
        </div>
    </section>
</div>
@endsection
