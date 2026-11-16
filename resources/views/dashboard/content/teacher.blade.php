<section id="ringkasan" class="role-section">
    <h1 class="role-section-title">Kartu Ringkasan</h1>
    <div class="role-stat-grid">
        <article class="role-stat stat-soft-blue"><span>Siswa Kelompok A1</span><strong>16</strong><small>Kelompok Melati</small></article>
        <article class="role-stat stat-soft-green"><span>Hadir Hari Ini</span><strong>15</strong><small>1 izin · 0 tanpa keterangan</small></article>
        <article class="role-stat stat-soft-yellow"><span>Draf Rapor Belum Lengkap</span><strong>4</strong><small>12 dari 16 draf tersimpan</small></article>
    </div>
</section>

<section id="jadwal" class="role-panel role-schedule" data-schedule-widget>
    <div class="role-panel-heading">
        <div><h2>Jadwal Mengajar</h2><p>{{ $dashboardTeacherName }} · Kelompok A1 – Melati · Tema Lingkunganku</p></div>
        <span class="role-panel-meta">4 sesi · 07.30–10.00 WIB</span>
    </div>
    @include('dashboard.schedule-picker')
    <div class="role-table-wrap"><table class="role-table"><thead><tr><th>Waktu (WIB)</th><th>Kegiatan mengajar</th><th>Kelompok / lokasi</th><th>Persiapan</th></tr></thead><tbody>
        <tr><td>07.30–08.00</td><td>Pembukaan &amp; doa bersama</td><td>A1 · Kelas A1</td><td>Rutin harian</td></tr>
        <tr><td>08.00–09.00</td><td>Bermain &amp; belajar di kebun</td><td>A1 · Kebun sekolah</td><td>Topi &amp; alat siram</td></tr>
        <tr><td>09.00–09.30</td><td>Pendampingan istirahat &amp; makan</td><td>A1 · Kelas A1</td><td>Cek bekal siswa</td></tr>
        <tr><td>09.30–10.00</td><td>Refleksi &amp; persiapan pulang</td><td>A1 · Kelas A1</td><td>Catatan observasi</td></tr>
    </tbody></table></div>
    <div class="role-panel-foot"><span>Kegiatan inti 08.00–10.00 · RPP Mingguan tersimpan</span><a href="#rencana">Lihat jadwal mingguan →</a></div>
</section>

<div class="role-two-column">
    <section id="absensi" class="role-panel">
        <div class="role-panel-heading"><div><h2>Absensi Hari Ini</h2><p>Kelas A1 – Melati · 16 siswa</p></div><span class="role-status">15 hadir · 1 izin</span></div>
        <div class="role-student-list">
            <div class="role-student"><i>AP</i><span><strong>Alva Putri</strong><small>Kelompok A1</small></span><b>Hadir</b><em>Izin</em></div>
            <div class="role-student"><i>AP</i><span><strong>Arka Pratama</strong><small>Kelompok A1</small></span><b>Hadir</b><em>Izin</em></div>
            <div class="role-student"><i>NR</i><span><strong>Naufal Rizki</strong><small>Kelompok A1</small></span><b>Hadir</b><em>Izin</em></div>
            <div class="role-student"><i>RS</i><span><strong>Rizky Saputra</strong><small>Kelompok A1</small></span><b>Hadir</b><em>Izin</em></div>
        </div>
        <a class="role-link" href="#absensi">Lihat seluruh absensi →</a>
    </section>
    <section id="nilai" class="role-panel">
        <div class="role-panel-heading"><div><h2>Nilai &amp; Perkembangan</h2><p>Siswa mencapai perkembangan sesuai harapan</p></div></div>
        <div class="role-chart-legend"><i></i> Rekap kelas · Oktober–November</div>
        <div class="role-chart">
            <div class="chart-y-labels"><span>100%</span><span>75%</span><span>50%</span><span>25%</span></div>
            <svg viewBox="0 0 420 180" role="img" aria-label="Perkembangan kelas meningkat dari 62 persen menjadi 81 persen">
                <path class="chart-grid" d="M0 20H420M0 65H420M0 110H420M0 155H420"/>
                <polyline class="chart-line chart-green" points="15,92 140,77 265,65 395,53"/>
                <g class="chart-points"><circle cx="15" cy="92" r="4"/><circle cx="140" cy="77" r="4"/><circle cx="265" cy="65" r="4"/><circle cx="395" cy="53" r="4"/></g>
            </svg>
        </div>
        <div class="role-chart-x-labels"><span>Minggu 1</span><span>Minggu 2</span><span>Minggu 3</span><span>Minggu 4</span></div>
        <p class="role-chart-note">81% siswa berkembang sesuai harapan minggu ini.</p>
    </section>
    <section id="rapor" class="role-panel">
        <div class="role-panel-heading"><div><h2>Observasi &amp; Rapor</h2><p>Catatan belajar menjadi dasar penyusunan rapor.</p></div></div>
        <div class="role-progress-heading"><span>Kelengkapan draf rapor</span><strong>12 / 16 siswa</strong></div><div class="role-progress"><span style="width:75%"></span></div>
        <div class="role-note"><strong>Arka Pratama · Sosial emosional</strong><p>Mulai percaya diri bercerita dan membantu teman.</p></div>
        <div class="role-panel-foot"><span class="text-warning">4 observasi perlu dilengkapi</span><a href="#rapor">Lanjutkan rapor →</a></div>
        <div id="dokumentasi" class="role-inline-row"><span><strong>Dokumentasi kegiatan</strong><small>12 foto · Bermain di kebun sekolah</small></span><a href="{{ route('dashboard.feature', ['section' => 'kegiatan']) }}">Kelola &amp; upload →</a></div>
    </section>
    <section id="pembelajaran" class="role-panel">
        <div class="role-panel-heading"><div><h2>Rencana Belajar &amp; Tugas</h2><p>Minggu ini · Tema Lingkunganku</p></div></div>
        <div id="rencana" class="role-task-row task-blue"><span>□</span><strong>RPP Mingguan</strong><small>Tersimpan</small><b>›</b></div>
        <div class="role-task-row task-yellow"><span>□</span><strong>Rencana Pembelajaran</strong><small>1 rencana</small><b>›</b></div>
        <div class="role-task-row task-green"><span>□</span><strong>Kegiatan Hari Ini</strong><small>08.00–10.00</small><b>›</b></div>
        <div id="tugas" class="role-task-row task-cream"><span>□</span><strong>Rekap &amp; Tugas</strong><small>3 tugas aktif</small><b>›</b></div>
    </section>
</div>
