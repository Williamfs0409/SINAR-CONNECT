@php
    $dashboardRoleKey = 'parent';
    $dashboardRoleName = 'Orang Tua';
    $dashboardContentView = 'dashboard.content.parent';
    $dashboardMenu = [
        ['id' => 'ringkasan', 'label' => 'Dashboard'],
        ['id' => 'perkembangan', 'label' => 'Perkembangan Anak'],
        ['id' => 'absensi', 'label' => 'Absensi Anak'],
        ['id' => 'spp', 'label' => 'Pembayaran SPP'],
        ['id' => 'tabungan', 'label' => 'Tabungan Anak'],
        ['id' => 'tugas', 'label' => 'Tugas Anak'],
        ['id' => 'rapor', 'label' => 'Rapor Anak'],
        ['id' => 'kegiatan', 'label' => 'Dokumentasi Kegiatan'],
        ['id' => 'siswa', 'label' => 'Data Siswa'],
    ];
    $dashboardNotifications = [
        ['id' => 'parent-spp', 'title' => 'Tagihan SPP Bulanan', 'message' => 'Pembayaran SPP TK A sebesar Rp350.000 jatuh tempo tanggal 10.', 'href' => route('dashboard', ['role' => 'parent']).'#spp'],
        ['id' => 'parent-progress', 'title' => 'Catatan perkembangan anak', 'message' => 'Ibu Lismainur mencatat perkembangan Aqila Rizky Azaela di kelas Ar Rahim.', 'href' => route('dashboard', ['role' => 'parent']).'#perkembangan'],
        ['id' => 'parent-tasks', 'title' => 'Tugas belajar anak', 'message' => 'Kolase Bentuk Geometri & Hafalan Doa Sebelum Makan.', 'href' => route('dashboard', ['role' => 'parent']).'#tugas'],
    ];
    $dashboardFeatureDetails = [
        'perkembangan' => ['description' => 'Pantau perkembangan belajar dan catatan guru untuk anak.', 'stats' => [['Nilai Agama', 'BSB'], ['Sosial Emosional', 'BSH'], ['Status hari ini', 'Hadir']], 'columns' => ['Aspek perkembangan', 'Penilaian', 'Catatan'], 'rows' => [['Nilai Agama & Budi Pekerti', 'BSB', 'Hafalan doa sebelum makan lancar'], ['Jati Diri', 'BSH', 'Percaya diri mengikuti kegiatan'], ['Literasi & STEAM', 'BSB', 'Kerapian kolase bentuk geometri baik']]],
        'absensi' => ['description' => 'Riwayat kehadiran anak di kelas TK A Ar Rahim.', 'stats' => [['Hadir', '18 hari'], ['Izin', '1 hari'], ['Sakit', '0 hari']], 'columns' => ['Tanggal', 'Keterangan', 'Dicatat oleh'], 'rows' => [['18 November 2026', 'Hadir', 'Lismainur, S.E.'], ['17 November 2026', 'Hadir', 'Lismainur, S.E.'], ['16 November 2026', 'Hadir', 'Lismainur, S.E.']]],
        'spp' => ['description' => 'Rincian tagihan SPP dan status pembayaran bulan ini.', 'stats' => [['Tarif SPP TK A', 'Rp350.000'], ['Jatuh tempo', 'Tanggal 10'], ['Rekening BSI', '7276048637']], 'columns' => ['Tagihan', 'Periode', 'Jumlah / status'], 'rows' => [['SPP Bulanan TK A', 'November 2026', 'Rp350.000 · Lunas'], ['SPP Bulanan TK A', 'Oktober 2026', 'Rp350.000 · Lunas']]],
        'tabungan' => ['description' => 'Riwayat setoran dan saldo tabungan anak.', 'stats' => [['Saldo saat ini', 'Sesuai buku'], ['Metode', 'Tunai / Transfer'], ['Pengelola', 'Sekolah']], 'columns' => ['Tanggal', 'Transaksi', 'Keterangan'], 'rows' => [['November 2026', 'Setor', 'Tabungan mingguan siswa']]],
        'tugas' => ['description' => 'Tugas belajar anak yang dapat didampingi di rumah.', 'stats' => [['Kelas', 'TK A · Ar Rahim'], ['Wali Kelas', 'Lismainur, S.E.'], ['Skala nilai', 'BB–BSB']], 'columns' => ['Nama tugas', 'Batas waktu', 'Status'], 'rows' => [['Kolase Bentuk Geometri & Doa', 'TK A · Ar Rahim', 'Dinilai (BSB)']]],
        'rapor' => ['description' => 'Lihat ringkasan rapor dan perkembangan semester anak.', 'stats' => [['Periode', 'Semester ganjil'], ['Elemen narasi', '4 aspek'], ['Status rapor', 'Tersedia']], 'columns' => ['Aspek', 'Perkembangan', 'Catatan guru'], 'rows' => [['Nilai Agama & Budi Pekerti', 'BSB', 'Mengikuti hafalan doa harian dengan baik'], ['Jati Diri', 'BSH', 'Mandiri dan mau bekerja sama'], ['Literasi & STEAM', 'BSB', 'Aktif dalam kegiatan kolase']]],
        'kegiatan' => ['description' => 'Foto dan cerita kegiatan belajar anak di TK Najmah Rugayah Darus.', 'stats' => [['Agenda', '27'], ['Lokasi', 'Medan'], ['T.A.', '2026/2027']], 'columns' => ['Kegiatan', 'Tanggal', 'Lokasi'], 'rows' => [['Pentas Seni TK Kec. Helvetia', '14 November 2026', 'Hairos'], ['Outing Class ke Pabrik Fiesta', '26 Agustus 2026', 'Kawasan Industri Medan']]],
        'siswa' => ['description' => 'Informasi siswa dan kelas yang terhubung ke akun orang tua.', 'stats' => [['Nama siswa', 'Aqila Rizky Azaela'], ['Kelas', 'TK A · Ar Rahim'], ['Wali kelas', 'Lismainur, S.E.']], 'columns' => ['Informasi siswa', 'Detail', 'Status'], 'rows' => [['Nama', 'Aqila Rizky Azaela', 'Aktif'], ['Kelas', 'TK A · Ar Rahim', 'Tahun ajaran 2026/2027'], ['Wali kelas', 'Lismainur, S.E.', 'Terhubung']]],
    ];
@endphp
@include('dashboard.role-shell')
