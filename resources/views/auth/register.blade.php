<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>SmartAqua — Register</title>
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

            <!-- Right: Register Form -->
            <div class="auth-form-wrapper">
                <div class="auth-card">
                    <h2>Buat Akun</h2>
                    <p class="auth-subtitle">Lengkapi data berikut untuk membuat akun Anda!</p>

                    <form method="POST" action="{{ route('register') }}">
                        @csrf

                        <!-- Full Name -->
                        <div class="auth-form-group">
                            <label for="name">Nama Lengkap</label>
                            <input id="name" type="text" name="name" value="{{ old('name') }}" placeholder="Masukkan nama lengkap" required autofocus autocomplete="name">
                            @error('name')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Email -->
                        <div class="auth-form-group">
                            <label for="email">Email</label>
                            <input id="email" type="email" name="email" value="{{ old('email') }}" placeholder="nama@email.com" required autocomplete="username">
                            @error('email')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Password -->
                        <div class="auth-form-group">
                            <label for="password">Kata Sandi</label>
                            <input id="password" type="password" name="password" placeholder="Minimal 8 karakter" required autocomplete="new-password">
                            @error('password')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Confirm Password -->
                        <div class="auth-form-group">
                            <label for="password_confirmation">Konfirmasi Kata Sandi</label>
                            <input id="password_confirmation" type="password" name="password_confirmation" placeholder="Ulangi kata sandi" required autocomplete="new-password">
                            @error('password_confirmation')
                                <div class="auth-error">{{ $message }}</div>
                            @enderror
                        </div>

                        <button type="submit" class="auth-btn">Daftar</button>
                    </form>

                    <div class="auth-footer-links">
                        <a href="{{ route('login') }}" class="auth-link">Sudah punya akun? Masuk</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
