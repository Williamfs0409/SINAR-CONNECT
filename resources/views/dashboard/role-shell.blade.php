<div class="role-dashboard">
    <aside class="role-sidebar">
        <a class="role-brand" href="{{ route('dashboard') }}">
            <img class="role-brand-mark" src="{{ asset('images/sinar-school-logo.png') }}" alt="">
            <strong>SINAR</strong>
            <small>TK Najmah Rugayah Darus</small>
        </a>
        <nav class="role-nav" aria-label="Menu dashboard">
            @php
                $roleIcons = [
                    'ringkasan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m3 10 9-7 9 7"/><path d="M5 9.5V20h14V9.5"/><path d="M9 20v-6h6v6"/></svg>',
                    'absensi' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2"/><path d="M16 2v4M8 2v4M3 10h18"/><path d="m9 16 2 2 4-4"/></svg>',
                    'pembelajaran' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
                    'rencana' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/><path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/></svg>',
                    'tugas' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="16" rx="2"/><path d="m8 10 2 2 4-4M8 16h8"/></svg>',
                    'nilai' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>',
                    'rapor' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6M8 13h8M8 17h5"/></svg>',
                    'kegiatan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14.5 4h-5L7 7H4a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h16a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-3l-2.5-3Z"/><circle cx="12" cy="13" r="3.2"/></svg>',
                    'pengaturan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>',
                    'jadwal' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 3"/></svg>',
                    'guru' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
                    'administrasi' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 21h18"/><path d="M5 21V7l7-4 7 4v14"/><path d="M9 9h1M14 9h1M9 13h1M14 13h1M10 21v-4h4v4"/></svg>',
                    'akademik' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m22 10-10-5L2 10l10 5 10-5Z"/><path d="M6 12v5c3 3 9 3 12 0v-5"/></svg>',
                    'evaluasi' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="M22 4 12 14.01l-3-3"/></svg>',
                    'spp' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="2" y="5" width="20" height="14" rx="2"/><path d="M2 10h20M6 15h4"/></svg>',
                    'laporan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3 3v18h18"/><path d="M7 16v-4M12 16V8M17 16v-6"/></svg>',
                    'perkembangan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg>',
                    'tabungan' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h14a2 2 0 0 1 2 2v10a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5"/><path d="M16 13h2"/></svg>',
                    'siswa' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>',
                    'default' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8"/></svg>',
                ];
            @endphp
            @foreach ($dashboardMenu as $item)
                @php
                    $dashboardLink = $item['id'] === 'ringkasan'
                        ? route('dashboard', auth()->guest() ? ['role' => $dashboardRoleKey] : [])
                        : route('dashboard.feature', array_merge(
                            ['section' => $item['id']],
                            auth()->guest() ? ['role' => $dashboardRoleKey] : []
                        ));
                @endphp
                <a class="role-nav-link {{ $dashboardSection === $item['id'] ? 'is-active' : '' }}" data-dashboard-searchable href="{{ $dashboardLink }}">
                    <span class="database-nav-icon" aria-hidden="true">{!! $roleIcons[$item['id']] ?? $roleIcons['default'] !!}</span>
                    {{ $item['label'] }}
                </a>
            @endforeach
        </nav>
        <p class="database-navigation-empty" data-dashboard-search-empty aria-live="polite">Menu tidak ditemukan.</p>
        <div id="pengaturan" class="role-sidebar-bottom">
            @auth
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="role-logout" type="submit"><span aria-hidden="true">↪</span> Keluar</button>
                </form>
            @endauth
            <small>SINAR © {{ now()->format('Y') }}</small>
        </div>
    </aside>

    <main class="role-main">
        <header class="role-topbar">
            <button class="sidebar-toggle" type="button" aria-label="Buka menu">☰</button>
            <label class="portal-dashboard-search">
                <span aria-hidden="true">⌕</span>
                <input type="search" data-dashboard-search placeholder="Cari menu..." aria-label="Cari menu">
            </label>
            <div class="portal-dashboard-term">
                <span>Semester Ganjil · T.A. 2026/2027</span>
                <strong>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</strong>
                <time data-live-clock datetime="{{ now('Asia/Jakarta')->toIso8601String() }}" aria-label="Waktu WIB">{{ now('Asia/Jakarta')->format('H:i:s') }}</time>
            </div>
            <div class="portal-dashboard-account">
                @guest
                    <div class="message-notifications role-notification-menu" data-role-notifications="{{ $dashboardRoleKey }}">
                        <button class="message-notifications-toggle"
                                type="button"
                                aria-label="Notifikasi {{ $dashboardRoleName }}"
                                aria-expanded="false"
                                aria-controls="role-notification-panel"
                                data-role-notifications-toggle>
                            <svg viewBox="0 0 24 24" aria-hidden="true">
                                <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                            </svg>
                            <span class="message-unread-badge"
                                  data-role-notification-badge
                                  aria-live="polite">{{ count($dashboardNotifications) }}</span>
                        </button>
                        <section class="message-notification-panel"
                                 id="role-notification-panel"
                                 data-role-notification-panel
                                 aria-label="Notifikasi {{ $dashboardRoleName }}"
                                 hidden>
                            <div class="message-notification-heading">
                                <strong>Notifikasi {{ $dashboardRoleName }}</strong>
                                <button class="role-notification-mark-all"
                                        type="button"
                                        data-role-notifications-mark-all>
                                    Tandai semua dibaca
                                </button>
                            </div>
                            <div data-role-notification-items>
                                @forelse ($dashboardNotifications as $notification)
                                    <a class="message-notification-item"
                                       data-role-notification-item
                                       data-notification-id="{{ $notification['id'] }}"
                                       href="{{ $notification['href'] }}">
                                        <strong>{{ $notification['title'] }}</strong>
                                        <span>{{ $notification['message'] }}</span>
                                    </a>
                                @empty
                                    <p class="message-notification-empty">Belum ada notifikasi baru.</p>
                                @endforelse
                            </div>
                        </section>
                    </div>
                @else
                    <a class="message-notifications-toggle"
                       href="{{ route('messages.index') }}"
                       aria-label="Buka pesan"
                       title="Buka pesan">
                        <svg viewBox="0 0 24 24" aria-hidden="true">
                            <path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9M10 21h4" />
                        </svg>
                    </a>
                @endguest
                <div class="portal-profile-menu" data-profile-menu>
                    <button class="portal-profile-trigger"
                            type="button"
                            aria-expanded="false"
                            aria-controls="role-profile-dropdown"
                            data-profile-menu-toggle>
                        @auth
                            @if (auth()->user()->profile_photo_path)
                                <img class="database-topbar-avatar" src="{{ route('profile.photo', ['user' => auth()->user()->getKey()]) }}" alt="">
                            @else
                                <span class="database-topbar-avatar" aria-hidden="true">{{ mb_substr($dashboardDisplayName, 0, 1) }}</span>
                            @endif
                        @else
                            <span class="database-topbar-avatar" aria-hidden="true">{{ mb_substr($dashboardDisplayName, 0, 1) }}</span>
                        @endauth
                        <span class="portal-profile-copy">
                            <strong>{{ $dashboardDisplayName }}</strong>
                            <small>{{ $dashboardRoleName }}</small>
                        </span>
                        <span class="portal-profile-chevron" aria-hidden="true">⌄</span>
                    </button>
                    <div class="portal-profile-dropdown" id="role-profile-dropdown" data-profile-menu-panel hidden>
                        <div class="portal-profile-dropdown-heading">
                            <strong>{{ $dashboardDisplayName }}</strong>
                            <small>{{ $dashboardRoleName }}</small>
                        </div>
                        @auth
                            <a href="{{ route('profile.edit') }}"><span aria-hidden="true">◎</span> Profil Saya</a>
                            <a href="{{ route('messages.index') }}"><span aria-hidden="true">✉</span> Pesan</a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit"><span aria-hidden="true">↪</span> Keluar</button>
                            </form>
                        @else
                            <a href="{{ route('login') }}"><span aria-hidden="true">↪</span> Masuk untuk mengakses akun</a>
                        @endauth
                    </div>
                </div>
            </div>
        </header>
        <div class="role-content">
            @guest
                <nav class="role-preview-banner" aria-label="Pilih dashboard pratinjau">
                    <span>Mode pratinjau · tanpa login</span>
                    <a class="{{ $dashboardRoleName === 'Guru' ? 'is-selected' : '' }}" href="{{ route('dashboard', ['role' => 'teacher']) }}">Guru</a>
                    <a class="{{ $dashboardRoleName === 'Kepala Sekolah' ? 'is-selected' : '' }}" href="{{ route('dashboard', ['role' => 'principal']) }}">Kepala Sekolah</a>
                    <a class="{{ $dashboardRoleName === 'Orang Tua' ? 'is-selected' : '' }}" href="{{ route('dashboard', ['role' => 'parent']) }}">Orang Tua</a>
                </nav>
                <p class="role-preview-greeting">Halo, {{ $dashboardDisplayName }} ({{ $dashboardRoleName }})</p>
            @endguest
            @if ($dashboardSection === 'ringkasan')
                @include($dashboardContentView)
            @else
                @include('dashboard.feature')
            @endif
            <footer class="role-footer">Data contoh · Diperbarui hari ini, {{ now()->format('H.i') }} WIB</footer>
        </div>
    </main>
</div>
