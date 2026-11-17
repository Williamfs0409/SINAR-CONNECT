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
    @endphp
    <div class="database-dashboard database-dashboard-portal">
        <header class="portal-dashboard-header">
            <a class="portal-dashboard-brand" href="{{ route('dashboard') }}">
                <img class="portal-dashboard-brand-mark" src="{{ asset('images/sinar-school-logo.png') }}" alt="">
                <span><strong>SINAR-CONNECT</strong><small>Portal sekolah</small></span>
            </a>
            <label class="portal-dashboard-search">
                <span aria-hidden="true">⌕</span>
                <input type="search" data-dashboard-search placeholder="Cari menu..." aria-label="Cari menu">
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
        <aside class="database-sidebar">
            <a class="database-brand" href="{{ route('dashboard') }}">
                <img class="database-brand-mark" src="{{ asset('images/sinar-school-logo.png') }}" alt="">
                <span><strong>SINAR-CONNECT</strong><small>Portal sekolah</small></span>
            </a>

            <nav class="database-navigation" aria-label="Menu data sekolah">
                <a class="{{ $section === 'ringkasan' ? 'is-active' : '' }}" data-dashboard-searchable href="{{ route('dashboard') }}" @if ($section === 'ringkasan') aria-current="page" @endif>
                    {{ $role === 'parent' ? 'Beranda' : 'Ringkasan' }}
                </a>
                @foreach ($moduleGroups as $group)
                    <div class="database-navigation-group" data-navigation-group>
                        <span class="database-navigation-heading">{{ $group['title'] }}</span>
                        @foreach ($group['modules'] as $key => $title)
                            <a class="{{ $section === $key ? 'is-active' : '' }}"
                               data-dashboard-searchable
                               @if ($section === $key) aria-current="page" @endif
                               href="{{ route('dashboard.feature', ['section' => $key]) }}">
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
                                    <span class="database-quick-action-icon" aria-hidden="true">{{ $link['icon'] }}</span>
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
                            <label class="database-record-search">
                                <span>Cari di halaman ini</span>
                                <input type="search" data-record-search placeholder="Cari di halaman ini..." aria-label="Cari data pada halaman ini">
                            </label>
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
                        <div class="database-table-wrap">
                            <table class="database-table">
                                <thead>
                                    <tr data-record-row>
                                        @foreach ($module['columns'] as $label)
                                            <th scope="col">{{ $label }}</th>
                                        @endforeach
                                        @if ($section === 'pengguna')
                                            <th scope="col">Hak akses</th>
                                        @endif
                                        @if ($role === 'admin' && in_array($section, ['siswa', 'keluarga', 'pengguna'], true))
                                            <th scope="col">Aksi</th>
                                        @endif
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($records as $record)
                                        <tr>
                                            @foreach ($module['columns'] as $field => $label)
                                                <td>
                                                    @php($cellValue = data_get($record, $field))
                                                    @if ($field === 'foto' && data_get($record, 'id_portofolio'))
                                                        <a class="database-photo-link" href="{{ route('database.portfolio.photo', ['portfolio' => data_get($record, 'id_portofolio')]) }}" target="_blank" rel="noopener">
                                                            <img src="{{ route('database.portfolio.photo', ['portfolio' => data_get($record, 'id_portofolio')]) }}" alt="Dokumentasi perkembangan {{ data_get($record, 'nama_lengkap') }}" loading="lazy">
                                                        </a>
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
                                            @if ($role === 'admin' && in_array($section, ['siswa', 'keluarga', 'pengguna'], true))
                                                <td>
                                                    @if ($section === 'siswa')
                                                        <div class="database-row-actions">
                                                            <button type="button" class="database-icon-button" data-open-editor="student-profile-editor-{{ data_get($record, 'id_siswa') }}" aria-label="Edit profil {{ data_get($record, 'nama_lengkap') }}" title="Edit profil">
                                                                <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L8 18l-4 1 1-4Z"/></svg>
                                                            </button>
                                                        <form method="POST" action="{{ route('database.student.destroy', ['student' => data_get($record, 'id_siswa')]) }}" data-confirm="Hapus data siswa {{ data_get($record, 'nama_lengkap') }}? Data yang masih memiliki riwayat akan ditolak.">
                                                            @csrf
                                                            @method('DELETE')
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
                                                    @else
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
        </main>
    </div>
@endsection
