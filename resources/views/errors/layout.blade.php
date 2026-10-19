<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'Something went wrong') | SSITE</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="{{ asset('css/errors.css') }}">
</head>
<body class="site-body error-page">
    <div class="error-shell">
        <header class="error-header">
            <a class="error-brand" href="{{ url('/') }}" aria-label="SSITE home">
                <img src="{{ asset('images/SSITE LOGO.png') }}" alt="">
                <span>Student Society in Information<br>Technology Education</span>
            </a>
        </header>

        <main class="error-main" aria-labelledby="error-title">
            <p class="error-code" aria-hidden="true">@yield('status')</p>
            <div class="error-copy">
                <h1 id="error-title">@yield('heading')</h1>
                <p>@yield('message')</p>
                <a class="error-home-link" href="{{ url('/') }}">
                    <span>Return to SSITE home</span>
                    <x-icon name="arrow-right" size="sm" />
                </a>
            </div>
        </main>

        <footer class="error-footer">SSITE · Mabalacat City College</footer>
    </div>
</body>
</html>
