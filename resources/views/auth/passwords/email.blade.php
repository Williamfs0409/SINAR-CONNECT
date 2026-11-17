@extends('layouts.app')

@section('title', 'Lupa Kata Sandi — SINAR')
@section('body-class', 'password-security-page')

@section('content')
    <main class="password-security-shell">
        <section class="password-security-card" aria-labelledby="password-security-title">
            <a class="password-security-home" href="{{ route('home') }}">SINAR-CONNECT</a>
            <p class="password-security-eyebrow">Pemulihan akun</p>
            <h1 id="password-security-title">Lupa kata sandi?</h1>
            <p>Masukkan email akun Anda. Jika terdaftar, kami akan mengirim tautan pengaturan ulang.</p>

            @if (session('status'))
                <p class="password-security-status" role="status">{{ session('status') }}</p>
            @endif
            @error('email')
                <p class="password-security-errors" role="alert">{{ $message }}</p>
            @enderror

            <form method="POST" action="{{ route('password.email') }}" class="password-security-form">
                @csrf
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required>
                <button type="submit">Kirim tautan</button>
            </form>

            <a class="password-security-back" href="{{ route('login') }}">Kembali ke halaman masuk</a>
        </section>
    </main>
@endsection
