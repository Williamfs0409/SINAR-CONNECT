<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SchoolFeatureController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploads)
    {
    }

    public function updateStudentProfile(Request $request, int $studentId): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'alamat_domisili' => ['nullable', 'string', 'max:2000'],
        ]);

        $exists = DB::table('siswa')->where('id_siswa', $studentId)->exists();
        abort_unless($exists, 404);

        DB::table('siswa')->where('id_siswa', $studentId)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Profil siswa berhasil diperbarui.');
    }

    public function reviewRegistration(Request $request, int $registrationId): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verify', 'accept', 'reject'])],
            'class_id' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $registrationId, $user): void {
            $application = DB::table('pendaftaran')
                ->where('id_pendaftaran', $registrationId)
                ->lockForUpdate()
                ->first();
            abort_unless($application, 404);

            if ($data['decision'] === 'verify') {
                if ($application->status !== 'Diajukan') {
                    throw ValidationException::withMessages(['decision' => 'Hanya pendaftaran yang diajukan dapat diverifikasi.']);
                }

                $hasUnverifiedDocuments = DB::table('dokumen_pendaftaran')
                    ->where('id_pendaftaran', $registrationId)
                    ->where('status_verifikasi', '!=', 'Valid')
                    ->exists();
                if ($hasUnverifiedDocuments) {
                    throw ValidationException::withMessages(['decision' => 'Periksa atau validasi semua dokumen sebelum memverifikasi pendaftaran.']);
                }

                DB::table('pendaftaran')->where('id_pendaftaran', $registrationId)->update([
                    'status' => 'Diverifikasi',
                    'catatan_verifikasi' => $data['note'] ?? null,
                    'id_user_verifikator' => $user->getKey(),
                    'tanggal_verifikasi' => now(),
                ]);

                return;
            }

            if ($data['decision'] === 'reject') {
                if (! in_array($application->status, ['Diajukan', 'Diverifikasi'], true)) {
                    throw ValidationException::withMessages(['decision' => 'Status pendaftaran ini tidak dapat ditolak.']);
                }

                DB::table('pendaftaran')->where('id_pendaftaran', $registrationId)->update([
                    'status' => 'Ditolak',
                    'catatan_verifikasi' => $data['note'] ?? null,
                    'id_user_verifikator' => $user->getKey(),
                    'tanggal_verifikasi' => now(),
                ]);

                return;
            }

            if ($application->status !== 'Diverifikasi') {
                throw ValidationException::withMessages(['decision' => 'Pendaftaran harus diverifikasi sebelum diterima.']);
            }
            if (DB::table('dokumen_pendaftaran')
                ->where('id_pendaftaran', $registrationId)
                ->where('status_verifikasi', '!=', 'Valid')
                ->exists()) {
                throw ValidationException::withMessages(['decision' => 'Masih ada dokumen pendaftaran yang belum valid.']);
            }

            $class = DB::table('kelas')
                ->where('id_kelas', $data['class_id'] ?? 0)
                ->where('id_tahun_ajaran', $application->id_tahun_ajaran)
                ->where('kelompok', $application->kelompok)
                ->first();
            if (! $class) {
                throw ValidationException::withMessages(['class_id' => 'Pilih kelas yang sesuai dengan tahun ajaran dan kelompok pendaftar.']);
            }

            DB::table('rombel_siswa')->insert([
                'id_siswa' => $application->id_siswa,
                'id_kelas' => $class->id_kelas,
                'status' => 'Aktif',
                'tanggal_masuk' => now()->toDateString(),
                'created_at' => now(),
            ]);

            if (! empty($data['due_date'])) {
                $registrationFees = DB::table('tarif as tf')
                    ->join('jenis_biaya as jb', 'jb.id_jenis_biaya', '=', 'tf.id_jenis_biaya')
                    ->where('tf.id_tahun_ajaran', $application->id_tahun_ajaran)
                    ->where('tf.kelompok', $application->kelompok)
                    ->where('jb.kategori', 'Pendaftaran')
                    ->get(['tf.id_jenis_biaya', 'tf.nominal']);

                $rosterId = DB::table('rombel_siswa')
                    ->where('id_siswa', $application->id_siswa)
                    ->where('id_kelas', $class->id_kelas)
                    ->where('status', 'Aktif')
                    ->value('id_rombel');

                foreach ($registrationFees as $fee) {
                    DB::table('tagihan')->insert([
                        'id_rombel' => $rosterId,
                        'id_jenis_biaya' => $fee->id_jenis_biaya,
                        'nominal' => $fee->nominal,
                        'diskon' => 0,
                        'jatuh_tempo' => $data['due_date'],
                        'created_at' => now(),
                    ]);
                }
            }

            DB::table('pendaftaran')->where('id_pendaftaran', $registrationId)->update([
                'status' => 'Diterima',
                'catatan_verifikasi' => $data['note'] ?? null,
                'id_user_verifikator' => $user->getKey(),
                'tanggal_verifikasi' => now(),
            ]);
        });

        return back()->with('status', 'Status pendaftaran berhasil diperbarui.');
    }

    public function verifyRegistrationDocument(Request $request, int $documentId): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        $data = $request->validate([
            'status_verifikasi' => ['required', Rule::in(['Valid', 'Tidak Valid'])],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $document = DB::table('dokumen_pendaftaran')
            ->where('id_dokumen', $documentId)
            ->first();
        abort_unless($document, 404);
        DB::table('dokumen_pendaftaran')
            ->where('id_dokumen', $documentId)
            ->update([
                'status_verifikasi' => $data['status_verifikasi'],
                'catatan' => $data['catatan'] ?? null,
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Pemeriksaan dokumen berhasil disimpan.');
    }

    public function storeAttendance(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.status' => ['required', Rule::in(['Hadir', 'Izin', 'Sakit', 'Alfa'])],
            'attendance.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        $teacherId = $this->teacherId($user);
        $this->assertTeacherClass($teacherId, (int) $data['id_kelas']);
        $roster = DB::table('rombel_siswa')
            ->where('id_kelas', $data['id_kelas'])
            ->where('status', 'Aktif')
            ->pluck('id_rombel')
            ->map(fn ($id): int => (int) $id)
            ->all();
        $submittedIds = array_map('intval', array_keys($data['attendance']));
        abort_unless(array_diff($submittedIds, $roster) === [], 403);

        DB::transaction(function () use ($data, $user): void {
            foreach ($data['attendance'] as $rosterId => $attendance) {
                DB::table('absensi')->updateOrInsert(
                    ['id_rombel' => (int) $rosterId, 'tanggal' => $data['tanggal']],
                    [
                        'status' => $attendance['status'],
                        'keterangan' => $attendance['keterangan'] ?? null,
                        'id_user_pencatat' => $user->getKey(),
                        'updated_at' => now(),
                    ]
                );
            }
        });

        return back()->with('status', 'Absensi siswa berhasil disimpan.');
    }

    public function storeLessonPlan(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'tanggal' => ['required', 'date'],
            'tema' => ['required', 'string', 'max:150'],
            'subtema' => ['nullable', 'string', 'max:150'],
            'tujuan' => ['nullable', 'string', 'max:5000'],
            'kegiatan' => ['nullable', 'string', 'max:5000'],
            'alat_bahan' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(['Draft', 'Final'])],
        ]);

        $teacherId = $this->teacherId($user);
        $this->assertTeacherClass($teacherId, (int) $data['id_kelas']);
        DB::table('rencana_pembelajaran')->insert([
            ...$data,
            'id_guru' => $teacherId,
            'created_at' => now(),
        ]);

        return back()->with('status', 'Rencana pembelajaran berhasil disimpan.');
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'judul' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'tanggal_diberikan' => ['required', 'date', 'before_or_equal:today'],
            'tenggat' => ['required', 'date', 'after_or_equal:tanggal_diberikan'],
            'lampiran' => ['nullable', 'file', 'max:10240'],
        ]);

        $teacherId = $this->teacherId($user);
        $this->assertTeacherClass($teacherId, (int) $data['id_kelas']);
        $path = $request->hasFile('lampiran')
            ? $this->imageUploads->store($request->file('lampiran'), 'tugas', 'lampiran')
            : null;

        DB::table('tugas')->insert([
            'id_kelas' => $data['id_kelas'],
            'id_guru' => $teacherId,
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'tanggal_diberikan' => $data['tanggal_diberikan'],
            'tenggat' => $data['tenggat'],
            'lampiran' => $path,
            'created_at' => now(),
        ]);

        return back()->with('status', 'Tugas berhasil dibuat.');
    }

    public function submitTask(Request $request, int $taskId): RedirectResponse
    {
        $user = $this->userFor(['parent']);
        $data = $request->validate([
            'id_siswa' => ['required', 'integer'],
            'catatan_ortu' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:10240'],
        ]);

        $familyId = $this->familyId($user);
        $task = DB::table('tugas')->where('id_tugas', $taskId)->first();
        abort_unless($task, 404);
        $roster = DB::table('rombel_siswa as r')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->where('s.id_keluarga', $familyId)
            ->where('s.id_siswa', $data['id_siswa'])
            ->where('r.id_kelas', $task->id_kelas)
            ->where('r.status', 'Aktif')
            ->first(['r.id_rombel']);
        abort_unless($roster, 403);

        $submission = DB::table('pengumpulan_tugas')
            ->where('id_tugas', $taskId)
            ->where('id_rombel', $roster->id_rombel)
            ->first();
        $path = $request->hasFile('file')
            ? $this->imageUploads->store($request->file('file'), 'pengumpulan-tugas', 'file')
            : ($submission->file_path ?? null);

        if ($submission) {
            DB::table('pengumpulan_tugas')
                ->where('id_pengumpulan', $submission->id_pengumpulan)
                ->update([
                    'file_path' => $path,
                    'catatan_ortu' => $data['catatan_ortu'] ?? null,
                    'dikumpulkan_pada' => now(),
                    'updated_at' => now(),
                ]);
        } else {
            DB::table('pengumpulan_tugas')->insert([
                'id_tugas' => $taskId,
                'id_rombel' => $roster->id_rombel,
                'file_path' => $path,
                'catatan_ortu' => $data['catatan_ortu'] ?? null,
                'dikumpulkan_pada' => now(),
                'created_at' => now(),
            ]);
        }

        return back()->with('status', 'Pengumpulan tugas berhasil dikirim.');
    }

    public function gradeTask(Request $request, int $submissionId): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'nilai' => ['required', Rule::in(['BB', 'MB', 'BSH', 'BSB'])],
            'umpan_balik' => ['nullable', 'string', 'max:5000'],
        ]);
        $teacherId = $this->teacherId($user);
        $submission = DB::table('pengumpulan_tugas as pt')
            ->join('tugas as t', 't.id_tugas', '=', 'pt.id_tugas')
            ->where('pt.id_pengumpulan', $submissionId)
            ->first(['pt.id_pengumpulan', 't.id_kelas']);
        abort_unless($submission, 404);
        $this->assertTeacherClass($teacherId, (int) $submission->id_kelas);

        DB::table('pengumpulan_tugas')->where('id_pengumpulan', $submissionId)->update([
            'nilai' => $data['nilai'],
            'umpan_balik' => $data['umpan_balik'] ?? null,
            'id_guru_penilai' => $teacherId,
            'dinilai_pada' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Nilai dan umpan balik tugas berhasil disimpan.');
    }

    public function storeIqroGrade(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'id_rombel' => ['required', 'integer', Rule::exists('rombel_siswa', 'id_rombel')],
            'id_indikator' => ['required', 'integer', Rule::exists('indikator_iqro', 'id_indikator')],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'skala' => ['required', Rule::in(['BB', 'MB', 'BSH', 'BSB'])],
        ]);

        $teacherId = $this->teacherId($user);
        $roster = DB::table('rombel_siswa as r')
            ->join('guru_kelas as gk', 'gk.id_kelas', '=', 'r.id_kelas')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->join('indikator_iqro as i', 'i.kelompok', '=', 'k.kelompok')
            ->where('r.id_rombel', $data['id_rombel'])
            ->where('r.status', 'Aktif')
            ->where('gk.id_guru', $teacherId)
            ->where('i.id_indikator', $data['id_indikator'])
            ->exists();
        abort_unless($roster, 403);

        $key = [
            'id_rombel' => $data['id_rombel'],
            'id_indikator' => $data['id_indikator'],
            'semester' => $data['semester'],
        ];
        DB::table('nilai_iqro')->updateOrInsert($key, [
            'skala' => $data['skala'],
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Nilai perkembangan Iqro berhasil disimpan.');
    }

    public function storeReport(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'id_rombel' => ['required', 'integer', Rule::exists('rombel_siswa', 'id_rombel')],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'tanggal_rapor' => ['nullable', 'date'],
            'tinggi_badan' => ['nullable', 'numeric', 'min:30', 'max:180'],
            'berat_badan' => ['nullable', 'numeric', 'min:5', 'max:120'],
            'narasi_agama_budi_pekerti' => ['nullable', 'string', 'max:5000'],
            'narasi_jati_diri' => ['nullable', 'string', 'max:5000'],
            'narasi_literasi_steam' => ['nullable', 'string', 'max:5000'],
            'narasi_p5' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['Draft', 'Final'])],
        ]);

        $teacherId = $this->teacherId($user);
        $this->assertTeacherRoster($teacherId, (int) $data['id_rombel']);
        $existing = DB::table('rapor')
            ->where('id_rombel', $data['id_rombel'])
            ->where('semester', $data['semester'])
            ->first();
        if ($existing?->status === 'Final') {
            throw ValidationException::withMessages(['semester' => 'Rapor final terkunci dan tidak dapat diubah.']);
        }

        $values = collect($data)->except('id_rombel')->all() + [
            'id_guru_kelas' => $teacherId,
            'updated_at' => now(),
        ];
        if ($existing) {
            DB::table('rapor')->where('id_rapor', $existing->id_rapor)->update($values);
        } else {
            DB::table('rapor')->insert($values + [
                'id_rombel' => $data['id_rombel'],
                'created_at' => now(),
            ]);
        }

        return back()->with('status', 'Rapor berhasil disimpan.');
    }

    public function updateReportReflection(Request $request, int $reportId): RedirectResponse
    {
        $user = $this->userFor(['parent']);
        $data = $request->validate([
            'refleksi_orang_tua' => ['nullable', 'string', 'max:5000'],
        ]);
        $familyId = $this->familyId($user);
        $report = DB::table('rapor as rp')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 'rp.id_rombel')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->where('rp.id_rapor', $reportId)
            ->where('s.id_keluarga', $familyId)
            ->first(['rp.id_rapor']);
        abort_unless($report, 404);

        DB::table('rapor')->where('id_rapor', $reportId)->update([
            'refleksi_orang_tua' => $data['refleksi_orang_tua'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Refleksi orang tua berhasil disimpan.');
    }

    public function storePortfolio(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'id_rombel' => ['required', 'integer', Rule::exists('rombel_siswa', 'id_rombel')],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'narasi' => ['required', 'string', 'max:5000'],
        ]);
        $teacherId = $this->teacherId($user);
        $this->assertTeacherRoster($teacherId, (int) $data['id_rombel']);
        $path = $this->imageUploads->store($request->file('foto'), 'portofolio', 'foto');

        DB::table('portofolio')->insert([
            'id_rombel' => $data['id_rombel'],
            'tanggal' => $data['tanggal'],
            'foto' => $path,
            'narasi' => $data['narasi'],
            'id_guru' => $teacherId,
            'created_at' => now(),
        ]);

        return back()->with('status', 'Dokumentasi perkembangan siswa berhasil disimpan.');
    }

    public function storeActivity(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'nama_kegiatan' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(['Dalam Kelas', 'Luar Kelas', 'Field Trip'])],
            'tanggal' => ['required', 'date'],
            'lokasi' => ['nullable', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ]);
        $teacherId = $this->teacherId($user);
        $this->assertTeacherClass($teacherId, (int) $data['id_kelas']);

        DB::transaction(function () use ($data, $user): void {
            $activityId = DB::table('kegiatan')->insertGetId([
                'nama_kegiatan' => $data['nama_kegiatan'],
                'jenis' => $data['jenis'],
                'tanggal' => $data['tanggal'],
                'lokasi' => $data['lokasi'] ?? null,
                'deskripsi' => $data['deskripsi'] ?? null,
                'seluruh_sekolah' => false,
                'id_user_pembuat' => $user->getKey(),
                'created_at' => now(),
            ]);
            DB::table('kegiatan_kelas')->insert([
                'id_kegiatan' => $activityId,
                'id_kelas' => $data['id_kelas'],
            ]);
        });

        return back()->with('status', 'Kegiatan berhasil ditambahkan.');
    }

    public function uploadActivityPhoto(Request $request, int $activityId): RedirectResponse
    {
        $user = $this->userFor(['teacher']);
        $data = $request->validate([
            'tahap' => ['required', Rule::in(['Sebelum', 'Selama', 'Sesudah'])],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        $teacherId = $this->teacherId($user);
        $activity = DB::table('kegiatan as k')
            ->leftJoin('kegiatan_kelas as kk', 'kk.id_kegiatan', '=', 'k.id_kegiatan')
            ->leftJoin('guru_kelas as gk', 'gk.id_kelas', '=', 'kk.id_kelas')
            ->where('k.id_kegiatan', $activityId)
            ->where(function ($query) use ($teacherId, $user): void {
                $query->where('k.id_user_pembuat', $user->getKey())
                    ->orWhere('gk.id_guru', $teacherId);
            })
            ->exists();
        abort_unless($activity, 403);
        $path = $this->imageUploads->store($request->file('foto'), 'kegiatan', 'foto');

        DB::table('dokumentasi_kegiatan')->insert([
            'id_kegiatan' => $activityId,
            'tahap' => $data['tahap'],
            'tipe' => 'Foto',
            'file_path' => $path,
            'keterangan' => $data['keterangan'] ?? null,
            'id_user_upload' => $user->getKey(),
            'created_at' => now(),
        ]);

        return back()->with('status', 'Foto kegiatan berhasil diunggah.');
    }

    public function activityPhoto(int $documentId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'principal', 'teacher', 'parent']);
        $document = DB::table('dokumentasi_kegiatan as d')
            ->join('kegiatan as k', 'k.id_kegiatan', '=', 'd.id_kegiatan')
            ->where('d.id_dokumentasi', $documentId)
            ->first(['d.file_path', 'k.id_kegiatan', 'k.id_user_pembuat', 'k.seluruh_sekolah']);
        abort_unless($document, 404);

        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $authorized = $document->id_user_pembuat === $user->getKey()
                || DB::table('kegiatan_kelas as kk')
                    ->join('guru_kelas as gk', 'gk.id_kelas', '=', 'kk.id_kelas')
                    ->where('kk.id_kegiatan', $document->id_kegiatan)
                    ->where('gk.id_guru', $teacherId)
                    ->exists();
            abort_unless($authorized, 403);
        } elseif ($user->role === 'parent') {
            $familyId = $this->familyId($user);
            $visible = (bool) $document->seluruh_sekolah
                || DB::table('kegiatan_kelas as kk')
                    ->join('rombel_siswa as r', 'r.id_kelas', '=', 'kk.id_kelas')
                    ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                    ->where('kk.id_kegiatan', $document->id_kegiatan)
                    ->where('s.id_keluarga', $familyId)
                    ->where('r.status', 'Aktif')
                    ->exists();
            abort_unless($visible, 403);
        }

        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path);
    }

    public function registrationDocument(int $documentId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'parent']);
        $document = DB::table('dokumen_pendaftaran as d')
            ->join('pendaftaran as p', 'p.id_pendaftaran', '=', 'd.id_pendaftaran')
            ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
            ->where('d.id_dokumen', $documentId)
            ->first(['d.file_path', 's.id_keluarga']);
        abort_unless($document, 404);
        if ($user->role === 'parent') {
            abort_unless((int) $document->id_keluarga === $this->familyId($user), 403);
        }
        abort_unless(Storage::disk('local')->exists($document->file_path), 404);

        return Storage::disk('local')->response($document->file_path);
    }

    public function portfolioPhoto(int $portfolioId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'principal', 'teacher', 'parent']);
        $portfolio = DB::table('portofolio as p')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 'p.id_rombel')
            ->where('p.id_portofolio', $portfolioId)
            ->first(['p.foto', 'r.id_kelas', 'r.id_siswa']);
        abort_unless($portfolio, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $portfolio->id_kelas);
        } elseif ($user->role === 'parent') {
            $studentIds = DB::table('siswa')
                ->where('id_keluarga', $this->familyId($user))
                ->pluck('id_siswa')
                ->map(fn ($id): int => (int) $id)
                ->all();
            abort_unless(in_array((int) $portfolio->id_siswa, $studentIds, true), 403);
        }

        abort_unless(Storage::disk('local')->exists($portfolio->foto), 404);

        return Storage::disk('local')->response($portfolio->foto);
    }

    public function taskAttachment(int $taskId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'principal', 'teacher', 'parent']);
        $task = DB::table('tugas')->where('id_tugas', $taskId)->first(['lampiran', 'id_kelas']);
        abort_unless($task && $task->lampiran, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $task->id_kelas);
        } elseif ($user->role === 'parent') {
            $familyId = $this->familyId($user);
            abort_unless(DB::table('rombel_siswa as r')
                ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                ->where('s.id_keluarga', $familyId)
                ->where('r.id_kelas', $task->id_kelas)
                ->where('r.status', 'Aktif')
                ->exists(), 403);
        }

        abort_unless(Storage::disk('local')->exists($task->lampiran), 404);

        return Storage::disk('local')->response($task->lampiran);
    }

    public function taskSubmissionFile(int $submissionId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'principal', 'teacher', 'parent']);
        $submission = DB::table('pengumpulan_tugas as pt')
            ->join('tugas as t', 't.id_tugas', '=', 'pt.id_tugas')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 'pt.id_rombel')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->where('pt.id_pengumpulan', $submissionId)
            ->first(['pt.file_path', 't.id_kelas', 's.id_keluarga']);
        abort_unless($submission && $submission->file_path, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $submission->id_kelas);
        } elseif ($user->role === 'parent') {
            abort_unless((int) $submission->id_keluarga === $this->familyId($user), 403);
        }

        abort_unless(Storage::disk('local')->exists($submission->file_path), 404);

        return Storage::disk('local')->response($submission->file_path);
    }

    public function paymentProof(int $paymentId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'parent']);
        $payment = DB::table('pembayaran as p')
            ->join('alokasi_pembayaran as a', 'a.id_pembayaran', '=', 'p.id_pembayaran')
            ->join('tagihan as t', 't.id_tagihan', '=', 'a.id_tagihan')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 't.id_rombel')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->where('p.id_pembayaran', $paymentId)
            ->first(['p.bukti_transfer', 's.id_keluarga']);
        abort_unless($payment && $payment->bukti_transfer, 404);
        if ($user->role === 'parent') {
            abort_unless((int) $payment->id_keluarga === $this->familyId($user), 403);
        }
        abort_unless(Storage::disk('local')->exists($payment->bukti_transfer), 404);

        return Storage::disk('local')->response($payment->bukti_transfer);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $user = $this->userFor(['admin', 'parent']);
        $data = $request->validate([
            'id_tagihan' => ['required', 'integer'],
            'jumlah' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'metode' => ['required', Rule::in(['Tunai', 'Transfer'])],
            'bukti_transfer' => [
                Rule::requiredIf($request->input('metode') === 'Transfer'),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $familyId = $user->role === 'parent' ? $this->familyId($user) : null;
        $proofPath = $request->hasFile('bukti_transfer')
            ? $this->imageUploads->store($request->file('bukti_transfer'), 'bukti-pembayaran', 'bukti_transfer')
            : null;

        DB::transaction(function () use ($data, $familyId, $proofPath, $user): void {
            $bill = DB::table('tagihan as t')
                ->join('rombel_siswa as r', 'r.id_rombel', '=', 't.id_rombel')
                ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                ->where('t.id_tagihan', $data['id_tagihan'])
                ->lockForUpdate()
                ->first([
                    't.id_tagihan',
                    't.total_tagihan',
                    's.id_keluarga',
                ]);
            abort_unless($bill, 404);
            if ($familyId !== null && (int) $bill->id_keluarga !== $familyId) {
                abort(403);
            }

            $allocated = DB::table('alokasi_pembayaran as a')
                ->join('pembayaran as p', 'p.id_pembayaran', '=', 'a.id_pembayaran')
                ->where('a.id_tagihan', $bill->id_tagihan)
                ->whereIn('p.status', ['Menunggu Verifikasi', 'Terverifikasi'])
                ->sum('a.jumlah');
            $remaining = (float) $bill->total_tagihan - (float) $allocated;
            if ((float) $data['jumlah'] > $remaining) {
                throw ValidationException::withMessages(['jumlah' => 'Jumlah pembayaran melebihi sisa tagihan.']);
            }

            $isCashAdminEntry = $user->role === 'admin' && $data['metode'] === 'Tunai';
            $paymentId = DB::table('pembayaran')->insertGetId([
                'metode' => $data['metode'],
                'bukti_transfer' => $proofPath,
                'status' => $isCashAdminEntry ? 'Terverifikasi' : 'Menunggu Verifikasi',
                'catatan' => $data['catatan'] ?? null,
                'id_user_pencatat' => $user->getKey(),
                'id_user_verifikator' => $isCashAdminEntry ? $user->getKey() : null,
                'tanggal_verifikasi' => $isCashAdminEntry ? now() : null,
                'created_at' => now(),
            ]);

            DB::table('alokasi_pembayaran')->insert([
                'id_pembayaran' => $paymentId,
                'id_tagihan' => $bill->id_tagihan,
                'jumlah' => $data['jumlah'],
            ]);
        });

        return back()->with('status', 'Pembayaran berhasil dicatat dan akan diproses sesuai metode pembayaran.');
    }

    public function reviewPayment(Request $request, int $paymentId): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verify', 'reject'])],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $payment = DB::table('pembayaran')->where('id_pembayaran', $paymentId)->first();
        abort_unless($payment, 404);
        if ($payment->status !== 'Menunggu Verifikasi') {
            throw ValidationException::withMessages(['decision' => 'Hanya pembayaran yang menunggu verifikasi dapat diproses.']);
        }

        DB::table('pembayaran')->where('id_pembayaran', $paymentId)->update([
            'status' => $data['decision'] === 'verify' ? 'Terverifikasi' : 'Ditolak',
            'id_user_verifikator' => $user->getKey(),
            'tanggal_verifikasi' => now(),
            'catatan' => $data['catatan'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Status pembayaran berhasil diperbarui.');
    }

    public function storeSavings(Request $request): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        $data = $request->validate([
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
            'jenis' => ['required', Rule::in(['Setor', 'Tarik'])],
            'jumlah' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'metode' => ['required', Rule::in(['Tunai', 'Transfer'])],
            'keterangan' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $user): void {
            $student = DB::table('siswa')->where('id_siswa', $data['id_siswa'])->lockForUpdate()->first();
            abort_unless($student, 404);

            $balance = DB::table('transaksi_tabungan')
                ->where('id_siswa', $student->id_siswa)
                ->selectRaw("COALESCE(SUM(CASE WHEN jenis = 'Setor' THEN jumlah ELSE -jumlah END), 0) as saldo")
                ->value('saldo');
            if ($data['jenis'] === 'Tarik' && (float) $data['jumlah'] > (float) $balance) {
                throw ValidationException::withMessages(['jumlah' => 'Saldo tabungan siswa tidak mencukupi.']);
            }

            DB::table('transaksi_tabungan')->insert([
                'id_siswa' => $student->id_siswa,
                'jenis' => $data['jenis'],
                'jumlah' => $data['jumlah'],
                'metode' => $data['metode'],
                'keterangan' => $data['keterangan'] ?? null,
                'id_user_pencatat' => $user->getKey(),
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Transaksi tabungan berhasil dicatat.');
    }

    public function generateMonthlyBills(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jatuh_tempo' => ['required', 'date'],
        ]);
        $schoolYear = DB::table('tahun_ajaran')->where('is_aktif', true)->first();
        abort_unless($schoolYear, 503, 'Tahun ajaran aktif belum tersedia.');
        $period = $data['periode'].'-01';

        $created = DB::transaction(function () use ($data, $period, $schoolYear): int {
            $rosters = DB::table('rombel_siswa as r')
                ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                ->where('r.status', 'Aktif')
                ->where('k.id_tahun_ajaran', $schoolYear->id_tahun_ajaran)
                ->get(['r.id_rombel', 'k.kelompok']);
            $tariffs = DB::table('tarif as tf')
                ->join('jenis_biaya as jb', 'jb.id_jenis_biaya', '=', 'tf.id_jenis_biaya')
                ->where('tf.id_tahun_ajaran', $schoolYear->id_tahun_ajaran)
                ->where('jb.kategori', 'Bulanan')
                ->get(['tf.id_jenis_biaya', 'tf.kelompok', 'tf.nominal']);

            $count = 0;
            foreach ($rosters as $roster) {
                foreach ($tariffs->where('kelompok', $roster->kelompok) as $tariff) {
                    $exists = DB::table('tagihan')
                        ->where('id_rombel', $roster->id_rombel)
                        ->where('id_jenis_biaya', $tariff->id_jenis_biaya)
                        ->whereDate('periode', $period)
                        ->exists();
                    if ($exists) {
                        continue;
                    }

                    DB::table('tagihan')->insert([
                        'id_rombel' => $roster->id_rombel,
                        'id_jenis_biaya' => $tariff->id_jenis_biaya,
                        'periode' => $period,
                        'nominal' => $tariff->nominal,
                        'diskon' => 0,
                        'jatuh_tempo' => $data['jatuh_tempo'],
                        'created_at' => now(),
                    ]);
                    $count++;
                }
            }

            return $count;
        });

        return back()->with('status', $created.' tagihan SPP baru berhasil dibuat.');
    }

    public function updateTariff(Request $request, int $tariffId): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        $exists = DB::table('tarif')->where('id_tarif', $tariffId)->exists();
        abort_unless($exists, 404);
        DB::table('tarif')->where('id_tarif', $tariffId)->update([
            'nominal' => $data['nominal'],
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Tarif biaya berhasil diperbarui.');
    }

    public function storeTariff(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_tahun_ajaran' => ['required', 'integer', Rule::exists('tahun_ajaran', 'id_tahun_ajaran')],
            'kelompok' => ['required', Rule::in(['KB', 'TK A', 'TK B'])],
            'id_jenis_biaya' => ['required', 'integer', Rule::exists('jenis_biaya', 'id_jenis_biaya')],
            'nominal' => ['required', 'numeric', 'min:0', 'max:9999999999'],
        ]);

        $exists = DB::table('tarif')
            ->where('id_tahun_ajaran', $data['id_tahun_ajaran'])
            ->where('kelompok', $data['kelompok'])
            ->where('id_jenis_biaya', $data['id_jenis_biaya'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'id_jenis_biaya' => 'Tarif untuk tahun ajaran, kelompok, dan jenis biaya tersebut sudah tersedia.',
            ]);
        }

        DB::table('tarif')->insert([
            ...$data,
            'created_at' => now(),
        ]);

        return back()->with('status', 'Tarif biaya berhasil ditambahkan.');
    }

    private function userFor(array $roles): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User && in_array($user->role, $roles, true), 403);

        return $user;
    }

    private function teacherId(User $user): int
    {
        $teacherId = DB::table('guru')->where('id_user', $user->getKey())->value('id_guru');
        abort_unless($teacherId, 403, 'Akun guru belum terhubung ke data guru.');

        return (int) $teacherId;
    }

    private function assertTeacherClass(int $teacherId, int $classId): void
    {
        abort_unless(DB::table('guru_kelas')
            ->where('id_guru', $teacherId)
            ->where('id_kelas', $classId)
            ->exists(), 403);
    }

    private function assertTeacherRoster(int $teacherId, int $rosterId): void
    {
        abort_unless(DB::table('rombel_siswa as r')
            ->join('guru_kelas as gk', 'gk.id_kelas', '=', 'r.id_kelas')
            ->where('r.id_rombel', $rosterId)
            ->where('r.status', 'Aktif')
            ->where('gk.id_guru', $teacherId)
            ->exists(), 403);
    }

    private function familyId(User $user): int
    {
        $familyId = DB::table('keluarga')->where('id_user', $user->getKey())->value('id_keluarga');
        abort_unless($familyId, 403, 'Akun orang tua belum terhubung ke data keluarga.');

        return (int) $familyId;
    }
}
