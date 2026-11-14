@php
    $dashboardRoleKey = 'principal';
    $dashboardRoleName = 'Kepala Sekolah';
    $dashboardContentView = 'dashboard.content.principal';
    $dashboardMenu = [
        ['id' => 'ringkasan', 'icon' => '⌂', 'label' => 'Dashboard'],
        ['id' => 'guru', 'icon' => '♧', 'label' => 'Data Siswa & Guru'],
        ['id' => 'administrasi', 'icon' => '▱', 'label' => 'Administrasi Sekolah'],
        ['id' => 'akademik', 'icon' => '▤', 'label' => 'Akademik'],
        ['id' => 'evaluasi', 'icon' => '▥', 'label' => 'Evaluasi Kegiatan'],
        ['id' => 'spp', 'icon' => '▣', 'label' => 'SPP & Tabungan'],
        ['id' => 'laporan', 'icon' => '▤', 'label' => 'Laporan Sekolah'],
        ['id' => 'jadwal', 'icon' => '▦', 'label' => 'Jadwal Guru'],
    ];
    $dashboardFeatureDetails = [
        'guru' => ['description' => 'Data guru, wali kelas, dan kelompok belajar di sekolah.', 'stats' => [['Guru aktif', '8'], ['Kelompok', '8'], ['Siswa', '128']], 'columns' => ['Guru', 'Kelompok', 'Status'], 'rows' => [['Dewi Puspita', 'A1 · Melati', 'Aktif'], ['Rina Lestari', 'A2 · Mawar', 'Aktif'], ['Siti Rahma', 'B1 · Anggrek', 'Aktif'], ['Nur Aisyah', 'B2 · Dahlia', 'Aktif']]],
        'administrasi' => ['description' => 'Ringkasan berkas dan kegiatan administrasi sekolah.', 'stats' => [['Berkas terverifikasi', '118 / 128'], ['Perlu diperbarui', '10'], ['Agenda terdekat', '18 Nov']], 'columns' => ['Administrasi', 'Jumlah', 'Status'], 'rows' => [['Berkas siswa', '118 dari 128', 'Dalam proses'], ['Data kelompok', '8 kelompok', 'Lengkap'], ['Rapat evaluasi bulanan', '18 November · 13.00', 'Terjadwal']]],
        'akademik' => ['description' => 'Pantau catatan pembelajaran dan capaian setiap kelompok.', 'stats' => [['Kelompok lengkap', '6 / 8'], ['Catatan siswa', '61 / 64'], ['Perlu ditinjau', '3']], 'columns' => ['Kelompok', 'Wali kelas', 'Catatan'], 'rows' => [['A1 · Melati', 'Dewi Puspita', '16 / 16 · Lengkap'], ['A2 · Mawar', 'Rina Lestari', '14 / 16 · Proses'], ['B1 · Anggrek', 'Siti Rahma', '16 / 16 · Lengkap'], ['B2 · Dahlia', 'Nur Aisyah', '15 / 16 · Proses']]],
        'evaluasi' => ['description' => 'Tinjau rencana belajar, laporan perkembangan, dan kegiatan sekolah.', 'stats' => [['Rencana menunggu', '4'], ['Laporan perlu dilengkapi', '3'], ['Kegiatan aktif', '8']], 'columns' => ['Item evaluasi', 'Jumlah', 'Tindak lanjut'], 'rows' => [['Rencana pembelajaran', '4 rencana', 'Menunggu tinjauan'], ['Laporan perkembangan', '3 laporan', 'Perlu dilengkapi'], ['Kegiatan belajar', '8 kelompok', 'Berjalan']]],
        'spp' => ['description' => 'Pantau rekap pembayaran SPP dan saldo tabungan siswa.', 'stats' => [['Pelunasan bulan ini', '78%'], ['Tabungan siswa', 'Rp24.800.000'], ['Tagihan aktif', '28']], 'columns' => ['Ringkasan pembayaran', 'Periode', 'Jumlah'], 'rows' => [['Pelunasan SPP', 'Oktober', '78%'], ['Pembayaran diterima', 'November', 'Rp12.450.000'], ['Tabungan siswa', 'Semester ganjil', 'Rp24.800.000']]],
        'laporan' => ['description' => 'Laporan perkembangan siswa dan ringkasan kegiatan sekolah.', 'stats' => [['Laporan tersedia', '12'], ['Menunggu tinjauan', '3'], ['Periode', 'Semester ganjil']], 'columns' => ['Laporan', 'Periode', 'Status'], 'rows' => [['Perkembangan kelompok A', 'Oktober–November', 'Tersedia'], ['Evaluasi kegiatan sekolah', 'November 2026', 'Menunggu tinjauan'], ['Rekap presensi', 'November 2026', 'Tersedia']]],
        'jadwal' => ['description' => 'Jadwal mengajar dan penempatan guru pada kelompok belajar.', 'stats' => [['Guru terjadwal', '8'], ['Kelompok belajar', '8'], ['Jam belajar', '07.30–10.00']], 'columns' => ['Guru', 'Kelompok', 'Kegiatan / lokasi'], 'rows' => [['Dewi Puspita', 'A1 · Melati', 'Belajar di kebun'], ['Rina Lestari', 'A2 · Mawar', 'Kolase daun'], ['Siti Rahma', 'B1 · Anggrek', 'Mengenal tanaman'], ['Nur Aisyah', 'B2 · Dahlia', 'Bercerita lingkungan']]],
    ];
@endphp
@include('dashboard.role-shell')
