@extends('layouts.app')

@section('title', 'Buat Kata Sandi Baru — SINAR')
@section('body-class', 'password-security-page')

@section('content')
    <main class="password-security-shell">
        <section class="password-security-card" aria-labelledby="password-security-title">
            <a class="password-security-home" href="{{ route('home') }}">SINAR-CONNECT</a>
            <p class="password-security-eyebrow">Pemulihan akun disetujui</p>
            <h1 id="password-security-title">Buat kata sandi baru</h1>
            <p>Administrator telah menyetujui permintaan Anda. Buat kata sandi untuk akun <strong>{{ $email }}</strong>.</p>

            @if ($errors->any())
                <div class="password-security-errors" role="alert">
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            <form method="POST" action="{{ route('password.owner-reset.update') }}" class="password-security-form">
                @csrf
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

                <button type="submit">Simpan kata sandi baru</button>
            </form>
        </section>
    </main>
@endsection
