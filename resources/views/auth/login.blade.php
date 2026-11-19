@extends('layouts.app')

@section('title', 'Masuk ke Akun — TK Najmah Rugayah Darus')
@section('body-class', 'login-page')

@section('content')
<a class="login-back" href="{{ route('home') }}">← <span>Kembali ke Beranda</span></a>
<div class="login-sky" aria-hidden="true">
    <span class="cloud cloud-left"></span><span class="cloud cloud-right"></span><span class="cloud cloud-bottom"></span>
    <span class="sky-star star-left">✦</span><span class="sky-star star-right">✧</span>
</div>
<main class="login-wrap">
    <section class="login-card" aria-labelledby="login-title">
        <a class="brand login-brand" href="{{ route('home') }}">
            @include('components.brand')
        </a>
        <h1 id="login-title">Masuk ke Akun</h1>
        <p class="login-subtitle">Silakan masuk untuk melanjutkan</p>
        <form class="login-form" id="login-form">
            <label for="email">Email</label>
            <div class="input-wrap">
                <span aria-hidden="true">✉</span>
                <input id="email" name="email" type="email" placeholder="Masukkan email" autocomplete="username" required>
            </div>
            <label for="password">Kata Sandi</label>
            <div class="input-wrap">
                <span aria-hidden="true">⌑</span>
                <input id="password" name="password" type="password" placeholder="Masukkan kata sandi" autocomplete="current-password" required>
                <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi">◉</button>
            </div>
            <div class="login-options">
                <label class="check-label"><input type="checkbox" name="remember"> <span>Ingat saya</span></label>
                <a href="mailto:info@tkcerdasceria.sch.id?subject=Bantuan%20akun">Lupa kata sandi?</a>
            </div>
            <button class="button button-primary login-submit" type="submit">Masuk</button>
            <div class="login-divider"><span>atau</span></div>
            <button class="button google-button" type="button" id="google-login"><span class="google-g">G</span> Masuk dengan Google</button>
            <p class="login-contact">Belum punya akun? <a href="mailto:info@tkcerdasceria.sch.id">Hubungi Administrator</a></p>
            <p class="login-feedback" id="login-feedback" role="status" aria-live="polite"></p>
        </form>
    </section>
    <p class="login-caption">Sistem Informasi Sekolah TK<br><strong>Mudah · Aman · Terintegrasi</strong></p>
</main>
@endsection
