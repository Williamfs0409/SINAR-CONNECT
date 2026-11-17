@extends('layouts.app')

@section('title', 'Menunggu Konfirmasi — SINAR')
@section('body-class', 'password-security-page')

@section('content')
    <main class="password-security-shell">
        <section class="password-security-card" aria-labelledby="password-security-title">
            <a class="password-security-home" href="{{ route('home') }}">SINAR-CONNECT</a>
            <p class="password-security-eyebrow">Pemulihan akun</p>
            <h1 id="password-security-title">Menunggu konfirmasi administrator</h1>

            @if (session('status'))
                <p class="password-security-status" role="status">{{ session('status') }}</p>
            @endif

            @if ($pollForApproval)
                <p>Permintaan reset sedang menunggu verifikasi. Halaman ini akan memeriksa secara otomatis dan membuka formulir sandi baru setelah disetujui. Jika Anda perlu login sebagai admin di browser yang sama, logout admin setelah menyetujui, lalu masukkan email ini kembali di halaman lupa kata sandi.</p>
                <div data-password-reset-waiting data-poll-interval="5000" aria-live="polite">
                    <p class="password-security-status">Menunggu administrator…</p>
                </div>
            @else
                <p>Jika akun terdaftar, permintaan akan ditinjau administrator. Biarkan halaman ini terbuka. Jika tidak beralih setelah konfirmasi admin, ajukan kembali permintaan dari peramban ini.</p>
            @endif

            <a class="password-security-back" href="{{ route('password.request') }}">Kembali ke formulir lupa kata sandi</a>
        </section>
    </main>
@endsection
