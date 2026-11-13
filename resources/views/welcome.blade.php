@extends('layouts.app')

@section('title', 'TK Cerdas Ceria — Cerdas, Mudah, Terintegrasi')
@section('body-class', 'site-home')

@section('content')
<header class="site-header">
    <a class="brand" href="{{ route('home') }}" aria-label="TK Cerdas Ceria, beranda">
        @include('components.brand')
    </a>

    <button class="mobile-menu-button" type="button" aria-expanded="false" aria-controls="main-navigation" aria-label="Buka menu">
        <span></span><span></span><span></span>
    </button>

    <nav class="main-navigation" id="main-navigation" aria-label="Navigasi utama">
        <a class="active" href="{{ route('home') }}">Beranda</a>
        <a href="#fitur">Fitur</a>
        <a href="#tentang">Tentang Kami</a>
        <a href="#kontak">Kontak</a>
    </nav>

    <div class="header-actions">
        <a class="button button-outline button-small" href="{{ route('login') }}">Masuk</a>
        <a class="button button-primary button-small" href="#kontak">Daftar</a>
    </div>
</header>

<main>
    <section class="hero">
        <div class="hero-copy">
            <span class="hero-kicker"><span class="kicker-dot"></span> Teman tumbuh kembang anak</span>
            <h1>Sistem Informasi<br>Sekolah TK</h1>
            <p class="hero-highlight">Cerdas, Mudah, Terintegrasi</p>
            <p class="hero-description">Solusi digital untuk mendukung kegiatan sekolah, guru, orang tua, dan tumbuh kembang anak.</p>
            <div class="hero-actions">
                <a class="button button-primary" href="{{ route('login') }}">Mulai Sekarang <span aria-hidden="true">→</span></a>
                <a class="button button-soft" href="#fitur">Pelajari Lebih Lanjut</a>
            </div>
            <div class="hero-trust"><span aria-hidden="true">✦</span> Lebih mudah, aman, dan menyenangkan</div>
        </div>
        <div class="hero-art">
            <span class="art-sparkle sparkle-one" aria-hidden="true">✦</span>
            <span class="art-sparkle sparkle-two" aria-hidden="true">✧</span>
            <img src="{{ asset('images/school-illustration.svg') }}" alt="Ilustrasi dua anak membaca bersama di depan sekolah">
            <span class="art-note"><span aria-hidden="true">☀</span> Belajar sambil bermain!</span>
        </div>
    </section>

    <section class="features-section" id="fitur">
        <div class="section-heading">
            <span class="section-eyebrow">Semua dalam satu tempat</span>
            <h2>Fitur Utama</h2>
            <p>Dirancang untuk memenuhi kebutuhan sekolah TK secara menyeluruh</p>
        </div>
        <div class="feature-grid">
            <article class="feature-card feature-blue">
                <span class="feature-icon" aria-hidden="true">▤</span>
                <h3>Pendaftaran &amp;<br>Data Siswa</h3>
                <p>Kelola pendaftaran, data siswa, dan absensi</p>
            </article>
            <article class="feature-card feature-yellow">
                <span class="feature-icon" aria-hidden="true">＄</span>
                <h3>SPP &amp; Tabungan</h3>
                <p>Pembayaran tunai / transfer dan tabungan siswa</p>
            </article>
            <article class="feature-card feature-green">
                <span class="feature-icon" aria-hidden="true">▣</span>
                <h3>Pembelajaran</h3>
                <p>Rencana belajar, tugas, nilai, dan rapor</p>
            </article>
            <article class="feature-card feature-pink">
                <span class="feature-icon" aria-hidden="true">◎</span>
                <h3>Kegiatan &amp;<br>Field Trip</h3>
                <p>Dokumentasi kegiatan dan pemantauan kehadiran</p>
            </article>
        </div>
    </section>

    <section class="roles-section" id="tentang">
        <div class="section-heading compact-heading">
            <h2>4 Pengguna, Sesuai Peran</h2>
            <p>Hak akses berbeda untuk setiap pengguna</p>
        </div>
        <div class="role-grid">
            <div class="role-item"><span class="role-avatar avatar-admin" aria-hidden="true">👨🏻‍💼</span><strong>Administrator TK</strong></div>
            <div class="role-item"><span class="role-avatar avatar-principal" aria-hidden="true">👩🏻‍🏫</span><strong>Kepala Sekolah</strong></div>
            <div class="role-item"><span class="role-avatar avatar-teacher" aria-hidden="true">👩🏽‍🏫</span><strong>Guru</strong></div>
            <div class="role-item"><span class="role-avatar avatar-parent" aria-hidden="true">👨🏻‍👧</span><strong>Orang Tua</strong></div>
        </div>
    </section>
</main>

<footer class="site-footer" id="kontak">
    <div class="footer-main">
        <a class="brand footer-brand" href="{{ route('home') }}">
            @include('components.brand')
        </a>
        <div class="footer-column">
            <h3>Menu</h3>
            <a href="{{ route('home') }}">Beranda</a>
            <a href="#fitur">Fitur</a>
            <a href="#tentang">Tentang Kami</a>
            <a href="#kontak">Kontak</a>
        </div>
        <div class="footer-column">
            <h3>Fitur</h3>
            <a href="#fitur">Pendaftaran</a>
            <a href="#fitur">Pembayaran</a>
            <a href="#fitur">Pembelajaran</a>
            <a href="#fitur">Kegiatan</a>
        </div>
        <div class="footer-column footer-contact">
            <h3>Kontak</h3>
            <span>⌖ &nbsp;Jl. Pendidikan No. 123</span>
            <span>⌕ &nbsp;Kota Bahagia</span>
            <a href="mailto:info@tkcerdasceria.sch.id">✉ &nbsp;info@tkcerdasceria.sch.id</a>
        </div>
    </div>
    <div class="footer-bottom">© 2026 TK Cerdas Ceria. Hak cipta dilindungi.</div>
</footer>
@endsection
