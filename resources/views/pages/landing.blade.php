@extends('layouts.landing')

@section('content')
@php
    $downloadUrl = config('mobile.download_url');
    $downloadAvailable = is_string($downloadUrl)
        && filter_var($downloadUrl, FILTER_VALIDATE_URL)
        && in_array(strtolower(parse_url($downloadUrl, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true);
@endphp
<section class="hero-band" aria-labelledby="landing-title">
    <div class="landing-hero wrap">
        <div class="hero-copy">
            <p class="eyebrow">SSITE at Mabalacat City College</p>
            <h1 id="landing-title">Campus life.<br>All in <span>one place.</span></h1>
            <p class="hero-description">The official home of the Student Society in Information Technology Education. Catch up on announcements, find your next activity, and see what your fellow IT students are building.</p>
            @if (session('error'))
                <p class="landing-alert" role="alert">{{ session('error') }}</p>
            @endif
            <div class="hero-actions">
                <a class="button button-microsoft" href="{{ route('microsoft.login') }}"><x-microsoft-mark /> Sign in with Microsoft <x-icon name="arrow-right" /></a>
                <a class="button button-outline" href="#get-app"><x-landing-download-icon /> Download app</a>
            </div>
            <p class="signin-note">Use your MCC Microsoft account to get started.</p>
            <a class="hero-explore" href="#community">Take a look around <x-icon name="chevron-down" /></a>
        </div>
        <figure class="hero-visual">
            <div class="mascot-poster">
                <div class="poster-meta"><span>STUDENT SOCIETY IN<br>INFORMATION TECHNOLOGY EDUCATION</span><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" width="42" height="42"></div>
                <span class="poster-word" aria-hidden="true">SSITE</span>
                <div class="mascot-stage"><img class="mascot" src="{{ asset('images/rawri-26-27-community.jpg') }}" width="360" height="316" alt="The SSITE teal wolf mascot in a hoodie" fetchpriority="high"></div>
                <div class="poster-bottom"><span>Different minds.<br><strong>One pack.</strong></span><span class="poster-campus">MCC<br>INFORMATION TECHNOLOGY</span></div>
            </div>
            <figcaption>Our community. Our corner of campus.</figcaption>
        </figure>
    </div>
    <div class="campus-strip wrap"><span>A student community at</span><div><img src="{{ asset('images/MCC LOGO 1.png') }}" alt="" width="30" height="30"><strong>Mabalacat City College</strong></div><a href="#community">Explore SSITE <x-icon name="chevron-down" size="sm" /></a></div>
</section>
<section class="community-section wrap" id="community" aria-labelledby="community-title">
    <div class="section-intro">
        <p class="eyebrow">INSIDE SSITE</p>
        <h2 id="community-title">Less searching.<br><span>More getting involved.</span></h2>
        <p>Announcements, student work, and organization records belong somewhere you can actually find them. Start here.</p>
        <p class="section-note"><x-icon name="users" size="sm" /> Sign in to explore the community.</p>
    </div>
    <div class="community-directory">
        <a class="directory-row" href="{{ route('discover') }}"><span class="directory-number">01</span><div><h3>News &amp; stories</h3><p>Official updates and articles from the SSITE community.</p></div><x-icon name="arrow-right" /></a>
        <a class="directory-row" href="{{ route('activities.index') }}"><span class="directory-number">02</span><div><h3>Activities &amp; achievements</h3><p>See what’s happening and celebrate the work of fellow students.</p></div><x-icon name="arrow-right" /></a>
        <a class="directory-row" href="{{ route('documents.index') }}"><span class="directory-number">03</span><div><h3>Documents &amp; resources</h3><p>Find published files by category, semester, and academic year.</p></div><x-icon name="arrow-right" /></a>
        <a class="directory-row" href="{{ route('liquidations.index') }}"><span class="directory-number">04</span><div><h3>Organization transparency</h3><p>Read published financial reports and supporting records.</p></div><x-icon name="arrow-right" /></a>
    </div>
</section>
<section class="app-section wrap" id="get-app" aria-labelledby="app-title">
    <div class="app-copy">
        <p class="eyebrow">SSITE ON MOBILE <span class="release-label">{{ $downloadAvailable ? 'AVAILABLE TO DOWNLOAD' : 'COMING SOON' }}</span></p>
        <h2 id="app-title">A little more campus.<br><span>In your pocket.</span></h2>
        <p>The SSITE app brings community updates, activities, and published resources together on your phone. Use the same MCC Microsoft account you use here.</p>
        <ul class="app-features"><li><x-icon name="file-text" /> Community news and student stories</li><li><x-icon name="folder" /> Documents and transparency reports</li><li><x-icon name="users" /> SSITE officers and organization information</li></ul>
        @if ($downloadAvailable)
            <a class="button button-dark" href="{{ $downloadUrl }}" data-app-download><x-landing-download-icon /> Download SSITE app <x-icon name="arrow-right" /></a>
            <p class="app-status">Sign in with your MCC Microsoft account after installing.</p>
        @else
            <button class="button button-unavailable" type="button" disabled aria-describedby="app-status"><x-landing-download-icon /> App download coming soon</button>
            <p class="app-status" id="app-status">The app isn’t available to download yet. You can use the website on your phone today.</p>
        @endif
        <a class="text-link" href="{{ route('microsoft.login') }}">Continue in your browser <x-icon name="arrow-right" size="sm" /></a>
    </div>
    <div class="app-art">
        <div class="phone" aria-hidden="true">
            <div class="phone-camera"></div>
            <div class="phone-header"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" width="28" height="28"><strong>SSITE</strong><x-icon name="bell" size="sm" /></div>
            <div class="phone-body">
                <span class="phone-eyebrow">YOUR CAMPUS COMMUNITY</span>
                <p class="phone-title">Welcome to<br><strong>SSITE.</strong></p>
                <div class="phone-mascot"><img src="{{ asset('images/rawri-26-27-community.jpg') }}" alt="" width="180" height="158" loading="lazy"></div>
                <p class="phone-section-label">Explore your community</p>
                <div class="phone-row"><x-icon name="compass" /><span>Discover</span><x-icon name="chevron-right" size="sm" /></div>
                <div class="phone-row"><x-icon name="folder" /><span>Documents</span><x-icon name="chevron-right" size="sm" /></div>
                <div class="phone-row"><x-icon name="receipt" /><span>Transparency</span><x-icon name="chevron-right" size="sm" /></div>
            </div>
            <div class="phone-tabs"><span><x-icon name="home" size="sm" />Home</span><span><x-icon name="compass" size="sm" />Discover</span><span><x-icon name="users" size="sm" />About us</span></div>
        </div>
        <p class="preview-caption">A look at the SSITE app · Illustrative preview</p>
    </div>
</section>
<section class="getting-started wrap" id="your-space" aria-labelledby="start-title">
    <div class="section-heading"><div><p class="eyebrow">YOUR FIRST VISIT</p><h2 id="start-title">Make yourself at home.</h2></div><p>One school account.<br>{{ $downloadAvailable ? 'On the web and in the app.' : 'On the web, and in the app when it’s ready.' }}</p></div>
    <ol class="start-steps">
        <li><span class="step-number">01</span><h3>Sign in with Microsoft</h3><p>Use your MCC Microsoft account. There’s no separate SSITE password to remember.</p></li>
        <li><span class="step-number">02</span><h3>Complete your profile</h3><p>On your first visit, add the details needed to set up your community profile.</p></li>
        <li><span class="step-number">03</span><h3>See what’s happening</h3><p>Head to Home for updates, or open Discover to browse articles, activities, and achievements.</p></li>
    </ol>
</section>
<section class="faq-section wrap" id="questions" aria-labelledby="faq-title">
    <div><p class="eyebrow">A FEW USEFUL ANSWERS</p><h2 id="faq-title">Before you<br><span>jump in.</span></h2></div>
    <div class="faq-list">
        <details><summary>What is SSITE? <span aria-hidden="true">+</span></summary><p>SSITE is the Student Society in Information Technology Education at Mabalacat City College. This website is the organization’s home for announcements, activities, achievements, and published resources.</p></details>
        <details><summary>Which account should I use? <span aria-hidden="true">+</span></summary><p>Use your MCC Microsoft account. Personal Microsoft accounts aren’t accepted. If Microsoft opens the wrong account, choose another account and sign in with your school email.</p></details>
        <details><summary>Do I need to download the app? <span aria-hidden="true">+</span></summary><p>No. You can use SSITE in your phone, tablet, or laptop browser. @if ($downloadAvailable)The app is also available from the <a href="#get-app">download section</a>.@else The app download isn’t available yet; the <a href="#get-app">app section</a> will have the link when it’s released.@endif</p></details>
        <details><summary>Where can I find documents and reports? <span aria-hidden="true">+</span></summary><p>After signing in, open Documents for published files and academic-year filters. Open Transparency for the organization’s published financial reports.</p></details>
    </div>
</section>
<section class="join-section" aria-labelledby="join-title"><div class="wrap join-inner"><div><p class="eyebrow">THE SSITE COMMUNITY</p><h2 id="join-title">See you inside.</h2><p>Your next campus update is a sign-in away.</p></div><a class="button button-microsoft" href="{{ route('microsoft.login') }}"><x-microsoft-mark /> Sign in with Microsoft <x-icon name="arrow-right" /></a></div></section>
@endsection
