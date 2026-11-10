@extends('layouts.landing')
@section('content')
<section class="landing-hero wrap" aria-labelledby="landing-title">
    <div class="hero-copy">
        <p class="eyebrow"><span class="status-dot"></span> THE HOME OF THE SSITE COMMUNITY</p>
        <h1 id="landing-title">Big ideas.<br>Stronger bonds.<br><span>One SSITE.</span></h1>
        <p class="hero-description">Your people. Your passion. Your next chapter.<br class="desktop-break"> Stay connected to the stories, activities, and achievements shaping our IT community.</p>
        @if (session('error'))
            <p class="landing-alert" role="alert">{{ session('error') }}</p>
        @endif
        <div class="hero-actions"><a class="button button-primary" href="{{ route('microsoft.login') }}"><span class="ms-mark" aria-hidden="true"><i></i><i></i><i></i><i></i></span>Sign in with Microsoft <x-icon name="arrow-right" /></a><a class="text-link" href="#community">Meet your community <span aria-hidden="true">↗</span></a></div>
        <div class="hero-note"><span class="note-icon"><x-icon name="users" /></span><p>Built for students. Powered by connection.<br><strong>Mabalacat City College · Information Technology</strong></p></div>
    </div>
    <div class="hero-visual">
        <div class="mascot-poster">
            <div class="poster-top"><span>THE SSITE SPIRIT</span><span>MABALACAT CITY COLLEGE ↗</span></div>
            <span class="poster-word" aria-hidden="true">SSITE</span><div class="mascot-circle"></div>
            <img class="mascot" src="{{ asset('images/rawri-26-27-community.jpg') }}" width="360" height="316" alt="SSITE’s teal wolf mascot wearing a hoodie" fetchpriority="high">
            <span class="poster-spark" aria-hidden="true">✳</span>
            <div class="poster-bottom"><span>Different talents.<br><strong>One pack.</strong></span><span class="poster-seal" aria-hidden="true"><x-icon name="heart" /></span></div>
        </div>
        <div class="floating-note"><span class="floating-icon"><x-icon name="award" size="lg" /></span><div><strong>A place to belong.</strong><span>A space to become.</span></div><span class="tiny-star" aria-hidden="true">✦</span></div>
        <span class="visual-caption">LEARN TOGETHER. BUILD TOGETHER. GROW TOGETHER.</span>
    </div>
</section>
<div class="values-strip"><div class="wrap"><span>More than a student organization.</span><strong>A community of possibilities.</strong><span class="strip-values">CONNECT <b aria-hidden="true">✳</b> CREATE <b aria-hidden="true">✳</b> GROW</span></div></div>
<section class="community-section wrap" id="community" aria-labelledby="community-title">
    <div class="section-heading"><div><p class="eyebrow">CONNECTED TO WHAT MATTERS</p><h2 id="community-title">Campus life, <span>closer.</span></h2></div><p>From the first announcement to the next big milestone,<br class="desktop-break"> your SSITE journey lives here.</p></div>
    <div class="feature-grid">
        <article class="feature-card"><div class="feature-top"><span class="feature-icon"><x-icon name="file-text" size="lg" /></span><span class="feature-number">01 /</span></div><h3>Stay in the know.</h3><p>Find organization news, student stories, and official updates in one shared space.</p><span class="feature-tag">NEWS &amp; STORIES</span></article>
        <article class="feature-card feature-card-teal"><div class="feature-top"><span class="feature-icon"><x-icon name="calendar-days" size="lg" /></span><span class="feature-number">02 /</span></div><h3>Be part of the moment.</h3><p>Keep up with SSITE activities and discover more ways to connect with your fellow students.</p><span class="feature-tag">ACTIVITIES &amp; CONNECTIONS</span></article>
        <article class="feature-card"><div class="feature-top"><span class="feature-icon"><x-icon name="award" size="lg" /></span><span class="feature-number">03 /</span></div><h3>Celebrate every win.</h3><p>Get to know the achievements, hard work, and ideas that move our community forward.</p><span class="feature-tag">ACHIEVEMENTS &amp; MILESTONES</span></article>
    </div>
</section>
<section class="access-section wrap" id="your-space" aria-labelledby="access-title">
    <div class="access-art" aria-hidden="true"><div class="mini-window"><div class="mini-toolbar"><span></span><span></span><span></span><b>YOUR SSITE SPACE</b></div><div class="mini-content"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" width="54" height="54" loading="lazy"><span>Good things happen<br><strong>when we connect.</strong></span></div><div class="mini-row"><x-icon name="file-text" /><span>Stories worth sharing</span><x-icon name="circle-check" /></div><div class="mini-row"><x-icon name="calendar-days" /><span>Moments worth making</span><x-icon name="circle-check" /></div><div class="mini-row"><x-icon name="award" /><span>Wins worth celebrating</span><x-icon name="circle-check" /></div></div><span class="access-sticker">Your community.<br>Within reach. <span>↗</span></span></div>
    <div class="access-copy"><p class="eyebrow">ONE COMMUNITY. WHEREVER YOU ARE.</p><h2 id="access-title">Your next connection<br>is just a <span>sign-in away.</span></h2><p>Open SSITE on your laptop, tablet, or phone. Sign in with your Microsoft account and make room for everything your community has to offer.</p><ul class="access-benefits"><li><x-icon name="circle-check" /> One place for your organization’s updates</li><li><x-icon name="circle-check" /> Documents and transparency reports within reach</li><li><x-icon name="circle-check" /> A layout that moves with you, on any screen</li></ul><a class="text-link" href="{{ route('microsoft.login') }}">Find your place in SSITE <x-icon name="arrow-right" /></a></div>
</section>
<section class="faq-section wrap" id="questions" aria-labelledby="faq-title"><div><p class="eyebrow">A LITTLE HELP GETTING STARTED</p><h2 id="faq-title">New here?<br><span>You’re in good company.</span></h2></div><div class="faq-list"><details><summary>What is SSITE? <span aria-hidden="true">+</span></summary><p>SSITE stands for Student Society in Information Technology Education at Mabalacat City College. This is the community’s space for organization updates, activities, achievements, and resources.</p></details><details><summary>How do I access the community? <span aria-hidden="true">+</span></summary><p>Choose “Sign in with Microsoft” and use your Microsoft account. If it’s your first visit, you’ll be guided through completing your profile before entering the community.</p></details><details><summary>Can I use SSITE on my phone? <span aria-hidden="true">+</span></summary><p>Yes. Open this website in your phone’s browser and sign in with Microsoft. The website adapts to your screen, so you can stay connected on the go.</p></details></div></section>
<section class="join-section wrap" aria-labelledby="join-title"><div><p class="eyebrow">THERE’S A PLACE FOR YOU HERE</p><h2 id="join-title">Let’s make things happen.<span aria-hidden="true">✳</span></h2><p>Bring your curiosity. Find your people. Be part of SSITE.</p></div><a class="button button-light" href="{{ route('microsoft.login') }}">Join your community <x-icon name="arrow-right" /></a></section>
@endsection
