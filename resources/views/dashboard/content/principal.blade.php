<section id="ringkasan" class="role-section">
    <h1 class="role-section-title">Kartu Ringkasan</h1>
    <div class="role-stat-grid">
        <article class="role-stat stat-soft-blue"><span>Total Siswa</span><strong>128</strong><small>8 kelompok belajar</small></article>
        <article class="role-stat stat-soft-green"><span>Hadir Hari Ini</span><strong>122</strong><small>95,3% dari total siswa</small></article>
        <article class="role-stat stat-soft-yellow"><span>Perlu Ditinjau</span><strong>7</strong><small>4 rencana · 3 laporan</small></article>
    </div>
</section>

<span id="guru" class="role-anchor" aria-hidden="true"></span>
<section id="jadwal" class="role-panel role-schedule" data-schedule-widget>
    <div class="role-panel-heading"><div><h2>Jadwal Guru</h2><p>Pantau jadwal mengajar dan penempatan guru per kelompok belajar.</p></div><span class="role-panel-meta">4 dari 8 guru ditampilkan</span></div>
    @include('dashboard.schedule-picker')
    <div class="role-table-wrap"><table class="role-table"><thead><tr><th>Guru</th><th>Kelompok</th><th>Waktu (WIB)</th><th>Kegiatan / lokasi</th></tr></thead><tbody>
        <tr><td>Dewi Puspita</td><td>A1 · Melati</td><td>07.30–10.00</td><td>Belajar di kebun · Kebun sekolah</td></tr>
        <tr><td>Rina Lestari</td><td>A2 · Mawar</td><td>07.30–10.00</td><td>Kolase daun · Kelas A2</td></tr>
        <tr><td>Siti Rahma</td><td>B1 · Anggrek</td><td>07.30–10.00</td><td>Mengenal tanaman · Halaman</td></tr>
        <tr><td>Nur Aisyah</td><td>B2 · Dahlia</td><td>07.30–10.00</td><td>Bercerita lingkungan · Kelas B2</td></tr>
    </tbody></table></div>
    <div class="role-panel-foot"><span>Tahun ajaran 2026/2027 · Semester ganjil</span><a href="#guru">Lihat seluruh jadwal guru →</a></div>
</section>

<div class="role-two-column">
    <section id="akademik" class="role-panel">
        <div class="role-panel-heading"><div><h2>Pemantauan Akademik</h2><p>Kelengkapan catatan belajar per kelompok</p></div></div>
        <div class="role-table-wrap"><table class="role-table compact-table"><thead><tr><th>Kelompok / Wali kelas</th><th>Catatan</th><th>Status</th></tr></thead><tbody>
            <tr><td>A1 · Melati<small>Dewi Puspita</small></td><td>16 / 16</td><td><span class="table-status status-complete">Lengkap</span></td></tr>
            <tr><td>A2 · Mawar<small>Rina Lestari</small></td><td>14 / 16</td><td><span class="table-status status-progress">Proses</span></td></tr>
            <tr><td>B1 · Anggrek<small>Siti Rahma</small></td><td>16 / 16</td><td><span class="table-status status-complete">Lengkap</span></td></tr>
            <tr><td>B2 · Dahlia<small>Nur Aisyah</small></td><td>15 / 16</td><td><span class="table-status status-progress">Proses</span></td></tr>
        </tbody></table></div>
        <a class="role-link" href="#akademik">Lihat seluruh data akademik →</a>
    </section>
    <section id="spp" class="role-panel">
        <div class="role-panel-heading"><div><h2>Pembayaran SPP &amp; Tabungan</h2><p>Persentase pelunasan SPP per bulan</p></div></div>
        <div class="role-chart-legend"><i class="legend-orange"></i> Rekap pembayaran sekolah</div>
        <div class="role-chart">
            <div class="chart-y-labels"><span>100%</span><span>75%</span><span>50%</span><span>25%</span><span>0%</span></div>
            <svg viewBox="0 0 420 180" role="img" aria-label="Grafik pembayaran SPP dari Mei sampai Oktober">
                <path class="chart-grid" d="M0 20H420M0 55H420M0 90H420M0 125H420M0 160H420"/>
                <polyline class="chart-line chart-orange" points="15,100 92,88 169,94 246,73 323,63 398,52"/>
                <g class="chart-points points-orange"><circle cx="15" cy="100" r="4"/><circle cx="92" cy="88" r="4"/><circle cx="169" cy="94" r="4"/><circle cx="246" cy="73" r="4"/><circle cx="323" cy="63" r="4"/><circle cx="398" cy="52" r="4"/></g>
            </svg>
        </div>
        <div class="role-chart-x-labels"><span>Mei</span><span>Jun</span><span>Jul</span><span>Agu</span><span>Sep</span><span>Okt</span></div>
        <div class="role-savings-total"><span>Total tabungan siswa</span><strong>Rp24.800.000</strong></div>
    </section>
    <section id="evaluasi" class="role-panel">
        <div class="role-panel-heading"><div><h2>Evaluasi Kegiatan Sekolah</h2><p>Ringkasan kegiatan dan tindak lanjut</p></div></div>
        <div class="role-action-row task-blue"><strong>Rencana pembelajaran</strong><small>4 rencana menunggu peninjauan</small><a href="#evaluasi">Tinjau ›</a></div>
        <div class="role-action-row task-yellow"><strong>Laporan perkembangan</strong><small>3 laporan belum dilengkapi</small><a href="#laporan">Lihat ›</a></div>
        <div class="role-action-row task-green"><strong>Kegiatan pembelajaran</strong><small>8 kelompok melaksanakan kegiatan</small><a href="#evaluasi">Detail ›</a></div>
        <a class="role-link" href="#evaluasi">Unduh ringkasan evaluasi →</a>
    </section>
    <section id="administrasi" class="role-panel">
        <div class="role-panel-heading"><div><h2>Administrasi &amp; Agenda</h2><p>Informasi untuk pengelolaan sekolah</p></div></div>
        <div class="role-progress-heading"><span>Berkas siswa terverifikasi</span><strong>118 / 128</strong></div><div class="role-progress"><span style="width:92%"></span></div>
        <p class="role-inline-message">10 berkas masih perlu diperbarui</p>
        <div id="laporan" class="role-agenda-row"><time>18 NOV</time><span><strong>Rapat evaluasi bulanan</strong><small>13.00–14.30 · Ruang guru</small></span></div>
        <div class="role-agenda-row"><time>20 NOV</time><span><strong>Kegiatan Jumat sehat</strong><small>07.30–09.00 · Halaman sekolah</small></span></div>
        <a class="role-link" href="#administrasi">Dokumentasi kegiatan sekolah →</a>
    </section>
</div>
