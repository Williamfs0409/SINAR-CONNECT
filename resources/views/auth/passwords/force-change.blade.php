@extends('layouts.app')

@section('title', 'Ganti Kata Sandi — SINAR')
@section('body-class', 'password-security-page')

@section('content')
    <main class="password-security-shell">
        <section class="password-security-card" aria-labelledby="password-security-title">
            <a class="password-security-home" href="{{ route('home') }}">SINAR-CONNECT</a>
            <p class="password-security-eyebrow">Keamanan akun</p>
            <h1 id="password-security-title">Buat kata sandi pribadi</h1>
            <p>Kata sandi sementara harus diganti sebelum Anda melanjutkan ke dashboard.</p>

            @if ($errors->any())
                <div class="password-security-errors" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.force.update') }}" class="password-security-form">
                @csrf
                @method('PUT')
                <label for="current_password">Kata sandi saat ini</label>
                <span class="registration-password-wrap">
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>
                    <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="current_password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                            <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </span>

                <label for="password">Kata sandi baru</label>
                <span class="registration-password-wrap">
                    <input id="password" name="password" type="password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                            <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </span>
                <small>Minimal 8 karakter dan harus memuat huruf besar, huruf kecil, serta angka. Simbol tidak wajib.</small>

                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <span class="registration-password-wrap">
                    <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="8" required>
                    <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="password_confirmation" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                            <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </span>

                <button type="submit">Simpan kata sandi</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="password-security-logout">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        </section>
    </main>
@endsection
