<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Login - SSITE</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/login.css') }}">
</head>

<body>

    <div class="login-container">

        {{-- TOP IMAGE --}}
        <div class="login-image"></div>
        @if(session('error'))
    <div class="login-error">
        {{ session('error') }}
    </div>
@endif

        {{-- LOGIN FORM --}}
        <div class="login-content">

            <h1>Welcome</h1>

            <p class="subtitle">
                Log in to your account to continue
            </p>


            <form method="POST" action="{{ route('login') }}">
                @csrf

                {{-- EMAIL --}}
                <div class="form-group">

                    <label for="email">
                        Email
                    </label>

                    <input
                        type="email"
                        id="email"
                        name="email"
                        value="{{ old('email') }}"
                        required
                        autofocus
                        autocomplete="email"
                    >

                    @error('email')
                        <span class="error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- PASSWORD --}}
                <div class="form-group">

                    <label for="password">
                        Password
                    </label>

                    <div class="password-wrapper">

                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            autocomplete="current-password"
                        >

                        <button
                            type="button"
                            class="show-password"
                            onclick="togglePassword()"
                        >
                            <span id="eye-icon">◉</span>
                        </button>

                    </div>

                    @error('password')
                        <span class="error">
                            {{ $message }}
                        </span>
                    @enderror

                </div>


                {{-- REMEMBER ME --}}
                <div class="remember">

                    <input
                        type="checkbox"
                        id="remember"
                        name="remember"
                    >

                    <label for="remember">
                        Remember me
                    </label>

                </div>


                {{-- LOGIN BUTTON --}}
                <button type="submit" class="login-button">
                    Login
                </button>

            </form>
        
            {{-- MICROSOFT LOGIN --}}
            <a href="{{ route('microsoft.login') }}" class="microsoft-login-btn">
    <img src="{{ asset('images/microsoft-logo.png') }}" alt="Microsoft logo">
    <span>Continue with Microsoft</span>
</a>
        </div>

    </div>


    <script>
        function togglePassword() {

            const password = document.getElementById('password');
            const icon = document.getElementById('eye-icon');

            if (password.type === 'password') {

                password.type = 'text';
                icon.textContent = '◉';

            } else {

                password.type = 'password';
                icon.textContent = '◉';

            }
        }
    </script>

</body>
</html>