@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi — SINAR')
@section('body-class', 'password-security-page')

@section('content')
    <main class="password-security-shell">
        <section class="password-security-card" aria-labelledby="password-security-title">
            <a class="password-security-home" href="{{ route('home') }}">SINAR-CONNECT</a>
            <p class="password-security-eyebrow">Pemulihan akun</p>
            <h1 id="password-security-title">Atur ulang kata sandi</h1>
            <p>Pilih kata sandi baru untuk akun SINAR Anda.</p>

            @if ($errors->any())
                <div class="password-security-errors" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.update') }}" class="password-security-form">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" required>

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
                <small>Minimal 8 karakter, dengan huruf besar, huruf kecil, dan angka. Simbol tidak wajib.</small>

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

                <button type="submit">Simpan kata sandi baru</button>
            </form>
        </section>
    </main>
@endsection
