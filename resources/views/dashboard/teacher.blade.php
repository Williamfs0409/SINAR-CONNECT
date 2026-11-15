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
        ['id' => 'dokumentasi', 'label' => 'Dokumentasi Kegiatan'],
        ['id' => 'pengaturan', 'label' => 'Pengaturan'],
        ['id' => 'jadwal', 'label' => 'Jadwal Mengajar'],
    ];
    $dashboardNotifications = [
        ['id' => 'teacher-report-drafts', 'title' => 'Draf rapor perlu dilengkapi', 'message' => '4 dari 16 siswa belum memiliki draf rapor lengkap.', 'href' => route('dashboard', ['role' => 'teacher']).'#rapor'],
        ['id' => 'teacher-attendance', 'title' => 'Rekap kehadiran hari ini', 'message' => '15 siswa hadir dan 1 siswa izin di kelompok A1.', 'href' => route('dashboard', ['role' => 'teacher']).'#absensi'],
        ['id' => 'teacher-tasks', 'title' => 'Tugas belajar aktif', 'message' => 'Tugas kolase daun kering menunggu tindak lanjut.', 'href' => route('dashboard', ['role' => 'teacher']).'#tugas'],
    ];
    $dashboardFeatureDetails = [
        'absensi' => ['description' => 'Kelola data siswa dan catat kehadiran kelompok A1.', 'stats' => [['Hadir', '15'], ['Izin', '1'], ['Jumlah siswa', '16']], 'columns' => ['Nama siswa', 'Kelompok', 'Kehadiran'], 'rows' => [['Alva Putri', 'A1 · Melati', 'Hadir'], ['Arka Pratama', 'A1 · Melati', 'Hadir'], ['Naufal Rizki', 'A1 · Melati', 'Hadir'], ['Rizky Saputra', 'A1 · Melati', 'Izin']]],
        'pembelajaran' => ['description' => 'Materi dan aktivitas belajar untuk tema Lingkunganku.', 'stats' => [['Tema aktif', 'Lingkunganku'], ['Kelompok', 'A1 · Melati'], ['Sesi hari ini', '4']], 'columns' => ['Waktu', 'Kegiatan', 'Lokasi'], 'rows' => [['07.30–08.00', 'Pembukaan & doa bersama', 'Kelas A1'], ['08.00–09.00', 'Bermain & belajar di kebun', 'Kebun sekolah'], ['09.00–09.30', 'Istirahat & makan', 'Kelas A1'], ['09.30–10.00', 'Refleksi & persiapan pulang', 'Kelas A1']]],
        'rencana' => ['description' => 'Rencana pembelajaran mingguan dan persiapan kegiatan kelas.', 'stats' => [['RPP tersimpan', '1'], ['Perlu disiapkan', '2'], ['Tema', 'Lingkunganku']], 'columns' => ['Rencana', 'Jadwal', 'Status'], 'rows' => [['RPP Mingguan', '16–20 November', 'Tersimpan'], ['Kegiatan di kebun', 'Senin · 08.00', 'Siap'], ['Kolase daun', 'Selasa · 08.00', 'Perlu disiapkan']]],
        'tugas' => ['description' => 'Daftar tugas belajar dan tindak lanjut kegiatan siswa.', 'stats' => [['Tugas aktif', '3'], ['Perlu ditinjau', '1'], ['Kelompok', 'A1']], 'columns' => ['Tugas', 'Kelompok', 'Status'], 'rows' => [['Kolase daun kering', 'A1 · Melati', 'Dibagikan'], ['Mengenal tanaman', 'A1 · Melati', 'Dikerjakan'], ['Bercerita tentang kebun', 'A1 · Melati', 'Menunggu hasil']]],
        'nilai' => ['description' => 'Catatan observasi perkembangan siswa di setiap aspek belajar.', 'stats' => [['Sesuai harapan', '12'], ['Perlu observasi', '4'], ['Aspek dinilai', '4']], 'columns' => ['Nama siswa', 'Aspek', 'Catatan'], 'rows' => [['Arka Pratama', 'Sosial emosional', 'Percaya diri bercerita'], ['Alva Putri', 'Bahasa & literasi', 'Mengikuti kegiatan'], ['Naufal Rizki', 'Motorik halus', 'Berkembang baik'], ['Rizky Saputra', 'Kemandirian', 'Perlu pendampingan']]],
        'rapor' => ['description' => 'Pantau kelengkapan draf rapor dan catatan perkembangan siswa.', 'stats' => [['Draf lengkap', '12 / 16'], ['Perlu dilengkapi', '4'], ['Periode', 'Semester ganjil']], 'columns' => ['Nama siswa', 'Kelengkapan', 'Status'], 'rows' => [['Arka Pratama', 'Lengkap', 'Tersimpan'], ['Alva Putri', 'Lengkap', 'Tersimpan'], ['Naufal Rizki', 'Perlu observasi', 'Proses'], ['Rizky Saputra', 'Perlu catatan', 'Proses']]],
        'dokumentasi' => ['description' => 'Dokumentasi kegiatan belajar kelompok A1.', 'stats' => [['Album', '4'], ['Foto kegiatan', '12'], ['Terbaru', '13 Nov 2026']], 'columns' => ['Kegiatan', 'Tanggal', 'Dokumentasi'], 'rows' => [['Bermain & belajar di kebun', '13 November 2026', '12 foto'], ['Kolase daun', '12 November 2026', '8 foto'], ['Membaca bersama', '10 November 2026', '6 foto']]],
        'pengaturan' => ['description' => 'Informasi akun dan preferensi dashboard guru.', 'stats' => [['Peran', 'Guru'], ['Kelompok', 'A1 · Melati'], ['Status akun', 'Aktif']], 'columns' => ['Informasi', 'Nilai', 'Status'], 'rows' => [['Nama akun', $dashboardTeacherName, 'Terverifikasi'], ['Email', auth()->user()?->email ?? 'pratinjau@sinar.sch.id', 'Aktif'], ['Wali kelas', 'Kelompok A1 · Melati', 'Aktif']]],
        'jadwal' => ['description' => 'Jadwal kegiatan mengajar mingguan kelompok A1.', 'stats' => [['Sesi hari ini', '4'], ['Jam belajar', '07.30–10.00'], ['Kelompok', 'A1']], 'columns' => ['Waktu', 'Kegiatan mengajar', 'Persiapan'], 'rows' => [['07.30–08.00', 'Pembukaan & doa bersama', 'Rutin harian'], ['08.00–09.00', 'Belajar di kebun', 'Topi & alat siram'], ['09.00–09.30', 'Istirahat & makan', 'Cek bekal siswa'], ['09.30–10.00', 'Refleksi & persiapan pulang', 'Catatan observasi']]],
    ];
@endphp
@include('dashboard.role-shell')
