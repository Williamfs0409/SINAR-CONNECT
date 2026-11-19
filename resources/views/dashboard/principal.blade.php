@php
    $dashboardRoleKey = 'principal';
    $dashboardRoleName = 'Kepala Sekolah';
    $dashboardContentView = 'dashboard.content.principal';
    $dashboardMenu = [
        ['id' => 'ringkasan', 'label' => 'Dashboard'],
        ['id' => 'guru', 'label' => 'Data Siswa & Guru'],
        ['id' => 'administrasi', 'label' => 'Administrasi Sekolah'],
        ['id' => 'akademik', 'label' => 'Akademik'],
        ['id' => 'evaluasi', 'label' => 'Evaluasi Kegiatan'],
        ['id' => 'spp', 'label' => 'SPP & Tabungan'],
        ['id' => 'laporan', 'label' => 'Laporan Sekolah'],
        ['id' => 'jadwal', 'label' => 'Jadwal Guru'],
    ];
    $dashboardNotifications = [
        ['id' => 'principal-reports', 'title' => 'Laporan menunggu tinjauan', 'message' => '3 laporan perkembangan belum dilengkapi.', 'href' => route('dashboard', ['role' => 'principal']).'#evaluasi'],
        ['id' => 'principal-student-files', 'title' => 'Berkas siswa perlu diperbarui', 'message' => '10 dari 128 berkas siswa belum terverifikasi.', 'href' => route('dashboard', ['role' => 'principal']).'#administrasi'],
        ['id' => 'principal-meeting', 'title' => 'Agenda rapat evaluasi', 'message' => 'Rapat dijadwalkan 18 November pukul 13.00.', 'href' => route('dashboard', ['role' => 'principal']).'#administrasi'],
    ];
    $dashboardFeatureDetails = [
        'guru' => ['description' => 'Data guru, wali kelas, dan kelompok belajar di TK Najmah Rugayah Darus.', 'stats' => [['Guru aktif', '8'], ['Kelas definitif', '6'], ['Siswa aktif', '62']], 'columns' => ['Guru', 'Kelas', 'Status'], 'rows' => [['Lismainur, S.E.', 'TK A · Ar Rahim', 'Aktif'], ['Chairunnisa, S.Pd.', 'TK A · Abu Bakar', 'Aktif'], ['Lidya Wuri', 'TK B · Marwah', 'Aktif'], ['Mardiani', 'TK B · Safa', 'Aktif']]],
        'administrasi' => ['description' => 'Ringkasan berkas dan kegiatan administrasi sekolah.', 'stats' => [['Kelas definitif', '6'], ['Siswa aktif', '62'], ['Agenda terdekat', '26 Nov']], 'columns' => ['Administrasi', 'Jumlah', 'Status'], 'rows' => [['Data rombel aktif', '51 di kelas definitif', 'Berjalan'], ['Data kelas T.A. 2026/2027', '6 kelas', 'Lengkap'], ['Peringatan Hari Guru', '26 November 2026', 'Terjadwal']]],
        'akademik' => ['description' => 'Pantau catatan pembelajaran dan capaian setiap kelas.', 'stats' => [['Kelas definitif', '6'], ['Siswa aktif', '62'], ['Indikator Iqro', '74']], 'columns' => ['Kelas', 'Wali kelas', 'Keterisian'], 'rows' => [['KB · PG', 'Nur Balqis Azzahra Sani, S.Sos.', '9 / 15'], ['TK A · Ar Rahim', 'Lismainur, S.E.', '10 / 12'], ['TK A · Abu Bakar', 'Chairunnisa, S.Pd.', '12 / 12'], ['TK B · Marwah', 'Lidya Wuri', '10 / 12']]],
        'evaluasi' => ['description' => 'Tinjau rencana belajar, laporan perkembangan, dan kegiatan sekolah.', 'stats' => [['Agenda kegiatan', '27'], ['Laporan perkembangan', 'Aktif'], ['Kelas aktif', '6']], 'columns' => ['Item evaluasi', 'Jumlah', 'Tindak lanjut'], 'rows' => [['Rencana pembelajaran (RPPH)', 'Tersedia', 'Berjalan'], ['Laporan perkembangan & Iqro', 'Semester Ganjil', 'Berjalan'], ['Kegiatan sekolah', '6 kelas', 'Berjalan']]],
        'spp' => ['description' => 'Pantau rekap pembayaran SPP (KB Rp250.000, TK A/B Rp350.000) dan tabungan.', 'stats' => [['Tarif KB', 'Rp250.000'], ['Tarif TK A & B', 'Rp350.000'], ['Rekening BSI', '7276048637']], 'columns' => ['Komponen', 'Keterangan', 'Nominal'], 'rows' => [['SPP Kelompok Bermain (KB)', 'Bulanan', 'Rp250.000'], ['SPP TK A & TK B', 'Bulanan', 'Rp350.000'], ['Diskon Anak Guru', 'Potongan 50%', 'Sesuai tarif']]],
        'laporan' => ['description' => 'Laporan perkembangan siswa dan ringkasan kegiatan sekolah.', 'stats' => [['Kesiapan data', 'Lengkap'], ['Periode', 'T.A. 2026/2027'], ['Semester', 'Ganjil']], 'columns' => ['Laporan', 'Periode', 'Status'], 'rows' => [['Rapor Kurikulum Merdeka & Iqro', 'Semester Ganjil', 'Tersedia'], ['Rekap Kehadiran Bulanan', 'T.A. 2026/2027', 'Tersedia'], ['Kapasitas Kelas', '6 kelas aktif', 'Tersedia']]],
        'jadwal' => ['description' => 'Jadwal mengajar dan penempatan wali kelas TK Najmah Rugayah Darus.', 'stats' => [['Guru terjadwal', '8'], ['Kelas definitif', '6'], ['Jam belajar', '07.50–11.30']], 'columns' => ['Guru', 'Kelas', 'Peran'], 'rows' => [['Nur Balqis Azzahra Sani, S.Sos.', 'KB · PG', 'Wali Kelas'], ['Lismainur, S.E.', 'TK A · Ar Rahim', 'Wali Kelas'], ['Chairunnisa, S.Pd.', 'TK A · Abu Bakar', 'Wali Kelas'], ['Lidya Wuri', 'TK B · Marwah', 'Wali Kelas']]],
    ];
@endphp
@include('dashboard.role-shell')
