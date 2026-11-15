<div class="role-dashboard">
    <aside class="role-sidebar">
        <a class="role-brand" href="{{ route('dashboard') }}">
            <span class="role-brand-mark" aria-hidden="true">S</span>
            <strong>SINAR</strong>
            <small>TK Najmah Rugayah Darus</small>
        </a>
        <nav class="role-nav" aria-label="Menu dashboard">
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
