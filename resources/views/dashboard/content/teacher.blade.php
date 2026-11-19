<section id="ringkasan" class="role-section">
    <h1 class="role-section-title">Kartu Ringkasan</h1>
    <div class="role-stat-grid">
        <article class="role-stat stat-soft-blue"><span>Siswa TK A Ar Rahim</span><strong>10</strong><small>Kapasitas 12 siswa</small></article>
        <article class="role-stat stat-soft-green"><span>Hadir Hari Ini</span><strong>9</strong><small>1 izin · 0 tanpa keterangan</small></article>
        <article class="role-stat stat-soft-yellow"><span>Draf Rapor Semester Ganjil</span><strong>2</strong><small>8 dari 10 rapor siap</small></article>
    </div>
</section>

<section id="jadwal" class="role-panel role-schedule" data-schedule-widget>
    <div class="role-panel-heading">
        <div><h2>Jadwal Mengajar</h2><p>Lismainur, S.E. · Kelas TK A – Ar Rahim · Kurikulum Merdeka PAUD</p></div>
        <span class="role-panel-meta">4 sesi · 07.50–11.30 WIB</span>
    </div>
    @include('dashboard.schedule-picker')
    <div class="role-table-wrap"><table class="role-table"><thead><tr><th>Waktu (WIB)</th><th>Kegiatan mengajar</th><th>Kelompok / lokasi</th><th>Persiapan</th></tr></thead><tbody>
        <tr><td>07.50–08.15</td><td>Berbaris &amp; Upacara Bendera</td><td>TK A · Halaman Sekolah</td><td>Rutin harian</td></tr>
        <tr><td>08.15–09.00</td><td>Kegiatan Awal: Salam, Berdoa &amp; Iqro / Anbata</td><td>TK A · Kelas Ar Rahim</td><td>Buku Iqro &amp; Anbata</td></tr>
        <tr><td>09.00–10.30</td><td>Kegiatan Inti: Kognitif, Bahasa &amp; Seni</td><td>TK A · Kelas Ar Rahim</td><td>Alat peraga &amp; kolase</td></tr>
        <tr><td>10.30–11.30</td><td>Istirahat, Evaluasi &amp; Doa Pulang</td><td>TK A · Kelas Ar Rahim</td><td>Catatan observasi</td></tr>
    </tbody></table></div>
    <div class="role-panel-foot"><span>Kegiatan inti 09.00–10.30 · RPPH tersimpan</span><a href="{{ route('dashboard.feature', ['section' => 'rencana', 'role' => 'teacher']) }}">Lihat jadwal mingguan →</a></div>
</section>

<div class="role-two-column">
    <section id="absensi" class="role-panel">
        <div class="role-panel-heading"><div><h2>Absensi Hari Ini</h2><p>Kelas TK A – Ar Rahim · 10 siswa</p></div><span class="role-status">9 hadir · 1 izin</span></div>
        <div class="role-student-list">
            <div class="role-student"><i>AR</i><span><strong>Aqila Rizky Azaela</strong><small>TK A · Ar Rahim</small></span><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><b>Hadir</b></a><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><em>Izin</em></a></div>
            <div class="role-student"><i>AH</i><span><strong>Arzan Habib Hia</strong><small>TK A · Ar Rahim</small></span><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><b>Hadir</b></a><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><em>Izin</em></a></div>
            <div class="role-student"><i>MN</i><span><strong>Maqil Niematan Waruwu</strong><small>TK A · Ar Rahim</small></span><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><b>Hadir</b></a><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><em>Izin</em></a></div>
            <div class="role-student"><i>RH</i><span><strong>Rayra Harfanisyah Lubis</strong><small>TK A · Ar Rahim</small></span><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><b>Hadir</b></a><a href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}"><em>Izin</em></a></div>
        </div>
        <a class="role-link" href="{{ route('dashboard.feature', ['section' => 'absensi', 'role' => 'teacher']) }}">Lihat seluruh absensi →</a>
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
        <p class="role-chart-note">81% siswa berkembang sesuai harapan (BSH/BSB) minggu ini.</p>
    </section>
    <section id="rapor" class="role-panel">
        <div class="role-panel-heading"><div><h2>Observasi &amp; Rapor</h2><p>Catatan belajar menjadi dasar penyusunan rapor.</p></div></div>
        <div class="role-progress-heading"><span>Kelengkapan draf rapor</span><strong>8 / 10 siswa</strong></div><div class="role-progress"><span style="width:80%"></span></div>
        <div class="role-note"><strong>Aqila Rizky Azaela · Nilai Agama &amp; Budi Pekerti</strong><p>Hafalan doa sebelum makan dan kerapian kolase berkembang sangat baik.</p></div>
        <div class="role-panel-foot"><span class="text-warning">2 observasi perlu dilengkapi</span><a href="{{ route('dashboard.feature', ['section' => 'rapor', 'role' => 'teacher']) }}">Lanjutkan rapor →</a></div>
        <div id="dokumentasi" class="role-inline-row"><span><strong>Dokumentasi kegiatan</strong><small>Pentas Seni TK Kec. Helvetia · Outing Class</small></span><a href="{{ route('dashboard.feature', ['section' => 'kegiatan', 'role' => 'teacher']) }}">Kelola &amp; upload →</a></div>
    </section>
    <section id="pembelajaran" class="role-panel">
        <div class="role-panel-heading"><div><h2>Rencana Belajar &amp; Tugas</h2><p>Minggu ini · TK A Ar Rahim</p></div></div>
        <a id="rencana" class="role-task-row task-blue" href="{{ route('dashboard.feature', ['section' => 'rencana', 'role' => 'teacher']) }}"><span>□</span><strong>RPPH Mingguan</strong><small>Tersimpan</small><b>›</b></a>
        <a class="role-task-row task-yellow" href="{{ route('dashboard.feature', ['section' => 'tugas', 'role' => 'teacher']) }}"><span>□</span><strong>Kolase Bentuk Geometri &amp; Doa</strong><small>Tugas aktif</small><b>›</b></a>
        <a class="role-task-row task-green" href="{{ route('dashboard.feature', ['section' => 'pembelajaran', 'role' => 'teacher']) }}"><span>□</span><strong>Kegiatan Hari Ini</strong><small>08.00–10.00</small><b>›</b></a>
        <a id="tugas" class="role-task-row task-cream" href="{{ route('dashboard.feature', ['section' => 'tugas', 'role' => 'teacher']) }}"><span>□</span><strong>Rekap &amp; Tugas</strong><small>2 tugas aktif</small><b>›</b></a>
    </section>
</div>
