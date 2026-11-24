@inject('siteContent', 'App\Support\SiteContent')
@extends('layouts.landing')

@section('content')
@php
    $downloadUrl = $siteContent->value('landing-app', 'download_url');
    $downloadAvailable = is_string($downloadUrl)
        && filter_var($downloadUrl, FILTER_VALIDATE_URL)
        && in_array(strtolower(parse_url($downloadUrl, PHP_URL_SCHEME) ?? ''), ['https', 'http'], true);
@endphp
<section class="hero-band" aria-labelledby="landing-title">
    <div class="landing-hero wrap">
        <div class="hero-copy">
            <p class="eyebrow">{{ $siteContent->value('landing-welcome', 'eyebrow') }}</p>
            <h1 id="landing-title">{{ $siteContent->value('landing-welcome', 'headline') }}<br><span>{{ $siteContent->value('landing-welcome', 'accent_heading') }}</span> <x-section-pencil section="landing-welcome" /></h1>
            <p class="hero-description">{{ $siteContent->value('landing-welcome', 'description') }}</p>
            @if (session('error'))
                <p class="landing-alert" role="alert">{{ session('error') }}</p>
            @endif
            <div class="hero-actions">
                <a class="button button-microsoft" href="{{ route('microsoft.login') }}"><x-microsoft-mark /> {{ $siteContent->value('landing-welcome', 'sign_in_button') }} <x-icon name="arrow-right" /></a>
                <a class="button button-outline" href="#get-app"><x-landing-download-icon /> {{ $siteContent->value('landing-welcome', 'mobile_button') }}</a>
            </div>
            <p class="signin-note">{{ $siteContent->value('landing-welcome', 'account_note') }}</p>
            <a class="hero-explore" href="#community">{{ $siteContent->value('landing-welcome', 'explore_link') }} <x-icon name="chevron-down" /></a>
        </div>
        <figure class="hero-visual">
            <div class="mascot-poster">
                <div class="poster-meta"><span>{{ $siteContent->value('landing-welcome', 'poster_name_line_one') }}<br>{{ $siteContent->value('landing-welcome', 'poster_name_line_two') }}</span><img src="{{ $siteContent->image('landing-welcome', 'poster_logo_image') }}" alt="" width="42" height="42"></div>
                <span class="poster-word" aria-hidden="true">{{ $siteContent->value('landing-welcome', 'poster_initials') }}</span>
                <div class="mascot-stage"><img class="mascot" src="{{ $siteContent->image('landing-welcome', 'mascot_image') }}" width="360" height="316" alt="{{ $siteContent->value('landing-welcome', 'mascot_description') }}" fetchpriority="high"></div>
                <div class="poster-bottom"><span>{{ $siteContent->value('landing-welcome', 'motto_line_one') }}<br><strong>{{ $siteContent->value('landing-welcome', 'motto_line_two') }}</strong></span><span class="poster-campus">{{ $siteContent->value('landing-welcome', 'campus_initials') }}<br>{{ $siteContent->value('landing-welcome', 'campus_program') }}</span></div>
            </div>
            <figcaption>{{ $siteContent->value('landing-welcome', 'caption') }}</figcaption>
        </figure>
    </div>
    <div class="campus-strip wrap"><div><img src="{{ $siteContent->image('landing-welcome', 'campus_logo_image') }}" alt="" width="30" height="30"><strong>{{ $siteContent->value('landing-welcome', 'campus_name') }}</strong></div><span>{{ $siteContent->value('landing-welcome', 'campus_description') }}</span></div>
</section>
<section class="community-section wrap" id="community" aria-labelledby="community-title">
    <div class="section-intro">
        <h2 id="community-title">{{ $siteContent->value('landing-community', 'heading') }} <x-section-pencil section="landing-community" /></h2>
        <p class="community-access-note">{{ $siteContent->value('landing-community', 'introduction') }}</p>
    </div>
    <ul class="community-directory" aria-label="Available after signing in">
        <li class="directory-row"><h3>{{ $siteContent->value('landing-community', 'articles_title') }}</h3><p>{{ $siteContent->value('landing-community', 'articles_description') }}</p></li>
        <li class="directory-row"><h3>{{ $siteContent->value('landing-community', 'activities_title') }}</h3><p>{{ $siteContent->value('landing-community', 'activities_description') }}</p></li>
        <li class="directory-row"><h3>{{ $siteContent->value('landing-community', 'documents_title') }}</h3><p>{{ $siteContent->value('landing-community', 'documents_description') }}</p></li>
        <li class="directory-row"><h3>{{ $siteContent->value('landing-community', 'transparency_title') }}</h3><p>{{ $siteContent->value('landing-community', 'transparency_description') }}</p></li>
    </ul>
