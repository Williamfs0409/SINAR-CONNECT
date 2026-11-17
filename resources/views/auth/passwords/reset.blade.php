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
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
                <small>Minimal 12 karakter, dengan huruf besar, huruf kecil, angka, dan simbol. Gunakan kata sandi unik yang tidak dipakai di akun lain.</small>

                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>

                <button type="submit">Simpan kata sandi baru</button>
            </form>
        </section>
    </main>
@endsection
