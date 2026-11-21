@extends('layouts.app')

@section('title', ($module['title'] ?? ('Dashboard '.$roleName)).' — SINAR')
@section('body-class', 'database-dashboard-page')

@section('content')
    @php
        $roleInternal = ['guru' => 'teacher', 'kepala_sekolah' => 'principal', 'orang_tua' => 'parent'];
        $currentAcademicDate = now('Asia/Jakarta');
        $academicStartYear = $currentAcademicDate->month >= 7
            ? $currentAcademicDate->year
            : $currentAcademicDate->year - 1;
        $navIcons = [
            'ringkasan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9.5V20h14V9.5"/><path d="M9 20v-6h6v6"/></svg>',
            'siswa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
            'guru' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 10-10-5L2 10l10 5 10-5Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
            'kelas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M10 21v-4h4v4"/></svg>',
            'keluarga' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/><circle cx="12" cy="13" r="2.2"/><path d="M8.5 18.5a3.5 3.5 0 0 1 7 0"/></svg>',
            'kegiatan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3Z"/><circle cx="12" cy="13" r="3.2"/></svg>',
            'pengguna' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><circle cx="12" cy="10" r="2.3"/><path d="M8.8 15.5a3.6 3.6 0 0 1 6.4 0"/></svg>',
            'pendaftaran' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><rect x="8" y="2" width="8" height="4" rx="1"/><path d="M12 11v6M9 14h6"/></svg>',
            'dokumen-pendaftaran' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/><path d="m9 15 2 2 4-4"/></svg>',
            'absensi' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>',
            'rekap-absensi' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-6"/></svg>',
            'pembelajaran' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
            'tugas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="m8 10 2 2 4-4M8 16h8"/></svg>',
            'nilai' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
            'rekap-nilai' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M18 20V10M12 20V4M6 20v-6"/></svg>',
            'perkembangan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
            'rapor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>',
            'tagihan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 2v20l2-1 2 1 2-1 2 1 2-1 2 1 2-1 2 1V2l-2 1-2-1-2 1-2-1-2 1-2-1-2 1Z"/><path d="M8 10h8M8 14h6"/></svg>',
            'pembayaran' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>',
            'tunggakan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>',
            'rekap-keuangan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="m7 14 4-4 4 3 5-6"/></svg>',
            'tarif' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.59 13.41 13.42 20.58a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82Z"/><circle cx="7" cy="7" r="1.5"/></svg>',
            'tabungan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5"/><path d="M16 13h2"/></svg>',
            'rekap-tabungan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/><path d="M3 12c0 1.66 4 3 9 3s9-1.34 9-3"/></svg>',
            'audit' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10Z"/><path d="m9 12 2 2 4-4"/></svg>',
            'kesiapan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>',
            'kualitas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/><path d="M11 8v6M8 11h6"/></svg>',
            'diskon-siswa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/></svg>',
            'kapasitas-kelas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/></svg>',
            'duplikat-siswa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="8" y="8" width="13" height="13" rx="2"/><path d="M4 16H3a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v1"/></svg>',
            'pesan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>',
            'default' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8"/></svg>',
        ];
    @endphp
    <div class="database-dashboard database-dashboard-portal">
        <header class="portal-dashboard-header">
            <a class="portal-dashboard-brand" href="{{ route('dashboard') }}">
                <img class="portal-dashboard-brand-mark" src="{{ asset('images/sinar-school-logo.png') }}" alt="">
                <span><strong>SINAR-CONNECT</strong><small>TK Najmah Rugayah Darus</small></span>
            </a>
            <label class="portal-dashboard-search">
                <span aria-hidden="true">⌕</span>
                <input type="search" data-dashboard-search data-global-search-url="{{ route('dashboard.global-search') }}" placeholder="Cari menu atau data..." aria-label="Cari menu atau data" autocomplete="off">
                <div class="portal-global-search-results" data-global-search-results hidden></div>
            </label>
            <div class="portal-dashboard-term">
                <span>Semester {{ $currentAcademicDate->month >= 7 ? 'Ganjil' : 'Genap' }} · T.A. {{ $academicStartYear }}/{{ $academicStartYear + 1 }}</span>
                <strong>{{ $currentAcademicDate->locale('id')->translatedFormat('l, d F Y') }}</strong>
            </div>
            <div class="portal-dashboard-account">
                @include('dashboard.partials.message-notifications')
                <div class="portal-profile-menu" data-profile-menu>
                    <button class="portal-profile-trigger"
                            type="button"
                            aria-expanded="false"
                            aria-controls="portal-profile-dropdown"
                            data-profile-menu-toggle>
                        @if ($user->profile_photo_path)
                            <img class="database-topbar-avatar" src="{{ route('profile.photo', ['user' => $user->getKey()]) }}" alt="">
                        @else
                            <span class="database-topbar-avatar">{{ mb_substr($user->name, 0, 1) }}</span>
                        @endif
                        <span class="portal-profile-copy">
                            <strong>{{ $user->name }}</strong>
                            @if (mb_strtolower($user->name) !== mb_strtolower($roleName))
                                <small>{{ $roleName }}</small>
                            @endif
                        </span>
                        <span class="portal-profile-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="portal-profile-dropdown" id="portal-profile-dropdown" data-profile-menu-panel hidden>
                        <div class="portal-profile-dropdown-heading">
                            <strong>{{ $user->name }}</strong>
                            @if (mb_strtolower($user->name) !== mb_strtolower($roleName))
                                <small>{{ $roleName }}</small>
                            @endif
                        </div>
                        <a href="{{ route('profile.edit') }}"><span aria-hidden="true">◎</span> Profil Saya</a>
                        <a href="{{ route('messages.index') }}"><span aria-hidden="true">✉</span> Pesan</a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit"><span aria-hidden="true">↪</span> Keluar</button>
                        </form>
                    </div>
                </div>
            </div>
        </header>
        <div class="mobile-drawer-backdrop" data-mobile-drawer-backdrop hidden></div>
        <button type="button" class="mobile-drawer-toggle" data-mobile-drawer-toggle aria-expanded="false" aria-controls="mobile-sidebar-drawer" aria-label="Buka menu navigasi">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <circle cx="5" cy="7" r="1" fill="currentColor" stroke="none"/>
                <line x1="9" y1="7" x2="19" y2="7"/>
                <circle cx="5" cy="12" r="1" fill="currentColor" stroke="none"/>
                <line x1="9" y1="12" x2="19" y2="12"/>
                <circle cx="5" cy="17" r="1" fill="currentColor" stroke="none"/>
                <line x1="9" y1="17" x2="19" y2="17"/>
            </svg>
        </button>
        <aside class="database-sidebar" id="mobile-sidebar-drawer">
            <div class="mobile-drawer-head">
                <span>Menu Navigasi</span>
                <button type="button" class="mobile-drawer-close" data-mobile-drawer-close aria-label="Tutup menu">✕</button>
            </div>
            <a class="database-brand" href="{{ route('dashboard') }}">
                <img class="database-brand-mark" src="{{ asset('images/sinar-school-logo.png') }}" alt="">
                <span><strong>SINAR-CONNECT</strong><small>Portal sekolah</small></span>
            </a>

            <nav class="database-navigation" aria-label="Menu data sekolah">
                <a class="{{ $section === 'ringkasan' ? 'is-active' : '' }}" data-dashboard-searchable href="{{ route('dashboard') }}" @if ($section === 'ringkasan') aria-current="page" @endif>
                    <span class="database-nav-icon" aria-hidden="true">{!! $navIcons['ringkasan'] !!}</span>
                    {{ $role === 'parent' ? 'Beranda' : 'Dashboard' }}
                </a>
                @foreach ($moduleGroups as $group)
                    <div class="database-navigation-group" data-navigation-group>
                        <span class="database-navigation-heading">{{ $group['title'] }}</span>
                        @foreach ($group['modules'] as $key => $title)
                            <a class="{{ $section === $key ? 'is-active' : '' }}"
                               data-dashboard-searchable
                               @if ($section === $key) aria-current="page" @endif
                               href="{{ route('dashboard.feature', ['section' => $key]) }}">
                                <span class="database-nav-icon" aria-hidden="true">{!! $navIcons[$key] ?? $navIcons['default'] !!}</span>
                                {{ $title }}
                            </a>
                        @endforeach
                    </div>
                @endforeach
                <div class="database-navigation-group" data-navigation-group>
                    <span class="database-navigation-heading">Komunikasi</span>
                    <a class="{{ request()->routeIs('messages.*') ? 'is-active' : '' }}"
                       data-dashboard-searchable
                       href="{{ route('messages.index') }}"
                       @if (request()->routeIs('messages.*')) aria-current="page" @endif>
                        <span class="database-nav-icon" aria-hidden="true">{!! $navIcons['pesan'] !!}</span>
                        Pesan
                    </a>
                </div>
            </nav>
            <p class="database-navigation-empty" data-dashboard-search-empty aria-live="polite">Menu tidak ditemukan.</p>

        </aside>

        <main class="database-main">
            <div class="database-page-heading">
                    @if ($role === 'parent' && $section === 'ringkasan')
                        <span class="database-eyebrow">PORTAL ORANG TUA</span>
                        <h1>Beranda</h1>
                    @else
                        <span class="database-eyebrow">{{ $roleName }} SINAR-CONNECT</span>
                        <h1>{{ $module['title'] ?? 'Dashboard '.$roleName }}</h1>
                    @endif
            </div>

            @if (session('status'))
                <div class="database-flash" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="database-form-errors" role="alert">
                    <strong>Ada data yang perlu diperbaiki:</strong>
                    <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
                </div>
            @endif

            @if ($section === 'ringkasan')
                @php
                    $roleWelcome = [
                        'admin' => ['kicker' => 'PANEL ADMINISTRATOR', 'description' => 'Kelola data sekolah, pendaftaran, keuangan, dan akses pengguna dari satu dashboard.', 'mark' => 'A'],
                        'principal' => ['kicker' => 'PEMANTAUAN SEKOLAH', 'description' => 'Pantau kegiatan akademik dan administrasi sekolah dalam satu ringkasan.', 'mark' => 'K'],
                        'teacher' => ['kicker' => 'RUANG KERJA GURU', 'description' => 'Akses kelas, kehadiran, pembelajaran, tugas, dan perkembangan siswa yang Anda ampu.', 'mark' => 'G'],
                        'parent' => ['kicker' => 'PORTAL ORANG TUA', 'description' => 'Pantau kehadiran, perkembangan, tugas, rapor, serta informasi pembayaran anak Anda.', 'mark' => 'O'],
                    ];
                    $welcome = $roleWelcome[$role];
                    $roleQuickLinks = [
                        'admin' => [
                            ['section' => 'pendaftaran', 'icon' => '+', 'title' => 'Pendaftaran', 'description' => 'Tinjau calon siswa dan dokumen'],
                            ['section' => 'siswa', 'icon' => 'S', 'title' => 'Data Siswa', 'description' => 'Lihat dan kelola profil siswa'],
                            ['section' => 'pembayaran', 'icon' => 'Rp', 'title' => 'Pembayaran', 'description' => 'Pantau pembayaran dan transaksi'],
                            ['section' => 'pengguna', 'icon' => 'A', 'title' => 'Pengguna & Hak Akses', 'description' => 'Atur akun dan peran pengguna'],
                        ],
                        'principal' => [
                            ['section' => 'siswa', 'icon' => 'S', 'title' => 'Data Siswa', 'description' => 'Pantau data dan kelas siswa'],
                            ['section' => 'pendaftaran', 'icon' => '+', 'title' => 'Pendaftaran', 'description' => 'Tinjau perkembangan pendaftaran'],
                            ['section' => 'absensi', 'icon' => 'H', 'title' => 'Kehadiran', 'description' => 'Pantau rekap kehadiran'],
                            ['section' => 'rapor', 'icon' => 'R', 'title' => 'Rapor', 'description' => 'Lihat status rapor siswa'],
                        ],
                        'teacher' => [
                            ['section' => 'absensi', 'icon' => 'H', 'title' => 'Kehadiran', 'description' => 'Catat kehadiran kelas'],
                            ['section' => 'pembelajaran', 'icon' => 'P', 'title' => 'Rencana Pembelajaran', 'description' => 'Kelola rencana mengajar'],
                            ['section' => 'tugas', 'icon' => 'T', 'title' => 'Tugas', 'description' => 'Buat dan nilai tugas kelas'],
                            ['section' => 'nilai', 'icon' => 'N', 'title' => 'Nilai Iqro', 'description' => 'Catat capaian belajar siswa'],
                        ],
                        'parent' => [
                            ['section' => 'perkembangan', 'icon' => 'P', 'title' => 'Perkembangan Anak', 'description' => 'Lihat catatan dan dokumentasi'],
                            ['section' => 'absensi', 'icon' => 'H', 'title' => 'Kehadiran', 'description' => 'Pantau kehadiran anak'],
                            ['section' => 'tugas', 'icon' => 'T', 'title' => 'Tugas Anak', 'description' => 'Lihat dan kumpulkan tugas'],
                            ['section' => 'pembayaran', 'icon' => 'Rp', 'title' => 'Pembayaran', 'description' => 'Periksa tagihan dan pembayaran'],
                        ],
                    ];
                    $quickLinks = $roleQuickLinks[$role] ?? [];
                    $quickLinkDescriptions = [
                        'admin' => 'Pintasan ke tugas administrasi yang sering digunakan.',
                        'principal' => 'Pintasan untuk memantau data dan kegiatan sekolah.',
                        'teacher' => 'Pintasan ke kegiatan belajar dan pengelolaan kelas.',
                        'parent' => 'Pintasan ke informasi anak yang terhubung ke akun Anda.',
                    ];
                @endphp
                @if ($role === 'parent')
                    <section class="parent-greeting">
                        <div>
                            <span class="database-welcome-kicker">PORTAL ORANG TUA · SINAR-CONNECT</span>
                            <h2>Selamat datang, {{ $user->name }}</h2>
                            <p>Pantau informasi dan kegiatan belajar anak Anda dari satu tempat.</p>
                        </div>
                    </section>
                    <section class="parent-children-section" aria-label="Profil anak">
                        <div class="parent-section-heading">
                            <div>
                                <h2>Anak Saya</h2>
                                <p>Profil anak yang terhubung dengan akun Anda.</p>
                            </div>
                            <span>{{ count($actions['rosters'] ?? []) }} anak</span>
                        </div>
                        <div class="parent-child-grid">
                            @forelse ($actions['rosters'] ?? [] as $child)
                                <article class="parent-child-card">
                                    <span class="parent-child-avatar">{{ mb_substr($child->nama_lengkap, 0, 1) }}</span>
                                    <div class="parent-child-info">
                                        <strong>{{ $child->nama_lengkap }}</strong>
                                        <span>{{ $child->nama_kelas }} · Kelompok {{ $child->kelompok }}</span>
                                    </div>
                                    <a href="{{ route('dashboard.feature', ['section' => 'siswa']) }}" aria-label="Lihat data {{ $child->nama_lengkap }}">→</a>
                                </article>
                            @empty
                                <div class="parent-child-empty">
                                    <strong>Belum ada profil anak yang terhubung</strong>
                                    <p>Hubungi administrator sekolah untuk menghubungkan akun orang tua dengan data anak.</p>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @else
                    <section class="database-intro database-welcome database-welcome-{{ $role }}">
                        <span class="database-welcome-kicker">{{ $welcome['kicker'] }} · SINAR-CONNECT</span>
                        <h2>Selamat datang, {{ $user->name }}</h2>
                        <p>{{ $welcome['description'] }}</p>
                        <span class="database-welcome-date">{{ now()->locale('id')->translatedFormat('l, d F Y') }}</span>
                        <span class="database-welcome-mark" aria-hidden="true">{{ $welcome['mark'] }}</span>
                    </section>
                @endif

                <section class="database-stat-grid" aria-label="Ringkasan">
                    @foreach ($cards as $card)
                        <article class="database-stat-card">
                            <span>{{ $card['label'] }}</span>
                            <strong>{{ $card['value'] }}</strong>
                        </article>
                    @endforeach
                </section>

                @if ($quickLinks !== [])
                    <section class="database-quick-actions" aria-label="Akses cepat {{ mb_strtolower($roleName) }}">
                        <div class="database-section-heading">
                            <div>
                                <h2>Akses cepat {{ mb_strtolower($roleName) }}</h2>
                                <p>{{ $quickLinkDescriptions[$role] }}</p>
                            </div>
                        </div>
                        <div class="database-quick-action-grid">
                            @foreach ($quickLinks as $link)
                                <a class="database-quick-action" data-dashboard-searchable href="{{ route('dashboard.feature', ['section' => $link['section']]) }}">
                                    <span class="database-quick-action-icon" aria-hidden="true">{!! $navIcons[$link['section']] ?? $navIcons['default'] !!}</span>
                                    <span class="database-quick-action-copy">
                                        <strong>{{ $link['title'] }}</strong>
                                        <small>{{ $link['description'] }}</small>
                                    </span>
                                    <span class="database-quick-action-arrow" aria-hidden="true">→</span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif

                <section class="database-module-section">
                    <div class="database-section-heading">
                        <div>
                            <h2>{{ $role === 'admin' ? 'Semua fitur administrator' : 'Semua menu '.mb_strtolower($roleName) }}</h2>
                            <p>{{ $role === 'admin' ? 'Buka modul untuk mengelola dan memantau data sekolah.' : 'Menu yang tersedia mengikuti peran dan akses akun Anda.' }}</p>
                        </div>
                    </div>
                    <div class="database-module-groups">
                        @foreach ($moduleGroups as $group)
                            <section class="database-module-group" data-module-group>
                                <h3>{{ $group['title'] }}</h3>
                                <div class="database-module-grid">
                                    @foreach ($group['modules'] as $key => $title)
                                        <a class="database-module-card" data-dashboard-searchable href="{{ route('dashboard.feature', ['section' => $key]) }}">
                                            <span class="database-module-icon" aria-hidden="true">{!! $navIcons[$key] ?? $navIcons['default'] !!}</span>
                                            <span class="database-module-arrow">→</span>
                                            <strong>{{ $title }}</strong>
                                            <small>Lihat data {{ strtolower($title) }}</small>
                                        </a>
                                    @endforeach
                                </div>
                            </section>
                        @endforeach
                    </div>
                </section>
            @else
                @include('dashboard.feature-actions')
                <section class="database-list-section">
                    <div class="database-section-heading">
                        <div>
                            <h2>{{ $module['title'] }}</h2>
                            <p>{{ $module['description'] }}</p>
                        </div>
                        <div class="database-list-controls">
                            <form method="GET" action="{{ url()->current() }}" class="database-record-search-form">
                                <label class="database-record-search">
                                    <span>Cari data</span>
                                    <input type="search" name="q" value="{{ request()->query('q') }}" data-record-search placeholder="Cari di semua halaman..." aria-label="Cari data">
                                </label>
                                <button class="database-button database-button-secondary" type="submit">Cari</button>
                                @if (filled(request()->query('q')))
                                    <a class="database-button database-button-secondary" href="{{ url()->current() }}">Reset</a>
                                @endif
                            </form>
                            <span class="database-result-count">{{ $records->total() }} data total</span>
                        </div>
                    </div>

                    @if ($records->isEmpty())
                        <div class="database-empty-state">
                            @if ($role === 'parent' && in_array($section, ['tagihan', 'pembayaran'], true) && ! $hasLinkedStudents)
                                <strong>Akun orang tua ini belum terhubung ke siswa.</strong>
                                <p>Hubungi administrator untuk menghubungkan akun ke data keluarga dan siswa yang benar.</p>
                            @elseif ($role === 'parent' && $section === 'tagihan')
                                <strong>Belum ada tagihan untuk siswa yang terhubung.</strong>
                                <p>Tagihan akan muncul setelah administrator menerbitkannya.</p>
                            @elseif ($role === 'parent' && $section === 'pembayaran')
                                <strong>Belum ada riwayat pembayaran.</strong>
                                <p>Pembayaran yang dikirim akan muncul setelah tercatat di sistem.</p>
                            @else
                                <strong>Belum ada data untuk ditampilkan</strong>
                                <p>Data pada modul ini akan muncul di sini setelah tersedia di database atau sesuai akses akun.</p>
                            @endif
                        </div>
                    @else
                        @php
                            $showActionColumn = ($role === 'admin' && in_array($section, ['siswa', 'keluarga', 'pengguna', 'guru', 'kelas', 'tarif', 'tagihan', 'pembelajaran', 'tugas', 'perkembangan', 'rapor', 'kegiatan'], true))
                                || ($role === 'teacher' && in_array($section, ['pembelajaran', 'tugas', 'perkembangan', 'rapor', 'kegiatan'], true));
                        @endphp
                        <div class="database-table-wrap">
                            <table class="database-table">
                                <thead>
                                    <tr>
                                        @foreach ($module['columns'] as $label)
                                            <th scope="col">{{ $label }}</th>
                                        @endforeach
                                        @if ($section === 'pengguna')
                                            <th scope="col">Hak akses</th>
                                        @endif
                                        @if ($showActionColumn)
                                            <th scope="col">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $currencyFields = ['nominal', 'total_tagihan', 'dibayar', 'sisa', 'total_bayar', 'total_tunggakan', 'total_diterima', 'saldo', 'jumlah', 'biaya'];
                                        $badgeFields = ['status', 'status_bayar', 'status_verifikasi', 'semester_1', 'semester_2', 'nilai', 'skala', 'jenis', 'kelompok', 'kelompok_tujuan', 'semester', 'role', 'tipe', 'kategori'];
                                        $badgeSuccess = ['Aktif', 'Lunas', 'Terverifikasi', 'Diterima', 'Valid', 'Lengkap', 'Final', 'Hadir', 'BSB', 'BSH', 'Tersedia', 'Setor', 'Dinilai'];
                                        $badgeWarning = ['Menunggu', 'Menunggu Verifikasi', 'Menunggu Penilaian', 'Diajukan', 'Diverifikasi', 'Draft', 'Cicilan', 'Sebagian', 'Izin', 'Sakit', 'MB', 'Penuh', 'Tarik'];
                                        $badgeDanger = ['Ditolak', 'Tidak Valid', 'Belum Bayar', 'Belum Lengkap', 'Dibatalkan', 'Nonaktif', 'Alfa', 'BB', 'Melebihi kapasitas'];
                                    @endphp
                                    @foreach ($records as $record)
                                        <tr data-record-row>
                                            @foreach ($module['columns'] as $field => $label)
                                                <td>
                                                    @php
                                                        $cellValue = data_get($record, $field);
                                                    @endphp
                                                    @if ($field === 'foto' && data_get($record, 'id_portofolio'))
                                                        <a class="database-photo-link" href="{{ route('database.portfolio.photo', ['portfolio' => data_get($record, 'id_portofolio')]) }}" target="_blank" rel="noopener">
                                                            <img src="{{ route('database.portfolio.photo', ['portfolio' => data_get($record, 'id_portofolio')]) }}" alt="Dokumentasi perkembangan {{ data_get($record, 'nama_lengkap') }}" loading="lazy">
                                                        </a>
                                                    @elseif ($field === 'is_active')
                                                        <span class="database-badge {{ $cellValue ? 'is-success' : 'is-danger' }}">
                                                            {{ $cellValue ? 'Aktif' : 'Nonaktif' }}
                                                        </span>
                                                    @elseif (in_array($field, $currencyFields, true) && is_numeric($cellValue))
                                                        <strong class="database-currency">Rp {{ number_format((float) $cellValue, 0, ',', '.') }}</strong>
                                                    @elseif ($field === 'nilai' && $section === 'diskon-siswa' && is_numeric($cellValue))
                                                        <strong class="database-currency">
                                                            {{ data_get($record, 'tipe') === 'Persen' ? rtrim(rtrim(number_format((float) $cellValue, 2, ',', '.'), '0'), ',').'%' : 'Rp '.number_format((float) $cellValue, 0, ',', '.') }}
                                                        </strong>
                                                    @elseif ($field === 'persen_hadir' && is_numeric($cellValue))
                                                        <strong>{{ number_format((float) $cellValue, 1, ',', '.') }}%</strong>
                                                    @elseif (in_array($field, $badgeFields, true) && is_string($cellValue) && trim($cellValue) !== '')
                                                        @php
                                                            $trimmedVal = trim($cellValue);
                                                            $badgeTone = in_array($trimmedVal, $badgeSuccess, true)
                                                                ? 'is-success'
                                                                : (in_array($trimmedVal, $badgeWarning, true)
                                                                    ? 'is-warning'
                                                                    : (in_array($trimmedVal, $badgeDanger, true) ? 'is-danger' : 'is-info'));
                                                        @endphp
                                                        <span class="database-badge {{ $badgeTone }}">{{ $trimmedVal }}</span>
                                                    @else
                                                        {{ is_string($cellValue) && trim($cellValue) === '' ? '—' : ($cellValue ?? '—') }}
                                                    @endif
                                                </td>
                                            @endforeach
                                            @if ($section === 'pengguna')
                                                <td>
                                                    <form method="POST" action="{{ route('admin.users.role', ['user' => data_get($record, 'id_user')]) }}" class="database-inline-form database-user-role-form">
                                                        @csrf
                                                        @method('PATCH')
                                                        <select name="role" aria-label="Peran {{ data_get($record, 'name') }}">
                                                            @foreach (['admin' => 'Administrator', 'teacher' => 'Guru', 'principal' => 'Kepala sekolah', 'parent' => 'Orang tua'] as $roleValue => $roleLabel)
                                                                <option value="{{ $roleValue }}" @selected(($roleInternal[data_get($record, 'role')] ?? data_get($record, 'role')) === $roleValue)>{{ $roleLabel }}</option>
                                                            @endforeach
                                                        </select>
                                                        <button class="database-button database-button-secondary" type="submit">Perbarui</button>
                                                    </form>
                                                </td>
                                            @endif
                                            @if ($showActionColumn)
                                                <td>
                                                    @if ($section === 'siswa')
                                                        <div class="database-row-actions">
                                                            <button type="button" class="database-icon-button" data-open-editor="student-profile-editor-{{ data_get($record, 'id_siswa') }}" aria-label="Edit profil {{ data_get($record, 'nama_lengkap') }}" title="Edit profil">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                            </button>
                                                        <form method="POST" action="{{ route('database.student.destroy', ['student' => data_get($record, 'id_siswa')]) }}" data-confirm="Hapus data siswa {{ data_get($record, 'nama_lengkap') }} beserta riwayat kelas dan pendaftarannya?">
                                                            @csrf
                                                            @method('DELETE')
                                                            <input type="hidden" name="force" value="1">
                                                            <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus data {{ data_get($record, 'nama_lengkap') }}" title="Hapus data">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                            </button>
                                                        </form>
                                                        </div>
                                                    @elseif ($section === 'keluarga')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit data keluarga {{ data_get($record, 'id_keluarga') }}" title="Edit data keluarga">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.family.update', ['family' => data_get($record, 'id_keluarga')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Nomor KK<input name="no_kk" value="{{ data_get($record, 'no_kk') }}" maxlength="16" inputmode="numeric"></label>
                                                                    <label>Alamat<textarea name="alamat" rows="2" maxlength="2000">{{ data_get($record, 'alamat') }}</textarea></label>
                                                                    <label>Nama wali / orang tua<input name="wali_name" value="{{ data_get($record, 'edit_wali_name') }}" maxlength="100"></label>
                                                                    <label>Hubungan<select name="hubungan">
                                                                        @if (! data_get($record, 'edit_wali_relationship'))
                                                                            <option value="" selected disabled>Pilih hubungan</option>
                                                                        @endif
                                                                        @foreach (['Ayah', 'Ibu', 'Wali'] as $relationship)
                                                                            <option value="{{ $relationship }}" @selected(data_get($record, 'edit_wali_relationship') === $relationship)>{{ $relationship }}</option>
                                                                        @endforeach
                                                                    </select></label>
                                                                    <label>Nomor kontak<input name="nomor" value="{{ data_get($record, 'edit_primary_phone') }}" maxlength="16" inputmode="tel"></label>
                                                                    <small>Nomor wali atau kontak yang dikosongkan tidak akan diubah.</small>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan perubahan</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.family.destroy', ['family' => data_get($record, 'id_keluarga')]) }}" data-confirm="Hapus data keluarga #{{ data_get($record, 'id_keluarga') }}? Hanya data yang tidak memiliki relasi yang dapat dihapus.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus data keluarga {{ data_get($record, 'id_keluarga') }}" title="Hapus data keluarga">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'pengguna')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit akun {{ data_get($record, 'name') }}" title="Edit akun">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.user.profile.update', ['user' => data_get($record, 'id_user')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Nama<input name="name" value="{{ data_get($record, 'name') }}" maxlength="100" required></label>
                                                                    <label>Email<input type="email" name="email" value="{{ data_get($record, 'email') }}" maxlength="150" required></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan perubahan</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.user.destroy', ['user' => data_get($record, 'id_user')]) }}" data-confirm="Hapus akun {{ data_get($record, 'name') }}? Akun yang masih memiliki riwayat atau relasi akan ditolak.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus akun {{ data_get($record, 'name') }}" title="Hapus akun">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'guru')
                                                        <div class="database-row-actions">
                                                            <form method="POST" action="{{ route('database.teacher.destroy', ['teacher' => data_get($record, 'id_guru')]) }}" data-confirm="Hapus data guru {{ data_get($record, 'nama_dengan_gelar') }}? Data yang masih memiliki riwayat kelas/rapor akan ditolak.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus guru {{ data_get($record, 'nama_dengan_gelar') }}" title="Hapus guru">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'kelas')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit kelas {{ data_get($record, 'nama_kelas') }}" title="Edit kelas">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.class.update', ['schoolClass' => data_get($record, 'id_kelas')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Nama kelas<input name="nama_kelas" value="{{ data_get($record, 'nama_kelas') }}" maxlength="50" required></label>
                                                                    <label>Kelompok<select name="kelompok" required>
                                                                        @foreach (['KB', 'TK A', 'TK B'] as $kelOption)
                                                                            <option value="{{ $kelOption }}" @selected(data_get($record, 'kelompok') === $kelOption)>{{ $kelOption }}</option>
                                                                        @endforeach
                                                                    </select></label>
                                                                    <label>Kapasitas<input type="number" name="kapasitas" value="{{ data_get($record, 'kapasitas') ?? 15 }}" min="1" max="30" required></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan perubahan</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.class.destroy', ['schoolClass' => data_get($record, 'id_kelas')]) }}" data-confirm="Hapus kelas {{ data_get($record, 'nama_kelas') }}? Kelas yang masih berisi siswa atau riwayat akan ditolak.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus kelas {{ data_get($record, 'nama_kelas') }}" title="Hapus kelas">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'tarif')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit tarif {{ data_get($record, 'nama') }}" title="Edit tarif">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.tariff.update', ['tariff' => data_get($record, 'id_tarif')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Nominal (Rp)<input type="number" name="nominal" value="{{ data_get($record, 'nominal') }}" min="0" step="1000" required></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan tarif</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.tariff.destroy', ['tariff' => data_get($record, 'id_tarif')]) }}" data-confirm="Hapus tarif {{ data_get($record, 'nama') }} ({{ data_get($record, 'kelompok') }})?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus tarif" title="Hapus tarif">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'tagihan')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit tagihan {{ data_get($record, 'nama_lengkap') }}" title="Edit tagihan">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.bills.update', ['bill' => data_get($record, 'id_tagihan')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Nominal (Rp)<input type="number" name="nominal" value="{{ (int) data_get($record, 'total_tagihan') }}" min="0" step="1000" required></label>
                                                                    <label>Diskon (Rp)<input type="number" name="diskon" value="0" min="0" step="1000" required></label>
                                                                    <label>Jatuh tempo<input type="date" name="jatuh_tempo" value="{{ data_get($record, 'jatuh_tempo') }}" required></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan tagihan</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.bills.destroy', ['bill' => data_get($record, 'id_tagihan')]) }}" data-confirm="Hapus tagihan {{ data_get($record, 'jenis_biaya') }} untuk {{ data_get($record, 'nama_lengkap') }}? Tagihan yang sudah memiliki pembayaran akan ditolak.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus tagihan" title="Hapus tagihan">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'pembelajaran')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit rencana {{ data_get($record, 'tema') }}" title="Edit rencana">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.lesson-plan.update', ['lessonPlan' => data_get($record, 'id_rencana')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Tanggal<input type="date" name="tanggal" value="{{ data_get($record, 'tanggal') }}" required></label>
                                                                    <label>Tema<input name="tema" value="{{ data_get($record, 'tema') }}" maxlength="150" required></label>
                                                                    <label>Subtema<input name="subtema" value="{{ data_get($record, 'subtema') }}" maxlength="150"></label>
                                                                    <label>Tujuan<textarea name="tujuan" rows="2">{{ data_get($record, 'tujuan') }}</textarea></label>
                                                                    <label>Kegiatan<textarea name="kegiatan" rows="2">{{ data_get($record, 'kegiatan') }}</textarea></label>
                                                                    <label>Alat &amp; bahan<textarea name="alat_bahan" rows="2">{{ data_get($record, 'alat_bahan') }}</textarea></label>
                                                                    <label>Status<select name="status" required>
                                                                        <option value="Draft" @selected(data_get($record, 'status') === 'Draft')>Draft</option>
                                                                        <option value="Final" @selected(data_get($record, 'status') === 'Final')>Final</option>
                                                                    </select></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan perubahan</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.lesson-plan.destroy', ['lessonPlan' => data_get($record, 'id_rencana')]) }}" data-confirm="Hapus rencana pembelajaran '{{ data_get($record, 'tema') }}'?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus rencana" title="Hapus rencana">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'tugas')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit tugas {{ data_get($record, 'judul') }}" title="Edit tugas">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.task.update', ['task' => data_get($record, 'id_tugas')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Judul tugas<input name="judul" value="{{ data_get($record, 'judul') }}" maxlength="150" required></label>
                                                                    <label>Tenggat<input type="date" name="tenggat" value="{{ data_get($record, 'tenggat') }}" required></label>
                                                                    <label>Deskripsi<textarea name="deskripsi" rows="2" placeholder="Opsional"></textarea></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan tugas</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.task.destroy', ['task' => data_get($record, 'id_tugas')]) }}" data-confirm="Hapus tugas '{{ data_get($record, 'judul') }}'? Tugas yang sudah dikumpulkan siswa tidak dapat dihapus.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus tugas" title="Hapus tugas">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'perkembangan')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit portofolio {{ data_get($record, 'nama_lengkap') }}" title="Edit portofolio">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.portfolio.update', ['portfolio' => data_get($record, 'id_portofolio')]) }}" enctype="multipart/form-data" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Tanggal<input type="date" name="tanggal" value="{{ data_get($record, 'tanggal') }}" max="{{ now()->toDateString() }}" required></label>
                                                                    <label>Narasi perkembangan<textarea name="narasi" rows="3" maxlength="5000" required>{{ data_get($record, 'narasi') }}</textarea></label>
                                                                    <label>Ganti foto (opsional, maks. 5 MB)<input type="file" name="foto" accept="image/jpeg,image/png,image/webp" data-max-mb="5"></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan perubahan</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.portfolio.destroy', ['portfolio' => data_get($record, 'id_portofolio')]) }}" data-confirm="Hapus portofolio {{ data_get($record, 'nama_lengkap') }}?">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus portofolio" title="Hapus portofolio">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'kegiatan')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit kegiatan {{ data_get($record, 'nama_kegiatan') }}" title="Edit kegiatan">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.activity.update', ['activity' => data_get($record, 'id_kegiatan')]) }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <label>Nama kegiatan<input name="nama_kegiatan" value="{{ data_get($record, 'nama_kegiatan') }}" maxlength="150" required></label>
                                                                    <label>Jenis<select name="jenis" required>
                                                                        @foreach (['Dalam Kelas', 'Luar Kelas', 'Field Trip'] as $jenisOption)
                                                                            <option value="{{ $jenisOption }}" @selected(data_get($record, 'jenis') === $jenisOption)>{{ $jenisOption }}</option>
                                                                        @endforeach
                                                                    </select></label>
                                                                    <label>Tanggal<input type="date" name="tanggal" value="{{ data_get($record, 'tanggal') }}" required></label>
                                                                    <label>Lokasi<input name="lokasi" value="{{ data_get($record, 'lokasi') }}" maxlength="150"></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan perubahan</button>
                                                                </form>
                                                            </details>
                                                            <form method="POST" action="{{ route('database.activity.destroy', ['activity' => data_get($record, 'id_kegiatan')]) }}" data-confirm="Hapus kegiatan '{{ data_get($record, 'nama_kegiatan') }}' beserta foto dokumentasinya? Kegiatan yang sudah memiliki tagihan akan ditolak.">
                                                                @csrf
                                                                @method('DELETE')
                                                                <button class="database-icon-button database-icon-button-danger" type="submit" aria-label="Hapus kegiatan" title="Hapus kegiatan">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M3 6h18"/><path d="M8 6V4h8v2"/><path d="m19 6-1 14H6L5 6"/><path d="M10 11v5M14 11v5"/></svg>
                                                                </button>
                                                            </form>
                                                        </div>
                                                    @elseif ($section === 'rapor')
                                                        <div class="database-row-actions">
                                                            <details class="database-row-editor">
                                                                <summary class="database-icon-button" aria-label="Edit rapor {{ data_get($record, 'nama_lengkap') }}" title="Edit rapor">
                                                                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                                </summary>
                                                                <form method="POST" action="{{ route('database.report.store') }}" class="database-row-editor-form">
                                                                    @csrf
                                                                    <input type="hidden" name="id_rombel" value="{{ data_get($record, 'id_rombel') }}">
                                                                    <input type="hidden" name="semester" value="{{ data_get($record, 'semester') }}">
                                                                    <input type="hidden" name="status" value="Final">
                                                                    <label>Tanggal rapor<input type="date" name="tanggal_rapor" value="{{ data_get($record, 'tanggal_rapor') ?: now()->toDateString() }}"></label>
                                                                    <label>Tinggi badan (cm)<input type="number" step="0.1" name="tinggi_badan" value="{{ data_get($record, 'tinggi_badan') }}" min="30" max="180"></label>
                                                                    <label>Berat badan (kg)<input type="number" step="0.1" name="berat_badan" value="{{ data_get($record, 'berat_badan') }}" min="5" max="120"></label>
                                                                    <label>Agama dan budi pekerti<textarea name="narasi_agama_budi_pekerti" rows="2" required>{{ data_get($record, 'narasi_agama_budi_pekerti') }}</textarea></label>
                                                                    <label>Jati diri<textarea name="narasi_jati_diri" rows="2" required>{{ data_get($record, 'narasi_jati_diri') }}</textarea></label>
                                                                    <label>Literasi dan STEAM<textarea name="narasi_literasi_steam" rows="2" required>{{ data_get($record, 'narasi_literasi_steam') }}</textarea></label>
                                                                    <label>Projek P5<textarea name="narasi_p5" rows="2" required>{{ data_get($record, 'narasi_p5') }}</textarea></label>
                                                                    <button class="database-button database-button-secondary" type="submit">Simpan perubahan</button>
                                                                </form>
                                                            </details>
                                                        </div>
                                                    @endif
                                                </td>
                                            @endif
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="database-filter-empty" data-record-search-empty hidden>Tidak ada data pada halaman ini yang cocok.</p>
                        <div class="database-pagination">{{ $records->links() }}</div>
                    @endif
                </section>
            @endif
            <footer class="database-footer">
                <span>© {{ now()->format('Y') }} TK Swasta Najmah Rugayah Darus Medan · NPSN 69797412</span>
                <span>Jl. Kapten Muslim No. 51, Medan Helvetia · SINAR-CONNECT</span>
            </footer>
        </main>
    </div>
@endsection
