@php
    $classOptions = $actions['classes'] ?? collect();
    $rosterOptions = $actions['rosters'] ?? collect();
@endphp

@if ($section === 'pendaftaran' && $role === 'parent')
    <section class="database-action-panel parent-reg-panel">
        <div class="database-section-heading">
            <div>
                <h2>Kelengkapan dokumen pendaftaran</h2>
                <p>Unggah dokumen yang belum ada atau ganti dokumen yang ditolak admin. Berkas yang sedang diperiksa tidak perlu diunggah ulang.</p>
            </div>
        </div>
        @forelse ($actions['parentApplications'] ?? [] as $application)
            @php
                $isAppRejected = $application->status === 'Ditolak';
                $totalDocs = $application->requiredDocuments->count();
                $uploadedDocs = $application->requiredDocuments->filter(fn ($item) => (bool) $item['document'])->count();
                $validDocs = $application->requiredDocuments->filter(fn ($item) => ! $isAppRejected && $item['document'] && $item['document']->status_verifikasi === 'Valid')->count();
                $rejectedDocs = $application->requiredDocuments->filter(fn ($item) => $item['document'] && ($isAppRejected || $item['document']->status_verifikasi === 'Tidak Valid'))->count();
                $documentsToUpload = $application->requiredDocuments->filter(function ($item) use ($isAppRejected) {
                    return ! $item['document'] || $isAppRejected || $item['document']->status_verifikasi === 'Tidak Valid';
                });
                $appStatusClass = match ($application->status) {
                    'Diverifikasi', 'Diterima' => 'is-valid',
                    'Ditolak' => 'is-invalid',
                    default => 'is-pending',
                };
                $progressPercent = $totalDocs > 0 ? (int) round(($uploadedDocs / $totalDocs) * 100) : 0;
                $studentInitial = mb_strtoupper(mb_substr(trim((string) $application->nama_lengkap), 0, 1)) ?: 'S';
            @endphp
            <article class="parent-reg-card">
                <header class="parent-reg-card-header">
                    <div class="parent-reg-student">
                        <span class="parent-reg-avatar" aria-hidden="true">{{ $studentInitial }}</span>
                        <div class="parent-reg-student-meta">
                            <strong>{{ $application->nama_lengkap }}</strong>
                            <span class="parent-reg-progress-text">
                                {{ $uploadedDocs }}/{{ $totalDocs }} berkas terunggah
                                @if ($validDocs > 0)
                                    · {{ $validDocs }} valid
                                @endif
                                @if ($rejectedDocs > 0)
                                    · {{ $rejectedDocs }} ditolak
                                @endif
                            </span>
                        </div>
                    </div>
                    <div class="parent-reg-status-wrap">
                        <span class="parent-reg-status-label">Status pendaftaran</span>
                        <span class="parent-reg-status-badge {{ $appStatusClass }}">
                            <span class="parent-reg-status-dot" aria-hidden="true"></span>
                            {{ $application->status }}
                        </span>
                    </div>
                </header>

                <div class="parent-reg-progress-track" role="progressbar" aria-valuenow="{{ $progressPercent }}" aria-valuemin="0" aria-valuemax="100" aria-label="Kelengkapan berkas {{ $application->nama_lengkap }}">
                    <span class="parent-reg-progress-fill @if ($application->status === 'Ditolak') is-rejected @elseif ($documentsToUpload->isEmpty()) is-complete @endif" style="width: {{ $progressPercent }}%"></span>
                </div>

                @if ($application->status === 'Ditolak')
                    <div class="parent-reg-rejected-banner" role="alert">
                        <span class="parent-reg-rejected-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </span>
                        <div class="parent-reg-rejected-body">
                            <strong>Pendaftaran ditolak oleh administrator</strong>
                            @if (! empty($application->catatan_verifikasi))
                                <p>Catatan admin: {{ $application->catatan_verifikasi }}</p>
                            @else
                                <p>Anda dapat memperbaiki data calon siswa atau mengganti dokumen di bawah ini, lalu ajukan ulang pendaftaran.</p>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="parent-reg-doc-table" role="list">
                    <div class="parent-reg-doc-head" aria-hidden="true">
                        <span>Nama Berkas</span>
                        <span>Status Berkas</span>
                        <span>Aksi</span>
                    </div>
                    @foreach ($application->requiredDocuments as $requiredDocument)
                        @php
                            $document = $requiredDocument['document'];
                            $isDocRejected = $document && ($isAppRejected || $document->status_verifikasi === 'Tidak Valid');
                            $docStatusLabel = match (true) {
                                ! $document => 'Belum diunggah',
                                $isDocRejected => 'Ditolak',
                                default => $document->status_verifikasi,
                            };
                            $docStateClass = match (true) {
                                ! $document => 'is-missing',
                                $isDocRejected => 'is-invalid',
                                $document->status_verifikasi === 'Valid' => 'is-valid',
                                default => 'is-pending',
                            };
                            $docExt = $document ? strtolower(pathinfo((string) ($document->file_path ?? ''), PATHINFO_EXTENSION)) : '';
                            $docUrl = $document ? route('database.registration.document.file', ['document' => $document->id_dokumen]) : null;
                        @endphp
                        <div class="parent-reg-doc-row {{ $docStateClass }}" role="listitem">
                            <div class="parent-reg-doc-info">
                                <span class="parent-reg-doc-icon {{ $docStateClass }}" aria-hidden="true">
                                    @if ($docStateClass === 'is-valid')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <polyline points="9 15 11 17 15 13"/>
                                        </svg>
                                    @elseif ($docStateClass === 'is-invalid')
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="9.5" y1="12.5" x2="14.5" y2="17.5"/>
                                            <line x1="14.5" y1="12.5" x2="9.5" y2="17.5"/>
                                        </svg>
                                    @else
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                            <polyline points="14 2 14 8 20 8"/>
                                            <line x1="9" y1="13" x2="15" y2="13"/>
                                            <line x1="9" y1="17" x2="13" y2="17"/>
                                        </svg>
                                    @endif
                                </span>
                                <div class="parent-reg-doc-text">
                                    <span class="parent-reg-doc-name">{{ $requiredDocument['label'] }}</span>
                                    @if ($document && $document->catatan)
                                        <span class="parent-reg-doc-note">Catatan: {{ $document->catatan }}</span>
                                    @endif
                                </div>
                            </div>

                            <div class="parent-reg-doc-status-col">
                                <span class="parent-reg-doc-badge {{ $docStateClass }}">
                                    <span class="parent-reg-status-dot" aria-hidden="true"></span>
                                    {{ $docStatusLabel }}
                                </span>
                            </div>

                            <div class="parent-reg-doc-action-col">
                                @if ($document && $docUrl)
                                    <a
                                        class="parent-reg-doc-view-btn"
                                        href="{{ $docUrl }}"
                                        data-doc-preview
                                        data-doc-name="{{ $application->nama_lengkap }} · {{ $requiredDocument['label'] }}"
                                        data-doc-ext="{{ $docExt }}"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                            <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                                            <circle cx="12" cy="12" r="3"/>
                                        </svg>
                                        <span>Lihat berkas</span>
                                    </a>
                                @else
                                    <span class="parent-reg-doc-empty-action">Perlu diunggah</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($application->status === 'Ditolak')
                    <div class="parent-reg-resubmit-box">
                        <div class="parent-reg-upload-heading">
                            <strong>Ajukan ulang pendaftaran</strong>
                            <span>Periksa dan perbaiki data calon siswa atau ganti berkas yang diperlukan, lalu kirim kembali untuk diverifikasi admin.</span>
                        </div>
                        <form method="POST" action="{{ route('database.registration.resubmit', ['registration' => $application->id_pendaftaran]) }}" enctype="multipart/form-data" class="database-form-grid">
                            @csrf
                            <label>Nama lengkap calon siswa
                                <input name="student_name" value="{{ old('student_name', $application->nama_lengkap) }}" maxlength="100" required>
                            </label>
                            <label>Nama panggilan
                                <input name="student_nickname" value="{{ old('student_nickname', $application->nama_panggilan ?? '') }}" maxlength="100">
                            </label>
                            <label>Jenis kelamin
                                <select name="student_gender" required>
                                    <option value="Laki-Laki" @selected(old('student_gender', $application->jenis_kelamin ?? '') === 'Laki-Laki')>Laki-laki</option>
                                    <option value="Perempuan" @selected(old('student_gender', $application->jenis_kelamin ?? '') === 'Perempuan')>Perempuan</option>
                                </select>
                            </label>
                            <label>Kelompok tujuan
                                <select name="group" required>
                                    <option value="KB" @selected(old('group', $application->kelompok ?? '') === 'KB')>KB</option>
                                    <option value="TK A" @selected(old('group', $application->kelompok ?? '') === 'TK A')>TK A</option>
                                    <option value="TK B" @selected(old('group', $application->kelompok ?? '') === 'TK B')>TK B</option>
                                </select>
                            </label>
                            <label>Tempat lahir
                                <input name="student_birthplace" value="{{ old('student_birthplace', $application->tempat_lahir ?? '') }}" maxlength="100">
                            </label>
                            <label>Tanggal lahir
                                <input type="date" name="student_birthdate" value="{{ old('student_birthdate', $application->tanggal_lahir ?? '') }}" max="{{ now()->toDateString() }}" required>
                            </label>
                            <label class="database-form-wide">Alamat domisili
                                <textarea name="family_address" rows="2" maxlength="2000" required>{{ old('family_address', $application->alamat_domisili ?? '') }}</textarea>
                            </label>

                            <p class="parent-reg-resubmit-section-title">Perbarui berkas pendaftaran (PDF/JPG/PNG, maks. 5 MB)</p>
                            @foreach ($application->requiredDocuments as $requiredDocument)
                                @php
                                    $documentKey = $requiredDocument['key'];
                                    $document = $requiredDocument['document'];
                                    $mustReupload = ! $document;
                                    $documentError = $errors->first('documents.'.$documentKey);
                                    $prevExt = $document ? strtolower(pathinfo((string) ($document->file_path ?? ''), PATHINFO_EXTENSION)) : '';
                                    $prevIsImage = in_array($prevExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                    $prevUrl = $document ? route('database.registration.document.file', ['document' => $document->id_dokumen]) : null;
                                @endphp
                                <div class="parent-reg-doc-upload-card">
                                    <label>
                                        <span>
                                            {{ $requiredDocument['label'] }}
                                            <span class="parent-reg-resubmit-doc-hint @if ($mustReupload) is-required @endif">
                                                ({{ $mustReupload ? 'Wajib diunggah' : 'Opsional — ganti jika perlu' }})
                                            </span>
                                        </span>
                                        <input
                                            type="file"
                                            name="documents[{{ $documentKey }}]"
                                            accept=".pdf,.jpg,.jpeg,.png"
                                            data-max-bytes="5242880"
                                            @required($mustReupload)
                                        >
                                        @if ($documentError)
                                            <small class="registration-upload-feedback is-error">{{ $documentError }}</small>
                                        @endif
                                    </label>
                                    @if ($document && $prevUrl)
                                        <div class="parent-reg-prev-file">
                                            <div class="parent-reg-prev-file-left">
                                                @if ($prevIsImage)
                                                    <a
                                                        class="parent-reg-prev-thumb-link"
                                                        href="{{ $prevUrl }}"
                                                        data-doc-preview
                                                        data-doc-name="{{ $application->nama_lengkap }} · {{ $requiredDocument['label'] }} (Sebelumnya)"
                                                        data-doc-ext="{{ $prevExt }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        title="Pratinjau {{ $requiredDocument['label'] }} sebelumnya"
                                                    >
                                                        <img class="parent-reg-prev-thumb" src="{{ $prevUrl }}" alt="Pratinjau {{ $requiredDocument['label'] }}" loading="lazy">
                                                    </a>
                                                @else
                                                    <a
                                                        class="parent-reg-prev-pdf"
                                                        href="{{ $prevUrl }}"
                                                        data-doc-preview
                                                        data-doc-name="{{ $application->nama_lengkap }} · {{ $requiredDocument['label'] }} (Sebelumnya)"
                                                        data-doc-ext="{{ $prevExt }}"
                                                        target="_blank"
                                                        rel="noopener"
                                                        title="Pratinjau {{ $requiredDocument['label'] }} sebelumnya"
                                                    >
                                                        {{ strtoupper($prevExt ?: 'FILE') }}
                                                    </a>
                                                @endif
                                                <div class="parent-reg-prev-meta">
                                                    <span class="parent-reg-prev-title">Berkas sebelumnya ({{ strtoupper($prevExt ?: 'FILE') }})</span>
                                                    <span class="parent-reg-prev-sub">Status: Ditolak · klik pratinjau untuk melihat</span>
                                                </div>
                                            </div>
                                            <a
                                                class="parent-reg-prev-btn"
                                                href="{{ $prevUrl }}"
                                                data-doc-preview
                                                data-doc-name="{{ $application->nama_lengkap }} · {{ $requiredDocument['label'] }} (Sebelumnya)"
                                                data-doc-ext="{{ $prevExt }}"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                    <path d="M1 12s4-7 11-7 11 7 11 7-4 7-11 7-11-7-11-7z"/>
                                                    <circle cx="12" cy="12" r="3"/>
                                                </svg>
                                                <span>Pratinjau</span>
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            @endforeach

                            <div class="database-form-wide parent-reg-upload-footer">
                                <small>Status pendaftaran akan kembali menjadi <strong>Diajukan</strong> untuk diperiksa ulang oleh admin.</small>
                                <button class="database-button" type="submit">Ajukan ulang pendaftaran</button>
                            </div>
                        </form>
                    </div>
                @elseif ($documentsToUpload->isNotEmpty())
                    <div class="parent-reg-upload-box">
                        <div class="parent-reg-upload-heading">
                            <strong>Unggah atau perbaiki dokumen</strong>
                            <span>Format yang didukung: PDF, JPG, atau PNG (maks. 5 MB per berkas).</span>
                        </div>
                        <form method="POST" action="{{ route('database.registration.documents.upload', ['registration' => $application->id_pendaftaran]) }}" enctype="multipart/form-data" class="database-form-grid">
                            @csrf
                            @foreach ($documentsToUpload as $requiredDocument)
                                @php
                                    $documentKey = $requiredDocument['key'];
                                    $documentError = $errors->first('documents.'.$documentKey);
                                @endphp
                                <label>{{ $requiredDocument['label'] }} (PDF/JPG/PNG, maks. 5 MB)
                                    <input
                                        type="file"
                                        name="documents[{{ $documentKey }}]"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        data-max-bytes="5242880"
                                    >
                                    @if ($documentError)
                                        <small class="registration-upload-feedback is-error">{{ $documentError }}</small>
                                    @endif
                                </label>
                            @endforeach
                            <div class="database-form-wide parent-reg-upload-footer">
                                <small>Pilih minimal satu dokumen untuk diunggah atau diperbaiki.</small>
                                <button class="database-button" type="submit">Unggah dokumen</button>
                            </div>
                        </form>
                    </div>
                @else
                    <div class="parent-reg-complete-banner">
                        <span class="parent-reg-complete-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                                <polyline points="22 4 12 14.01 9 11.01"/>
                            </svg>
                        </span>
                        <p>Semua dokumen yang perlu diunggah sudah tersedia dan sedang menunggu pemeriksaan atau telah valid.</p>
                    </div>
                @endif
            </article>
        @empty
            <p class="database-empty-note">Tidak ada pendaftaran aktif yang memerlukan dokumen tambahan.</p>
        @endforelse
    </section>
@endif

@if ($section === 'siswa' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div>
                <h2>Pendaftaran siswa baru</h2>
                <p>Calon siswa baru didaftarkan langsung oleh orang tua melalui formulir PPDB daring, lalu diverifikasi dan diterima oleh admin pada menu Pendaftaran.</p>
            </div>
            <a class="database-button database-button-secondary" href="{{ route('dashboard.feature', ['section' => 'pendaftaran']) }}">Buka Pendaftaran →</a>
        </div>
    </section>

    @if (($actions['studentPlacements'] ?? collect())->isNotEmpty() && ($actions['activeClasses'] ?? collect())->isNotEmpty())
        <section class="database-action-panel">
            <div class="database-section-heading">
                <div><h2>Penempatan kelas &amp; diskon SPP siswa</h2><p>Tempatkan siswa dari kelas sementara (Belum Ditentukan) ke kelas definitif atau atur potongan biaya SPP (Anak Guru / Kurang Mampu).</p></div>
            </div>
            <form method="POST" action="{{ route('database.student.place') }}" class="database-form-grid">
                @csrf
                <label>Siswa
                    <select name="id_siswa" required>
                        @foreach ($actions['studentPlacements'] as $placement)
                            <option value="{{ $placement->id_siswa }}">{{ $placement->nama_lengkap }} · Saat ini: {{ $placement->nama_kelas ?? 'Belum punya rombel' }}{{ $placement->kelompok ? ' ('.$placement->kelompok.')' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Kelas tujuan
                    <select name="id_kelas" required>
                        @foreach ($actions['activeClasses'] as $activeClass)
                            <option value="{{ $activeClass->id_kelas }}">{{ $activeClass->nama_kelas }} · {{ $activeClass->kelompok }} ({{ $activeClass->tahun_ajaran }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Kategori diskon SPP
                    <select name="id_diskon">
                        <option value="">Tanpa diskon</option>
                        @foreach ($actions['discounts'] ?? [] as $discount)
                            <option value="{{ $discount->id_diskon }}">{{ $discount->nama }} ({{ ($discount->tipe ?? $discount->jenis ?? '') === 'Persen' ? $discount->nilai.'%' : 'Rp'.number_format($discount->nilai, 0, ',', '.') }})</option>
                        @endforeach
                    </select>
                </label>
                <div class="database-form-wide"><button class="database-button" type="submit">Simpan penempatan kelas</button></div>
            </form>
        </section>
    @endif

    <section class="database-action-panel">
        <div class="database-section-heading">
            <div><h2>Kelola profil siswa</h2><p>Perbarui informasi siswa yang sudah terdaftar. Data hanya dapat dikelola administrator.</p></div>
        </div>
        @forelse ($actions['studentProfiles'] ?? [] as $student)
            <details class="database-student-editor" id="student-profile-editor-{{ $student->id_siswa }}">
                <summary>{{ $student->nama_lengkap }} <small>Ubah profil</small></summary>
                <form method="POST" action="{{ route('database.student.profile', ['student' => $student->id_siswa]) }}" class="database-form-grid">
                    @csrf
                    @method('PATCH')
                    <label>Nama lengkap<input name="nama_lengkap" value="{{ $student->nama_lengkap }}" maxlength="100" required></label>
                    <label>Nama panggilan<input name="nama_panggilan" value="{{ $student->nama_panggilan }}" maxlength="100"></label>
                    @if (property_exists($student, 'nik'))
                        <label>NIK (16 digit)<input name="nik" value="{{ $student->nik }}" maxlength="16" pattern="[0-9]{16}"></label>
                    @endif
                    @if (property_exists($student, 'nisn'))
                        <label>NISN (10 digit)<input name="nisn" value="{{ $student->nisn }}" maxlength="10" pattern="[0-9]{10}"></label>
                    @endif
                    @if (property_exists($student, 'no_induk'))
                        <label>Nomor induk<input name="no_induk" value="{{ $student->no_induk }}" maxlength="30"></label>
                    @endif
                    <label>Tempat lahir<input name="tempat_lahir" value="{{ $student->tempat_lahir }}" maxlength="100"></label>
                    <label>Tanggal lahir<input type="date" name="tanggal_lahir" value="{{ $student->tanggal_lahir }}" max="{{ now()->toDateString() }}" required></label>
                    <label>Jenis kelamin
                        <select name="jenis_kelamin" required>
                            <option value="Laki-Laki" @selected($student->jenis_kelamin === 'Laki-Laki')>Laki-laki</option>
                            <option value="Perempuan" @selected($student->jenis_kelamin === 'Perempuan')>Perempuan</option>
                        </select>
                    </label>
                    @if (property_exists($student, 'agama'))
                        <label>Agama<input name="agama" value="{{ $student->agama }}" maxlength="30"></label>
                    @endif
                    @if (property_exists($student, 'anak_ke'))
                        <label>Anak ke-<input type="number" name="anak_ke" value="{{ $student->anak_ke }}" min="1" max="20"></label>
                    @endif
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

@if ($section === 'guru' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div><h2>Tambah &amp; tugaskan guru</h2><p>Kelola data pendidik dan penugasan wali kelas/pendamping pada tahun ajaran aktif.</p></div>
        </div>
        <details class="database-student-editor">
            <summary>Tambah data guru baru</summary>
            <form method="POST" action="{{ route('database.teacher.store') }}" class="database-form-grid">
                @csrf
                <label>Nama lengkap<input name="nama_lengkap" maxlength="100" required></label>
                <label>Gelar akademik<input name="gelar_belakang" maxlength="50" placeholder="S.Pd."></label>
                <label>NUPTK<input name="nuptk" maxlength="16"></label>
                <label>NIK (16 digit)<input name="nik" maxlength="16" pattern="[0-9]{16}"></label>
                <label>Jenis kelamin
                    <select name="jenis_kelamin" required>
                        <option value="Perempuan">Perempuan</option>
                        <option value="Laki-Laki">Laki-laki</option>
                    </select>
                </label>
                <label>Tempat lahir<input name="tempat_lahir" maxlength="100"></label>
                <label>Tanggal lahir<input type="date" name="tanggal_lahir" max="{{ now()->toDateString() }}"></label>
                <label>Nomor HP<input name="no_hp" maxlength="16"></label>
                <label>Pendidikan terakhir<input name="pendidikan" maxlength="30" value="Sarjana (S1/D4)"></label>
                <label>Jabatan<input name="jabatan" maxlength="30" value="Guru"></label>
                <label>Tanggal mulai bertugas<input type="date" name="tanggal_mulai" value="{{ now()->toDateString() }}"></label>
                <label>Akun login guru (opsional)
                    <select name="id_user">
                        <option value="">— Belum dihubungkan —</option>
                        @foreach ($actions['teacherUsers'] ?? [] as $teacherUser)
                            <option value="{{ $teacherUser->id_user }}">{{ $teacherUser->name }} · {{ $teacherUser->email }}</option>
                        @endforeach
                    </select>
                </label>
                <label class="database-form-wide">Alamat<textarea name="alamat" rows="2" maxlength="2000"></textarea></label>
                <div class="database-form-wide"><button class="database-button" type="submit">Simpan guru baru</button></div>
            </form>
        </details>

        @if (($actions['teachers'] ?? collect())->isNotEmpty() && ($actions['activeClasses'] ?? collect())->isNotEmpty())
            <h3 class="database-subheading">Penugasan guru ke kelas aktif</h3>
            <form method="POST" action="{{ route('database.teacher.assign-class') }}" class="database-form-grid">
                @csrf
                <label>Guru
                    <select name="id_guru" required>
                        @foreach ($actions['teachers'] as $teacher)
                            <option value="{{ $teacher->id_guru }}">{{ $teacher->nama_lengkap }}{{ !empty($teacher->gelar_belakang) ? ', '.$teacher->gelar_belakang : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Kelas
                    <select name="id_kelas" required>
                        @foreach ($actions['activeClasses'] as $activeClass)
                            <option value="{{ $activeClass->id_kelas }}">{{ $activeClass->nama_kelas }} · {{ $activeClass->kelompok }} ({{ $activeClass->tahun_ajaran }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Peran
                    <select name="peran" required>
                        <option value="Wali Kelas">Wali Kelas</option>
                        <option value="Pendamping">Pendamping</option>
                    </select>
                </label>
                <div class="database-form-wide"><button class="database-button" type="submit">Simpan penugasan</button></div>
            </form>
        @endif

        <h3 class="database-subheading">Perbarui profil guru</h3>
        @forelse ($actions['teachers'] ?? [] as $teacher)
            <details class="database-student-editor">
                <summary>{{ $teacher->nama_lengkap }}{{ !empty($teacher->gelar_belakang) ? ', '.$teacher->gelar_belakang : '' }} <small>{{ $teacher->status ?? 'Aktif' }}</small></summary>
                <form method="POST" action="{{ route('database.teacher.update', ['teacher' => $teacher->id_guru]) }}" class="database-form-grid">
                    @csrf
                    @method('PATCH')
                    <label>Nama lengkap<input name="nama_lengkap" value="{{ $teacher->nama_lengkap }}" maxlength="100" required></label>
                    <label>Gelar<input name="gelar_belakang" value="{{ $teacher->gelar_belakang ?? '' }}" maxlength="50"></label>
                    <label>NUPTK<input name="nuptk" value="{{ $teacher->nuptk ?? '' }}" maxlength="16"></label>
                    <label>NIK<input name="nik" value="{{ $teacher->nik ?? '' }}" maxlength="16" pattern="[0-9]{16}"></label>
                    <label>Jenis kelamin
                        <select name="jenis_kelamin" required>
                            <option value="Perempuan" @selected(($teacher->jenis_kelamin ?? 'Perempuan') === 'Perempuan')>Perempuan</option>
                            <option value="Laki-Laki" @selected(($teacher->jenis_kelamin ?? '') === 'Laki-Laki')>Laki-laki</option>
                        </select>
                    </label>
                    <label>Nomor HP<input name="no_hp" value="{{ $teacher->no_hp ?? '' }}" maxlength="16"></label>
                    <label>Jabatan<input name="jabatan" value="{{ $teacher->jabatan ?? 'Guru' }}" maxlength="30"></label>
                    <label>Pendidikan terakhir<input name="pendidikan" value="{{ $teacher->pendidikan ?? '' }}" maxlength="30"></label>
                    <label>Status
                        <select name="status" required>
                            <option value="Aktif" @selected(($teacher->status ?? 'Aktif') === 'Aktif')>Aktif</option>
                            <option value="Nonaktif" @selected(($teacher->status ?? '') === 'Nonaktif')>Nonaktif</option>
                        </select>
                    </label>
                    <label>Akun login
                        <select name="id_user">
                            <option value="">— Tidak terhubung —</option>
                            @foreach ($actions['teacherUsers'] ?? [] as $teacherUser)
                                <option value="{{ $teacherUser->id_user }}" @selected((int) ($teacher->id_user ?? 0) === (int) $teacherUser->id_user)>{{ $teacherUser->name }} · {{ $teacherUser->email }}</option>
                            @endforeach
                        </select>
                    </label>
                    <label class="database-form-wide">Alamat<textarea name="alamat" rows="2" maxlength="2000">{{ $teacher->alamat ?? '' }}</textarea></label>
                    <div class="database-form-wide"><button class="database-button" type="submit">Simpan perubahan guru</button></div>
                </form>
            </details>
        @empty
            <p class="database-empty-note">Belum ada data guru.</p>
        @endforelse
    </section>
@endif

@if ($section === 'kelas' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div><h2>Kelola ruang kelas &amp; tahun ajaran</h2><p>Atur tahun ajaran aktif, tambah ruang kelas baru, atau sesuaikan kapasitas dan wali kelas.</p></div>
        </div>
        <div class="database-form-grid">
            <form method="POST" action="{{ route('database.class.store') }}" class="database-form-grid">
                @csrf
                <h3 class="database-subheading database-form-wide">Tambah ruang kelas baru</h3>
                <label>Tahun ajaran
                    <select name="id_tahun_ajaran" required>
                        @foreach ($actions['schoolYears'] ?? [] as $year)
                            <option value="{{ $year->id_tahun_ajaran }}" @selected((bool) $year->is_aktif)>{{ $year->nama }}{{ $year->is_aktif ? ' (Aktif)' : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <label>Kelompok
                    <select name="kelompok" required>
                        <option value="KB">KB</option>
                        <option value="TK A">TK A</option>
                        <option value="TK B">TK B</option>
                    </select>
                </label>
                <label>Nama kelas<input name="nama_kelas" maxlength="50" placeholder="TK A Al-Ikhlas" required></label>
                <label>Kapasitas (maks. 30)<input type="number" name="kapasitas" min="1" max="30" value="15" required></label>
                <label class="database-form-wide">Wali kelas
                    <select name="id_wali_kelas">
                        <option value="">— Pilih wali kelas —</option>
                        @foreach ($actions['teachers'] ?? [] as $teacher)
                            <option value="{{ $teacher->id_guru }}">{{ $teacher->nama_lengkap }}{{ !empty($teacher->gelar_belakang) ? ', '.$teacher->gelar_belakang : '' }}</option>
                        @endforeach
                    </select>
                </label>
                <div class="database-form-wide"><button class="database-button" type="submit">Tambah kelas</button></div>
            </form>

            <form method="POST" action="{{ route('database.school-year.store') }}" class="database-form-grid">
                @csrf
                <h3 class="database-subheading database-form-wide">Kelola tahun ajaran</h3>
                <label>Nama tahun ajaran<input name="nama" maxlength="20" placeholder="2026-2027" required></label>
                <label>Tahun mulai<input type="number" name="tahun_mulai" min="2020" max="2099" value="2026" required></label>
                <label>Tanggal mulai<input type="date" name="tanggal_mulai" value="2026-07-13"></label>
                <label>Tanggal selesai<input type="date" name="tanggal_selesai" value="2027-06-25"></label>
                <label class="database-form-wide">Status tahun ajaran
                    <select name="is_aktif" required>
                        <option value="1">Jadikan tahun ajaran aktif</option>
                        <option value="0">Simpan sebagai tidak aktif</option>
                    </select>
                </label>
                <div class="database-form-wide"><button class="database-button database-button-secondary" type="submit">Simpan tahun ajaran</button></div>
            </form>
        </div>

        @if (($actions['managedClasses'] ?? collect())->isNotEmpty())
            <h3 class="database-subheading">Ubah kapasitas &amp; wali kelas</h3>
            @foreach ($actions['managedClasses'] as $managedClass)
                <details class="database-student-editor">
                    <summary>{{ $managedClass->nama_kelas }} · {{ $managedClass->kelompok }} ({{ $managedClass->tahun_ajaran }}) <small>Kapasitas {{ $managedClass->kapasitas ?? '—' }}</small></summary>
                    <form method="POST" action="{{ route('database.class.update', ['schoolClass' => $managedClass->id_kelas]) }}" class="database-form-grid">
                        @csrf
                        @method('PATCH')
                        <label>Nama kelas<input name="nama_kelas" value="{{ $managedClass->nama_kelas }}" maxlength="50" required></label>
                        <label>Kelompok
                            <select name="kelompok" required>
                                @foreach (['KB', 'TK A', 'TK B'] as $kelompokOption)
                                    <option value="{{ $kelompokOption }}" @selected($managedClass->kelompok === $kelompokOption)>{{ $kelompokOption }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Kapasitas<input type="number" name="kapasitas" value="{{ $managedClass->kapasitas ?? 15 }}" min="1" max="30" required></label>
                        <label>Wali kelas
                            <select name="id_wali_kelas">
                                <option value="">— Belum ditentukan —</option>
                                @foreach ($actions['teachers'] ?? [] as $teacher)
                                    <option value="{{ $teacher->id_guru }}" @selected((int) ($managedClass->id_wali_kelas ?? 0) === (int) $teacher->id_guru)>{{ $teacher->nama_lengkap }}{{ !empty($teacher->gelar_belakang) ? ', '.$teacher->gelar_belakang : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                        <div class="database-form-wide"><button class="database-button" type="submit">Simpan kelas</button></div>
                    </form>
                </details>
            @endforeach
        @endif
    </section>
@endif

@if ($section === 'duplikat-siswa' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div><h2>Gabungkan data ganda (deduplikasi)</h2><p>Pindahkan seluruh riwayat rombel, pendaftaran, dan tabungan dari data duplikat ke data utama secara aman dalam satu transaksi.</p></div>
        </div>
        <div class="database-form-grid">
            <form method="POST" action="{{ route('database.student.merge') }}" class="database-form-grid" onsubmit="return confirm('Gabungkan data siswa duplikat ke siswa utama? Data duplikat akan dihapus setelah riwayatnya dipindahkan.');">
                @csrf
                <h3 class="database-subheading database-form-wide">Gabungkan duplikat siswa</h3>
                <label>Siswa utama (dipertahankan)
                    <select name="id_siswa_utama" required>
                        @foreach ($actions['students'] ?? [] as $student)
                            <option value="{{ $student->id_siswa }}">#{{ $student->id_siswa }} · {{ $student->nama_lengkap }} ({{ $student->tanggal_lahir }})</option>
                        @endforeach
                    </select>
                </label>
                <label>Siswa duplikat (digabung &amp; dihapus)
                    <select name="id_siswa_duplikat" required>
                        @foreach ($actions['students'] ?? [] as $student)
                            <option value="{{ $student->id_siswa }}">#{{ $student->id_siswa }} · {{ $student->nama_lengkap }} ({{ $student->tanggal_lahir }})</option>
                        @endforeach
                    </select>
                </label>
                <div class="database-form-wide"><button class="database-button" type="submit">Gabungkan siswa</button></div>
            </form>

            <form method="POST" action="{{ route('database.family.merge') }}" class="database-form-grid" onsubmit="return confirm('Gabungkan keluarga duplikat ke keluarga utama?');">
                @csrf
                <h3 class="database-subheading database-form-wide">Gabungkan duplikat keluarga</h3>
                <label>Keluarga utama (dipertahankan)
                    <select name="id_keluarga_utama" required>
                        @foreach ($actions['families'] ?? [] as $family)
                            <option value="{{ $family->id_keluarga }}">Keluarga #{{ $family->id_keluarga }} · {{ $family->wali_names ?: 'Tanpa nama wali' }} ({{ $family->jumlah_siswa }} siswa)</option>
                        @endforeach
                    </select>
                </label>
                <label>Keluarga duplikat (digabung &amp; dihapus)
                    <select name="id_keluarga_duplikat" required>
                        @foreach ($actions['families'] ?? [] as $family)
                            <option value="{{ $family->id_keluarga }}">Keluarga #{{ $family->id_keluarga }} · {{ $family->wali_names ?: 'Tanpa nama wali' }} ({{ $family->jumlah_siswa }} siswa)</option>
                        @endforeach
                    </select>
                </label>
                <div class="database-form-wide"><button class="database-button database-button-secondary" type="submit">Gabungkan keluarga</button></div>
            </form>
        </div>
    </section>
@endif

@if ($section === 'pendaftaran' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div>
                <h2>Pendaftaran umum</h2>
                <p>Status saat ini: <strong>{{ ($actions['registrationIsOpen'] ?? false) ? 'Dibuka' : 'Ditutup' }}</strong>. Pengaturan ini mengontrol formulir pendaftaran orang tua/wali.</p>
            </div>
        </div>
        <form method="POST" action="{{ route('database.registration.settings.update') }}" class="database-inline-form">
            @csrf
            @method('PATCH')
            <input type="hidden" name="is_open" value="{{ ($actions['registrationIsOpen'] ?? false) ? '0' : '1' }}">
            <button class="database-button" type="submit">
                {{ ($actions['registrationIsOpen'] ?? false) ? 'Tutup pendaftaran' : 'Buka pendaftaran' }}
            </button>
        </form>
    </section>

    @php
        $allApplications = collect($actions['applications'] ?? []);
        $pendingApplications = $allApplications->where('status', 'Diajukan')->values();
        $verifiedApplications = $allApplications->where('status', 'Diverifikasi')->values();
        $documentsByRegistration = $actions['documentsByRegistration'] ?? collect();
        $activeTeachers = collect($actions['teachers'] ?? []);
    @endphp

    <section class="database-action-panel">
        <div class="database-section-heading">
            <div>
                <h2>Verifikasi Berkas Pendaftaran</h2>
                <p>Klik ikon dokumen untuk melihat berkas calon siswa, lalu klik <strong>Verifikasi</strong> atau <strong>Tolak</strong> langsung.</p>
            </div>
            <span class="database-result-count">{{ $pendingApplications->count() }} menunggu verifikasi</span>
        </div>

        @if ($pendingApplications->isNotEmpty())
            <div class="database-table-wrap">
                <table class="database-table reg-doc-table">
                    <thead>
                        <tr>
                            <th>Calon Siswa</th>
                            <th>Berkas-berkas (Klik Logo untuk Lihat Dokumen)</th>
                            <th>Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($pendingApplications as $application)
                            @php
                                $appDocs = $documentsByRegistration->get($application->id_pendaftaran, collect());
                            @endphp
                            <tr>
                                <td>
                                    <div class="reg-student-cell">
                                        <strong>{{ $application->nama_lengkap }}</strong>
                                        <small>{{ $application->no_pendaftaran }} · {{ $application->kelompok }} · {{ $application->tahun_ajaran }}</small>
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
                                </td>
                                <td>
                                    <div class="reg-doc-chips">
                                        @forelse ($appDocs as $doc)
                                            @php
                                                $shortDoc = match ($doc->jenis) {
                                                    'Formulir Pendaftaran' => 'Formulir',
                                                    'Akta Kelahiran' => 'Akta',
                                                    'Kartu Keluarga' => 'KK',
                                                    'KTP Ibu' => 'KTP Ibu',
                                                    'KTP Bapak' => 'KTP Bapak',
                                                    default => $doc->jenis,
                                                };
                                                $docStateClass = match ($doc->status_verifikasi) {
                                                    'Valid' => 'is-valid',
                                                    'Tidak Valid' => 'is-invalid',
                                                    default => 'is-pending',
                                                };
                                                $docExt = strtolower(pathinfo((string) ($doc->file_path ?? ''), PATHINFO_EXTENSION));
                                                $docIsImage = in_array($docExt, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true);
                                                $docUrl = route('database.registration.document.file', ['document' => $doc->id_dokumen]);
                                            @endphp
                                            <a
                                                class="reg-doc-chip-btn {{ $docStateClass }}"
                                                href="{{ $docUrl }}"
                                                data-doc-preview
                                                data-doc-name="{{ $application->nama_lengkap }} · {{ $doc->jenis }}"
                                                data-doc-ext="{{ $docExt }}"
                                                target="_blank"
                                                rel="noopener"
                                                title="{{ $doc->file_size_bytes === null ? 'File tidak ditemukan di server' : 'Preview '.$doc->jenis.' ('.$doc->status_verifikasi.')' }}"
                                            >
                                                @if ($docIsImage && $doc->file_size_bytes !== null)
                                                    <img class="reg-doc-thumb" src="{{ $docUrl }}" alt="" loading="lazy">
                                                @else
                                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                                        <polyline points="14 2 14 8 20 8"/>
                                                        <line x1="9" y1="15" x2="15" y2="15"/>
                                                    </svg>
                                                @endif
                                                <span>{{ $shortDoc }}</span>
                                            </a>
                                        @empty
                                            <span class="database-empty-note">Belum ada berkas.</span>
                                        @endforelse
                                    </div>
                                </td>
                                <td>
                                    <form method="POST" action="{{ route('database.registration.review', ['registration' => $application->id_pendaftaran]) }}" class="reg-verify-form">
                                        @csrf
                                        <input name="note" maxlength="255" placeholder="Catatan (opsional)" aria-label="Catatan verifikasi">
                                        <div class="reg-action-buttons">
                                            <button class="database-button" type="submit" name="decision" value="verify">Verifikasi</button>
                                            <button class="database-button database-button-danger" type="submit" name="decision" value="reject">Tolak</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="database-empty-note">Tidak ada berkas pendaftaran baru yang menunggu verifikasi.</p>
        @endif
    </section>

    @if ($verifiedApplications->isNotEmpty())
        <section class="database-action-panel">
            <div class="database-section-heading">
                <div>
                    <h2>Penetapan Kelas &amp; Guru</h2>
                    <p>Calon siswa yang sudah diverifikasi siap ditetapkan ke kelas dan wali kelasnya.</p>
                </div>
                <span class="database-result-count">{{ $verifiedApplications->count() }} siap ditetapkan</span>
            </div>

            @foreach ($verifiedApplications as $application)
                @php
                    $eligibleClasses = $classOptions->filter(fn ($class) => $class->kelompok === $application->kelompok
                        && $class->tahun_ajaran === $application->tahun_ajaran
                        && $class->nama_kelas !== 'Belum Ditentukan');
                @endphp
                <form method="POST" action="{{ route('database.registration.review', ['registration' => $application->id_pendaftaran]) }}" class="database-action-form database-registration-review reg-placement-card">
                    @csrf
                    <div>
                        <strong>{{ $application->nama_lengkap }}</strong>
                        <small>{{ $application->no_pendaftaran }} · {{ $application->kelompok }} · {{ $application->tahun_ajaran }} · Berkas Terverifikasi</small>
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
                    <div class="database-registration-controls">
                        <label>Pilih Kelas
                            <select name="class_id" aria-label="Pilih kelas">
                                <option value="">— Pilih kelas —</option>
                                @foreach ($eligibleClasses as $class)
                                    <option value="{{ $class->id_kelas }}">{{ $class->nama_kelas }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Pilih Guru (Wali Kelas)
                            <select name="teacher_id" aria-label="Pilih guru">
                                <option value="">— Gunakan wali kelas saat ini —</option>
                                @foreach ($activeTeachers as $teacher)
                                    <option value="{{ $teacher->id_guru }}">{{ $teacher->nama_lengkap }}{{ $teacher->gelar_belakang ? ', '.$teacher->gelar_belakang : '' }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label>Jatuh tempo biaya (opsional)
                            <input type="date" name="due_date" aria-label="Jatuh tempo biaya pendaftaran">
                        </label>
                        <label>Catatan (opsional)
                            <input name="note" maxlength="255" placeholder="Catatan penetapan">
                        </label>
                        <div class="reg-action-buttons database-form-wide">
                            <button class="database-button" type="submit" name="decision" value="accept">Tetapkan</button>
                            <button class="database-button database-button-danger" type="submit" name="decision" value="reject">Tidak Tetapkan</button>
                        </div>
                    </div>
                </form>
            @endforeach
        </section>
    @endif
@endif

@if ($section === 'absensi' && in_array($role, ['teacher', 'admin'], true))
    @php
        $selectableClasses = ($actions['attendanceClasses'] ?? $classOptions)->values();
        $academicYears = ($actions['attendanceAcademicYears'] ?? collect())->values();
        $selectedAcademicYear = $actions['selectedAcademicYear'] ?? ($selectableClasses->first()->tahun_ajaran ?? null);
        $currentAttendanceDate = $actions['attendanceDate'] ?? now()->toDateString();
        $selectedClassId = (int) ($actions['selectedClass'] ?? 0);
    @endphp
    <section class="database-action-panel">
        <div class="database-section-heading">
            <div>
                <h2>Catat kehadiran</h2>
                <p>Pilih tahun ajaran dan klik kotak kelas di bawah untuk memilih kelas, lalu klik tombol bulat status kehadiran pada masing-masing siswa.</p>
            </div>
        </div>

        @if ($selectableClasses->isNotEmpty())
            <div class="attendance-class-picker">
                @if ($academicYears->isNotEmpty())
                    <form method="GET" action="{{ route('dashboard.feature', ['section' => 'absensi']) }}" class="attendance-year-filter">
                        <input type="hidden" name="tanggal" value="{{ $currentAttendanceDate }}">
                        <label class="attendance-year-select-label">
                            <span>Pilih Tahun Ajaran</span>
                            <select name="tahun_ajaran" class="attendance-year-select" onchange="this.form.submit()">
                                @foreach ($academicYears as $yearOption)
                                    <option value="{{ $yearOption->tahun_ajaran }}" @selected($selectedAcademicYear === $yearOption->tahun_ajaran)>
                                        T.A. {{ $yearOption->tahun_ajaran }}{{ $yearOption->tahun_ajaran_aktif ? ' (Aktif)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </form>
                @endif

                <span class="attendance-picker-label">Pilih Kelas</span>
                <div class="attendance-class-grid" role="list" aria-label="Daftar kelas absensi {{ $selectedAcademicYear }}">
                    @foreach ($selectableClasses as $class)
                        @php
                            $isSelectedClass = $selectedClassId === (int) $class->id_kelas;
                        @endphp
                        <a
                            href="{{ route('dashboard.feature', ['section' => 'absensi', 'tahun_ajaran' => $selectedAcademicYear, 'kelas' => $class->id_kelas, 'tanggal' => $currentAttendanceDate]) }}"
                            class="attendance-class-card{{ $isSelectedClass ? ' is-selected' : '' }}"
                            @if ($isSelectedClass) aria-current="page" @endif
                        >
                            <div class="attendance-class-top">
                                <span class="attendance-class-badge">{{ $class->kelompok }}</span>
                                <span class="attendance-class-year">{{ $class->tahun_ajaran }}</span>
                            </div>
                            <strong>{{ $class->nama_kelas }}</strong>
                            <small>{{ $isSelectedClass ? 'Kelas aktif dipilih' : 'Klik untuk buka absensi' }}</small>
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="attendance-toolbar">
                <form method="GET" action="{{ route('dashboard.feature', ['section' => 'absensi']) }}" class="attendance-date-form" data-attendance-date-form>
                    <input type="hidden" name="tahun_ajaran" value="{{ $selectedAcademicYear }}">
                    <input type="hidden" name="kelas" value="{{ $selectedClassId }}">
                    <label class="attendance-date-label">
                        <span>Tanggal Absensi</span>
                        <input
                            type="date"
                            name="tanggal"
                            value="{{ $currentAttendanceDate }}"
                            max="{{ now()->toDateString() }}"
                            required
                            data-attendance-date-input
                        >
                    </label>
                    <button class="database-button database-button-secondary" type="submit">Tampilkan tanggal</button>
                </form>

                <div class="attendance-quick-actions">
                    <span class="attendance-picker-label">Tandai Semua</span>
                    <div class="attendance-quick-buttons">
                        @foreach (['Hadir' => 'hadir', 'Izin' => 'izin', 'Sakit' => 'sakit', 'Alfa' => 'alfa'] as $statusLabel => $statusSlug)
                            <button
                                type="button"
                                class="attendance-quick-chip is-{{ $statusSlug }}"
                                data-attendance-select-all="{{ $statusLabel }}"
                            >
                                <span class="attendance-radio-dot"></span>
                                Semua {{ $statusLabel }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif

        @if (($actions['attendanceRoster'] ?? collect())->isNotEmpty())
            <form method="POST" action="{{ route('database.attendance.store') }}" data-attendance-form>
                @csrf
                <input type="hidden" name="id_kelas" value="{{ $actions['selectedClass'] }}">
                <input type="hidden" name="tanggal" value="{{ $currentAttendanceDate }}">

                <div class="database-attendance-header">
                    <span>Nama Siswa</span>
                    <span>Pilih Status Kehadiran</span>
                    <span>Keterangan</span>
                </div>

                <div class="database-attendance-list">
                    @foreach ($actions['attendanceRoster'] as $index => $student)
                        @php
                            $currentStatus = $student->status_absensi;
                        @endphp
                        <div class="database-attendance-row">
                            <div class="attendance-student-name">
                                <span class="attendance-student-number">{{ $index + 1 }}</span>
                                <strong>{{ $student->nama_lengkap }}</strong>
                            </div>

                            <div class="attendance-status-group" role="radiogroup" aria-label="Status {{ $student->nama_lengkap }}">
                                @foreach (['Hadir' => 'hadir', 'Izin' => 'izin', 'Sakit' => 'sakit', 'Alfa' => 'alfa'] as $status => $slug)
                                    <label class="attendance-status-option is-{{ $slug }}">
                                        <input
                                            type="radio"
                                            name="attendance[{{ $student->id_rombel }}][status]"
                                            value="{{ $status }}"
                                            @checked($currentStatus === $status)
                                            required
                                        >
                                        <span class="attendance-radio-circle" aria-hidden="true"></span>
                                        <span class="attendance-status-text">{{ $status }}</span>
                                    </label>
                                @endforeach
                            </div>

                            <input
                                class="attendance-note-input"
                                name="attendance[{{ $student->id_rombel }}][keterangan]"
                                value="{{ $student->keterangan }}"
                                maxlength="255"
                                placeholder="Keterangan (opsional)"
                            >
                        </div>
                    @endforeach
                </div>

                <div class="attendance-submit-bar">
                    <button class="database-button" type="submit">Simpan absensi</button>
                </div>
            </form>
        @elseif ($selectableClasses->isEmpty())
            <p class="database-empty-note">Belum ada kelas aktif yang ditugaskan.</p>
        @else
            <p class="database-empty-note">Belum ada siswa aktif pada kelas yang dipilih.</p>
        @endif
    </section>
@endif

@if ($section === 'absensi' && $role === 'parent')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Riwayat kehadiran harian anak</h2><p>Catatan kehadiran harian per tanggal yang dicatat oleh wali kelas.</p></div></div>
        @forelse ($actions['parentAttendanceHistory'] ?? [] as $history)
            <div class="database-action-row">
                <div>
                    <strong>{{ $history->nama_lengkap }} · {{ $history->tanggal }}</strong>
                    <small>{{ $history->nama_kelas }}{{ $history->keterangan ? ' · '.$history->keterangan : '' }}</small>
                </div>
                <span class="database-status">{{ $history->status }}</span>
            </div>
        @empty
            <p class="database-empty-note">Belum ada riwayat kehadiran harian anak yang tercatat.</p>
        @endforelse
    </section>
@endif

@if ($section === 'pembelajaran' && in_array($role, ['teacher', 'admin'], true))
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Buat rencana pembelajaran</h2><p>Simpan jenis rencana (RPPH/RPPM/Modul Ajar), tema, tujuan, kegiatan, serta alat dan bahan.</p></div></div>
        <form method="POST" action="{{ route('database.lesson-plan.store') }}" class="database-form-grid">
            @csrf
            <label>Kelas<select name="id_kelas" required>@foreach ($classOptions as $class)<option value="{{ $class->id_kelas }}">{{ $class->nama_kelas }} · {{ $class->kelompok }}</option>@endforeach</select></label>
            <label>Jenis dokumen<select name="jenis"><option value="RPPH">RPPH</option><option value="RPPM">RPPM</option><option value="Modul Ajar">Modul Ajar</option></select></label>
            <label>Tanggal mulai<input type="date" name="tanggal" value="{{ now()->toDateString() }}" required></label>
            <label>Tanggal selesai (opsional)<input type="date" name="tanggal_selesai"></label>
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

@if ($section === 'tugas' && in_array($role, ['teacher', 'admin'], true))
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Buat tugas kelas</h2><p>Buat tugas untuk kelas TK A atau TK B dan beri nilai beserta umpan balik atas pengumpulan siswa.</p></div></div>
        <form method="POST" action="{{ route('database.task.store') }}" enctype="multipart/form-data" class="database-form-grid">
            @csrf
            <label>Kelas (TK A / TK B)
                <select name="id_kelas" required>
                    @foreach ($classOptions->filter(fn ($c) => ($c->kelompok ?? '') !== 'KB') as $class)
                        <option value="{{ $class->id_kelas }}">{{ $class->nama_kelas }} · {{ $class->kelompok }}</option>
                    @endforeach
                </select>
            </label>
            <label>Judul tugas<input name="judul" maxlength="150" required></label>
            <label>Tanggal diberikan<input type="date" name="tanggal_diberikan" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
            <label>Tenggat<input type="date" name="tenggat" required></label>
            <label class="database-form-wide">Deskripsi<textarea name="deskripsi" rows="3"></textarea></label>
            <label class="database-form-wide">Lampiran tugas (opsional, maks. 5 MB)<input type="file" name="lampiran" data-max-mb="5"></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Buat tugas</button></div>
        </form>

        <h3 class="database-subheading">Pengumpulan untuk dinilai</h3>
        <p class="database-empty-note" style="margin: 0 0 10px;"><strong>Keterangan:</strong> BSB = Berkembang Sangat Baik · BSH = Berkembang Sesuai Harapan · MB = Mulai Berkembang · BB = Belum Berkembang</p>
        <div class="submission-card-list">
            @forelse ($actions['submissions'] ?? [] as $submission)
                <form method="POST" action="{{ route('database.task.grade', ['submission' => $submission->id_pengumpulan]) }}" class="submission-card">
                    @csrf @method('PATCH')
                    <div class="submission-card-header">
                        <div class="submission-card-meta">
                            <strong>{{ $submission->nama_lengkap }}</strong>
                            <span>{{ $submission->judul }} · <time>{{ $submission->dikumpulkan_pada }}</time></span>
                        </div>
                        <div class="submission-card-badges">
                            <span class="database-badge {{ $submission->status === 'Dinilai' ? 'is-success' : 'is-warning' }}">{{ $submission->status }}</span>
                            @if ($submission->file_path)
                                <a class="database-button database-button-secondary" href="{{ route('database.task.submission.file', ['submission' => $submission->id_pengumpulan]) }}" target="_blank" rel="noopener">Lihat file</a>
                            @endif
                        </div>
                    </div>
                    <div class="submission-card-controls">
                        <label class="submission-grade-field">
                            <span>Nilai</span>
                            <select name="nilai" required aria-label="Nilai tugas">
                                @foreach (['BB' => 'Belum Berkembang', 'MB' => 'Mulai Berkembang', 'BSH' => 'Berkembang Sesuai Harapan', 'BSB' => 'Berkembang Sangat Baik'] as $grade => $gradeLabel)
                                    <option value="{{ $grade }}" @selected($submission->nilai === $grade)>{{ $grade }} = {{ $gradeLabel }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="submission-feedback-field">
                            <span>Umpan balik guru</span>
                            <input name="umpan_balik" value="{{ $submission->umpan_balik }}" placeholder="Tulis umpan balik untuk siswa...">
                        </label>
                        <button class="database-button" type="submit">Simpan nilai</button>
                    </div>
                </form>
            @empty
                <p class="database-empty-note">Tidak ada pengumpulan tugas yang menunggu penilaian.</p>
            @endforelse
        </div>

        @php
            $gradeLabels = [
                'BB' => 'Belum Berkembang',
                'MB' => 'Mulai Berkembang',
                'BSH' => 'Berkembang Sesuai Harapan',
                'BSB' => 'Berkembang Sangat Baik',
            ];
        @endphp
        <h3 class="database-subheading" style="margin-top: 22px;">Sudah dinilai</h3>
        @if (($actions['gradedSubmissions'] ?? collect())->isNotEmpty())
            <div class="database-table-wrap">
                <table class="database-table">
                    <thead>
                        <tr>
                            <th>Nama siswa</th>
                            <th>Tugas</th>
                            <th>Waktu pengumpulan</th>
                            <th>Nilai</th>
                            <th>Umpan balik guru</th>
                            <th>File</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($actions['gradedSubmissions'] as $submission)
                            <tr>
                                <td><strong>{{ $submission->nama_lengkap }}</strong></td>
                                <td>{{ $submission->judul }}</td>
                                <td>{{ $submission->dikumpulkan_pada }}</td>
                                <td>
                                    <span class="database-badge is-success">
                                        {{ $submission->nilai }}{{ isset($gradeLabels[$submission->nilai]) ? ' — '.$gradeLabels[$submission->nilai] : '' }}
                                    </span>
                                </td>
                                <td>{{ $submission->umpan_balik ?: '—' }}</td>
                                <td>
                                    @if ($submission->file_path)
                                        <a class="database-button database-button-secondary" href="{{ route('database.task.submission.file', ['submission' => $submission->id_pengumpulan]) }}" target="_blank" rel="noopener">Lihat file</a>
                                    @else
                                        —
                                    @endif
                                </td>
                                <td>
                                    <details class="database-student-editor" style="border-top: 0; padding: 0;">
                                        <summary class="database-button database-button-secondary" style="display: inline-flex; list-style: none;">Edit nilai</summary>
                                        <form method="POST" action="{{ route('database.task.grade', ['submission' => $submission->id_pengumpulan]) }}" class="database-inline-form" style="margin-top: 8px;">
                                            @csrf @method('PATCH')
                                            <select name="nilai" required aria-label="Edit nilai tugas">
                                                @foreach ($gradeLabels as $grade => $gradeLabel)
                                                    <option value="{{ $grade }}" @selected($submission->nilai === $grade)>{{ $grade }} = {{ $gradeLabel }}</option>
                                                @endforeach
                                            </select>
                                            <input name="umpan_balik" value="{{ $submission->umpan_balik }}" placeholder="Tulis umpan balik untuk siswa...">
                                            <button class="database-button" type="submit">Simpan</button>
                                        </form>
                                    </details>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="database-empty-note">Belum ada tugas yang selesai dinilai.</p>
        @endif
    </section>
@endif

@if ($section === 'tugas' && $role === 'parent')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Kumpulkan tugas anak</h2><p>Pengumpulan hanya dapat dilakukan untuk anak dan kelas yang terhubung ke akun ini.</p></div></div>
        <div class="submission-card-list">
            @forelse ($actions['parentTasks'] ?? [] as $task)
                <form method="POST" action="{{ route('database.task.submit', ['task' => $task->id_tugas]) }}" enctype="multipart/form-data" class="submission-card">
                    @csrf
                    <input type="hidden" name="id_siswa" value="{{ $task->id_siswa }}">
                    <div class="submission-card-header">
                        <div class="submission-card-meta">
                            <strong>{{ $task->nama_lengkap }} · {{ $task->judul }}</strong>
                            <span>Tenggat: {{ $task->tenggat }}</span>
                        </div>
                        <div class="submission-card-badges">
                            <span class="database-badge {{ $task->dikumpulkan_pada ? 'is-success' : 'is-warning' }}">{{ $task->dikumpulkan_pada ? 'Sudah dikumpulkan' : 'Belum dikumpulkan' }}</span>
                            @if ($task->has_attachment)
                                <a class="database-button database-button-secondary" href="{{ route('database.task.attachment', ['task' => $task->id_tugas]) }}" target="_blank" rel="noopener">Lihat lampiran</a>
                            @endif
                            @if ($task->submission_file)
                                <a class="database-button database-button-secondary" href="{{ route('database.task.submission.file', ['submission' => $task->id_pengumpulan]) }}" target="_blank" rel="noopener">Lihat pengumpulan</a>
                            @endif
                        </div>
                    </div>
                    <div class="submission-card-controls is-parent-submit">
                        <label class="submission-file-field">
                            <span>File tugas (maks. 5 MB)</span>
                            <input type="file" name="file" aria-label="File tugas" data-max-mb="5">
                        </label>
                        <label class="submission-feedback-field">
                            <span>Catatan untuk guru</span>
                            <input name="catatan_ortu" maxlength="255" placeholder="Catatan opsional...">
                        </label>
                        <button class="database-button" type="submit">Kirim</button>
                    </div>
                </form>
            @empty
                <p class="database-empty-note">Tidak ada tugas yang belum dinilai pada kelas anak yang terhubung.</p>
            @endforelse
        </div>

        @php
            $parentGradeLabels = [
                'BB' => 'Belum Berkembang',
                'MB' => 'Mulai Berkembang',
                'BSH' => 'Berkembang Sesuai Harapan',
                'BSB' => 'Berkembang Sangat Baik',
            ];
        @endphp
        <h3 class="database-subheading" style="margin-top: 22px;">Sudah dinilai</h3>
        @if (($actions['parentGradedTasks'] ?? collect())->isNotEmpty())
            <div class="database-table-wrap">
                <table class="database-table">
                    <thead>
                        <tr>
                            <th>Nama siswa</th>
                            <th>Tugas</th>
                            <th>Tenggat</th>
                            <th>Nilai</th>
                            <th>Umpan balik guru</th>
                            <th>File</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($actions['parentGradedTasks'] as $task)
                            <tr>
                                <td><strong>{{ $task->nama_lengkap }}</strong></td>
                                <td>{{ $task->judul }}</td>
                                <td>{{ $task->tenggat }}</td>
                                <td>
                                    <span class="database-badge is-success">
                                        {{ $task->nilai }}{{ isset($parentGradeLabels[$task->nilai]) ? ' — '.$parentGradeLabels[$task->nilai] : '' }}
                                    </span>
                                </td>
                                <td>{{ $task->umpan_balik ?: '—' }}</td>
                                <td>
                                    @if ($task->submission_file)
                                        <a class="database-button database-button-secondary" href="{{ route('database.task.submission.file', ['submission' => $task->id_pengumpulan]) }}" target="_blank" rel="noopener">Lihat pengumpulan</a>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p class="database-empty-note">Belum ada tugas yang selesai dinilai.</p>
        @endif
    </section>
@endif

@if ($section === 'nilai' && in_array($role, ['teacher', 'admin'], true))
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Catat perkembangan Iqro</h2><p>Indikator disesuaikan otomatis dengan kelompok kelas siswa (KB, TK A, atau TK B).</p></div></div>
        <form method="POST" action="{{ route('database.iqro.store') }}" class="database-form-grid">
            @csrf
            <label>Siswa / kelas
                <select name="id_rombel" required data-iqro-roster-select>
                    @foreach ($rosterOptions as $roster)
                        <option value="{{ $roster->id_rombel }}" data-kelompok="{{ $roster->kelompok }}">{{ $roster->nama_lengkap }} · {{ $roster->nama_kelas }} ({{ $roster->kelompok }})</option>
                    @endforeach
                </select>
            </label>
            <label>Indikator
                <select name="id_indikator" required data-iqro-indicator-select>
                    @foreach ($actions['indicators'] ?? [] as $indicator)
                        <option value="{{ $indicator->id_indikator }}" data-kelompok="{{ $indicator->kelompok }}">{{ $indicator->kelompok }} · {{ $indicator->kategori }} · {{ $indicator->nama }}</option>
                    @endforeach
                </select>
            </label>
            <label>Semester<select name="semester"><option>Ganjil</option><option>Genap</option></select></label>
            <label>Capaian<select name="skala"><option value="BB">BB = Belum Berkembang</option><option value="MB">MB = Mulai Berkembang</option><option value="BSH" selected>BSH = Berkembang Sesuai Harapan</option><option value="BSB">BSB = Berkembang Sangat Baik</option></select></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Simpan nilai</button></div>
        </form>
    </section>
@endif

@if ($section === 'perkembangan' && in_array($role, ['teacher', 'admin'], true))
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Tambah portofolio perkembangan</h2><p>Unggah foto dan catatan perkembangan siswa pada kelas yang diampu.</p></div></div>
        <form method="POST" action="{{ route('database.portfolio.store') }}" enctype="multipart/form-data" class="database-form-grid">
            @csrf
            <label>Siswa / kelas<select name="id_rombel" required>@foreach ($rosterOptions as $roster)<option value="{{ $roster->id_rombel }}">{{ $roster->nama_lengkap }} · {{ $roster->nama_kelas }}</option>@endforeach</select></label>
            <label>Tanggal<input type="date" name="tanggal" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></label>
            <label>Foto (JPG/PNG/WEBP, maks. 5 MB)<input type="file" name="foto" accept="image/jpeg,image/png,image/webp" required data-max-mb="5"></label>
            <label class="database-form-wide">Narasi<textarea name="narasi" rows="3" maxlength="5000" required></textarea></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Simpan portofolio</button></div>
        </form>
    </section>
@endif

@if ($section === 'rapor' && in_array($role, ['teacher', 'admin'], true))
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Susun rapor</h2><p>Rapor final terkunci sesuai aturan database; semua 4 narasi perkembangan wajib terisi sebelum rapor difinalkan.</p></div></div>
        <form method="POST" action="{{ route('database.report.store') }}" class="database-form-grid">
            @csrf
            <label>Siswa / kelas<select name="id_rombel" required>@foreach ($rosterOptions as $roster)<option value="{{ $roster->id_rombel }}">{{ $roster->nama_lengkap }} · {{ $roster->nama_kelas }}</option>@endforeach</select></label>
            <label>Semester<select name="semester"><option>Ganjil</option><option>Genap</option></select></label>
            <label>Tanggal rapor<input type="date" name="tanggal_rapor" value="{{ now()->toDateString() }}"></label>
            <label>Status<select name="status"><option value="Final" selected>Final</option></select></label>
            <label>Tinggi badan (cm)<input type="number" step="0.1" name="tinggi_badan" min="30" max="180"></label>
            <label>Berat badan (kg)<input type="number" step="0.1" name="berat_badan" min="5" max="120"></label>
            @if (($actions['iqroTeachers'] ?? collect())->isNotEmpty())
                <label class="database-form-wide">Guru pendamping Iqro
                    <select name="id_guru_iqro">
                        <option value="">— Sesuai wali kelas —</option>
                        @foreach ($actions['iqroTeachers'] as $iqroTeacher)
                            <option value="{{ $iqroTeacher->id_guru }}">{{ $iqroTeacher->nama_lengkap }}{{ !empty($iqroTeacher->gelar_belakang) ? ', '.$iqroTeacher->gelar_belakang : '' }}</option>
                        @endforeach
                    </select>
                </label>
            @endif
            <label class="database-form-wide">Agama dan budi pekerti<textarea name="narasi_agama_budi_pekerti" rows="2"></textarea></label>
            <label class="database-form-wide">Jati diri<textarea name="narasi_jati_diri" rows="2"></textarea></label>
            <label class="database-form-wide">Literasi dan STEAM<textarea name="narasi_literasi_steam" rows="2"></textarea></label>
            <label class="database-form-wide">Projek P5<textarea name="narasi_p5" rows="2"></textarea></label>
            <div class="database-form-wide"><button class="database-button" type="submit">Simpan rapor</button></div>
        </form>
    </section>
@endif

@if ($section === 'rapor' && in_array($role, ['teacher', 'principal', 'admin'], true) && ($actions['detailedReports'] ?? collect())->isNotEmpty())
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Detail isi rapor lengkap</h2><p>Buka rincian narasi perkembangan, pertumbuhan fisik, rekap ketidakhadiran, dan refleksi orang tua.</p></div></div>
        @foreach ($actions['detailedReports'] as $detailReport)
            <details class="database-student-editor">
                <summary>{{ $detailReport->nama_lengkap }} · {{ $detailReport->nama_kelas }} ({{ $detailReport->semester }} {{ $detailReport->tahun_ajaran }}) <small>{{ $detailReport->status }}</small></summary>
                <div class="database-registration-details">
                    <dl>
                        <div><dt>Tanggal rapor</dt><dd>{{ $detailReport->tanggal_rapor ?: '—' }}</dd></div>
                        <div><dt>Pertumbuhan fisik</dt><dd>Tinggi: {{ $detailReport->tinggi_badan ? $detailReport->tinggi_badan.' cm' : '—' }} · Berat: {{ $detailReport->berat_badan ? $detailReport->berat_badan.' kg' : '—' }}</dd></div>
                        <div><dt>Ketidakhadiran</dt><dd>Sakit: {{ $detailReport->sakit ?? 0 }} · Izin: {{ $detailReport->izin ?? 0 }} · Tanpa keterangan: {{ $detailReport->tanpa_keterangan ?? $detailReport->alfa ?? 0 }}</dd></div>
                        <div><dt>Wali kelas &amp; Guru Iqro</dt><dd>{{ $detailReport->guru_kelas ?? $detailReport->wali_kelas ?? '—' }}{{ !empty($detailReport->guru_iqro) ? ' / '.$detailReport->guru_iqro : '' }}</dd></div>
                        <div class="database-registration-address"><dt>1. Nilai Agama dan Budi Pekerti</dt><dd>{{ $detailReport->narasi_agama_budi_pekerti ?: 'Belum diisi' }}</dd></div>
                        <div class="database-registration-address"><dt>2. Jati Diri</dt><dd>{{ $detailReport->narasi_jati_diri ?: 'Belum diisi' }}</dd></div>
                        <div class="database-registration-address"><dt>3. Dasar-Dasar Literasi, Matematika, Sains, Teknologi, Rekayasa, dan Seni (STEAM)</dt><dd>{{ $detailReport->narasi_literasi_steam ?: 'Belum diisi' }}</dd></div>
                        <div class="database-registration-address"><dt>4. Projek Penguatan Profil Pelajar Pancasila (P5)</dt><dd>{{ $detailReport->narasi_p5 ?: 'Belum diisi' }}</dd></div>
                        <div class="database-registration-address"><dt>Refleksi Orang Tua</dt><dd>{{ $detailReport->refleksi_orang_tua ?: 'Belum diisi oleh orang tua' }}</dd></div>
                    </dl>
                    @if (in_array($role, ['teacher', 'admin'], true))
                        <details class="database-report-inline-editor" style="margin-top: 12px;">
                            <summary class="database-button database-button-secondary" style="display: flex; width: fit-content; margin-left: auto; list-style: none;">Edit rapor</summary>
                            <form method="POST" action="{{ route('database.report.store') }}" class="database-form-grid" style="margin-top: 12px;">
                                @csrf
                                <input type="hidden" name="id_rombel" value="{{ $detailReport->id_rombel }}">
                                <input type="hidden" name="semester" value="{{ $detailReport->semester }}">
                                <input type="hidden" name="status" value="Final">
                                <label>Tanggal rapor<input type="date" name="tanggal_rapor" value="{{ $detailReport->tanggal_rapor ?: now()->toDateString() }}"></label>
                                <label>Tinggi badan (cm)<input type="number" step="0.1" name="tinggi_badan" value="{{ $detailReport->tinggi_badan }}" min="30" max="180"></label>
                                <label>Berat badan (kg)<input type="number" step="0.1" name="berat_badan" value="{{ $detailReport->berat_badan }}" min="5" max="120"></label>
                                <label class="database-form-wide">Agama dan budi pekerti<textarea name="narasi_agama_budi_pekerti" rows="2" required>{{ $detailReport->narasi_agama_budi_pekerti }}</textarea></label>
                                <label class="database-form-wide">Jati diri<textarea name="narasi_jati_diri" rows="2" required>{{ $detailReport->narasi_jati_diri }}</textarea></label>
                                <label class="database-form-wide">Literasi dan STEAM<textarea name="narasi_literasi_steam" rows="2" required>{{ $detailReport->narasi_literasi_steam }}</textarea></label>
                                <label class="database-form-wide">Projek P5<textarea name="narasi_p5" rows="2" required>{{ $detailReport->narasi_p5 }}</textarea></label>
                                <div class="database-form-wide" style="display: flex; justify-content: flex-end;">
                                    <button class="database-button" type="submit">Simpan perubahan</button>
                                </div>
                            </form>
                        </details>
                    @endif
                </div>
            </details>
        @endforeach
    </section>
@endif

@if ($section === 'rapor' && $role === 'parent')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Rapor perkembangan &amp; refleksi orang tua</h2><p>Lihat laporan capaian pembelajaran Kurikulum Merdeka anak Anda lalu tuliskan refleksi orang tua.</p></div></div>
        @forelse ($actions['parentReports'] ?? [] as $report)
            <div class="database-student-editor">
                <div class="database-registration-details">
                    <strong>{{ $report->nama_lengkap }} · {{ $report->nama_kelas ?? '' }} · Semester {{ $report->semester }} ({{ $report->tahun_ajaran ?? '' }}) · {{ $report->status }}</strong>
                    <dl>
                        <div><dt>Tanggal rapor</dt><dd>{{ $report->tanggal_rapor ?: '—' }}</dd></div>
                        <div><dt>Tinggi &amp; berat badan</dt><dd>{{ $report->tinggi_badan ? $report->tinggi_badan.' cm' : '—' }} / {{ $report->berat_badan ? $report->berat_badan.' kg' : '—' }}</dd></div>
                        <div><dt>Rekap kehadiran</dt><dd>Sakit: {{ $report->sakit ?? 0 }} hari · Izin: {{ $report->izin ?? 0 }} hari · Tanpa keterangan: {{ $report->tanpa_keterangan ?? $report->alfa ?? 0 }} hari</dd></div>
                        <div><dt>Wali kelas &amp; Kepala sekolah</dt><dd>{{ $report->guru_kelas ?? $report->wali_kelas ?? '—' }}{{ !empty($report->kepala_sekolah) ? ' · Kepsek: '.$report->kepala_sekolah : '' }}</dd></div>
                        <div class="database-registration-address"><dt>Nilai Agama dan Budi Pekerti</dt><dd>{{ $report->narasi_agama_budi_pekerti ?: '—' }}</dd></div>
                        <div class="database-registration-address"><dt>Jati Diri</dt><dd>{{ $report->narasi_jati_diri ?: '—' }}</dd></div>
                        <div class="database-registration-address"><dt>Dasar Literasi &amp; STEAM</dt><dd>{{ $report->narasi_literasi_steam ?: '—' }}</dd></div>
                        <div class="database-registration-address"><dt>Projek Penguatan Profil Pelajar Pancasila (P5)</dt><dd>{{ $report->narasi_p5 ?: '—' }}</dd></div>
                    </dl>
                </div>
                <form method="POST" action="{{ route('database.report.reflection', ['report' => $report->id_rapor]) }}" class="database-action-form">
                    @csrf @method('PATCH')
                    <strong>Refleksi orang tua untuk {{ $report->nama_lengkap }} ({{ $report->semester }})</strong>
                    <textarea name="refleksi_orang_tua" rows="3" maxlength="5000" placeholder="Refleksi orang tua">{{ $report->refleksi_orang_tua }}</textarea>
                    <button class="database-button" type="submit">Simpan refleksi</button>
                </form>
            </div>
        @empty
            <p class="database-empty-note">Rapor anak belum tersedia.</p>
        @endforelse
    </section>
@endif

@if ($section === 'kegiatan' && in_array($role, ['teacher', 'admin'], true))
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Unggah dokumentasi kegiatan</h2><p>Pilih kelas, buat kegiatan, lalu unggah foto dokumentasi. Orang tua yang terhubung dengan kelas tersebut dapat melihat dokumentasinya.</p></div></div>
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
                <input type="file" name="foto[]" aria-label="Pilih foto dokumentasi" accept="image/jpeg,image/png,image/webp" multiple required data-activity-photo-input data-max-files="10" data-max-mb="5">
                <input name="keterangan" maxlength="255" placeholder="Keterangan foto">
                <button class="database-button" type="submit">Upload foto</button>
                <div class="activity-photo-preview-grid" data-activity-photo-previews hidden aria-live="polite"></div>
                <small class="database-empty-note" data-activity-photo-count>Pilih hingga 10 foto sekaligus (maksimal 5 MB per foto).</small>
            </form>
        @endforeach
    </section>
@endif

@if ($section === 'kegiatan' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Biaya dan tagihan Field Trip</h2><p>Tetapkan biaya sebelum menerbitkan tagihan. Jatuh tempo mengikuti tanggal kegiatan; biaya terkunci setelah tagihan pertama dibuat.</p></div></div>
        @forelse ($actions['fieldTrips'] ?? [] as $activity)
            <div class="database-action-row">
                <div>
                    <strong>{{ $activity->nama_kegiatan }}</strong>
                    <small>{{ $activity->tanggal }}{{ $activity->lokasi ? ' · '.$activity->lokasi : '' }} · {{ $activity->tagihan_dibuat }} tagihan diterbitkan</small>
                </div>
                <div class="database-action-buttons">
                    @if ((int) $activity->tagihan_dibuat === 0)
                        <form method="POST" action="{{ route('database.activity.cost.update', ['activity' => $activity->id_kegiatan]) }}" class="database-inline-form">
                            @csrf
                            @method('PATCH')
                            <label>Biaya per siswa (Rp)<input type="number" name="biaya" min="0.01" step="0.01" value="{{ old('biaya', $activity->biaya) }}" required></label>
                            <button class="database-button database-button-secondary" type="submit">Simpan biaya</button>
                        </form>
                    @else
                        <strong>Biaya: Rp{{ number_format((float) $activity->biaya, 0, ',', '.') }}</strong>
                    @endif
                    <form method="POST" action="{{ route('database.activity.bills.generate', ['activity' => $activity->id_kegiatan]) }}">
                        @csrf
                        <button class="database-button" type="submit" @disabled($activity->biaya === null || (float) $activity->biaya <= 0)>Terbitkan tagihan</button>
                    </form>
                </div>
            </div>
        @empty
            <p class="database-empty-note">Belum ada kegiatan Field Trip. Guru dapat membuat kegiatan terlebih dahulu.</p>
        @endforelse
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
            <div class="database-section-heading"><div><h2>Buat tagihan SPP bulanan</h2><p>Tagihan dibuat untuk siswa aktif pada tahun ajaran aktif sesuai tarif database dan potongan diskon siswa; tagihan yang sudah ada tidak digandakan.</p></div></div>
            <form method="POST" action="{{ route('database.bills.generate') }}" class="database-inline-form">
                @csrf
                <label>Periode<input type="month" name="periode" required></label>
                <label>Jatuh tempo<input type="date" name="jatuh_tempo" required></label>
                <button class="database-button" type="submit">Buat tagihan</button>
            </form>

            <div class="database-section-heading database-subsection-heading"><div><h2>Buat tagihan untuk satu siswa</h2><p>Nominal mengikuti tarif bulanan aktif serta diskon siswa yang dipilih.</p></div></div>
            @if (! ($actions['activeSchoolYear'] ?? null))
                <p class="database-empty-note">Tahun ajaran aktif belum tersedia.</p>
            @elseif (($actions['billRosters'] ?? collect())->isEmpty())
                <p class="database-empty-note">Belum ada siswa aktif di kelas pada tahun ajaran ini.</p>
            @elseif (($actions['monthlyTariffs'] ?? collect())->isEmpty())
                <p class="database-empty-note">Tarif bulanan untuk tahun ajaran aktif belum tersedia.</p>
            @else
                <form method="POST" action="{{ route('database.bills.store') }}" class="database-form-grid">
                    @csrf
                    <label>Siswa<select name="id_rombel" required>
                        <option value="">Pilih siswa aktif</option>
                        @foreach ($actions['billRosters'] as $roster)
                            <option value="{{ $roster->id_rombel }}" @selected(old('id_rombel') == $roster->id_rombel)>{{ $roster->nama_lengkap }} · {{ $roster->nama_kelas }} · {{ $roster->kelompok }}</option>
                        @endforeach
                    </select></label>
                    <label>Jenis tarif<select name="id_jenis_biaya" required>
                        <option value="">Pilih tarif bulanan</option>
                        @foreach ($actions['monthlyTariffs'] as $tariff)
                            <option value="{{ $tariff->id_jenis_biaya }}" @selected(old('id_jenis_biaya') == $tariff->id_jenis_biaya)>{{ $tariff->nama }} · {{ $tariff->kelompok }} · Rp{{ number_format($tariff->nominal, 0, ',', '.') }}</option>
                        @endforeach
                    </select></label>
                    <label>Periode<input type="month" name="periode" value="{{ old('periode', now('Asia/Jakarta')->format('Y-m')) }}" required></label>
                    <label>Jatuh tempo<input type="date" name="jatuh_tempo" value="{{ old('jatuh_tempo', now('Asia/Jakarta')->endOfMonth()->toDateString()) }}" required></label>
                    <div class="database-form-wide"><button class="database-button" type="submit">Buat tagihan siswa</button></div>
                </form>
            @endif
        @endif

        @if ($role === 'admin' && $section === 'pembayaran')
            <div class="database-section-heading"><div><h2>Rekening pembayaran</h2><p>Informasi ini ditampilkan kepada orang tua sebelum transfer.</p></div></div>
            <form method="POST" action="{{ route('database.payment-settings.update') }}" class="database-form-grid">
                @csrf
                @method('PATCH')
                <label>Bank<input name="bank_name" value="{{ old('bank_name', $actions['paymentSettings']->bank_name ?? '') }}" maxlength="100" required></label>
                <label>Atas nama<input name="account_holder" value="{{ old('account_holder', $actions['paymentSettings']->account_holder ?? '') }}" maxlength="150" required></label>
                <label>Nomor rekening<input name="account_number" value="{{ old('account_number', $actions['paymentSettings']->account_number ?? '') }}" maxlength="40" required></label>
                <div class="database-form-wide"><button class="database-button" type="submit">Simpan rekening</button></div>
            </form>
            <div class="database-section-heading"><div><h2>Verifikasi pembayaran</h2><p>Periksa bukti transfer sebelum menyetujui transaksi.</p></div></div>
            @forelse ($actions['pendingPayments'] ?? [] as $payment)
                <form method="POST" action="{{ route('database.payment.review', ['payment' => $payment->id_pembayaran]) }}" class="database-action-row">
                    @csrf @method('PATCH')
                    <div>
                        <strong>{{ $payment->no_kuitansi }}</strong>
                        <small>{{ $payment->tanggal_bayar }} · {{ $payment->metode }} · Rp {{ number_format($payment->total_bayar, 0, ',', '.') }}</small>
                        @if (! empty($payment->catatan))
                            <small>Catatan orang tua: {{ $payment->catatan }}</small>
                        @endif
                    </div>
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

            @if (($actions['pendingSavingsTransfers'] ?? collect())->isNotEmpty())
                <div class="database-section-heading database-subsection-heading"><div><h2>Verifikasi setoran tabungan (Transfer)</h2><p>Periksa bukti transfer setoran tabungan dari orang tua sebelum masuk ke saldo tabungan siswa.</p></div></div>
                @foreach ($actions['pendingSavingsTransfers'] as $deposit)
                    <form method="POST" action="{{ route('database.savings.transfer.review', ['deposit' => $deposit->id]) }}" class="database-action-row">
                        @csrf @method('PATCH')
                        <div>
                            <strong>{{ $deposit->nama_lengkap }} · Setor Tabungan</strong>
                            <small>{{ $deposit->created_at }} · {{ $deposit->metode }} · Rp {{ number_format((float) $deposit->jumlah, 0, ',', '.') }}</small>
                            @if (! empty($deposit->catatan))
                                <small>Catatan orang tua: {{ $deposit->catatan }}</small>
                            @endif
                        </div>
                        <a class="database-button database-button-secondary" href="{{ route('database.savings.transfer.proof', ['deposit' => $deposit->id]) }}" target="_blank" rel="noopener">Lihat bukti</a>
                        <input name="catatan" maxlength="255" placeholder="Catatan verifikasi">
                        <button class="database-button" type="submit" name="decision" value="verify">Setujui</button>
                        <button class="database-button database-button-danger" type="submit" name="decision" value="reject">Tolak</button>
                    </form>
                @endforeach
            @endif

            @if (($actions['cancellablePayments'] ?? collect())->isNotEmpty())
                <div class="database-section-heading database-subsection-heading"><div><h2>Batalkan kuitansi pembayaran</h2><p>Pembatalan tidak menghapus baris transaksi (immutable ledger), melainkan mengubah status menjadi Dibatalkan dengan alasan wajib.</p></div></div>
                @foreach ($actions['cancellablePayments'] as $cancellable)
                    <form method="POST" action="{{ route('database.payment.cancel', ['payment' => $cancellable->id_pembayaran]) }}" class="database-action-row" onsubmit="return confirm('Batalkan kuitansi pembayaran ini?');">
                        @csrf @method('PATCH')
                        <div>
                            <strong>{{ $cancellable->no_kuitansi }}</strong>
                            <small>{{ $cancellable->tanggal_bayar }} · {{ $cancellable->metode }} · Rp {{ number_format($cancellable->total_bayar, 0, ',', '.') }}</small>
                            @if (! empty($cancellable->catatan))
                                <small>Catatan: {{ $cancellable->catatan }}</small>
                            @endif
                        </div>
                        <input name="catatan" maxlength="255" placeholder="Alasan pembatalan (wajib)" required>
                        <button class="database-button database-button-danger" type="submit">Batalkan kuitansi</button>
                    </form>
                @endforeach
            @endif

            <div class="database-section-heading database-subsection-heading"><div><h2>Catat transaksi tabungan siswa</h2><p>Transaksi setor dan tarik disimpan sebagai riwayat tabungan; transaksi yang sudah tercatat tidak dapat dihapus.</p></div></div>
            <form method="POST" action="{{ route('database.savings.store') }}" class="database-form-grid">
                @csrf
                <label>Siswa<select name="id_siswa" required>@foreach ($actions['activeStudents'] ?? [] as $student)<option value="{{ $student->id_siswa }}">{{ $student->nama_lengkap }}</option>@endforeach</select></label>
                <label>Jenis<select name="jenis"><option>Setor</option><option>Tarik</option></select></label>
                <label>Jumlah<input type="number" name="jumlah" min="1" step="0.01" required></label>
                <label>Metode<select name="metode"><option>Tunai</option><option>Transfer</option></select></label>
                <label class="database-form-wide">Keterangan<input name="keterangan" maxlength="255"></label>
                <div class="database-form-wide"><button class="database-button" type="submit">Catat transaksi tabungan</button></div>
            </form>
        @endif

        @if ($section === 'pembayaran')
            <div class="database-section-heading @if ($role === 'admin') database-subsection-heading @endif"><div><h2>{{ $role === 'parent' ? 'Kirim pembayaran' : 'Catat pembayaran tagihan' }}</h2><p>{{ $role === 'parent' ? 'Pilih jenis pembayaran (Tagihan atau Setor Tabungan) menggunakan metode Transfer dan unggah bukti transfer.' : 'Pembayaran transfer membutuhkan bukti; transaksi tunai oleh administrator langsung tercatat.' }}</p></div></div>
            @if ($role === 'parent')
                @if ($actions['paymentSettings'] ?? null)
                    <div class="database-bank-details" role="note" aria-label="Informasi rekening pembayaran">
                        <div><span>Bank</span><strong>{{ $actions['paymentSettings']->bank_name }}</strong></div>
                        <div>
                            <span>Nomor rekening</span>
                            <strong id="payment-account-number">{{ $actions['paymentSettings']->account_number }}</strong>
                            <button type="button" class="database-button database-button-secondary database-copy-button" data-copy-target="payment-account-number" data-copy-status="payment-account-copy-status">Salin nomor</button>
                            <span id="payment-account-copy-status" aria-live="polite"></span>
                        </div>
                        <div><span>Atas nama</span><strong>{{ $actions['paymentSettings']->account_holder }}</strong></div>
                        <p>Transfer sesuai nominal yang diinginkan, lalu unggah bukti transfer agar dapat diverifikasi admin.</p>
                    </div>
                @else
                    <p class="database-form-errors">Informasi rekening belum disiapkan. Hubungi administrator sebelum membayar.</p>
                @endif

                @php
                    $defaultPayMode = old('payment_mode', (($actions['payableBills'] ?? collect())->isEmpty() && ($actions['parentStudents'] ?? collect())->isNotEmpty()) ? 'savings' : 'bill');
                @endphp
                <div class="payment-mode-switcher" data-payment-mode-root>
                    <div class="payment-mode-tabs" role="tablist" aria-label="Pilih jenis pembayaran">
                        <button type="button" role="tab" class="payment-mode-btn @if ($defaultPayMode === 'bill') is-active @endif" data-payment-mode-btn="bill" aria-selected="{{ $defaultPayMode === 'bill' ? 'true' : 'false' }}">
                            Bayar Tagihan (Transfer)
                        </button>
                        <button type="button" role="tab" class="payment-mode-btn @if ($defaultPayMode === 'savings') is-active @endif" data-payment-mode-btn="savings" aria-selected="{{ $defaultPayMode === 'savings' ? 'true' : 'false' }}">
                            Setor Tabungan (Transfer)
                        </button>
                    </div>

                    <div class="payment-mode-panel" data-payment-mode-panel="bill" @if ($defaultPayMode !== 'bill') hidden @endif>
                        @if (($actions['payableBills'] ?? collect())->isEmpty())
                            <p class="database-empty-note">Belum ada tagihan dengan sisa pembayaran yang dapat dibayar.</p>
                        @else
                            <form method="POST" action="{{ route('database.payment.store') }}" enctype="multipart/form-data" class="database-form-grid">
                                @csrf
                                <input type="hidden" name="payment_mode" value="bill">
                                <label>Tagihan<select name="id_tagihan" required>
                                    @foreach ($actions['payableBills'] as $bill)
                                        <option value="{{ $bill->id_tagihan }}">{{ $bill->nama_lengkap }} · {{ $bill->jenis_biaya }} · Sisa Rp {{ number_format($bill->sisa, 0, ',', '.') }}</option>
                                    @endforeach
                                </select></label>
                                <label>Jumlah<input type="number" name="jumlah" min="1" step="0.01" required></label>
                                <label>Metode<select name="metode" required>
                                    <option value="Transfer" selected>Transfer</option>
                                </select></label>
                                <label>Bukti transfer (JPG/PNG/PDF, maks. 5 MB)<input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" data-max-mb="5" required></label>
                                <label class="database-form-wide">Catatan<input name="catatan" maxlength="255"></label>
                                <div class="database-form-wide"><button class="database-button" type="submit">Simpan pembayaran</button></div>
                            </form>
                        @endif
                    </div>

                    <div class="payment-mode-panel" data-payment-mode-panel="savings" @if ($defaultPayMode !== 'savings') hidden @endif>
                        @if (($actions['parentStudents'] ?? collect())->isEmpty())
                            <p class="database-empty-note">Belum ada data anak yang terhubung untuk setoran tabungan.</p>
                        @else
                            <form method="POST" action="{{ route('database.savings.transfer.store') }}" enctype="multipart/form-data" class="database-form-grid">
                                @csrf
                                <input type="hidden" name="payment_mode" value="savings">
                                <label>Siswa<select name="id_siswa" required>
                                    @foreach ($actions['parentStudents'] as $student)
                                        <option value="{{ $student->id_siswa }}">{{ $student->nama_lengkap }}</option>
                                    @endforeach
                                </select></label>
                                <label>Jumlah setoran<input type="number" name="jumlah" min="1" step="0.01" required></label>
                                <label>Metode<select name="metode" disabled><option value="Transfer" selected>Transfer</option></select></label>
                                <label>Bukti transfer (JPG/PNG/PDF, maks. 5 MB)<input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" data-max-mb="5" required></label>
                                <label class="database-form-wide">Catatan<input name="catatan" maxlength="255" placeholder="Contoh: Setoran tabungan bulan ini"></label>
                                <div class="database-form-wide"><button class="database-button" type="submit">Kirim setoran tabungan</button></div>
                            </form>
                        @endif
                    </div>
                </div>

                @if (($actions['parentSavingsTransfers'] ?? collect())->isNotEmpty())
                    <div class="database-section-heading database-subsection-heading"><div><h2>Riwayat pengajuan setoran tabungan (Transfer)</h2><p>Setoran tabungan yang telah disetujui admin otomatis masuk ke Saldo Tabungan dan Riwayat Tabungan.</p></div></div>
                    <div class="parent-savings-transfer-list">
                        @foreach ($actions['parentSavingsTransfers'] as $deposit)
                            @php
                                $depositClass = match ($deposit->status) {
                                    'Terverifikasi' => 'is-valid',
                                    'Ditolak' => 'is-invalid',
                                    default => 'is-pending',
                                };
                            @endphp
                            <div class="database-action-row">
                                <div>
                                    <strong>{{ $deposit->nama_lengkap }} · Rp {{ number_format((float) $deposit->jumlah, 0, ',', '.') }}</strong>
                                    <small>{{ $deposit->created_at }} · {{ $deposit->metode }}</small>
                                    @if (! empty($deposit->catatan))
                                        <small>Catatan: {{ $deposit->catatan }}</small>
                                    @endif
                                </div>
                                <span class="parent-reg-doc-badge {{ $depositClass }}">
                                    <span class="parent-reg-status-dot" aria-hidden="true"></span>
                                    {{ $deposit->status }}
                                </span>
                                <a class="database-button database-button-secondary" href="{{ route('database.savings.transfer.proof', ['deposit' => $deposit->id]) }}" target="_blank" rel="noopener">Lihat bukti</a>
                            </div>
                        @endforeach
                    </div>
                @endif

                <p class="database-empty-note">Status pembayaran: <strong>Menunggu Verifikasi</strong> sedang diperiksa admin, <strong>Terverifikasi</strong> sudah disetujui, dan <strong>Ditolak</strong> perlu ditindaklanjuti.</p>
            @else
                @if (($actions['payableBills'] ?? collect())->isEmpty())
                    <p class="database-empty-note">Belum ada tagihan dengan sisa pembayaran yang dapat dibayar.</p>
                @else
                    <form method="POST" action="{{ route('database.payment.store') }}" enctype="multipart/form-data" class="database-form-grid">
                        @csrf
                        <label>Tagihan<select name="id_tagihan" required>
                            @foreach ($actions['payableBills'] as $bill)
                                <option value="{{ $bill->id_tagihan }}">{{ $bill->nama_lengkap }} · {{ $bill->jenis_biaya }} · Sisa Rp {{ number_format($bill->sisa, 0, ',', '.') }}</option>
                            @endforeach
                        </select></label>
                        <label>Jumlah<input type="number" name="jumlah" min="1" step="0.01" required></label>
                        <label>Metode<select name="metode" required>
                            <option value="Tunai">Tunai</option><option value="Transfer">Transfer</option>
                        </select></label>
                        <label>Bukti transfer (JPG/PNG/PDF, maks. 5 MB)<input type="file" name="bukti_transfer" accept=".jpg,.jpeg,.png,.pdf" data-max-mb="5"></label>
                        <label class="database-form-wide">Catatan<input name="catatan" maxlength="255"></label>
                        <div class="database-form-wide"><button class="database-button" type="submit">Simpan pembayaran</button></div>
                    </form>
                @endif
            @endif
        @endif
    </section>
@endif

@if ($section === 'keluarga' && $role === 'admin')
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Tambah data keluarga baru</h2><p>Tambahkan kartu keluarga, nama orang tua/wali, serta nomor kontak utama.</p></div></div>
        <details class="database-student-editor">
            <summary>Buka formulir keluarga baru</summary>
            <form method="POST" action="{{ route('database.family.store') }}" class="database-form-grid">
                @csrf
                <label>Nomor KK (16 digit)<input name="no_kk" maxlength="16" pattern="[0-9]{16}" placeholder="16 digit angka"></label>
                <label>Nomor HP / WhatsApp utama<input name="nomor" maxlength="16" placeholder="0812..."></label>
                <label>Nama Ayah<input name="nama_ayah" maxlength="100"></label>
                <label>Nama Ibu<input name="nama_ibu" maxlength="100"></label>
                <label>Nama Wali (opsional)<input name="nama_wali" maxlength="100"></label>
                <label class="database-form-wide">Alamat keluarga<textarea name="alamat" rows="2" maxlength="2000"></textarea></label>
                <div class="database-form-wide"><button class="database-button" type="submit">Simpan keluarga baru</button></div>
            </form>
        </details>
    </section>

    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Buat akun orang tua untuk keluarga lama</h2><p>Buat akun memakai email yang diberikan orang tua lalu hubungkan langsung ke keluarga yang benar. Akun hanya mendapat akses ke siswa dalam keluarga tersebut.</p></div></div>
        @if (($actions['unlinkedFamilies'] ?? collect())->isEmpty())
            <p class="database-empty-note">Tidak ada data keluarga berisi siswa yang belum terhubung.</p>
        @else
            <form method="POST" action="{{ route('database.family.create-parent') }}" class="database-form-grid">
                @csrf
                <label>Data keluarga<select name="id_keluarga" required>
                    <option value="">Pilih data keluarga</option>
                    @foreach ($actions['unlinkedFamilies'] as $family)
                        <option value="{{ $family->id_keluarga }}" @selected(old('id_keluarga') == $family->id_keluarga)>Keluarga #{{ $family->id_keluarga }} · {{ $family->studentNames }}</option>
                    @endforeach
                </select></label>
                <label>Nama akun orang tua<input name="name" value="{{ old('name') }}" maxlength="100" required></label>
                <label>Email orang tua<input type="email" name="email" value="{{ old('email') }}" maxlength="150" required autocomplete="email"></label>
                <label>Kata sandi sementara<input type="password" name="password" minlength="8" autocomplete="new-password" required></label>
                <label>Ulangi kata sandi<input type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required></label>
                <div class="database-form-wide"><small>Minimal 8 karakter, huruf besar dan kecil, serta angka. Simbol tidak wajib. Sampaikan kata sandi sementara kepada orang tua secara pribadi.</small></div>
                <div class="database-form-wide"><button class="database-button" type="submit">Buat akun dan hubungkan</button></div>
            </form>
        @endif
    </section>

    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Hubungkan akun yang sudah ada</h2><p>Pilih akun orang tua aktif yang belum terhubung dan pasangkan ke data keluarga yang belum terhubung.</p></div></div>
        @if (($actions['unlinkedParents'] ?? collect())->isEmpty())
            <p class="database-empty-note">Tidak ada akun orang tua aktif yang belum terhubung.</p>
        @elseif (($actions['unlinkedFamilies'] ?? collect())->isEmpty())
            <p class="database-empty-note">Tidak ada data keluarga berisi siswa yang belum terhubung.</p>
        @else
            <form method="POST" action="{{ route('database.family.link-parent') }}" class="database-form-grid">
                @csrf
                <label>Akun orang tua<select name="id_user" required>
                    <option value="">Pilih akun orang tua</option>
                    @foreach ($actions['unlinkedParents'] as $parent)
                        <option value="{{ $parent->id_user }}" @selected(old('id_user') == $parent->id_user)>{{ $parent->name }} · {{ $parent->email }}</option>
                    @endforeach
                </select></label>
                <label>Data keluarga<select name="id_keluarga" required>
                    <option value="">Pilih data keluarga</option>
                    @foreach ($actions['unlinkedFamilies'] as $family)
                        <option value="{{ $family->id_keluarga }}" @selected(old('id_keluarga') == $family->id_keluarga)>Keluarga #{{ $family->id_keluarga }} · {{ $family->studentNames }}</option>
                    @endforeach
                </select></label>
                <div class="database-form-wide"><button class="database-button" type="submit">Hubungkan akun</button></div>
            </form>
        @endif
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
        <div class="database-section-heading">
            <div><h2>Permintaan reset kata sandi</h2><p>Tinjau dan verifikasi permintaan orang tua tanpa mengirim tautan ke email.</p></div>
            <a class="database-button" href="{{ route('admin.password-reset-requests.index') }}">Buka permintaan</a>
        </div>
    </section>
    <section class="database-action-panel">
        <div class="database-section-heading"><div><h2>Buat akun pengguna</h2><p>Peran akun disimpan sesuai enum pengguna pada database.</p></div></div>
        <form method="POST" action="{{ route('admin.users.store') }}" class="database-form-grid">
            @csrf
            <label>Nama<input name="name" maxlength="100" required></label>
            <label>Email<input type="email" name="email" maxlength="150" required></label>
            <label>Peran<select name="role"><option value="teacher">Guru</option><option value="principal">Kepala sekolah</option><option value="parent">Orang tua</option><option value="admin">Administrator</option></select></label>
            <label>Kata sandi
                <span class="registration-password-wrap">
                    <input id="database-user-password" type="password" name="password" minlength="8" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="database-user-password" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                            <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </span>
                <small>Minimal 8 karakter, huruf besar dan kecil, serta angka. Simbol tidak wajib.</small>
            </label>
            <label>Ulangi kata sandi
                <span class="registration-password-wrap">
                    <input id="database-user-password-confirmation" type="password" name="password_confirmation" minlength="8" autocomplete="new-password" required>
                    <button type="button" class="password-toggle" aria-label="Tampilkan kata sandi" aria-controls="database-user-password-confirmation" aria-pressed="false">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                            <path d="M2.5 12s3.4-6 9.5-6 9.5 6 9.5 6-3.4 6-9.5 6-9.5-6-9.5-6Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <circle cx="12" cy="12" r="2.7" stroke="currentColor" stroke-width="2"/>
                            <path class="password-eye-slash" d="m4 4 16 16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        </svg>
                    </button>
                </span>
            </label>
            <div class="database-form-wide"><button class="database-button" type="submit">Buat akun</button></div>
        </form>
    </section>
@endif
