@extends('layouts.app')

@section('title', 'Dashboard Administrator — TK Cerdas Ceria')
@section('body-class', 'dashboard-page')

@section('content')
<div class="dashboard-shell">
    <aside class="sidebar">
        <a class="brand sidebar-brand" href="{{ route('home') }}">
            @include('components.brand')
        </a>
        <p class="sidebar-label">MENU UTAMA</p>
        <nav class="sidebar-nav" aria-label="Menu dashboard">
            <a class="side-link active" href="{{ route('dashboard') }}"><span>⌂</span> Dashboard</a>
            <a class="side-link" href="#siswa"><span>▣</span> Pendaftaran &amp; Data Siswa</a>
            <a class="side-link" href="#absensi"><span>◷</span> Absensi</a>
            <a class="side-link" href="#keuangan"><span>＄</span> SPP &amp; Tabungan</a>
            <a class="side-link" href="#pembelajaran"><span>▤</span> Pembelajaran</a>
            <a class="side-link" href="#nilai"><span>☆</span> Nilai &amp; Rapor</a>
            <a class="side-link" href="#kegiatan"><span>♧</span> Kegiatan</a>
            <a class="side-link" href="#field-trip"><span>⌖</span> Field Trip</a>
        </nav>
        <a class="sidebar-help" href="mailto:info@tkcerdasceria.sch.id"><span>?</span> Pusat Bantuan</a>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <button class="sidebar-toggle" type="button" aria-label="Buka menu">☰</button>
            <label class="search-box"><span aria-hidden="true">⌕</span><input type="search" placeholder="Cari..." aria-label="Cari"></label>
            <button class="notification-button" type="button" aria-label="Notifikasi">♧<i></i></button>
            <div class="admin-profile">
                <span class="admin-avatar">👩🏻‍💼</span>
                <span><strong>Administrator</strong><small>TK Cerdas Ceria</small></span>
                <span class="profile-caret">⌄</span>
            </div>
        </header>

        <div class="dashboard-content">
            <div class="welcome-row">
                <div>
                    <p class="dashboard-greeting">Kamis, 13 November 2026 <span>☀</span></p>
                    <h1>Selamat Datang, Administrator</h1>
                    <p>Kelola sistem informasi sekolah dengan mudah dan efisien.</p>
                </div>
                <button class="button button-primary add-student" type="button">＋ Tambah Siswa</button>
            </div>

            <section class="stats-grid" aria-label="Ringkasan sekolah">
                <article class="stat-card stat-blue"><span class="stat-icon">♧</span><div><p>Total Siswa</p><strong>128</strong><small>↑ 8% dari bulan lalu</small></div><span class="stat-spark">▁▃▂▅▆</span></article>
                <article class="stat-card stat-green"><span class="stat-icon">♙</span><div><p>Guru</p><strong>12</strong><small>Semua aktif</small></div><span class="stat-spark">▂▃▅▃▆</span></article>
                <article class="stat-card stat-yellow"><span class="stat-icon">＄</span><div><p>Pembayaran Bulan Ini</p><strong>Rp 12.450.000</strong><small>78% dari target</small></div><span class="stat-spark">▁▂▄▃▆</span></article>
                <article class="stat-card stat-purple"><span class="stat-icon">☆</span><div><p>Kegiatan</p><strong>8</strong><small>2 kegiatan mendatang</small></div><span class="stat-spark">▃▂▄▅▆</span></article>
            </section>

            <section class="dashboard-panels">
                <article class="panel activity-panel">
                    <div class="panel-heading"><div><h2>Aktivitas Terbaru</h2><p>Ringkasan kegiatan sekolah</p></div><a href="#aktivitas">Lihat semua →</a></div>
                    <div class="activity-list">
                        <div class="activity-item"><span class="activity-icon activity-registration">＋</span><div><strong>Pendaftaran siswa baru</strong><p>Adik Naya bergabung di Kelas A</p></div><time>09:42</time></div>
                        <div class="activity-item"><span class="activity-icon activity-payment">＄</span><div><strong>Pembayaran SPP diterima</strong><p>Rafi Pratama · November 2026</p></div><time>09:15</time></div>
                        <div class="activity-item"><span class="activity-icon activity-attendance">✓</span><div><strong>Absensi hari ini</strong><p>90 siswa hadir di sekolah</p></div><time>08:30</time></div>
                    </div>
                </article>
                <article class="panel announcements-panel">
                    <div class="panel-heading"><div><h2>Pengumuman</h2><p>Informasi untuk warga sekolah</p></div><a href="#pengumuman">Lihat semua →</a></div>
                    <article class="announcement-item"><span class="announcement-dot"></span><div><strong>Rapat orang tua murid</strong><p>Sabtu, 15 November 2026 · Aula TK</p><span class="announcement-tag">Mendatang</span></div></article>
                    <article class="announcement-item"><span class="announcement-dot dot-green"></span><div><strong>Field Trip ke Kebun Binatang</strong><p>Persiapkan bekal dan topi anak.</p><span class="announcement-tag tag-green">Kegiatan</span></div></article>
                </article>
            </section>
            <footer class="dashboard-footer">© 2026 TK Cerdas Ceria <span>Belajar · Bermain · Tumbuh</span></footer>
        </div>
    </main>
</div>
@endsection