</section>
<section class="app-band" id="get-app" aria-labelledby="app-title">
  <div class="app-section wrap">
    <div class="app-copy">
        <p class="eyebrow"><span class="release-label">{{ $downloadAvailable ? 'AVAILABLE TO DOWNLOAD' : 'COMING SOON' }}</span></p>
        <h2 id="app-title">{{ $siteContent->value('landing-app', 'heading') }} <x-section-pencil section="landing-app" /></h2>
        <p>{{ $siteContent->value('landing-app', 'description') }}</p>
        <ul class="app-features"><li><x-icon name="file-text" /> {{ $siteContent->value('landing-app', 'stories_feature') }}</li><li><x-icon name="folder" /> {{ $siteContent->value('landing-app', 'records_feature') }}</li><li><x-icon name="users" /> {{ $siteContent->value('landing-app', 'officers_feature') }}</li></ul>
        @if ($downloadAvailable)
            <a class="button button-dark" href="{{ $downloadUrl }}" data-app-download><x-landing-download-icon /> {{ $siteContent->value('landing-app', 'download_button') }} <x-icon name="arrow-right" /></a>
            <p class="app-status">{{ $siteContent->value('landing-app', 'installation_note') }}</p>
        @else
            <button class="button button-unavailable" type="button" disabled aria-describedby="app-status"><x-landing-download-icon /> {{ $siteContent->value('landing-app', 'unavailable_button') }}</button>
            <p class="app-status" id="app-status">{{ $siteContent->value('landing-app', 'browser_note') }}</p>
        @endif
        <a class="text-link" href="{{ route('microsoft.login') }}">{{ $siteContent->value('landing-app', 'browser_link') }} <x-icon name="arrow-right" size="sm" /></a>
    </div>
    <div class="app-art">
        <div class="phone" aria-hidden="true">
            <div class="phone-camera"></div>
            <div class="phone-header"><img src="{{ $siteContent->image('landing-app', 'preview_logo_image') }}" alt="" width="28" height="28"><strong>{{ $siteContent->value('landing-app', 'preview_initials') }}</strong><x-icon name="bell" size="sm" /></div>
            <div class="phone-body">
                <span class="phone-eyebrow">{{ $siteContent->value('landing-app', 'preview_eyebrow') }}</span>
                <p class="phone-title">{{ $siteContent->value('landing-app', 'preview_title') }}<br><strong>{{ $siteContent->value('landing-app', 'preview_accent') }}</strong></p>
                <div class="phone-mascot"><img src="{{ $siteContent->image('landing-app', 'preview_mascot_image') }}" alt="" width="180" height="158" loading="lazy"></div>
                <p class="phone-section-label">{{ $siteContent->value('landing-app', 'preview_heading') }}</p>
                <div class="phone-row"><x-icon name="compass" /><span>{{ $siteContent->value('landing-app', 'discover_label') }}</span><x-icon name="chevron-right" size="sm" /></div>
                <div class="phone-row"><x-icon name="folder" /><span>{{ $siteContent->value('landing-app', 'documents_label') }}</span><x-icon name="chevron-right" size="sm" /></div>
                <div class="phone-row"><x-icon name="receipt" /><span>{{ $siteContent->value('landing-app', 'transparency_label') }}</span><x-icon name="chevron-right" size="sm" /></div>
            </div>
            <div class="phone-tabs"><span><x-icon name="home" size="sm" />{{ $siteContent->value('landing-app', 'home_label') }}</span><span><x-icon name="compass" size="sm" />{{ $siteContent->value('landing-app', 'discover_label') }}</span><span><x-icon name="users" size="sm" />{{ $siteContent->value('landing-app', 'about_label') }}</span></div>
        </div>
        <p class="preview-caption">{{ $siteContent->value('landing-app', 'preview_caption') }}</p>
    </div>
  </div>
</section>
<section class="getting-started wrap" id="your-space" aria-labelledby="start-title">
    <div class="start-intro">
        <p class="eyebrow">{{ $siteContent->value('landing-getting-started', 'eyebrow') }}</p>
        <h2 id="start-title">{{ $siteContent->value('landing-getting-started', 'heading') }} <x-section-pencil section="landing-getting-started" /></h2>
        <p>{{ $siteContent->value('landing-getting-started', 'introduction') }}</p>
    </div>
    <ol class="start-steps">
        <li><h3>{{ $siteContent->value('landing-getting-started', 'account_heading') }}</h3><p>{{ $siteContent->value('landing-getting-started', 'account_instructions') }}</p></li>
        <li><h3>{{ $siteContent->value('landing-getting-started', 'profile_heading') }}</h3><p>{{ $siteContent->value('landing-getting-started', 'profile_instructions') }}</p></li>
        <li><h3>{{ $siteContent->value('landing-getting-started', 'browse_heading') }}</h3><p>{{ $siteContent->value('landing-getting-started', 'browse_instructions') }}</p></li>
    </ol>
</section>
<section class="faq-section wrap" id="questions" aria-labelledby="faq-title">
    <div class="policy-intro"><p class="eyebrow">{{ $siteContent->value('landing-policies', 'eyebrow') }}</p><h2 id="faq-title">{{ $siteContent->value('landing-policies', 'heading') }} <x-section-pencil section="landing-policies" /></h2><p>{{ $siteContent->value('landing-policies', 'introduction') }}</p></div>
    <div class="faq-list">
        <details id="acceptable-use"><summary>{{ $siteContent->value('landing-policies', 'acceptable_use_heading') }} <span aria-hidden="true">+</span></summary><p>{{ $siteContent->value('landing-policies', 'acceptable_use_text') }}</p></details>
        @include('pages.partials.policies')
    </div>
</section>
<section class="join-section" aria-labelledby="join-title"><div class="wrap join-inner"><div><h2 id="join-title">{{ $siteContent->value('landing-join', 'heading') }} <x-section-pencil section="landing-join" /></h2><p>{{ $siteContent->value('landing-join', 'description') }}</p></div><a class="button button-microsoft" href="{{ route('microsoft.login') }}"><x-microsoft-mark /> {{ $siteContent->value('landing-join', 'sign_in_button') }} <x-icon name="arrow-right" /></a></div></section>
@endsection
