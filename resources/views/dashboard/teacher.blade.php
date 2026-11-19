@php
    $dashboardRoleKey = 'teacher';
    $dashboardRoleName = 'Guru';
    $dashboardContentView = 'dashboard.content.teacher';
    $dashboardMenu = [
        ['id' => 'ringkasan', 'label' => 'Dashboard'],
        ['id' => 'absensi', 'label' => 'Data Siswa & Absensi'],
        ['id' => 'pembelajaran', 'label' => 'Pembelajaran'],
        ['id' => 'rencana', 'label' => 'Rencana Pembelajaran'],
        ['id' => 'tugas', 'label' => 'Tugas'],
        ['id' => 'nilai', 'label' => 'Nilai & Observasi'],
        ['id' => 'rapor', 'label' => 'Rapor'],
        ['id' => 'kegiatan', 'label' => 'Dokumentasi Kegiatan'],
        ['id' => 'pengaturan', 'label' => 'Pengaturan'],
        ['id' => 'jadwal', 'label' => 'Jadwal Mengajar'],
    ];
    $dashboardNotifications = [
        ['id' => 'teacher-report-drafts', 'title' => 'Draf rapor perlu dilengkapi', 'message' => '2 dari 10 siswa TK A Ar Rahim belum memiliki draf rapor lengkap.', 'href' => route('dashboard', ['role' => 'teacher']).'#rapor'],
        ['id' => 'teacher-attendance', 'title' => 'Rekap kehadiran hari ini', 'message' => '9 siswa hadir dan 1 siswa izin di kelas TK A Ar Rahim.', 'href' => route('dashboard', ['role' => 'teacher']).'#absensi'],
        ['id' => 'teacher-tasks', 'title' => 'Tugas belajar aktif', 'message' => 'Kolase Bentuk Geometri & Hafalan Doa Sebelum Makan menunggu penilaian.', 'href' => route('dashboard', ['role' => 'teacher']).'#tugas'],
    ];
    $dashboardFeatureDetails = [
        'absensi' => ['description' => 'Kelola data siswa dan catat kehadiran kelas TK A Ar Rahim.', 'stats' => [['Hadir', '9'], ['Izin', '1'], ['Jumlah siswa', '10']], 'columns' => ['Nama siswa', 'Kelompok', 'Kehadiran'], 'rows' => [['Aqila Rizky Azaela', 'TK A · Ar Rahim', 'Hadir'], ['Arzan Habib Hia', 'TK A · Ar Rahim', 'Hadir'], ['Maqil Niematan Waruwu', 'TK A · Ar Rahim', 'Hadir'], ['Rayra Harfanisyah Lubis', 'TK A · Ar Rahim', 'Hadir']]],
        'pembelajaran' => ['description' => 'Materi dan aktivitas belajar sesuai jadwal TK A & TK B.', 'stats' => [['Kelas', 'TK A · Ar Rahim'], ['Wali Kelas', 'Lismainur, S.E.'], ['Sesi hari ini', '4']], 'columns' => ['Waktu', 'Kegiatan', 'Lokasi'], 'rows' => [['07.50–08.15', 'Berbaris & upacara bendera', 'Halaman sekolah'], ['08.15–09.00', 'Salam, berdoa & Iqro / Anbata', 'Kelas Ar Rahim'], ['09.00–10.30', 'Kognitif, Bahasa & Seni', 'Kelas Ar Rahim'], ['10.30–11.30', 'Istirahat & evaluasi kegiatan', 'Kelas Ar Rahim']]],
        'rencana' => ['description' => 'Rencana pembelajaran mingguan (RPPH/RPPM) dan persiapan kegiatan kelas.', 'stats' => [['RPPH tersimpan', '1'], ['Perlu disiapkan', '2'], ['Kelas', 'Ar Rahim']], 'columns' => ['Rencana', 'Jadwal', 'Status'], 'rows' => [['RPPH Mingguan', 'T.A. 2026/2027', 'Tersimpan'], ['Iqro & Anbata', 'Senin · 08.15', 'Siap'], ['Kolase bentuk geometri', 'Selasa · 09.00', 'Perlu disiapkan']]],
        'tugas' => ['description' => 'Daftar tugas belajar dan tindak lanjut kegiatan siswa.', 'stats' => [['Tugas aktif', '2'], ['Menunggu dinilai', '1'], ['Kelas', 'Ar Rahim']], 'columns' => ['Tugas', 'Kelompok', 'Status'], 'rows' => [['Kolase Bentuk Geometri & Doa', 'TK A · Ar Rahim', 'Dibagikan'], ['Hafalan Surah Al-Fatihah', 'TK A · Ar Rahim', 'Dinilai']]],
        'nilai' => ['description' => 'Catatan observasi perkembangan siswa dan indikator Iqro.', 'stats' => [['BSH / BSB', '8'], ['MB', '2'], ['Indikator TK A', '54']], 'columns' => ['Nama siswa', 'Aspek', 'Catatan'], 'rows' => [['Aqila Rizky Azaela', 'Nilai Agama & Budi Pekerti', 'BSB'], ['Arzan Habib Hia', 'Kolase & Motorik Halus', 'BSB'], ['Rayra Harfanisyah Lubis', 'Bahasa & Hafalan Doa', 'BSH'], ['Maqil Niematan Waruwu', 'Kemandirian & P5', 'BSB']]],
        'rapor' => ['description' => 'Pantau kelengkapan draf rapor dan catatan perkembangan siswa.', 'stats' => [['Draf lengkap', '8 / 10'], ['Perlu dilengkapi', '2'], ['Periode', 'Semester ganjil']], 'columns' => ['Nama siswa', 'Kelengkapan', 'Status'], 'rows' => [['Aqila Rizky Azaela', 'Lengkap', 'Final'], ['Arzan Habib Hia', 'Lengkap', 'Final'], ['Maqil Niematan Waruwu', 'Lengkap', 'Tersimpan'], ['M. Harith Abqary Firasa', 'Perlu catatan', 'Draft']]],
        'kegiatan' => ['description' => 'Dokumentasi kegiatan belajar TK Swasta Najmah Rugayah Darus.', 'stats' => [['Agenda', '27'], ['Kegiatan aktif', 'Outing Class'], ['T.A.', '2026/2027']], 'columns' => ['Kegiatan', 'Tanggal', 'Lokasi'], 'rows' => [['Pentas Seni TK Kec. Helvetia', '14 November 2026', 'Hairos'], ['Outing Class ke Pabrik Fiesta', '26 Agustus 2026', 'Kawasan Industri Medan'], ['Manasik Haji Anak TK', '21 Oktober 2026', 'Asrama Haji Medan']]],
        'pengaturan' => ['description' => 'Informasi akun dan preferensi dashboard guru.', 'stats' => [['Peran', 'Guru'], ['Kelas', 'TK A · Ar Rahim'], ['Status akun', 'Aktif']], 'columns' => ['Informasi', 'Nilai', 'Status'], 'rows' => [['Wali kelas', 'Lismainur, S.E.', 'Aktif'], ['Email', auth()->user()?->email ?? 'lismainur81@gmail.com', 'Aktif'], ['Sekolah', 'TK Najmah Rugayah Darus', 'Aktif']]],
        'jadwal' => ['description' => 'Jadwal kegiatan mengajar mingguan kelas TK A Ar Rahim.', 'stats' => [['Sesi hari ini', '4'], ['Jam belajar', '07.50–11.30'], ['Kelompok', 'TK A']], 'columns' => ['Waktu', 'Kegiatan mengajar', 'Persiapan'], 'rows' => [['07.50–08.15', 'Berbaris & upacara bendera', 'Rutin harian'], ['08.15–09.00', 'Salam, berdoa & Iqro / Anbata', 'Buku Iqro'], ['09.00–10.30', 'Kognitif, Bahasa & Seni', 'Alat peraga'], ['10.30–11.30', 'Istirahat & evaluasi pulang', 'Catatan observasi']]],
    ];
@endphp
@include('dashboard.role-shell')
