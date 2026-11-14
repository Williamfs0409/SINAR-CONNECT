@php
    $dashboardRoleKey = 'teacher';
    $dashboardRoleName = 'Guru';
    $dashboardContentView = 'dashboard.content.teacher';
    $dashboardMenu = [
        ['id' => 'ringkasan', 'icon' => '⌂', 'label' => 'Dashboard'],
        ['id' => 'absensi', 'icon' => '♧', 'label' => 'Data Siswa & Absensi'],
        ['id' => 'pembelajaran', 'icon' => '▤', 'label' => 'Pembelajaran'],
        ['id' => 'rencana', 'icon' => '−', 'label' => 'Rencana Pembelajaran'],
        ['id' => 'tugas', 'icon' => '−', 'label' => 'Tugas'],
        ['id' => 'nilai', 'icon' => '−', 'label' => 'Nilai & Observasi'],
        ['id' => 'rapor', 'icon' => '−', 'label' => 'Rapor'],
        ['id' => 'dokumentasi', 'icon' => '▱', 'label' => 'Dokumentasi Kegiatan'],
        ['id' => 'pengaturan', 'icon' => '⚙', 'label' => 'Pengaturan'],
        ['id' => 'jadwal', 'icon' => '▦', 'label' => 'Jadwal Mengajar'],
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
