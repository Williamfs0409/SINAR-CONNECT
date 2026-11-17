@extends('layouts.app')

@section('title', 'Pendaftaran Berhasil — SINAR')
@section('body-class', 'registration-page')

@section('content')
    <main class="registration-shell">
        <header class="registration-header">
            <a class="registration-brand" href="{{ route('home') }}"><img src="{{ asset('images/sinar-school-logo.png') }}" alt=""><strong>SINAR-CONNECT</strong></a>
        </header>

        <section
            class="registration-card"
            data-registration-success
            data-login-url="{{ route('login') }}"
            aria-labelledby="registration-success-title"
        >
            <p class="registration-eyebrow">Pendaftaran siswa baru</p>
            <h1 id="registration-success-title">Pendaftaran berhasil dikirim</h1>
            <p class="registration-intro" role="status">Akun orang tua berhasil dibuat. Pendaftaran menunggu pemeriksaan administrator; masuk untuk memantau statusnya.</p>
            <p>Anda akan diarahkan ke halaman masuk dalam <strong data-registration-countdown>5</strong> detik.</p>
            <a href="{{ route('login') }}">Masuk sekarang</a>
        </section>
    </main>
@endsection
