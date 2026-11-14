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
                <a class="role-nav-link {{ $dashboardSection === $item['id'] ? 'is-active' : '' }}" href="{{ $dashboardLink }}">
                    <span aria-hidden="true">{{ $item['icon'] }}</span>{{ $item['label'] }}
                </a>
            @endforeach
        </nav>
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
            <div class="role-welcome">
                <strong>Halo, {{ $dashboardDisplayName }} ({{ $dashboardRoleName }})</strong>
                <small><time data-live-date>{{ now()->locale('id')->translatedFormat('l, d F Y') }}</time> · <time data-live-clock>{{ now()->timezone('Asia/Jakarta')->format('H:i:s') }}</time> WIB · Semester ganjil 2026/2027</small>
            </div>
            <button class="role-notifications" type="button" aria-label="Notifikasi">Notifikasi <span aria-hidden="true">♧</span></button>
            <span class="role-user-avatar" aria-hidden="true">{{ mb_substr($dashboardDisplayName, 0, 1) }}</span>
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
