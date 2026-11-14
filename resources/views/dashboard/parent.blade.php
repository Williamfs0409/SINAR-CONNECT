@php
    $dashboardRoleKey = 'parent';
    $dashboardRoleName = 'Orang Tua';
    $dashboardContentView = 'dashboard.content.parent';
    $dashboardMenu = [
        ['id' => 'ringkasan', 'icon' => '⌂', 'label' => 'Dashboard'],
        ['id' => 'perkembangan', 'icon' => '▥', 'label' => 'Perkembangan Anak'],
        ['id' => 'absensi', 'icon' => '▦', 'label' => 'Absensi Anak'],
        ['id' => 'spp', 'icon' => '▣', 'label' => 'Pembayaran SPP'],
        ['id' => 'tabungan', 'icon' => '▣', 'label' => 'Tabungan Anak'],
        ['id' => 'tugas', 'icon' => '▤', 'label' => 'Tugas Anak'],
        ['id' => 'rapor', 'icon' => '▤', 'label' => 'Rapor Anak'],
        ['id' => 'dokumentasi', 'icon' => '▱', 'label' => 'Dokumentasi Kegiatan'],
        ['id' => 'siswa', 'icon' => '♧', 'label' => 'Data Siswa'],
    ];
    $dashboardFeatureDetails = [
        'perkembangan' => ['description' => 'Pantau perkembangan belajar dan catatan guru untuk anak.', 'stats' => [['Bahasa & literasi', 'Sesuai harapan'], ['Sosial emosional', 'Sangat baik'], ['Status hari ini', 'Hadir']], 'columns' => ['Aspek perkembangan', 'Penilaian', 'Catatan'], 'rows' => [['Bahasa & literasi', 'Sesuai harapan', 'Mengikuti kegiatan bercerita'], ['Sosial emosional', 'Sangat baik', 'Percaya diri dan senang membantu'], ['Motorik halus', 'Sesuai harapan', 'Aktif dalam kegiatan kolase']]],
        'absensi' => ['description' => 'Riwayat kehadiran anak di sekolah.', 'stats' => [['Hadir', '18 hari'], ['Izin', '1 hari'], ['Sakit', '1 hari']], 'columns' => ['Tanggal', 'Keterangan', 'Dicatat oleh'], 'rows' => [['14 November 2026', 'Hadir', 'Ibu Dewi'], ['13 November 2026', 'Hadir', 'Ibu Dewi'], ['12 November 2026', 'Izin', 'Orang tua'], ['11 November 2026', 'Hadir', 'Ibu Dewi']]],
        'spp' => ['description' => 'Rincian tagihan SPP dan status pembayaran bulan ini.', 'stats' => [['Tagihan November', 'Rp250.000'], ['Jatuh tempo', '20 November'], ['Status', 'Belum dibayar']], 'columns' => ['Tagihan', 'Periode', 'Jumlah / status'], 'rows' => [['SPP bulanan', 'November 2026', 'Rp250.000 · Belum dibayar'], ['SPP bulanan', 'Oktober 2026', 'Rp250.000 · Lunas'], ['Kegiatan sekolah', 'Oktober 2026', 'Rp50.000 · Lunas']]],
        'tabungan' => ['description' => 'Riwayat setoran dan saldo tabungan anak.', 'stats' => [['Saldo saat ini', 'Rp850.000'], ['Setoran terakhir', 'Rp50.000'], ['Bulan ini', 'November']], 'columns' => ['Tanggal', 'Transaksi', 'Saldo'], 'rows' => [['10 November 2026', 'Setoran', 'Rp850.000'], ['10 Oktober 2026', 'Setoran Rp50.000', 'Rp800.000'], ['10 September 2026', 'Setoran Rp50.000', 'Rp750.000']]],
        'tugas' => ['description' => 'Tugas belajar anak yang dapat didampingi di rumah.', 'stats' => [['Tugas aktif', '2'], ['Tugas berikutnya', '17 Nov'], ['Sudah dikumpulkan', '3']], 'columns' => ['Nama tugas', 'Batas waktu', 'Status'], 'rows' => [['Kolase daun kering', '17 November · Seni & kreativitas', 'Belum dikumpulkan'], ['Bercerita bersama keluarga', '20 November · Bahasa', 'Belum dikumpulkan'], ['Mengenal tanaman', '13 November · Sains', 'Dikumpulkan']]],
        'rapor' => ['description' => 'Lihat ringkasan rapor dan perkembangan semester anak.', 'stats' => [['Periode', 'Semester ganjil'], ['Catatan tersedia', '4 aspek'], ['Status rapor', 'Berjalan']], 'columns' => ['Aspek', 'Perkembangan', 'Catatan guru'], 'rows' => [['Bahasa & literasi', 'Sesuai harapan', 'Senang mendengarkan cerita'], ['Sosial emosional', 'Sangat baik', 'Mulai percaya diri bercerita'], ['Motorik halus', 'Sesuai harapan', 'Aktif membuat kolase']]],
        'dokumentasi' => ['description' => 'Foto dan cerita kegiatan belajar anak di sekolah.', 'stats' => [['Album tersedia', '4'], ['Foto terbaru', '12'], ['Diperbarui', '13 Nov 2026']], 'columns' => ['Kegiatan', 'Tanggal', 'Dokumentasi'], 'rows' => [['Bermain & belajar di kebun', '13 November 2026', '12 foto'], ['Kolase daun', '12 November 2026', '8 foto'], ['Membaca bersama', '10 November 2026', '6 foto']]],
        'siswa' => ['description' => 'Informasi siswa dan kelompok belajar yang terhubung ke akun orang tua.', 'stats' => [['Nama siswa', 'Arka Pratama'], ['Kelompok', 'A1 · Melati'], ['Wali kelas', 'Dewi Puspita']], 'columns' => ['Informasi siswa', 'Detail', 'Status'], 'rows' => [['Nama', 'Arka Pratama', 'Aktif'], ['Kelompok belajar', 'A1 · Melati', 'Tahun ajaran 2026/2027'], ['Wali kelas', 'Ibu Dewi Puspita', 'Terhubung']]],
    ];
@endphp
@include('dashboard.role-shell')
