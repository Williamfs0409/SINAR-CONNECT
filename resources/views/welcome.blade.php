@extends('layouts.app')

@section('title', 'Masuk — TK Cerdas Ceria')
@section('body-class', 'welcome-login-page')

@section('content')
<main class="welcome-login">
    <img class="welcome-backdrop" src="{{ asset('images/login-screen-background.png') }}" alt="" aria-hidden="true">
    <img class="welcome-background" src="{{ asset('images/login-screen-background.png') }}" alt="" aria-hidden="true">
    <img class="welcome-mobile-background" src="{{ asset('images/login-school-background.jpeg') }}" alt="" aria-hidden="true">
    <section class="welcome-message" aria-labelledby="welcome-title">
        <div class="welcome-message-copy">
            <p class="welcome-eyebrow">Sistem Informasi Sekolah</p>
            <h1 id="welcome-title">Halo, Sobat Cerdas Ceria!</h1>
            <p>Dengan SINAR, akses informasi sekolah dan dampingi setiap langkah tumbuh kembang anak.</p>
        </div>
        <p class="welcome-message-note"><span aria-hidden="true">✦</span> Belajar · Bermain · Tumbuh bersama</p>
    </section>

    <section class="welcome-login-card" aria-labelledby="welcome-login-title">
        <a class="brand welcome-card-brand" href="{{ route('home') }}" aria-label="TK Cerdas Ceria">
            @include('components.brand')
        </a>
        <h2 id="welcome-login-title">Selamat Datang</h2>
        <p class="welcome-login-subtitle">Masuk untuk melanjutkan ke akun Anda</p>
        @if (session('status'))
            <p class="login-status-alert" role="status">{{ session('status') }}</p>
        @endif
        @error('email')
            <p class="login-error-alert" id="email-error" role="alert">{{ $message }}</p>
        @enderror

        <form class="login-form welcome-form" id="login-form" method="POST" action="{{ route('login.authenticate') }}">
            @csrf
            <label for="email">Email <span class="required-marker" aria-hidden="true">*</span></label>
            <div class="input-wrap">
                <span aria-hidden="true">✉</span>
                <input id="email" name="email" type="email" placeholder="Masukkan email" autocomplete="username" value="{{ old('email') }}" @error('email') aria-invalid="true" aria-describedby="email-error" @enderror required>
            </div>

            <label for="password">Kata Sandi <span class="required-marker" aria-hidden="true">*</span></label>
            <div class="input-wrap">
                <svg class="password-lock" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                    <rect x="5" y="10" width="14" height="11" rx="2" stroke="currentColor" stroke-width="2"/>
                    <path d="M8 10V7a4 4 0 1 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                </svg>
                <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-pressed="false">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                        <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                    </svg>
                </button>
            </div>

            <div class="login-options">
                <label class="check-label"><input type="checkbox" name="remember"> <span>Ingat saya</span></label>
                <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
            </div>

            <button class="button button-primary welcome-submit" type="submit">Masuk <span aria-hidden="true">→</span></button>
        </form>

        <p class="welcome-contact">Orang tua belum punya akun? <a href="{{ route('registration.create') }}">Daftarkan anak secara daring</a></p>
    </section>
</main>
@endsection
