<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Your people, your passion, your SSITE community. Connect with the stories, activities, and achievements of the Student Society in Information Technology Education at Mabalacat City College.">
    <meta name="theme-color" content="#084b50">
    <title>SSITE — Your community. Your next chapter.</title>
    <link rel="icon" type="image/png" href="{{ asset('images/ssite - revamp logo 2.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;650;700&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/landing.css') }}">
    <script src="{{ asset('js/landing.js') }}" defer></script>
</head>
<body class="landing-page">
    <a class="skip-link" href="#main-content">Skip to content</a>
    <header class="landing-header wrap">
        <a class="brand" href="{{ route('landing') }}" aria-label="SSITE home"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="" width="48" height="48"><span><strong>SSITE<span class="brand-period">.</span></strong><small>MABALACAT CITY COLLEGE</small></span></a>
        <nav class="desktop-nav" aria-label="Main navigation"><a href="#community">The community</a><a href="#your-space">Your space</a><a href="#questions">FAQs</a></nav>
        <a class="header-signin" href="{{ route('microsoft.login') }}">Sign in <x-icon name="arrow-right" size="sm" /></a>
    </header>
    <main id="main-content">@yield('content')</main>
    <footer class="landing-footer wrap"><a class="brand footer-brand" href="{{ route('landing') }}"><img src="{{ asset('images/ssite - revamp logo 2.png') }}" alt="SSITE logo" width="38" height="38"><span><strong>SSITE<span class="brand-period">.</span></strong><small>Student Society in Information Technology Education</small></span></a><p>© {{ date('Y') }} SSITE · Mabalacat City College</p><a class="back-top" href="#">Back to top <x-icon name="arrow-up" size="sm" /></a></footer>
</body>
</html>
