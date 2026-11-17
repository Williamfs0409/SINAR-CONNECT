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
                <input id="current_password" name="current_password" type="password" autocomplete="current-password" required>

                <label for="password">Kata sandi baru</label>
                <input id="password" name="password" type="password" autocomplete="new-password" minlength="12" required>
                <small>Minimal 12 karakter, dengan huruf besar, huruf kecil, angka, dan simbol. Gunakan kata sandi unik yang tidak dipakai di akun lain.</small>

                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" minlength="12" required>

                <button type="submit">Simpan kata sandi</button>
            </form>

            <form method="POST" action="{{ route('logout') }}" class="password-security-logout">
                @csrf
                <button type="submit">Keluar</button>
            </form>
        </section>
    </main>
@endsection
