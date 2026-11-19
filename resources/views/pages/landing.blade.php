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
            <h1 id="landing-title">Campus life.<br><span>Closer to you.</span></h1>
            <p class="hero-description">News, activities, and resources for the SSITE community at Mabalacat City College.</p>
            @if (session('error'))
                <p class="landing-alert" role="alert">{{ session('error') }}</p>
            @endif
            <div class="hero-actions">
                <a class="button button-microsoft" href="{{ route('microsoft.login') }}"><x-microsoft-mark /> Sign in with Microsoft <x-icon name="arrow-right" /></a>
                <a class="button button-outline" href="#get-app"><x-landing-download-icon /> Download app</a>
            </div>
            <p class="signin-note">Use your MCC Microsoft account to get started.</p>
            <a class="hero-explore" href="#community">See what’s inside <x-icon name="chevron-down" /></a>
        </div>
        <figure class="hero-visual">
            <div class="mascot-poster">
                <div class="poster-meta"><span>STUDENT SOCIETY IN<br>INFORMATION TECHNOLOGY EDUCATION</span><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" width="42" height="42"></div>
                <span class="poster-word" aria-hidden="true">SSITE</span>
                <div class="mascot-stage"><img class="mascot" src="{{ asset('images/rawri-26-27-community.jpg') }}" width="360" height="316" alt="The SSITE teal wolf mascot in a hoodie" fetchpriority="high"></div>
                <div class="poster-bottom"><span>Different minds.<br><strong>One pack.</strong></span><span class="poster-campus">MCC<br>INFORMATION TECHNOLOGY</span></div>
            </div>
            <figcaption>Student Society in Information Technology Education</figcaption>
        </figure>
    </div>
    <div class="campus-strip wrap"><div><img src="{{ asset('images/MCC LOGO 1.png') }}" alt="" width="30" height="30"><strong>Mabalacat City College</strong></div><span>The official SSITE community website</span></div>
</section>
<section class="community-section wrap" id="community" aria-labelledby="community-title">
    <div class="section-intro">
        <p class="eyebrow">EXPLORE SSITE</p>
        <h2 id="community-title">Keep up. Find what you need.</h2>
        <p class="community-access-note">Sign in with your MCC Microsoft account to view these resources.</p>
    </div>
    <ul class="community-directory" aria-label="Available after signing in">
        <li class="directory-row"><h3>News &amp; stories</h3><p>Announcements and articles from SSITE.</p></li>
        <li class="directory-row"><h3>Activities &amp; achievements</h3><p>Upcoming events and student accomplishments.</p></li>
        <li class="directory-row"><h3>Documents &amp; resources</h3><p>Files organized by category, semester, and academic year.</p></li>
        <li class="directory-row"><h3>Organization transparency</h3><p>Financial reports and supporting records.</p></li>
    </ul>
</section>
<section class="app-band" id="get-app" aria-labelledby="app-title">
  <div class="app-section wrap">
    <div class="app-copy">
        <p class="eyebrow">SSITE ON MOBILE <span class="release-label">{{ $downloadAvailable ? 'AVAILABLE TO DOWNLOAD' : 'COMING SOON' }}</span></p>
        <h2 id="app-title">SSITE, wherever<br>you are.</h2>
        <p>Keep the community close on your phone. The same updates and resources, with the same MCC account.</p>
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
        <p class="preview-caption">SSITE mobile app · Illustrative preview</p>
    </div>
  </div>
</section>
<section class="getting-started wrap" id="your-space" aria-labelledby="start-title">
    <div class="start-intro">
        <p class="eyebrow">GETTING STARTED</p>
        <h2 id="start-title">First time here?</h2>
        <p>You only need your school Microsoft account. There’s no separate SSITE password to create.</p>
    </div>
    <ol class="start-steps">
        <li><h3>Choose your school account</h3><p>If Microsoft shows a personal account, choose another account and enter your MCC email.</p></li>
        <li><h3>Set up your profile</h3><p>On your first visit, upload a profile photo and agree to the Terms of Service and Privacy Policy.</p></li>
        <li><h3>Browse the community</h3><p>Read updates on Home, find activities in Discover, or open Documents and Transparency.</p></li>
    </ol>
</section>
<section class="faq-section wrap" id="questions" aria-labelledby="faq-title">
    <div class="policy-intro"><p class="eyebrow">USING SSITE</p><h2 id="faq-title">Terms &amp; privacy</h2><p>How to use the site responsibly and what information your account stores.</p></div>
    <div class="faq-list">
        <details id="acceptable-use"><summary>Acceptable use <span aria-hidden="true">+</span></summary><p>Use your own MCC Microsoft account and provide accurate profile information. Treat other members respectfully. Do not share someone else’s personal information without permission, post harmful or misleading material, or attempt to access accounts and records you are not authorized to view.</p></details>
        @include('pages.partials.policies')
    </div>
</section>
<section class="join-section" aria-labelledby="join-title"><div class="wrap join-inner"><div><h2 id="join-title">Your community is here.</h2><p>Sign in with your MCC Microsoft account.</p></div><a class="button button-microsoft" href="{{ route('microsoft.login') }}"><x-microsoft-mark /> Sign in with Microsoft <x-icon name="arrow-right" /></a></div></section>
@endsection
