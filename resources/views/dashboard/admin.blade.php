<div class="dashboard-shell">
    @php
        $adminDashboardData = array_merge([
            'studentCount' => 0,
            'pendingCount' => 0,
            'userCount' => 0,
            'monthIncome' => 0,
            'arrearsCount' => 0,
        ], $adminDashboardData);
        $adminMenu = [
            ['id' => 'ringkasan', 'icon' => '⌂', 'label' => 'Dashboard'],
            ['id' => 'pendaftaran', 'icon' => '＋', 'label' => 'Pendaftaran Siswa'],
            ['id' => 'siswa', 'icon' => '▣', 'label' => 'Data Siswa'],
            ['id' => 'transaksi', 'icon' => '＄', 'label' => 'SPP & Tabungan'],
            ['id' => 'tunggakan', 'icon' => '◷', 'label' => 'Tunggakan SPP'],
            ['id' => 'pengguna', 'icon' => '♙', 'label' => 'Pengguna & Hak Akses'],
        ];
    @endphp
    <aside class="sidebar">
        <a class="brand sidebar-brand" href="{{ route('home') }}">
            @include('components.brand')
        </a>
        <p class="sidebar-label">MENU UTAMA</p>
        <nav class="sidebar-nav" aria-label="Menu dashboard">
            @foreach ($adminMenu as $item)
                <a class="side-link {{ $adminSection === $item['id'] ? 'active' : '' }}" href="{{ $item['id'] === 'ringkasan' ? route('dashboard') : route('dashboard.feature', ['section' => $item['id']]) }}">
                    <span>{{ $item['icon'] }}</span>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <a class="sidebar-help" href="mailto:info@tkcerdasceria.sch.id"><span>?</span> Pusat Bantuan</a>
    </aside>

    <main class="dashboard-main">
        <header class="dashboard-topbar">
            <button class="sidebar-toggle" type="button" aria-label="Buka menu">☰</button>
            <label class="search-box"><span aria-hidden="true">⌕</span><input type="search" placeholder="Cari..." aria-label="Cari"></label>
            <button class="notification-button" type="button" aria-label="Notifikasi">♧<i></i></button>
            @if (($passwordResetAttentionCount ?? 0) > 0)
                <a class="message-notifications-toggle"
                   href="{{ route('admin.password-reset-requests.index') }}"
                   aria-label="{{ $passwordResetAttentionCount }} permintaan reset kata sandi masih perlu ditindaklanjuti"
                   title="Permintaan reset kata sandi masih perlu ditindaklanjuti">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M7 14a4 4 0 1 1 3.9-4.9H21v4h-3v3h-3v-3h-3.1A4 4 0 0 1 7 14Z" />
                        <circle cx="7" cy="10" r="1" />
                    </svg>
                    <span class="message-unread-badge">{{ $passwordResetAttentionCount > 99 ? '99+' : $passwordResetAttentionCount }}</span>
                </a>
            @endif
            <div class="admin-profile">
                <span class="admin-avatar">{{ mb_substr(auth()->user()->name, 0, 1) }}</span>
                <span><strong>{{ auth()->user()->name }}</strong><small>Administrator</small></span>
                @auth
                    <form method="POST" action="{{ route('logout') }}">@csrf<button class="admin-logout" type="submit">Keluar</button></form>
                @endauth
            </div>
        </header>

        <div class="dashboard-content">
            @if ($adminSection === 'ringkasan')
            <div class="welcome-row">
                <div>
                    <p class="dashboard-greeting">{{ now()->locale('id')->translatedFormat('l, d F Y') }} <span>☀</span></p>
                    <h1>Selamat Datang, {{ auth()->user()->name }}</h1>
                    <p>Kelola sistem informasi sekolah dengan mudah dan efisien.</p>
                </div>
                <a class="button button-primary add-student" href="{{ route('dashboard.feature', ['section' => 'pendaftaran']) }}">＋ Daftarkan Siswa</a>
            </div>

            <section class="stats-grid" aria-label="Ringkasan sekolah">
                <article class="stat-card stat-blue"><span class="stat-icon">♧</span><div><p>Siswa Aktif</p><strong>{{ $adminDashboardData['studentCount'] }}</strong><small>{{ $adminDashboardData['pendingCount'] }} pendaftaran menunggu</small></div><span class="stat-spark">▁▃▂▅▆</span></article>
                <article class="stat-card stat-green"><span class="stat-icon">♙</span><div><p>Akun Pengguna</p><strong>{{ $adminDashboardData['userCount'] }}</strong><small>Guru, orang tua &amp; administrator</small></div><span class="stat-spark">▂▃▅▃▆</span></article>
                <article class="stat-card stat-yellow"><span class="stat-icon">＄</span><div><p>Pembayaran SPP Bulan Ini</p><strong>Rp {{ number_format($adminDashboardData['monthIncome'], 0, ',', '.') }}</strong><small>Transaksi tercatat</small></div><span class="stat-spark">▁▂▄▃▆</span></article>
                <article class="stat-card stat-purple"><span class="stat-icon">◷</span><div><p>Tunggakan SPP</p><strong>{{ $adminDashboardData['arrearsCount'] }}</strong><small>Tagihan melewati jatuh tempo</small></div><span class="stat-spark">▃▂▄▅▆</span></article>
            </section>

            <section class="dashboard-panels">
                <article class="panel activity-panel">
                    <div class="panel-heading"><div><h2>Pengelolaan Data</h2><p>Akses cepat fitur administrator</p></div><a href="{{ route('dashboard.feature', ['section' => 'siswa']) }}">Data siswa →</a></div>
                    <div class="activity-list">
                        <a class="activity-item" href="{{ route('dashboard.feature', ['section' => 'pendaftaran']) }}"><span class="activity-icon activity-registration">＋</span><div><strong>Pendaftaran siswa baru</strong><p>Kelola calon siswa dan konfirmasi penerimaan</p></div><span>→</span></a>
                        <a class="activity-item" href="{{ route('dashboard.feature', ['section' => 'transaksi']) }}"><span class="activity-icon activity-payment">＄</span><div><strong>SPP &amp; Tabungan</strong><p>Catat transaksi tunai dan transfer</p></div><span>→</span></a>
                        <a class="activity-item" href="{{ route('dashboard.feature', ['section' => 'pengguna']) }}"><span class="activity-icon activity-attendance">♙</span><div><strong>Akun dan hak akses</strong><p>Atur peran pengguna SINAR</p></div><span>→</span></a>
                    </div>
                </article>
                <article class="panel announcements-panel">
                    <div class="panel-heading"><div><h2>Perlu Ditindaklanjuti</h2><p>Administrasi dan keuangan</p></div></div>
                    <a class="announcement-item" href="{{ route('dashboard.feature', ['section' => 'pendaftaran']) }}"><span class="announcement-dot"></span><div><strong>{{ $adminDashboardData['pendingCount'] }} pendaftaran baru</strong><p>Periksa data dan aktifkan siswa yang diterima.</p><span class="announcement-tag">Pendaftaran</span></div></a>
                    <a class="announcement-item" href="{{ route('dashboard.feature', ['section' => 'tunggakan']) }}"><span class="announcement-dot dot-green"></span><div><strong>{{ $adminDashboardData['arrearsCount'] }} tunggakan SPP</strong><p>Lihat tagihan yang melewati jatuh tempo.</p><span class="announcement-tag tag-green">Keuangan</span></div></a>
                </article>
            </section>
            @else
                @include('dashboard.admin-feature')
            @endif
            <footer class="dashboard-footer">© {{ now()->format('Y') }} TK Swasta Najmah Rugayah Darus Medan <span>Belajar · Bermain · Tumbuh</span></footer>
        </div>
    </main>
</div>
