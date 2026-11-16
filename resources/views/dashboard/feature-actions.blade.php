@php
    $classOptions = $actions['classes'] ?? collect();
    $rosterOptions = $actions['rosters'] ?? collect();
@endphp

@if ($section === 'siswa' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div><h2>Kelola profil siswa</h2><p>Perbarui informasi siswa yang sudah terdaftar. Data hanya dapat dikelola administrator.</p></div>
        </div>
        @forelse ($actions['studentProfiles'] ?? [] as $student)
            <details class="database-student-editor">
                <summary>{{ $student->nama_lengkap }} <small>Ubah profil</small></summary>
                <form method="POST" action="{{ route('database.student.profile', ['student' => $student->id_siswa]) }}" class="database-form-grid">
                    @csrf
                    @method('PATCH')
                    <label>Nama lengkap<input name="nama_lengkap" value="{{ $student->nama_lengkap }}" maxlength="100" required></label>
                    <label>Nama panggilan<input name="nama_panggilan" value="{{ $student->nama_panggilan }}" maxlength="100"></label>
                    <label>Tempat lahir<input name="tempat_lahir" value="{{ $student->tempat_lahir }}" maxlength="100"></label>
                    <label>Tanggal lahir<input type="date" name="tanggal_lahir" value="{{ $student->tanggal_lahir }}" max="{{ now()->toDateString() }}" required></label>
                    <label>Jenis kelamin
                        <select name="jenis_kelamin" required>
                            <option value="Laki-Laki" @selected($student->jenis_kelamin === 'Laki-Laki')>Laki-laki</option>
                            <option value="Perempuan" @selected($student->jenis_kelamin === 'Perempuan')>Perempuan</option>
                        </select>
                    </label>
                    <label class="database-form-wide">Alamat domisili<textarea name="alamat_domisili" rows="2" maxlength="2000">{{ $student->alamat_domisili }}</textarea></label>
                    <div class="database-form-wide"><button class="database-button" type="submit">Simpan profil</button></div>
                </form>
            </details>
        @empty
            <p class="database-empty-note">Belum ada profil siswa yang dapat dikelola.</p>
        @endforelse
        @if (($actions['studentProfiles'] ?? null) && $actions['studentProfiles']->hasPages())
            <div class="database-pagination">{{ $actions['studentProfiles']->links() }}</div>
        @endif
    </section>
@endif

@if ($section === 'pendaftaran' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div><h2>Proses pendaftaran</h2><p>Periksa dokumen, verifikasi calon siswa, lalu tentukan kelas saat menerima.</p></div>
        </div>
        @forelse ($actions['registrationDocuments'] ?? [] as $document)
            <div class="database-action-row">
                <div>
                    <strong>{{ $document->nama_lengkap }} · {{ $document->jenis }}</strong>
                    <small>
                        Ukuran file:
                        @if ($document->file_size_bytes === null)
                            File tidak ditemukan
                        @elseif ($document->file_size_bytes < 1048576)
                            {{ number_format($document->file_size_bytes / 1024, 1, ',', '.') }} KB
                        @else
                            {{ number_format($document->file_size_bytes / 1048576, 2, ',', '.') }} MB
                        @endif
                    </small>
                    <small>Status dokumen: {{ $document->status_verifikasi }}{{ $document->catatan ? ' — '.$document->catatan : '' }}</small>
                </div>
                <div class="database-action-buttons">
                    <a class="database-button database-button-secondary" href="{{ route('database.registration.document.file', ['document' => $document->id_dokumen]) }}" target="_blank" rel="noopener">Lihat dokumen</a>
                    <form method="POST" action="{{ route('database.registration.document', ['document' => $document->id_dokumen]) }}" class="database-inline-form">
                        @csrf
                        <select name="status_verifikasi" aria-label="Status dokumen">
                            <option value="Valid">Valid</option>
                            <option value="Tidak Valid">Tidak valid</option>
                        </select>
                        <input name="catatan" placeholder="Catatan (opsional)" maxlength="255">
                        <button class="database-button" type="submit">Simpan</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="database-empty-note">Belum ada dokumen pendaftaran yang perlu diperiksa.</p>
        @endforelse

        @forelse ($actions['applications'] ?? [] as $application)
            <form method="POST" action="{{ route('database.registration.review', ['registration' => $application->id_pendaftaran]) }}" class="database-action-form database-registration-review">
                @csrf
                <div>
                    <strong>{{ $application->nama_lengkap }}</strong>
                    <small>{{ $application->no_pendaftaran }} · {{ $application->kelompok }} · {{ $application->tahun_ajaran }} · {{ $application->dokumen_valid }}/{{ $application->dokumen_diunggah }} dokumen valid</small>
                    <details class="database-registration-details">
                        <summary>Lihat data calon siswa dan wali</summary>
                        <dl>
                            <div><dt>Nama panggilan</dt><dd>{{ $application->nama_panggilan ?: '—' }}</dd></div>
                            <div><dt>Jenis kelamin</dt><dd>{{ $application->jenis_kelamin ?: '—' }}</dd></div>
                            <div><dt>Tempat, tanggal lahir</dt><dd>{{ $application->tempat_lahir ?: '—' }}, {{ $application->tanggal_lahir ?: '—' }}</dd></div>
                            <div><dt>Nomor KK</dt><dd>{{ $application->no_kk ? 'Sudah diisi' : 'Belum diisi' }}</dd></div>
                            <div><dt>Orang tua/wali</dt><dd>{{ $application->nama_wali ?: '—' }}{{ $application->hubungan_wali ? ' · '.$application->hubungan_wali : '' }}</dd></div>
                            <div><dt>Nomor kontak</dt><dd>{{ $application->nomor_wali ?: '—' }}</dd></div>
                            <div><dt>Email akun orang tua</dt><dd>{{ $application->email ?: '—' }}</dd></div>
                            <div class="database-registration-address"><dt>Alamat keluarga</dt><dd>{{ $application->alamat ?: '—' }}</dd></div>
                        </dl>
                    </details>
                </div>
                <select name="decision" required aria-label="Keputusan pendaftaran">
                    <option value="verify" @selected($application->status === 'Diajukan')>Verifikasi</option>
                    <option value="accept" @selected($application->status === 'Diverifikasi')>Terima</option>
                    <option value="reject">Tolak</option>
                </select>
                <select name="class_id" aria-label="Kelas penerimaan">
                    <option value="">Pilih kelas jika menerima</option>
                    @foreach ($classOptions as $class)
                        <option value="{{ $class->id_kelas }}" @disabled($class->kelompok !== $application->kelompok || $class->tahun_ajaran !== $application->tahun_ajaran)>
                            {{ $class->tahun_ajaran }} · {{ $class->kelompok }} · {{ $class->nama_kelas }}
                        </option>
                    @endforeach
                </select>
                <input type="date" name="due_date" aria-label="Jatuh tempo biaya pendaftaran">
                <input name="note" maxlength="255" placeholder="Catatan verifikasi">
                <button class="database-button" type="submit">Simpan keputusan</button>
            </form>
        @empty
            <p class="database-empty-note">Tidak ada pendaftaran yang menunggu proses.</p>
        @endforelse
    </section>
@endif

@if ($section === 'absensi' && $role === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Catat kehadiran</h2><p>Perubahan dicatat per siswa dan tanggal sesuai kelas yang diampu.</p></div></div>
        <form method="GET" action="{{ route('dashboard.feature', ['section' => 'absensi']) }}" class="database-inline-form database-filter-form">
            <select name="kelas" required aria-label="Pilih kelas">
                @foreach ($classOptions as $class)
                    <option value="{{ $class->id_kelas }}" @selected((int) ($actions['selectedClass'] ?? 0) === (int) $class->id_kelas)>{{ $class->nama_kelas }} · {{ $class->kelompok }}</option>
                @endforeach
            </select>
            <input type="date" name="tanggal" value="{{ $actions['attendanceDate'] ?? now()->toDateString() }}" max="{{ now()->toDateString() }}" required>
            <button class="database-button database-button-secondary" type="submit">Tampilkan daftar</button>
        </form>
        @if (($actions['attendanceRoster'] ?? collect())->isNotEmpty())
            <form method="POST" action="{{ route('database.attendance.store') }}">
                @csrf
                <input type="hidden" name="id_kelas" value="{{ $actions['selectedClass'] }}">
                <input type="hidden" name="tanggal" value="{{ $actions['attendanceDate'] }}">
                <div class="database-attendance-list">
                    @foreach ($actions['attendanceRoster'] as $student)
                        <div class="database-attendance-row">
                            <strong>{{ $student->nama_lengkap }}</strong>
                            <select name="attendance[{{ $student->id_rombel }}][status]" required aria-label="Status {{ $student->nama_lengkap }}">
                                @foreach (['Hadir', 'Izin', 'Sakit', 'Alfa'] as $status)
                                    <option value="{{ $status }}" @selected(($student->status_absensi ?? 'Hadir') === $status)>{{ $status }}</option>
                                @endforeach
                            </select>
                            <input name="attendance[{{ $student->id_rombel }}][keterangan]" value="{{ $student->keterangan }}" maxlength="255" placeholder="Keterangan">
                        </div>
                    @endforeach
                </div>
                <button class="database-button" type="submit">Simpan absensi</button>
            </form>
        @elseif ($classOptions->isEmpty())
            <p class="database-empty-note">Akun guru belum memiliki kelas yang ditugaskan.</p>
        @endif
    </section>
@endif

@if ($section === 'pembelajaran' && $role === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Buat rencana pembelajaran</h2><p>Simpan tema, tujuan, kegiatan, serta alat dan bahan.</p></div></div>
        <form method="POST" action="{{ route('database.lesson-plan.store') }}" class="database-form-grid">
            @csrf
            <label>Kelas<select name="id_kelas" required>@foreach ($classOptions as $class)<option value="{{ $class->id_kelas }}">{{ $class->nama_kelas }} · {{ $class->kelompok }}</option>@endforeach</select></label>
            <label>Tanggal<input type="date" name="tanggal" value="{{ now()->toDateString() }}" required></label>
            <label>Tema<input name="tema" maxlength="150" required></label>
            <label>Subtema<input name="subtema" maxlength="150"></label>
            <label class="database-form-wide">Tujuan<textarea name="tujuan" rows="2"></textarea></label>
            <label class="database-form-wide">Kegiatan<textarea name="kegiatan" rows="3"></textarea></label>
            <label class="database-form-wide">Alat dan bahan<textarea name="alat_bahan" rows="2"></textarea></label>
            <label>Status<select name="status"><option>Draft</option><option>Final</option></select></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Simpan rencana</button></div>
        </form>
    </section>
@endif

@if ($section === 'tugas' && $role === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Buat tugas kelas</h2><p>Guru dapat membuat tugas untuk kelas yang diampu dan menilai pengumpulan.</p></div></div>
        <form method="POST" action="{{ route('database.task.store') }}" enctype="multipart/form-data" class="database-form-grid">
            @csrf
            <label>Kelas<select name="id_kelas" required>@foreach ($classOptions as $class)<option value="{{ $class->id_kelas }}">{{ $class->nama_kelas }} · {{ $class->kelompok }}</option>@endforeach</select></label>
            <label>Judul tugas<input name="judul" maxlength="150" required></label>
            <label>Tanggal diberikan<input type="date" name="tanggal_diberikan" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
            <label>Tenggat<input type="date" name="tenggat" required></label>
            <label class="database-form-wide">Deskripsi<textarea name="deskripsi" rows="3"></textarea></label>
            <label class="database-form-wide">Lampiran tugas<input type="file" name="lampiran"></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Buat tugas</button></div>
        </form>

        <h3 class="database-subheading">Pengumpulan untuk dinilai</h3>
        @forelse ($actions['submissions'] ?? [] as $submission)
            <form method="POST" action="{{ route('database.task.grade', ['submission' => $submission->id_pengumpulan]) }}" class="database-action-row">
                @csrf @method('PATCH')
                <div>
                    <strong>{{ $submission->nama_lengkap }} · {{ $submission->judul }}</strong>
                    <small>{{ $submission->dikumpulkan_pada }} · {{ $submission->status }}</small>
                </div>
                @if ($submission->file_path)
                    <a class="database-button database-button-secondary" href="{{ route('database.task.submission.file', ['submission' => $submission->id_pengumpulan]) }}" target="_blank" rel="noopener">Lihat file</a>
                @endif
                <select name="nilai" required aria-label="Nilai tugas">
                    @foreach (['BB', 'MB', 'BSH', 'BSB'] as $grade)
                        <option value="{{ $grade }}" @selected($submission->nilai === $grade)>{{ $grade }}</option>
                    @endforeach
                </select>
                <input name="umpan_balik" value="{{ $submission->umpan_balik }}" placeholder="Umpan balik">
                <button class="database-button" type="submit">Simpan nilai</button>
            </form>
        @empty
            <p class="database-empty-note">Belum ada tugas yang dikumpulkan untuk kelas ini.</p>
        @endforelse
    </section>
@endif

@if ($section === 'tugas' && $role === 'parent')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Kumpulkan tugas anak</h2><p>Pengumpulan hanya dapat dilakukan untuk anak dan kelas yang terhubung ke akun ini.</p></div></div>
        @forelse ($actions['parentTasks'] ?? [] as $task)
            <form method="POST" action="{{ route('database.task.submit', ['task' => $task->id_tugas]) }}" enctype="multipart/form-data" class="database-action-row">
                @csrf
                <div><strong>{{ $task->nama_lengkap }} · {{ $task->judul }}</strong><small>Tenggat {{ $task->tenggat }} · {{ $task->dikumpulkan_pada ? 'Sudah dikumpulkan' : 'Belum dikumpulkan' }}</small></div>
                @if ($task->has_attachment)
                    <a class="database-button database-button-secondary" href="{{ route('database.task.attachment', ['task' => $task->id_tugas]) }}" target="_blank" rel="noopener">Lihat lampiran</a>
                @endif
                @if ($task->submission_file)
                    <a class="database-button database-button-secondary" href="{{ route('database.task.submission.file', ['submission' => $task->id_pengumpulan]) }}" target="_blank" rel="noopener">Lihat pengumpulan</a>
                @endif
                <input type="hidden" name="id_siswa" value="{{ $task->id_siswa }}">
                <input type="file" name="file" aria-label="File tugas">
                <input name="catatan_ortu" maxlength="255" placeholder="Catatan untuk guru">
                <button class="database-button" type="submit">Kirim</button>
            </form>
        @empty
            <p class="database-empty-note">Belum ada tugas pada kelas anak yang terhubung.</p>
        @endforelse
    </section>
@endif

@if ($section === 'nilai' && $role === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Catat perkembangan Iqro</h2><p>Indikator hanya dapat disimpan untuk kelompok siswa yang sesuai.</p></div></div>
        <form method="POST" action="{{ route('database.iqro.store') }}" class="database-form-grid">
            @csrf
            <label>Siswa / kelas<select name="id_rombel" required>@foreach ($rosterOptions as $roster)<option value="{{ $roster->id_rombel }}">{{ $roster->nama_lengkap }} · {{ $roster->nama_kelas }} ({{ $roster->kelompok }})</option>@endforeach</select></label>
            <label>Indikator<select name="id_indikator" required>@foreach ($actions['indicators'] ?? [] as $indicator)<option value="{{ $indicator->id_indikator }}">{{ $indicator->kelompok }} · {{ $indicator->kategori }} · {{ $indicator->nama }}</option>@endforeach</select></label>
            <label>Semester<select name="semester"><option>Ganjil</option><option>Genap</option></select></label>
            <label>Capaian<select name="skala"><option>BB</option><option>MB</option><option>BSH</option><option>BSB</option></select></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Simpan nilai</button></div>
        </form>
    </section>
@endif

@if ($section === 'perkembangan' && $role === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Tambah portofolio perkembangan</h2><p>Unggah foto dan catatan perkembangan siswa pada kelas yang diampu.</p></div></div>
        <form method="POST" action="{{ route('database.portfolio.store') }}" enctype="multipart/form-data" class="database-form-grid">
            @csrf
            <label>Siswa / kelas<select name="id_rombel" required>@foreach ($rosterOptions as $roster)<option value="{{ $roster->id_rombel }}">{{ $roster->nama_lengkap }} · {{ $roster->nama_kelas }}</option>@endforeach</select></label>
            <label>Tanggal<input type="date" name="tanggal" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
            <label>Foto<input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required></label>
            <label class="database-form-wide">Narasi<textarea name="narasi" rows="3" maxlength="5000" required></textarea></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Simpan portofolio</button></div>
        </form>
    </section>
@endif

@if ($section === 'rapor' && $role === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Susun rapor</h2><p>Rapor final terkunci sesuai aturan database; simpan sebagai draft untuk melanjutkan penyuntingan.</p></div></div>
        <form method="POST" action="{{ route('database.report.store') }}" class="database-form-grid">
            @csrf
            <label>Siswa / kelas<select name="id_rombel" required>@foreach ($rosterOptions as $roster)<option value="{{ $roster->id_rombel }}">{{ $roster->nama_lengkap }} · {{ $roster->nama_kelas }}</option>@endforeach</select></label>
            <label>Semester<select name="semester"><option>Ganjil</option><option>Genap</option></select></label>
            <label>Tanggal rapor<input type="date" name="tanggal_rapor"></label>
            <label>Status<select name="status"><option>Draft</option><option>Final</option></select></label>
            <label>Tinggi badan (cm)<input type="number" step="0.1" name="tinggi_badan" min="30" max="180"></label>
            <label>Berat badan (kg)<input type="number" step="0.1" name="berat_badan" min="5" max="120"></label>
            <label class="database-form-wide">Agama dan budi pekerti<textarea name="narasi_agama_budi_pekerti" rows="2"></textarea></label>
            <label class="database-form-wide">Jati diri<textarea name="narasi_jati_diri" rows="2"></textarea></label>
            <label class="database-form-wide">Literasi dan STEAM<textarea name="narasi_literasi_steam" rows="2"></textarea></label>
            <label class="database-form-wide">Projek P5<textarea name="narasi_p5" rows="2"></textarea></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Simpan rapor</button></div>
        </form>
    </section>
@endif

@if ($section === 'rapor' && $role === 'parent')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Refleksi orang tua</h2><p>Tulis refleksi pada rapor anak yang terhubung ke akun ini.</p></div></div>
        @forelse ($actions['parentReports'] ?? [] as $report)
            <form method="POST" action="{{ route('database.report.reflection', ['report' => $report->id_rapor]) }}" class="database-action-form">
                @csrf @method('PATCH')
                <strong>{{ $report->nama_lengkap }} · {{ $report->semester }} · {{ $report->status }}</strong>
                <textarea name="refleksi_orang_tua" rows="3" maxlength="5000" placeholder="Refleksi orang tua">{{ $report->refleksi_orang_tua }}</textarea>
                <button class="database-button" type="submit">Simpan refleksi</button>
            </form>
        @empty
            <p class="database-empty-note">Rapor anak belum tersedia.</p>
        @endforelse
    </section>
@endif

@if ($section === 'kegiatan' && $role === 'teacher')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Unggah dokumentasi kegiatan</h2><p>Buat kegiatan kelas, lalu unggah foto sebelum, selama, atau sesudah kegiatan. Foto hanya dapat dilihat oleh pengguna yang terhubung dengan kelas terkait.</p></div></div>
        <form method="POST" action="{{ route('database.activity.store') }}" class="database-form-grid">
            @csrf
            <label>Kelas<select name="id_kelas" required>@foreach ($classOptions as $class)<option value="{{ $class->id_kelas }}">{{ $class->nama_kelas }} · {{ $class->kelompok }}</option>@endforeach</select></label>
            <label>Nama kegiatan<input name="nama_kegiatan" maxlength="150" required></label>
            <label>Jenis<select name="jenis"><option>Dalam Kelas</option><option>Luar Kelas</option><option>Field Trip</option></select></label>
            <label>Tanggal<input type="date" name="tanggal" required></label>
            <label>Lokasi<input name="lokasi" maxlength="150"></label>
            <label class="database-form-wide">Keterangan<textarea name="deskripsi" rows="3"></textarea></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Buat kegiatan</button></div>
        </form>
        <h3 class="database-subheading">Pilih kegiatan dan upload foto</h3>
        @foreach ($actions['activities'] ?? [] as $activity)
            <form method="POST" action="{{ route('database.activity.photo.store', ['activity' => $activity->id_kegiatan]) }}" enctype="multipart/form-data" class="database-inline-form">
                @csrf
                <strong>{{ $activity->nama_kegiatan }} · {{ $activity->tanggal }}</strong>
                <select name="tahap"><option>Selama</option><option>Sebelum</option><option>Sesudah</option></select>
                <input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required>
                <input name="keterangan" maxlength="255" placeholder="Keterangan foto">
                <button class="database-button" type="submit">Upload foto</button>
            </form>
        @endforeach
    </section>
@endif

@if ($section === 'kegiatan')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Dokumentasi kegiatan</h2><p>Foto kegiatan yang dapat diakses sesuai peran dan kelas terkait.</p></div></div>
        <div class="database-photo-grid">
            @forelse ($actions['activityPhotos'] ?? [] as $photo)
                <a class="database-photo-card" href="{{ route('database.activity.photo', ['document' => $photo->id_dokumentasi]) }}" target="_blank" rel="noopener">
                    <img src="{{ route('database.activity.photo', ['document' => $photo->id_dokumentasi]) }}" alt="{{ $photo->nama_kegiatan }} - {{ $photo->tahap }}" loading="lazy">
                    <strong>{{ $photo->nama_kegiatan }}</strong>
                    <small>{{ $photo->tanggal }} · {{ $photo->tahap }}{{ $photo->keterangan ? ' · '.$photo->keterangan : '' }}</small>
                </a>
            @empty
                <p class="database-empty-note">Belum ada foto kegiatan.</p>
            @endforelse
        </div>
    </section>
@endif

@if (in_array($section, ['tagihan', 'pembayaran'], true) && in_array($role, ['admin', 'parent'], true))
    <section class="database-action-panel">
        @if ($role === 'admin' && $section === 'tagihan')
            <div class="database-section-heading"><div><h2>Buat tagihan SPP bulanan</h2><p>Tagihan dibuat untuk siswa aktif pada tahun ajaran aktif sesuai tarif database; tagihan yang sudah ada tidak digandakan.</p></div></div>
            <form method="POST" action="{{ route('database.bills.generate') }}" class="database-inline-form">
                @csrf
                <label>Periode<input type="month" name="periode" required></label>
                <label>Jatuh tempo<input type="date" name="jatuh_tempo" required></label>
                <button class="database-button" type="submit">Buat tagihan</button>
            </form>
        @endif

        @if ($role === 'admin' && $section === 'pembayaran')
            <div class="database-section-heading"><div><h2>Verifikasi pembayaran</h2><p>Periksa bukti transfer sebelum menyetujui transaksi.</p></div></div>
            @forelse ($actions['pendingPayments'] ?? [] as $payment)
                <form method="POST" action="{{ route('database.payment.review', ['payment' => $payment->id_pembayaran]) }}" class="database-action-row">
                    @csrf @method('PATCH')
                    <div><strong>{{ $payment->no_kuitansi }}</strong><small>{{ $payment->tanggal_bayar }} · {{ $payment->metode }} · Rp {{ number_format($payment->total_bayar, 0, ',', '.') }}</small></div>
                    @if ($payment->metode === 'Transfer')
                        <a class="database-button database-button-secondary" href="{{ route('database.payment.proof', ['payment' => $payment->id_pembayaran]) }}" target="_blank" rel="noopener">Lihat bukti</a>
                    @endif
                    <input name="catatan" maxlength="255" placeholder="Catatan verifikasi">
                    <button class="database-button" type="submit" name="decision" value="verify">Setujui</button>
                    <button class="database-button database-button-danger" type="submit" name="decision" value="reject">Tolak</button>
                </form>
            @empty
                <p class="database-empty-note">Tidak ada pembayaran yang menunggu verifikasi.</p>
            @endforelse
        @endif

        @if ($section === 'pembayaran')
            <div class="database-section-heading"><div><h2>{{ $role === 'parent' ? 'Kirim pembayaran' : 'Catat pembayaran' }}</h2><p>Pembayaran transfer membutuhkan bukti; transaksi tunai oleh administrator langsung tercatat.</p></div></div>
            @if ($role === 'parent')
                <div class="database-bank-details" role="note" aria-label="Informasi rekening pembayaran">
                    <div><span>Bank</span><strong>Bank Syariah Indonesia (BSI)</strong></div>
                    <div><span>Nomor rekening</span><strong>7276048637</strong></div>
                    <div><span>Atas nama</span><strong>Sri Hariati · Kepala Sekolah</strong></div>
                    <p>Transfer sesuai jumlah tagihan, lalu unggah bukti transfer agar pembayaran dapat diverifikasi.</p>
                </div>
            @endif
            <form method="POST" action="{{ route('database.payment.store') }}" enctype="multipart/form-data" class="database-form-grid">
                @csrf
                <label>Tagihan<select name="id_tagihan" required>
                    @foreach ($actions['bills'] ?? [] as $bill)
                        @if ((float) $bill->sisa > 0)
                            <option value="{{ $bill->id_tagihan }}">{{ $bill->nama_lengkap }} · {{ $bill->jenis_biaya }} · Sisa Rp {{ number_format($bill->sisa, 0, ',', '.') }}</option>
                        @endif
                    @endforeach
                </select></label>
                <label>Jumlah<input type="number" name="jumlah" min="1" step="0.01" required></label>
                <label>Metode<select name="metode" required><option value="Tunai">Tunai</option><option value="Transfer">Transfer</option></select></label>
                <label>Bukti transfer (wajib untuk transfer)<input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf"></label>
                <label class="database-form-wide">Catatan<input name="catatan" maxlength="255"></label>
                <div class="database-form-wide"><button class="database-button" type="submit">Simpan pembayaran</button></div>
            </form>
        @endif
    </section>
@endif

@if ($section === 'tabungan' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Catat transaksi tabungan</h2><p>Transaksi setor dan tarik disimpan sebagai riwayat; transaksi yang sudah tercatat tidak dihapus.</p></div></div>
        <form method="POST" action="{{ route('database.savings.store') }}" class="database-form-grid">
            @csrf
            <label>Siswa<select name="id_siswa" required>@foreach ($actions['activeStudents'] ?? [] as $student)<option value="{{ $student->id_siswa }}">{{ $student->nama_lengkap }}</option>@endforeach</select></label>
            <label>Jenis<select name="jenis"><option>Setor</option><option>Tarik</option></select></label>
            <label>Jumlah<input type="number" name="jumlah" min="1" step="0.01" required></label>
            <label>Metode<select name="metode"><option>Tunai</option><option>Transfer</option></select></label>
            <label class="database-form-wide">Keterangan<input name="keterangan" maxlength="255"></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Catat transaksi</button></div>
        </form>
    </section>
@endif

@if ($section === 'tarif' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div><h2>Kelola tarif biaya</h2><p>Tarif baru berlaku untuk tagihan yang dibuat setelah perubahan. Tagihan yang sudah terbit tidak ikut berubah.</p></div>
        </div>
        <form method="POST" action="{{ route('database.tariff.store') }}" class="database-form-grid">
            @csrf
            <label>Tahun ajaran<select name="id_tahun_ajaran" required>@foreach ($actions['schoolYears'] ?? [] as $year)<option value="{{ $year->id_tahun_ajaran }}">{{ $year->nama }}</option>@endforeach</select></label>
            <label>Kelompok<select name="kelompok" required><option>KB</option><option>TK A</option><option>TK B</option></select></label>
            <label>Jenis biaya<select name="id_jenis_biaya" required>@foreach ($actions['feeTypes'] ?? [] as $feeType)<option value="{{ $feeType->id_jenis_biaya }}">{{ $feeType->nama }} · {{ $feeType->kategori }}</option>@endforeach</select></label>
            <label>Nominal<input type="number" name="nominal" min="0" max="9999999999" step="0.01" required></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Tambah tarif</button></div>
        </form>
        @forelse ($records as $tariff)
            <form method="POST" action="{{ route('database.tariff.update', ['tariff' => $tariff->id_tarif]) }}" class="database-action-row">
                @csrf
                @method('PATCH')
                <div>
                    <strong>{{ $tariff->tahun_ajaran }} · {{ $tariff->kelompok }} · {{ $tariff->nama }}</strong>
                    <small>{{ $tariff->kategori }}</small>
                </div>
                <label>Nominal
                    <input type="number" name="nominal" value="{{ $tariff->nominal }}" min="0" max="9999999999" step="0.01" required>
                </label>
                <button class="database-button" type="submit">Simpan tarif</button>
            </form>
        @empty
            <p class="database-empty-note">Tarif biaya belum tersedia.</p>
        @endforelse
    </section>
@endif

@if ($section === 'pengguna' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Buat akun pengguna</h2><p>Peran akun disimpan sesuai enum pengguna pada database.</p></div></div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="database-form-grid">
            @csrf
            <label>Nama<input name="name" maxlength="100" required></label>
            <label>Email<input type="email" name="email" maxlength="150" required></label>
            <label>Peran<select name="role"><option value="teacher">Guru</option><option value="principal">Kepala sekolah</option><option value="parent">Orang tua</option><option value="admin">Administrator</option></select></label>
            <label>Kata sandi<input type="password" name="password" minlength="8" required></label>
            <label>Ulangi kata sandi<input type="password" name="password_confirmation" minlength="8" required></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Buat akun</button></div>
        </form>
    </section>
@endif
