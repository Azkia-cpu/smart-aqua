<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartAqua — Login</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/auth.css') }}">
</head>
<body>
    <div class="auth-page">
        <div class="auth-container">
            <!-- Left: Branding -->
            <div class="auth-branding">
                <h1>
                    Smart
                    <span class="highlight">Aquaculture</span>
                </h1>
                <p class="auth-description">
                    Monitor kualitas air secara real-time dan optimalkan
                    performa akuakultur melalui pemantauan cerdas,
                    analisis data akurat, dan solusi manajemen
                    yang berkelanjutan.
                </p>
            </div>

            <!-- Right: Login Form -->
            <div class="auth-form-wrapper">
                <div class="auth-card">
                    <h2>Selamat Datang!</h2>
                    <p class="auth-subtitle">Masuk untuk mengakses akun Anda</p>

                    <!-- Session Status -->
                    @if (session('status'))
                        <div style="color: #06d6a0; margin-bottom: 1rem; font-size: 0.85rem;">{{ session('status') }}</div>
                    @endif

                    <form method="POST" action="{{ route('login') }}">
                        @csrf

                        <!-- Email -->
                        <div class="auth-form-group">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autofocus autocomplete="username">
                            @error('email')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="auth-form-group">
                            <label for="password">Kata Sandi</label>
                            <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" required autocomplete="current-password">
                            @error('password')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="auth-btn">Masuk</button>
                    </form>

                    <div class="auth-footer-links">
                        <a href="{{ route('register') }}" class="auth-link">Belum punya akun? Daftar</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
