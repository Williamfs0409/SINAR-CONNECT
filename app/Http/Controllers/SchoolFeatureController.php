<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageUploadService;
use App\Support\PasswordPolicy;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Symfony\Component\HttpFoundation\HeaderUtils;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class SchoolFeatureController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploads) {}

    public function updateStudentProfile(Request $request, int $studentId): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'nik' => ['nullable', 'digits:16'],
            'nisn' => ['nullable', 'digits:10'],
            'no_induk' => ['nullable', 'string', 'max:30'],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'agama' => ['nullable', 'string', 'max:30'],
            'anak_ke' => ['nullable', 'integer', 'min:1', 'max:20'],
            'alamat_domisili' => ['nullable', 'string', 'max:2000'],
        ]);

        $exists = DB::table('siswa')->where('id_siswa', $studentId)->exists();
        abort_unless($exists, 404);

        $payload = [
            'nama_lengkap' => $data['nama_lengkap'],
            'nama_panggilan' => $data['nama_panggilan'] ?? null,
            'tempat_lahir' => $data['tempat_lahir'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'alamat_domisili' => $data['alamat_domisili'] ?? null,
            'updated_at' => now(),
        ];

        foreach (['nik', 'nisn', 'no_induk', 'agama', 'anak_ke'] as $optionalColumn) {
            if (array_key_exists($optionalColumn, $data) && Schema::hasColumn('siswa', $optionalColumn)) {
                $payload[$optionalColumn] = $data[$optionalColumn];
            }
        }

        DB::table('siswa')->where('id_siswa', $studentId)->update($payload);

        return back()->with('status', 'Profil siswa berhasil diperbarui.');
    }

    public function storeStudent(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_keluarga' => ['nullable', 'integer', Rule::exists('keluarga', 'id_keluarga')],
            'nama_ayah' => ['nullable', 'string', 'max:100'],
            'nama_ibu' => ['nullable', 'string', 'max:100'],
            'nomor_telepon' => ['nullable', 'string', 'max:16'],
            'no_kk' => ['nullable', 'digits:16'],
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'nama_panggilan' => ['nullable', 'string', 'max:100'],
            'nik' => ['nullable', 'digits:16', Rule::unique('siswa', 'nik')],
            'nisn' => ['nullable', 'digits:10', Rule::unique('siswa', 'nisn')],
            'no_induk' => ['nullable', 'string', 'max:30', Rule::unique('siswa', 'no_induk')],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['required', 'date', 'before_or_equal:today'],
            'jenis_kelamin' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'agama' => ['nullable', 'string', 'max:30'],
            'anak_ke' => ['nullable', 'integer', 'min:1', 'max:20'],
            'alamat_domisili' => ['nullable', 'string', 'max:2000'],
            'id_kelas' => ['nullable', 'integer', Rule::exists('kelas', 'id_kelas')],
            'id_diskon' => ['nullable', 'integer'],
        ]);

        if (empty($data['id_keluarga']) && ! filled($data['nama_ayah'] ?? null) && ! filled($data['nama_ibu'] ?? null)) {
            throw ValidationException::withMessages([
                'id_keluarga' => 'Pilih keluarga yang sudah ada atau isi minimal nama Ayah/Ibu untuk membuat keluarga baru.',
            ]);
        }

        DB::transaction(function () use ($data): void {
            $familyId = $data['id_keluarga'] ?? null;
            if (! $familyId) {
                $familyId = DB::table('keluarga')->insertGetId([
                    'no_kk' => $data['no_kk'] ?? null,
                    'alamat' => $data['alamat_domisili'] ?? null,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                if (filled($data['nama_ayah'] ?? null)) {
                    DB::table('wali')->insert([
                        'id_keluarga' => $familyId,
                        'hubungan' => 'Ayah',
                        'nama_lengkap' => $data['nama_ayah'],
                        'created_at' => now(),
                    ]);
                }
                if (filled($data['nama_ibu'] ?? null)) {
                    DB::table('wali')->insert([
                        'id_keluarga' => $familyId,
                        'hubungan' => 'Ibu',
                        'nama_lengkap' => $data['nama_ibu'],
                        'created_at' => now(),
                    ]);
                }
                if (filled($data['nomor_telepon'] ?? null)) {
                    DB::table('kontak_keluarga')->insert([
                        'id_keluarga' => $familyId,
                        'nomor' => $data['nomor_telepon'],
                        'urutan' => 1,
                    ]);
                }
            }

            $studentPayload = [
                'id_keluarga' => $familyId,
                'nama_lengkap' => $data['nama_lengkap'],
                'nama_panggilan' => $data['nama_panggilan'] ?? null,
                'tempat_lahir' => $data['tempat_lahir'] ?? null,
                'tanggal_lahir' => $data['tanggal_lahir'],
                'jenis_kelamin' => $data['jenis_kelamin'],
                'alamat_domisili' => $data['alamat_domisili'] ?? null,
                'created_at' => now(),
            ];
            foreach (['nik', 'nisn', 'no_induk', 'agama', 'anak_ke'] as $col) {
                if (Schema::hasColumn('siswa', $col)) {
                    $studentPayload[$col] = $data[$col] ?? ($col === 'agama' ? 'Islam' : null);
                }
            }

            $studentId = DB::table('siswa')->insertGetId($studentPayload);

            if (! empty($data['id_kelas']) && Schema::hasTable('rombel_siswa')) {
                $this->assertClassHasCapacity((int) $data['id_kelas'], $studentId);
                $rombelPayload = [
                    'id_siswa' => $studentId,
                    'id_kelas' => (int) $data['id_kelas'],
                    'status' => 'Aktif',
                    'tanggal_masuk' => now()->toDateString(),
                    'created_at' => now(),
                ];
                if (Schema::hasColumn('rombel_siswa', 'id_diskon')) {
                    $rombelPayload['id_diskon'] = ($data['id_diskon'] ?? null) ?: null;
                }
                DB::table('rombel_siswa')->insert($rombelPayload);
            }
        });

        return back()->with('status', 'Data siswa baru berhasil ditambahkan.');
    }

    public function placeStudent(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'id_diskon' => ['nullable', 'integer'],
        ]);

        DB::transaction(function () use ($data): void {
            $this->assertClassHasCapacity((int) $data['id_kelas'], (int) $data['id_siswa']);

            $activeRombel = DB::table('rombel_siswa')
                ->where('id_siswa', $data['id_siswa'])
                ->where('status', 'Aktif')
                ->lockForUpdate()
                ->first();

            $updatePayload = [
                'id_kelas' => (int) $data['id_kelas'],
                'updated_at' => now(),
            ];
            if (Schema::hasColumn('rombel_siswa', 'id_diskon')) {
                $updatePayload['id_diskon'] = ($data['id_diskon'] ?? null) ?: null;
            }

            if ($activeRombel) {
                DB::table('rombel_siswa')
                    ->where('id_rombel', $activeRombel->id_rombel)
                    ->update($updatePayload);
            } else {
                DB::table('rombel_siswa')->insert([
                    'id_siswa' => (int) $data['id_siswa'],
                    'id_kelas' => (int) $data['id_kelas'],
                    'id_diskon' => Schema::hasColumn('rombel_siswa', 'id_diskon') ? (($data['id_diskon'] ?? null) ?: null) : null,
                    'status' => 'Aktif',
                    'tanggal_masuk' => now()->toDateString(),
                    'created_at' => now(),
                ]);
            }
        });

        return back()->with('status', 'Penempatan kelas dan kategori diskon siswa berhasil diperbarui.');
    }

    public function updateFamilyProfile(Request $request, int $familyId): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'no_kk' => ['nullable', 'digits:16'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'wali_name' => ['nullable', 'string', 'max:100'],
            'hubungan' => ['required_with:wali_name', Rule::in(['Ayah', 'Ibu', 'Wali'])],
            'nomor' => ['nullable', 'string', 'max:16'],
        ]);

        DB::transaction(function () use ($familyId, $data): void {
            $family = DB::table('keluarga')
                ->where('id_keluarga', $familyId)
                ->lockForUpdate()
                ->first();
            abort_unless($family !== null, 404);

            DB::table('keluarga')->where('id_keluarga', $familyId)->update([
                'no_kk' => $data['no_kk'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'updated_at' => now(),
            ]);

            if (filled($data['wali_name'] ?? null)) {
                $matchingRelation = DB::table('wali')
                    ->where('id_keluarga', $familyId)
                    ->where('hubungan', $data['hubungan'])
                    ->first();

                if ($matchingRelation) {
                    DB::table('wali')->where('id_wali', $matchingRelation->id_wali)->update([
                        'nama_lengkap' => $data['wali_name'],
                        'updated_at' => now(),
                    ]);
                } else {
                    $guardianCount = DB::table('wali')->where('id_keluarga', $familyId)->count();
                    $firstGuardian = DB::table('wali')
                        ->where('id_keluarga', $familyId)
                        ->orderBy('id_wali')
                        ->first();

                    if ($guardianCount === 1 && $firstGuardian) {
                        DB::table('wali')->where('id_wali', $firstGuardian->id_wali)->update([
                            'nama_lengkap' => $data['wali_name'],
                            'hubungan' => $data['hubungan'],
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('wali')->insert([
                            'id_keluarga' => $familyId,
                            'nama_lengkap' => $data['wali_name'],
                            'hubungan' => $data['hubungan'],
                            'created_at' => now(),
                        ]);
                    }
                }
            }

            if (filled($data['nomor'] ?? null)) {
                $contact = DB::table('kontak_keluarga')
                    ->where('id_keluarga', $familyId)
                    ->orderBy('urutan')
                    ->first();

                if ($contact) {
                    DB::table('kontak_keluarga')->where('id_kontak', $contact->id_kontak)
                        ->update(['nomor' => $data['nomor']]);
                } else {
                    DB::table('kontak_keluarga')->insert([
                        'id_keluarga' => $familyId,
                        'nomor' => $data['nomor'],
                        'urutan' => 1,
                    ]);
                }
            }
        });

        return back()->with('status', 'Data keluarga berhasil diperbarui.');
    }

    public function storeFamily(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'no_kk' => ['nullable', 'digits:16', Rule::unique('keluarga', 'no_kk')],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'nama_ayah' => ['nullable', 'string', 'max:100'],
            'nama_ibu' => ['nullable', 'string', 'max:100'],
            'nama_wali' => ['nullable', 'string', 'max:100'],
            'nomor' => ['nullable', 'string', 'max:16'],
        ]);

        if (! filled($data['nama_ayah'] ?? null) && ! filled($data['nama_ibu'] ?? null) && ! filled($data['nama_wali'] ?? null)) {
            throw ValidationException::withMessages([
                'nama_ayah' => 'Isi minimal salah satu nama orang tua/wali (Ayah, Ibu, atau Wali).',
            ]);
        }

        DB::transaction(function () use ($data): void {
            $familyId = DB::table('keluarga')->insertGetId([
                'no_kk' => $data['no_kk'] ?? null,
                'alamat' => $data['alamat'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            foreach (['Ayah' => 'nama_ayah', 'Ibu' => 'nama_ibu', 'Wali' => 'nama_wali'] as $relation => $field) {
                if (filled($data[$field] ?? null)) {
                    DB::table('wali')->insert([
                        'id_keluarga' => $familyId,
                        'hubungan' => $relation,
                        'nama_lengkap' => $data[$field],
                        'created_at' => now(),
                    ]);
                }
            }

            if (filled($data['nomor'] ?? null)) {
                DB::table('kontak_keluarga')->insert([
                    'id_keluarga' => $familyId,
                    'nomor' => $data['nomor'],
                    'urutan' => 1,
                ]);
            }
        });

        return back()->with('status', 'Data keluarga baru berhasil ditambahkan.');
    }

    public function updateRegistrationStatus(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'is_open' => ['required', 'boolean'],
        ]);
        abort_unless(
            Schema::hasTable('registration_settings'),
            503,
            'Pengaturan pendaftaran belum tersedia. Terapkan migrasi terlebih dahulu.'
        );
        abort_unless(
            DB::table('registration_settings')->where('id', 1)->exists(),
            503,
            'Pengaturan pendaftaran belum diinisialisasi.'
        );

        DB::table('registration_settings')->where('id', 1)->update([
            'is_open' => (bool) $data['is_open'],
            'updated_at' => now(),
        ]);

        return redirect()
            ->route('dashboard.feature', ['section' => 'pendaftaran'])
            ->with('status', $data['is_open']
                ? 'Pendaftaran siswa baru berhasil dibuka.'
                : 'Pendaftaran siswa baru berhasil ditutup.');
    }

    public function updateUserProfile(Request $request, int $userId): RedirectResponse
    {
        $this->userFor(['admin']);
        abort_unless(DB::table('users')->where('id_user', $userId)->exists(), 404);
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($userId, 'id_user')],
        ]);

        DB::table('users')->where('id_user', $userId)->update([
            ...$data,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Profil akun pengguna berhasil diperbarui.');
    }

    public function destroyStudent(Request $request, int $studentId): RedirectResponse
    {
        $this->userFor(['admin']);
        abort_unless(DB::table('siswa')->where('id_siswa', $studentId)->exists(), 404);

        if (! $request->boolean('force')) {
            $this->assertNoDeleteDependencies($studentId, [
                ['pendaftaran', 'id_siswa', 'pendaftaran'],
                ['rombel_siswa', 'id_siswa', 'riwayat kelas'],
                ['transaksi_tabungan', 'id_siswa', 'transaksi tabungan'],
            ], 'siswa');

            DB::table('siswa')->where('id_siswa', $studentId)->delete();

            return back()->with('status', 'Data siswa berhasil dihapus.');
        }

        // Lindungi siswa yang sudah memiliki transaksi keuangan (tagihan / tabungan)
        if (Schema::hasTable('transaksi_tabungan')
            && DB::table('transaksi_tabungan')->where('id_siswa', $studentId)->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Data siswa tidak dapat dihapus karena masih memiliki riwayat transaksi tabungan.',
            ]);
        }

        $rosterIds = Schema::hasTable('rombel_siswa')
            ? DB::table('rombel_siswa')->where('id_siswa', $studentId)->pluck('id_rombel')->all()
            : [];

        if ($rosterIds !== [] && Schema::hasTable('tagihan')
            && DB::table('tagihan')->whereIn('id_rombel', $rosterIds)->exists()) {
            throw ValidationException::withMessages([
                'delete' => 'Data siswa tidak dapat dihapus karena masih memiliki data tagihan/pembayaran.',
            ]);
        }

        DB::transaction(function () use ($studentId, $rosterIds): void {
            if (Schema::hasTable('pendaftaran')) {
                $registrationIds = DB::table('pendaftaran')
                    ->where('id_siswa', $studentId)
                    ->pluck('id_pendaftaran')
                    ->all();

                if ($registrationIds !== [] && Schema::hasTable('dokumen_pendaftaran')) {
                    $docPaths = DB::table('dokumen_pendaftaran')
                        ->whereIn('id_pendaftaran', $registrationIds)
                        ->pluck('file_path')
                        ->filter()
                        ->all();
                    foreach ($docPaths as $path) {
                        Storage::disk('local')->delete($path);
                    }
                    DB::table('dokumen_pendaftaran')->whereIn('id_pendaftaran', $registrationIds)->delete();
                }

                DB::table('pendaftaran')->where('id_siswa', $studentId)->delete();
            }

            if ($rosterIds !== []) {
                foreach (['absensi', 'nilai_iqro', 'pengumpulan_tugas', 'portofolio', 'rapor'] as $table) {
                    if (Schema::hasTable($table)) {
                        DB::table($table)->whereIn('id_rombel', $rosterIds)->delete();
                    }
                }
                DB::table('rombel_siswa')->whereIn('id_rombel', $rosterIds)->delete();
            }

            DB::table('siswa')->where('id_siswa', $studentId)->delete();
        });

        return back()->with('status', 'Data siswa berhasil dihapus.');
    }

    public function destroyFamily(int $familyId): RedirectResponse
    {
        $this->userFor(['admin']);
        $family = DB::table('keluarga')->where('id_keluarga', $familyId)->first();
        abort_unless($family !== null, 404);

        if ($family->id_user !== null) {
            throw ValidationException::withMessages([
                'delete' => 'Data keluarga masih terhubung ke akun orang tua. Lepaskan hubungan akun terlebih dahulu.',
            ]);
        }

        $this->assertNoDeleteDependencies($familyId, [
            ['siswa', 'id_keluarga', 'data siswa'],
            ['kontak_keluarga', 'id_keluarga', 'kontak keluarga'],
            ['wali', 'id_keluarga', 'data wali'],
        ], 'keluarga');

        DB::table('keluarga')->where('id_keluarga', $familyId)->delete();

        return back()->with('status', 'Data keluarga berhasil dihapus.');
    }

    public function destroyUser(int $userId): RedirectResponse
    {
        $this->userFor(['admin']);
        $target = DB::table('users')->where('id_user', $userId)->first();
        abort_unless($target !== null, 404);

        if ((int) auth()->id() === $userId) {
            throw ValidationException::withMessages([
                'delete' => 'Akun yang sedang digunakan tidak dapat dihapus.',
            ]);
        }

        if ($target->role === 'admin'
            && DB::table('users')->where('role', 'admin')->where('is_active', true)->count() <= 1) {
            throw ValidationException::withMessages([
                'delete' => 'Administrator aktif terakhir tidak dapat dihapus.',
            ]);
        }

        $this->assertNoDeleteDependencies($userId, [
            ['keluarga', 'id_user', 'data keluarga'],
            ['guru', 'id_user', 'data guru'],
            ['absensi', 'id_user_pencatat', 'catatan absensi'],
            ['dokumentasi_kegiatan', 'id_user_upload', 'dokumentasi kegiatan'],
            ['kegiatan', 'id_user_pembuat', 'kegiatan'],
            ['pembayaran', 'id_user_pencatat', 'catatan pembayaran'],
            ['pembayaran', 'id_user_verifikator', 'verifikasi pembayaran'],
            ['pendaftaran', 'id_user_verifikator', 'verifikasi pendaftaran'],
            ['transaksi_tabungan', 'id_user_pencatat', 'catatan transaksi tabungan'],
            ['audit_log', 'app_user_id', 'riwayat perubahan'],
            ['sessions', 'user_id', 'sesi login'],
        ], 'akun pengguna');

        DB::table('users')->where('id_user', $userId)->delete();

        return back()->with('status', 'Akun pengguna berhasil dihapus.');
    }

    public function reviewRegistration(Request $request, int $registrationId): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verify', 'accept', 'reject'])],
            'class_id' => ['nullable', 'integer'],
            'teacher_id' => ['nullable', 'integer'],
            'due_date' => ['nullable', 'date'],
            'note' => ['nullable', 'string', 'max:255'],
            'documents' => ['nullable', 'array'],
            'documents.*.status_verifikasi' => ['required', Rule::in(['Valid', 'Tidak Valid'])],
            'documents.*.catatan' => ['nullable', 'string', 'max:255'],
        ]);

        if (! empty($data['documents'])) {
            $applicationExists = DB::table('pendaftaran')
                ->where('id_pendaftaran', $registrationId)
                ->exists();
            abort_unless($applicationExists, 404);

            foreach ($data['documents'] as $documentId => $documentData) {
                DB::table('dokumen_pendaftaran')
                    ->where('id_dokumen', (int) $documentId)
                    ->where('id_pendaftaran', $registrationId)
                    ->update([
                        'status_verifikasi' => $documentData['status_verifikasi'],
                        'catatan' => $documentData['catatan'] ?? null,
                        'updated_at' => now(),
                    ]);
            }
        }

        try {
            DB::transaction(function () use ($data, $registrationId, $user): void {
                $application = DB::table('pendaftaran')
                    ->where('id_pendaftaran', $registrationId)
                    ->lockForUpdate()
                    ->first();
                abort_unless($application !== null, 404);

                if ($data['decision'] === 'verify') {
                    if ($application->status !== 'Diajukan') {
                        throw ValidationException::withMessages(['decision' => 'Hanya pendaftaran yang diajukan dapat diverifikasi.']);
                    }

                    // 1-klik Verifikasi: otomatis validkan berkas 'Menunggu' jika seluruh berkas wajib sudah diunggah dan tidak ada yang 'Tidak Valid'
                    if (Schema::hasTable('dokumen_pendaftaran')) {
                        $requiredDocs = array_values(config('registration.required_documents'));
                        $uploadedTypes = DB::table('dokumen_pendaftaran')
                            ->where('id_pendaftaran', $registrationId)
                            ->pluck('jenis')
                            ->all();
                        $hasInvalid = DB::table('dokumen_pendaftaran')
                            ->where('id_pendaftaran', $registrationId)
                            ->where('status_verifikasi', 'Tidak Valid')
                            ->exists();

                        if (! $hasInvalid && array_diff($requiredDocs, $uploadedTypes) === []) {
                            DB::table('dokumen_pendaftaran')
                                ->where('id_pendaftaran', $registrationId)
                                ->where('status_verifikasi', 'Menunggu')
                                ->update([
                                    'status_verifikasi' => 'Valid',
                                    'updated_at' => now(),
                                ]);
                        }
                    }

                    $this->assertRegistrationDocumentsValid($registrationId);

                    DB::table('pendaftaran')->where('id_pendaftaran', $registrationId)->update([
                        'status' => 'Diverifikasi',
                        'catatan_verifikasi' => $data['note'] ?? null,
                        'id_user_verifikator' => $user->getKey(),
                        'tanggal_verifikasi' => now(),
                    ]);

                    return;
                }

                if ($data['decision'] === 'reject') {
                    if (! \in_array($application->status, ['Diajukan', 'Diverifikasi'], true)) {
                        throw ValidationException::withMessages(['decision' => 'Status pendaftaran ini tidak dapat ditolak.']);
                    }

                    $rejectNote = isset($data['note']) && trim((string) $data['note']) !== ''
                        ? trim((string) $data['note'])
                        : null;

                    DB::table('dokumen_pendaftaran')
                        ->where('id_pendaftaran', $registrationId)
                        ->update([
                            'status_verifikasi' => 'Tidak Valid',
                            'updated_at' => now(),
                        ]);

                    if ($rejectNote !== null) {
                        DB::table('dokumen_pendaftaran')
                            ->where('id_pendaftaran', $registrationId)
                            ->where(function ($query): void {
                                $query->whereNull('catatan')->orWhere('catatan', '');
                            })
                            ->update([
                                'catatan' => $rejectNote,
                                'updated_at' => now(),
                            ]);
                    }

                    DB::table('pendaftaran')->where('id_pendaftaran', $registrationId)->update([
                        'status' => 'Ditolak',
                        'catatan_verifikasi' => $rejectNote,
                        'id_user_verifikator' => $user->getKey(),
                        'tanggal_verifikasi' => now(),
                    ]);

                    return;
                }

                if ($application->status !== 'Diverifikasi') {
                    throw ValidationException::withMessages(['decision' => 'Pendaftaran harus diverifikasi sebelum diterima.']);
                }
                $this->assertRegistrationDocumentsValid($registrationId);

                $class = DB::table('kelas')
                    ->where('id_kelas', $data['class_id'] ?? 0)
                    ->where('id_tahun_ajaran', $application->id_tahun_ajaran)
                    ->where('kelompok', $application->kelompok)
                    ->where('nama_kelas', '<>', 'Belum Ditentukan')
                    ->first();
                if (! $class) {
                    throw ValidationException::withMessages(['class_id' => 'Pilih kelas definitif yang sesuai dengan tahun ajaran dan kelompok pendaftar.']);
                }

                $existingRoster = DB::table('rombel_siswa as r')
                    ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                    ->where('r.id_siswa', $application->id_siswa)
                    ->where('k.id_tahun_ajaran', $application->id_tahun_ajaran)
                    ->select(['r.id_rombel', 'r.id_kelas'])
                    ->first();

                if ($existingRoster) {
                    DB::table('rombel_siswa')
                        ->where('id_rombel', $existingRoster->id_rombel)
                        ->update([
                            'id_kelas' => $class->id_kelas,
                            'status' => 'Aktif',
                            'tanggal_masuk' => now()->toDateString(),
                            'updated_at' => now(),
                        ]);
                    $rosterId = $existingRoster->id_rombel;
                } else {
                    $rosterId = DB::table('rombel_siswa')->insertGetId([
                        'id_siswa' => $application->id_siswa,
                        'id_kelas' => $class->id_kelas,
                        'status' => 'Aktif',
                        'tanggal_masuk' => now()->toDateString(),
                        'created_at' => now(),
                    ]);
                }

                if (! empty($data['teacher_id']) && Schema::hasTable('guru_kelas') && Schema::hasTable('guru')) {
                    $teacherId = (int) $data['teacher_id'];
                    $teacherExists = DB::table('guru')
                        ->where('id_guru', $teacherId)
                        ->where('status', 'Aktif')
                        ->exists();
                    if ($teacherExists) {
                        $samePairExists = DB::table('guru_kelas')
                            ->where('id_kelas', $class->id_kelas)
                            ->where('id_guru', $teacherId)
                            ->exists();

                        if ($samePairExists) {
                            DB::table('guru_kelas')
                                ->where('id_kelas', $class->id_kelas)
                                ->where('id_guru', $teacherId)
                                ->update(['peran' => 'Wali Kelas']);
                        } else {
                            DB::table('guru_kelas')
                                ->where('id_kelas', $class->id_kelas)
                                ->where('peran', 'Wali Kelas')
                                ->delete();

                            DB::table('guru_kelas')->insert([
                                'id_guru' => $teacherId,
                                'id_kelas' => $class->id_kelas,
                                'peran' => 'Wali Kelas',
                            ]);
                        }
                    }
                }

                if (! empty($data['due_date']) && $rosterId) {
                    $registrationFees = DB::table('tarif as tf')
                        ->join('jenis_biaya as jb', 'jb.id_jenis_biaya', '=', 'tf.id_jenis_biaya')
                        ->where('tf.id_tahun_ajaran', $application->id_tahun_ajaran)
                        ->where('tf.kelompok', $application->kelompok)
                        ->where('jb.kategori', 'Pendaftaran')
                        ->get(['tf.id_jenis_biaya', 'tf.nominal']);

                    foreach ($registrationFees as $fee) {
                        $existsBill = DB::table('tagihan')
                            ->where('id_rombel', $rosterId)
                            ->where('id_jenis_biaya', $fee->id_jenis_biaya)
                            ->whereNull('periode')
                            ->whereNull('id_kegiatan')
                            ->exists();

                        if (! $existsBill) {
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
                }

                DB::table('pendaftaran')->where('id_pendaftaran', $registrationId)->update([
                    'status' => 'Diterima',
                    'catatan_verifikasi' => $data['note'] ?? null,
                    'id_user_verifikator' => $user->getKey(),
                    'tanggal_verifikasi' => now(),
                ]);
            });
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? '';
            $message = $exception->errorInfo[2] ?? $exception->getMessage();
            if ($sqlState === '45000' || str_contains($message, '1644')) {
                $cleanMessage = preg_replace('/^.*1644\s*/', '', (string) $message);
                throw ValidationException::withMessages(['decision' => $cleanMessage ?: 'Keputusan pendaftaran tidak dapat diproses.']);
            }
            throw $exception;
        }

        $statusMessage = match ($data['decision']) {
            'verify' => 'Berkas pendaftaran berhasil diverifikasi. Silakan pilih kelas dan guru pada form penetapan di bawah.',
            'accept' => 'Calon siswa berhasil ditetapkan dan diterima ke kelas.',
            'reject' => 'Pendaftaran telah ditolak.',
        };

        return back()->with('status', $statusMessage);
    }

    public function uploadRegistrationDocuments(Request $request, int $registrationId): RedirectResponse
    {
        $user = $this->userFor(['parent']);
        $requiredDocuments = config('registration.required_documents');
        $rules = [
            'documents' => ['required', 'array:'.implode(',', array_keys($requiredDocuments)), 'min:1'],
        ];
        $messages = [];

        foreach ($requiredDocuments as $key => $label) {
            $rules["documents.{$key}"] = ['sometimes', 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $messages["documents.{$key}.mimes"] = "Berkas {$label} harus berformat PDF, JPG, atau PNG.";
            $messages["documents.{$key}.max"] = "Ukuran berkas {$label} maksimal 5 MB.";
        }

        $data = $request->validate($rules, $messages);
        $familyId = $this->familyId($user);
        $storedPaths = [];

        try {
            $previousPaths = DB::transaction(function () use ($data, $familyId, $registrationId, $requiredDocuments, &$storedPaths): array {
                $application = DB::table('pendaftaran as p')
                    ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
                    ->where('p.id_pendaftaran', $registrationId)
                    ->where('s.id_keluarga', $familyId)
                    ->lockForUpdate()
                    ->first(['p.id_pendaftaran', 'p.status']);
                abort_unless($application !== null, 404);

                if (! \in_array($application->status, ['Diajukan', 'Diverifikasi'], true)) {
                    throw ValidationException::withMessages([
                        'documents' => 'Dokumen hanya dapat diperbarui selama pendaftaran masih diproses.',
                    ]);
                }

                $previousPaths = [];
                foreach ($data['documents'] as $key => $file) {
                    $label = $requiredDocuments[$key];
                    $document = DB::table('dokumen_pendaftaran')
                        ->where('id_pendaftaran', $registrationId)
                        ->where('jenis', $label)
                        ->lockForUpdate()
                        ->first();

                    if ($document && $document->status_verifikasi !== 'Tidak Valid') {
                        throw ValidationException::withMessages([
                            "documents.{$key}" => "Berkas {$label} tidak perlu diganti saat statusnya {$document->status_verifikasi}.",
                        ]);
                    }

                    $path = $this->imageUploads->store($file, 'pendaftaran', "documents.{$key}");
                    $storedPaths[] = $path;

                    if ($document) {
                        $previousPaths[] = $document->file_path;
                        DB::table('dokumen_pendaftaran')->where('id_dokumen', $document->id_dokumen)->update([
                            'file_path' => $path,
                            'status_verifikasi' => 'Menunggu',
                            'catatan' => null,
                            'updated_at' => now(),
                        ]);
                    } else {
                        DB::table('dokumen_pendaftaran')->insert([
                            'id_pendaftaran' => $registrationId,
                            'jenis' => $label,
                            'file_path' => $path,
                            'status_verifikasi' => 'Menunggu',
                            'created_at' => now(),
                        ]);
                    }
                }

                return $previousPaths;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                if (! Storage::disk('local')->delete($path)) {
                    report(new RuntimeException('Failed to clean up a replaced registration document after upload failed.'));
                }
            }

            throw $exception;
        }

        foreach ($previousPaths as $path) {
            if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
                report(new RuntimeException('Failed to remove a superseded registration document.'));
            }
        }

        return back()->with('status', 'Dokumen pendaftaran berhasil diunggah dan menunggu pemeriksaan admin.');
    }

    public function resubmitRegistration(Request $request, int $registrationId): RedirectResponse
    {
        $user = $this->userFor(['parent']);
        $requiredDocuments = config('registration.required_documents');
        $rules = [
            'student_name' => ['required', 'string', 'max:100'],
            'student_nickname' => ['nullable', 'string', 'max:100'],
            'student_gender' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'student_birthplace' => ['nullable', 'string', 'max:100'],
            'student_birthdate' => ['required', 'date', 'before_or_equal:today'],
            'group' => ['required', Rule::in(['KB', 'TK A', 'TK B'])],
            'family_address' => ['required', 'string', 'max:2000'],
            'documents' => ['nullable', 'array:'.implode(',', array_keys($requiredDocuments))],
        ];
        $messages = [
            'student_birthdate.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
        ];

        foreach ($requiredDocuments as $key => $label) {
            $rules["documents.{$key}"] = ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $messages["documents.{$key}.mimes"] = "Berkas {$label} harus berformat PDF, JPG, atau PNG.";
            $messages["documents.{$key}.max"] = "Ukuran berkas {$label} maksimal 5 MB.";
        }

        $data = $request->validate($rules, $messages);
        $familyId = $this->familyId($user);
        $storedPaths = [];

        try {
            $previousPaths = DB::transaction(function () use ($data, $familyId, $registrationId, $requiredDocuments, &$storedPaths): array {
                $application = DB::table('pendaftaran as p')
                    ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
                    ->where('p.id_pendaftaran', $registrationId)
                    ->where('s.id_keluarga', $familyId)
                    ->lockForUpdate()
                    ->first(['p.id_pendaftaran', 'p.id_siswa', 'p.status']);
                abort_unless($application !== null, 404);

                if ($application->status !== 'Ditolak') {
                    throw ValidationException::withMessages([
                        'registration' => 'Hanya pendaftaran yang ditolak yang dapat diajukan ulang.',
                    ]);
                }

                $existingDocuments = DB::table('dokumen_pendaftaran')
                    ->where('id_pendaftaran', $registrationId)
                    ->lockForUpdate()
                    ->get()
                    ->keyBy('jenis');

                $uploadedFiles = $data['documents'] ?? [];
                $missingErrors = [];
                foreach ($requiredDocuments as $key => $label) {
                    $existing = $existingDocuments->get($label);
                    $hasNewFile = isset($uploadedFiles[$key]) && $uploadedFiles[$key] !== null;

                    if (! $hasNewFile && ! $existing) {
                        $missingErrors["documents.{$key}"] = "Berkas {$label} wajib diunggah sebelum mengajukan pendaftaran kembali.";
                    }
                }

                if ($missingErrors !== []) {
                    throw ValidationException::withMessages($missingErrors);
                }

                DB::table('siswa')
                    ->where('id_siswa', $application->id_siswa)
                    ->update([
                        'nama_lengkap' => $data['student_name'],
                        'nama_panggilan' => $data['student_nickname'] ?? null,
                        'tempat_lahir' => $data['student_birthplace'] ?? null,
                        'tanggal_lahir' => $data['student_birthdate'],
                        'jenis_kelamin' => $data['student_gender'],
                        'alamat_domisili' => $data['family_address'],
                        'updated_at' => now(),
                    ]);

                DB::table('keluarga')
                    ->where('id_keluarga', $familyId)
                    ->update([
                        'alamat' => $data['family_address'],
                        'updated_at' => now(),
                    ]);

                $previousPaths = [];
                $replacedLabels = [];
                foreach ($uploadedFiles as $key => $file) {
                    if ($file === null) {
                        continue;
                    }

                    $label = $requiredDocuments[$key];
                    $replacedLabels[] = $label;
                    $existing = $existingDocuments->get($label);
                    $path = $this->imageUploads->store($file, 'pendaftaran', "documents.{$key}");
                    $storedPaths[] = $path;

                    if ($existing) {
                        $previousPaths[] = $existing->file_path;
                        DB::table('dokumen_pendaftaran')
                            ->where('id_dokumen', $existing->id_dokumen)
                            ->update([
                                'file_path' => $path,
                                'status_verifikasi' => 'Menunggu',
                                'catatan' => null,
                                'updated_at' => now(),
                            ]);
                    } else {
                        DB::table('dokumen_pendaftaran')->insert([
                            'id_pendaftaran' => $registrationId,
                            'jenis' => $label,
                            'file_path' => $path,
                            'status_verifikasi' => 'Menunggu',
                            'created_at' => now(),
                        ]);
                    }
                }

                $keptQuery = DB::table('dokumen_pendaftaran')
                    ->where('id_pendaftaran', $registrationId)
                    ->where('status_verifikasi', 'Tidak Valid');
                if ($replacedLabels !== []) {
                    $keptQuery->whereNotIn('jenis', $replacedLabels);
                }
                $keptQuery->update([
                    'status_verifikasi' => 'Menunggu',
                    'catatan' => null,
                    'updated_at' => now(),
                ]);

                DB::table('pendaftaran')
                    ->where('id_pendaftaran', $registrationId)
                    ->update([
                        'kelompok' => $data['group'],
                        'status' => 'Diajukan',
                        'catatan_verifikasi' => null,
                        'id_user_verifikator' => null,
                        'tanggal_verifikasi' => null,
                        'updated_at' => now(),
                    ]);

                return $previousPaths;
            });
        } catch (Throwable $exception) {
            foreach ($storedPaths as $path) {
                if (! Storage::disk('local')->delete($path)) {
                    report(new RuntimeException('Failed to clean up a registration document after resubmission failed.'));
                }
            }

            throw $exception;
        }

        foreach ($previousPaths as $path) {
            if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
                report(new RuntimeException('Failed to remove a superseded registration document after resubmission.'));
            }
        }

        return back()->with('status', 'Pendaftaran berhasil diajukan ulang dan sedang menunggu verifikasi admin.');
    }

    public function verifyRegistrationDocument(Request $request, int $documentId): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'status_verifikasi' => ['required', Rule::in(['Valid', 'Tidak Valid'])],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        $document = DB::table('dokumen_pendaftaran')
            ->where('id_dokumen', $documentId)
            ->first();
        abort_unless($document !== null, 404);
        DB::table('dokumen_pendaftaran')
            ->where('id_dokumen', $documentId)
            ->update([
                'status_verifikasi' => $data['status_verifikasi'],
                'catatan' => $data['catatan'] ?? null,
                'updated_at' => now(),
            ]);

        return back()->with('status', 'Pemeriksaan dokumen berhasil disimpan.');
    }

    public function initializeAttendance(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
        ]);

        if (DB::table('kelas')
            ->where('id_kelas', $data['id_kelas'])
            ->where('nama_kelas', 'Belum Ditentukan')
            ->exists()) {
            throw ValidationException::withMessages([
                'id_kelas' => 'Pilih kelas definitif yang sudah ditentukan.',
            ]);
        }

        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $this->assertTeacherClass($teacherId, (int) $data['id_kelas']);
        }

        $inserted = DB::transaction(function () use ($data, $user): int {
            $rosterIds = DB::table('rombel_siswa')
                ->where('id_kelas', $data['id_kelas'])
                ->where('status', 'Aktif')
                ->pluck('id_rombel')
                ->map(fn ($id): int => (int) $id)
                ->all();

            $count = 0;
            foreach ($rosterIds as $rosterId) {
                $exists = DB::table('absensi')
                    ->where('id_rombel', $rosterId)
                    ->whereDate('tanggal', $data['tanggal'])
                    ->exists();
                if ($exists) {
                    continue;
                }

                DB::table('absensi')->insert([
                    'id_rombel' => $rosterId,
                    'tanggal' => $data['tanggal'],
                    'status' => 'Hadir',
                    'keterangan' => 'Inisialisasi kehadiran harian',
                    'id_user_pencatat' => $user->getKey(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $count++;
            }

            return $count;
        });

        return back()->with('status', $inserted.' siswa berhasil diinisialisasi hadir untuk tanggal '.$data['tanggal'].'.');
    }

    public function storeAttendance(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'attendance' => ['required', 'array', 'min:1'],
            'attendance.*.status' => ['required', Rule::in(['Hadir', 'Izin', 'Sakit', 'Alfa'])],
            'attendance.*.keterangan' => ['nullable', 'string', 'max:255'],
        ]);
        if (DB::table('kelas')
            ->where('id_kelas', $data['id_kelas'])
            ->where('nama_kelas', 'Belum Ditentukan')
            ->exists()) {
            throw ValidationException::withMessages([
                'id_kelas' => 'Pilih kelas yang sudah ditentukan.',
            ]);
        }

        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $this->assertTeacherClass($teacherId, (int) $data['id_kelas']);
        }
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
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'jenis' => ['nullable', Rule::in(['RPPH', 'RPPM', 'Modul Ajar'])],
            'tanggal' => ['required', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after_or_equal:tanggal'],
            'tema' => ['required', 'string', 'max:150'],
            'subtema' => ['nullable', 'string', 'max:150'],
            'tujuan' => ['nullable', 'string', 'max:5000'],
            'kegiatan' => ['nullable', 'string', 'max:5000'],
            'alat_bahan' => ['nullable', 'string', 'max:3000'],
            'status' => ['required', Rule::in(['Draft', 'Final'])],
        ]);

        $teacherId = $this->resolveTeacherIdForClass($user, (int) $data['id_kelas']);
        $payload = [
            'id_kelas' => $data['id_kelas'],
            'id_guru' => $teacherId,
            'tanggal' => $data['tanggal'],
            'tema' => $data['tema'],
            'subtema' => $data['subtema'] ?? null,
            'kegiatan' => $data['kegiatan'] ?? null,
            'alat_bahan' => $data['alat_bahan'] ?? null,
            'status' => $data['status'],
            'created_at' => now(),
        ];
        if (Schema::hasColumn('rencana_pembelajaran', 'tujuan')) {
            $payload['tujuan'] = $data['tujuan'] ?? null;
        }
        if (Schema::hasColumn('rencana_pembelajaran', 'jenis') && filled($data['jenis'] ?? null)) {
            $payload['jenis'] = $data['jenis'];
        }
        if (Schema::hasColumn('rencana_pembelajaran', 'tanggal_selesai')) {
            $payload['tanggal_selesai'] = $data['tanggal_selesai'] ?? null;
        }

        DB::table('rencana_pembelajaran')->insert($payload);

        return back()->with('status', 'Rencana pembelajaran berhasil disimpan.');
    }

    public function storeTask(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'judul' => ['required', 'string', 'max:150'],
            'kategori' => ['nullable', 'string', 'max:100'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
            'tanggal_diberikan' => ['required', 'date', 'before_or_equal:today'],
            'tenggat' => ['required', 'date', 'after_or_equal:tanggal_diberikan'],
            'lampiran' => ['nullable', 'file', 'max:5120'],
        ], [
            'lampiran.max' => 'Ukuran lampiran tugas melebihi batas maksimal 5 MB.',
            'lampiran.uploaded' => 'Lampiran gagal diunggah. Pastikan ukurannya tidak melebihi 5 MB.',
            'tenggat.after_or_equal' => 'Tenggat harus sama atau setelah tanggal diberikan.',
        ]);

        $schoolClass = DB::table('kelas')->where('id_kelas', $data['id_kelas'])->first();
        if (! $schoolClass || $schoolClass->nama_kelas === 'Belum Ditentukan') {
            throw ValidationException::withMessages([
                'id_kelas' => 'Pilih kelas definitif.',
            ]);
        }
        if ($schoolClass->kelompok === 'KB') {
            throw ValidationException::withMessages([
                'id_kelas' => 'Kelompok Bermain (PG) tidak diberi tugas. Pilih kelas TK A atau TK B.',
            ]);
        }

        $teacherId = $this->resolveTeacherIdForClass($user, (int) $data['id_kelas']);
        $path = $request->hasFile('lampiran')
            ? $this->imageUploads->store($request->file('lampiran'), 'tugas', 'lampiran')
            : null;

        $payload = [
            'id_kelas' => $data['id_kelas'],
            'id_guru' => $teacherId,
            'judul' => $data['judul'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'tanggal_diberikan' => $data['tanggal_diberikan'],
            'tenggat' => $data['tenggat'],
            'lampiran' => $path,
            'created_at' => now(),
        ];
        if (Schema::hasColumn('tugas', 'kategori')) {
            $payload['kategori'] = $data['kategori'] ?? null;
        }

        DB::table('tugas')->insert($payload);

        return back()->with('status', 'Tugas berhasil dibuat.');
    }

    public function submitTask(Request $request, int $taskId): RedirectResponse
    {
        $user = $this->userFor(['parent']);
        $data = $request->validate([
            'id_siswa' => ['required', 'integer'],
            'catatan_ortu' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:5120'],
        ], [
            'file.max' => 'Ukuran file tugas melebihi batas maksimal 5 MB.',
            'file.uploaded' => 'File gagal diunggah. Pastikan ukurannya tidak melebihi 5 MB.',
        ]);

        $familyId = $this->familyId($user);
        $task = DB::table('tugas')->where('id_tugas', $taskId)->first();
        abort_unless($task !== null, 404);
        $roster = DB::table('rombel_siswa as r')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->where('s.id_keluarga', $familyId)
            ->where('s.id_siswa', $data['id_siswa'])
            ->where('r.id_kelas', $task->id_kelas)
            ->where('r.status', 'Aktif')
            ->first(['r.id_rombel']);
        abort_unless($roster !== null, 403);

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
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'nilai' => ['required', Rule::in(['BB', 'MB', 'BSH', 'BSB'])],
            'umpan_balik' => ['nullable', 'string', 'max:5000'],
        ]);
        $submission = DB::table('pengumpulan_tugas as pt')
            ->join('tugas as t', 't.id_tugas', '=', 'pt.id_tugas')
            ->where('pt.id_pengumpulan', $submissionId)
            ->first(['pt.id_pengumpulan', 't.id_kelas']);
        abort_unless($submission !== null, 404);
        $teacherId = $this->resolveTeacherIdForClass($user, (int) $submission->id_kelas);

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
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'id_rombel' => ['required', 'integer', Rule::exists('rombel_siswa', 'id_rombel')],
            'id_indikator' => ['required', 'integer', Rule::exists('indikator_iqro', 'id_indikator')],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'skala' => ['required', Rule::in(['BB', 'MB', 'BSH', 'BSB'])],
        ]);

        $this->resolveTeacherIdForRoster($user, (int) $data['id_rombel']);
        $rosterClass = DB::table('rombel_siswa as r')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->where('r.id_rombel', $data['id_rombel'])
            ->where('r.status', 'Aktif')
            ->first(['k.kelompok']);
        abort_unless($rosterClass !== null, 403);

        $indicator = DB::table('indikator_iqro')
            ->where('id_indikator', $data['id_indikator'])
            ->first(['kelompok']);
        if (! $indicator || $indicator->kelompok !== $rosterClass->kelompok) {
            throw ValidationException::withMessages([
                'id_indikator' => "Indikator Iqro harus sesuai dengan kelompok kelas siswa ({$rosterClass->kelompok}).",
            ]);
        }

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
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'id_rombel' => ['required', 'integer', Rule::exists('rombel_siswa', 'id_rombel')],
            'semester' => ['required', Rule::in(['Ganjil', 'Genap'])],
            'id_guru_iqro' => ['nullable', 'integer', Rule::exists('guru', 'id_guru')],
            'tanggal_rapor' => ['nullable', 'date'],
            'tinggi_badan' => ['nullable', 'numeric', 'min:30', 'max:180'],
            'berat_badan' => ['nullable', 'numeric', 'min:5', 'max:120'],
            'narasi_agama_budi_pekerti' => ['nullable', 'string', 'max:5000'],
            'narasi_jati_diri' => ['nullable', 'string', 'max:5000'],
            'narasi_literasi_steam' => ['nullable', 'string', 'max:5000'],
            'narasi_p5' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['Draft', 'Final'])],
        ]);

        if ($data['status'] === 'Final') {
            $missingNarratives = [];
            foreach ([
                'narasi_agama_budi_pekerti' => 'Narasi Agama & Budi Pekerti wajib diisi untuk rapor Final.',
                'narasi_jati_diri' => 'Narasi Jati Diri wajib diisi untuk rapor Final.',
                'narasi_literasi_steam' => 'Narasi Literasi & STEAM wajib diisi untuk rapor Final.',
                'narasi_p5' => 'Narasi P5 wajib diisi untuk rapor Final.',
            ] as $field => $message) {
                if (! filled($data[$field] ?? null)) {
                    $missingNarratives[$field] = $message;
                }
            }
            if ($missingNarratives !== []) {
                throw ValidationException::withMessages($missingNarratives);
            }
            if (empty($data['tanggal_rapor'])) {
                $data['tanggal_rapor'] = now()->toDateString();
            }
        }

        $teacherId = $this->resolveTeacherIdForRoster($user, (int) $data['id_rombel']);
        $existing = DB::table('rapor')
            ->where('id_rombel', $data['id_rombel'])
            ->where('semester', $data['semester'])
            ->first();
        if ($existing?->status === 'Final' && $data['status'] === 'Final') {
            throw ValidationException::withMessages([
                'semester' => 'Rapor final terkunci dan tidak dapat diubah. Pilih status Draft terlebih dahulu jika ingin membuka kunci.',
            ]);
        }

        $values = collect($data)->except(['id_rombel', 'id_guru_iqro'])->all() + [
            'id_guru_kelas' => $teacherId,
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('rapor', 'id_guru_iqro')) {
            $values['id_guru_iqro'] = $data['id_guru_iqro'] ?? ($existing->id_guru_iqro ?? $teacherId);
        }

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
        abort_unless($report !== null, 404);

        DB::table('rapor')->where('id_rapor', $reportId)->update([
            'refleksi_orang_tua' => $data['refleksi_orang_tua'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Refleksi orang tua berhasil disimpan.');
    }

    public function storePortfolio(Request $request): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'id_rombel' => ['required', 'integer', Rule::exists('rombel_siswa', 'id_rombel')],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'narasi' => ['required', 'string', 'max:5000'],
        ], [
            'foto.max' => 'Ukuran foto melebihi batas maksimal 5 MB.',
            'foto.image' => 'File yang dipilih harus berupa gambar (JPG, PNG, atau WEBP).',
            'foto.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
        ]);
        $teacherId = $this->resolveTeacherIdForRoster($user, (int) $data['id_rombel']);
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
        $user = $this->userFor(['teacher', 'admin']);
        $activeYearId = DB::table('tahun_ajaran')
            ->where('is_aktif', true)
            ->value('id_tahun_ajaran');
        if ($activeYearId === null) {
            throw ValidationException::withMessages([
                'id_kelas' => 'Tahun ajaran aktif belum ditentukan.',
            ]);
        }

        $data = $request->validate([
            'id_kelas' => [
                'required',
                'integer',
                Rule::exists('kelas', 'id_kelas')->where('id_tahun_ajaran', $activeYearId),
            ],
            'nama_kegiatan' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(['Dalam Kelas', 'Luar Kelas', 'Field Trip'])],
            'tanggal' => ['required', 'date'],
            'lokasi' => ['nullable', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ]);
        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $this->assertTeacherClass($teacherId, (int) $data['id_kelas']);
        }

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

    public function updateActivityCost(Request $request, int $activityId): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'biaya' => ['required', 'numeric', 'min:0.01', 'max:9999999999'],
        ]);

        DB::transaction(function () use ($activityId, $data): void {
            $activity = DB::table('kegiatan')
                ->where('id_kegiatan', $activityId)
                ->lockForUpdate()
                ->first();
            abort_unless($activity !== null, 404);

            if ($activity->jenis !== 'Field Trip') {
                throw ValidationException::withMessages([
                    'biaya' => 'Biaya hanya dapat ditetapkan untuk kegiatan Field Trip.',
                ]);
            }

            $hasBills = DB::table('tagihan')->where('id_kegiatan', $activityId)->exists();
            if ($hasBills && (float) $activity->biaya !== (float) $data['biaya']) {
                throw ValidationException::withMessages([
                    'biaya' => 'Biaya tidak dapat diubah setelah tagihan kegiatan diterbitkan.',
                ]);
            }

            DB::table('kegiatan')->where('id_kegiatan', $activityId)->update([
                'biaya' => $data['biaya'],
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', 'Biaya Field Trip berhasil disimpan.');
    }

    public function generateActivityBills(int $activityId): RedirectResponse
    {
        $this->userFor(['admin']);
        $activity = DB::table('kegiatan')->where('id_kegiatan', $activityId)->first();
        abort_unless($activity !== null, 404);

        if ($activity->jenis !== 'Field Trip') {
            throw ValidationException::withMessages([
                'activity' => 'Tagihan kegiatan hanya dapat dibuat untuk Field Trip.',
            ]);
        }
        if ($activity->biaya === null || (float) $activity->biaya <= 0) {
            throw ValidationException::withMessages([
                'activity' => 'Tetapkan biaya Field Trip sebelum menerbitkan tagihan.',
            ]);
        }

        $result = DB::select('CALL sp_buat_tagihan_kegiatan(?)', [$activityId]);
        if (! isset($result[0]->tagihan_dibuat)) {
            throw new RuntimeException('Prosedur tagihan Field Trip tidak mengembalikan jumlah tagihan.');
        }

        return back()->with('status', (int) $result[0]->tagihan_dibuat.' tagihan Field Trip berhasil diterbitkan.');
    }

    public function uploadActivityPhoto(Request $request, int $activityId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $data = $request->validate([
            'tahap' => ['required', Rule::in(['Sebelum', 'Selama', 'Sesudah'])],
            'keterangan' => ['nullable', 'string', 'max:255'],
            'foto' => ['required', 'array', 'min:1', 'max:10'],
            'foto.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'foto.max' => 'Maksimal 10 foto per unggahan.',
            'foto.*.max' => 'Salah satu foto melebihi batas maksimal 5 MB per foto.',
            'foto.*.image' => 'Semua file harus berupa gambar (JPG, PNG, atau WEBP).',
            'foto.*.mimes' => 'Format foto harus JPG, PNG, atau WEBP.',
        ]);
        if ($user->role === 'teacher') {
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
        } else {
            abort_unless(DB::table('kegiatan')->where('id_kegiatan', $activityId)->exists(), 404);
        }

        $storedPaths = [];
        $documents = [];

        try {
            foreach ($data['foto'] as $photo) {
                $path = $this->imageUploads->store($photo, 'kegiatan', 'foto');
                $storedPaths[] = $path;
                $documents[] = [
                    'id_kegiatan' => $activityId,
                    'tahap' => $data['tahap'],
                    'tipe' => 'Foto',
                    'file_path' => $path,
                    'keterangan' => $data['keterangan'] ?? null,
                    'id_user_upload' => $user->getKey(),
                    'created_at' => now(),
                ];
            }

            DB::table('dokumentasi_kegiatan')->insert($documents);
        } catch (Throwable $exception) {
            if ($storedPaths !== [] && ! Storage::disk('local')->delete($storedPaths)) {
                throw new RuntimeException('Unable to clean up activity photos after upload failed.', 0, $exception);
            }

            throw $exception;
        }

        return back()->with('status', \count($documents).' foto kegiatan berhasil diunggah.');
    }

    public function activityPhoto(int $documentId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'principal', 'teacher', 'parent']);
        $document = DB::table('dokumentasi_kegiatan as d')
            ->join('kegiatan as k', 'k.id_kegiatan', '=', 'd.id_kegiatan')
            ->where('d.id_dokumentasi', $documentId)
            ->first(['d.file_path', 'k.id_kegiatan', 'k.id_user_pembuat', 'k.seluruh_sekolah']);
        abort_unless($document !== null, 404);

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

        return $this->privateFileResponse($document->file_path);
    }

    public function registrationDocument(int $documentId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'parent']);
        $document = DB::table('dokumen_pendaftaran as d')
            ->join('pendaftaran as p', 'p.id_pendaftaran', '=', 'd.id_pendaftaran')
            ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
            ->where('d.id_dokumen', $documentId)
            ->first(['d.file_path', 's.id_keluarga']);
        abort_unless($document !== null, 404);
        if ($user->role === 'parent') {
            abort_unless((int) $document->id_keluarga === $this->familyId($user), 403);
        }

        return $this->privateFileResponse($document->file_path);
    }

    public function portfolioPhoto(int $portfolioId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'principal', 'teacher', 'parent']);
        $portfolio = DB::table('portofolio as p')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 'p.id_rombel')
            ->where('p.id_portofolio', $portfolioId)
            ->first(['p.foto', 'r.id_kelas', 'r.id_siswa']);
        abort_unless($portfolio !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $portfolio->id_kelas);
        } elseif ($user->role === 'parent') {
            $studentIds = DB::table('siswa')
                ->where('id_keluarga', $this->familyId($user))
                ->pluck('id_siswa')
                ->map(fn ($id): int => (int) $id)
                ->all();
            abort_unless(\in_array((int) $portfolio->id_siswa, $studentIds, true), 403);
        }

        return $this->privateFileResponse($portfolio->foto);
    }

    public function taskAttachment(int $taskId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'principal', 'teacher', 'parent']);
        $task = DB::table('tugas')->where('id_tugas', $taskId)->first(['lampiran', 'id_kelas']);
        abort_unless($task !== null && $task->lampiran !== null, 404);

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

        return $this->privateFileResponse($task->lampiran);
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
        abort_unless($submission !== null && $submission->file_path !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $submission->id_kelas);
        } elseif ($user->role === 'parent') {
            abort_unless((int) $submission->id_keluarga === $this->familyId($user), 403);
        }

        return $this->privateFileResponse($submission->file_path);
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
        abort_unless($payment !== null && $payment->bukti_transfer !== null, 404);
        if ($user->role === 'parent') {
            abort_unless((int) $payment->id_keluarga === $this->familyId($user), 403);
        }

        return $this->privateFileResponse($payment->bukti_transfer);
    }

    public function storePayment(Request $request): RedirectResponse
    {
        $user = $this->userFor(['admin', 'parent']);
        $data = $request->validate([
            'id_tagihan' => ['required', 'integer'],
            'jumlah' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'metode' => ['required', Rule::in($user->role === 'parent' ? ['Transfer'] : ['Tunai', 'Transfer'])],
            'bukti_transfer' => [
                Rule::requiredIf($request->input('metode') === 'Transfer'),
                'nullable',
                'file',
                'mimes:jpg,jpeg,png,pdf',
                'max:5120',
            ],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'bukti_transfer.max' => 'Ukuran bukti transfer melebihi batas maksimal 5 MB.',
            'bukti_transfer.mimes' => 'Bukti transfer harus berformat JPG, PNG, atau PDF.',
            'bukti_transfer.required' => 'Bukti transfer wajib diunggah untuk pembayaran transfer.',
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
            abort_unless($bill !== null, 404);
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

    public function updatePaymentSettings(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'bank_name' => ['required', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:150'],
            'account_number' => ['required', 'string', 'max:40'],
        ]);

        if (DB::table('payment_settings')->where('id', 1)->exists()) {
            DB::table('payment_settings')->where('id', 1)->update([
                ...$data,
                'updated_at' => now(),
            ]);
        } else {
            DB::table('payment_settings')->insert([
                'id' => 1,
                ...$data,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return redirect()->route('dashboard.feature', ['section' => 'pembayaran'])
            ->with('status', 'Rekening pembayaran berhasil diperbarui.');
    }

    public function reviewPayment(Request $request, int $paymentId): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        $data = $request->validate([
            'decision' => ['required', Rule::in(['verify', 'reject'])],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $paymentId, $user): void {
            $payment = DB::table('pembayaran')
                ->where('id_pembayaran', $paymentId)
                ->lockForUpdate()
                ->first();
            abort_unless($payment !== null, 404);
            if ($payment->status !== 'Menunggu Verifikasi') {
                throw ValidationException::withMessages([
                    'decision' => 'Hanya pembayaran yang menunggu verifikasi dapat diproses.',
                ]);
            }

            $existingNote = trim((string) ($payment->catatan ?? ''));
            $adminNote = trim((string) ($data['catatan'] ?? ''));
            if ($adminNote === '') {
                $finalNote = $existingNote !== '' ? $existingNote : null;
            } elseif ($existingNote !== '') {
                $finalNote = Str::limit("Orang tua: {$existingNote} | Admin: {$adminNote}", 255, '');
            } else {
                $finalNote = $adminNote;
            }

            DB::table('pembayaran')->where('id_pembayaran', $paymentId)->update([
                'status' => $data['decision'] === 'verify' ? 'Terverifikasi' : 'Ditolak',
                'id_user_verifikator' => $user->getKey(),
                'tanggal_verifikasi' => now(),
                'catatan' => $finalNote,
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', 'Status pembayaran berhasil diperbarui.');
    }

    public function cancelPayment(Request $request, int $paymentId): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        $data = $request->validate([
            'catatan' => ['required', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $paymentId, $user): void {
            $payment = DB::table('pembayaran')
                ->where('id_pembayaran', $paymentId)
                ->lockForUpdate()
                ->first();
            abort_unless($payment !== null, 404);
            if ($payment->status === 'Dibatalkan') {
                throw ValidationException::withMessages([
                    'catatan' => 'Pembayaran ini sudah berstatus dibatalkan.',
                ]);
            }

            $existingNote = trim((string) ($payment->catatan ?? ''));
            $cancelNote = trim((string) $data['catatan']);
            $finalNote = $existingNote !== ''
                ? Str::limit("{$existingNote} | Dibatalkan: {$cancelNote}", 255, '')
                : $cancelNote;

            DB::table('pembayaran')->where('id_pembayaran', $paymentId)->update([
                'status' => 'Dibatalkan',
                'catatan' => $finalNote,
                'id_user_verifikator' => $user->getKey(),
                'tanggal_verifikasi' => now(),
                'updated_at' => now(),
            ]);
        });

        return back()->with('status', 'Kuitansi pembayaran berhasil dibatalkan.');
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
            abort_unless($student !== null, 404);

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

    public function storeParentSavingsTransfer(Request $request): RedirectResponse
    {
        $user = $this->userFor(['parent']);
        abort_unless(Schema::hasTable('setoran_tabungan'), 503, 'Tabel setoran tabungan belum tersedia. Jalankan migrasi terlebih dahulu.');

        $data = $request->validate([
            'id_siswa' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
            'jumlah' => ['required', 'numeric', 'min:1', 'max:100000000'],
            'bukti_transfer' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:5120'],
            'catatan' => ['nullable', 'string', 'max:255'],
        ], [
            'bukti_transfer.required' => 'Bukti transfer wajib diunggah untuk setoran tabungan.',
            'bukti_transfer.mimes' => 'Bukti transfer harus berformat JPG, PNG, atau PDF.',
            'bukti_transfer.max' => 'Ukuran bukti transfer melebihi batas maksimal 5 MB.',
        ]);

        $familyId = $this->familyId($user);
        $student = DB::table('siswa')
            ->where('id_siswa', $data['id_siswa'])
            ->first(['id_siswa', 'id_keluarga']);
        abort_unless($student !== null, 404);
        abort_unless((int) $student->id_keluarga === $familyId, 403);

        $proofPath = $this->imageUploads->store(
            $request->file('bukti_transfer'),
            'bukti-pembayaran',
            'bukti_transfer'
        );

        DB::table('setoran_tabungan')->insert([
            'id_siswa' => $student->id_siswa,
            'jumlah' => $data['jumlah'],
            'metode' => 'Transfer',
            'bukti_transfer' => $proofPath,
            'status' => 'Menunggu Verifikasi',
            'catatan' => isset($data['catatan']) && trim((string) $data['catatan']) !== '' ? trim((string) $data['catatan']) : null,
            'id_user_pencatat' => $user->getKey(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Setoran tabungan transfer berhasil dikirim dan menunggu verifikasi admin.');
    }

    public function reviewSavingsTransfer(Request $request, int $depositId): RedirectResponse
    {
        $user = $this->userFor(['admin']);
        abort_unless(Schema::hasTable('setoran_tabungan'), 503, 'Tabel setoran tabungan belum tersedia.');

        $data = $request->validate([
            'decision' => ['required', Rule::in(['verify', 'reject'])],
            'catatan' => ['nullable', 'string', 'max:255'],
        ]);

        DB::transaction(function () use ($data, $depositId, $user): void {
            $deposit = DB::table('setoran_tabungan')
                ->where('id', $depositId)
                ->lockForUpdate()
                ->first();
            abort_unless($deposit !== null, 404);

            if ($deposit->status !== 'Menunggu Verifikasi') {
                throw ValidationException::withMessages([
                    'decision' => 'Hanya setoran tabungan yang menunggu verifikasi dapat diproses.',
                ]);
            }

            $existingNote = trim((string) ($deposit->catatan ?? ''));
            $adminNote = trim((string) ($data['catatan'] ?? ''));
            if ($adminNote === '') {
                $finalNote = $existingNote !== '' ? $existingNote : null;
            } elseif ($existingNote !== '') {
                $finalNote = Str::limit("Orang tua: {$existingNote} | Admin: {$adminNote}", 255, '');
            } else {
                $finalNote = $adminNote;
            }

            $transactionId = null;
            if ($data['decision'] === 'verify') {
                $transactionId = DB::table('transaksi_tabungan')->insertGetId([
                    'id_siswa' => $deposit->id_siswa,
                    'jenis' => 'Setor',
                    'jumlah' => $deposit->jumlah,
                    'metode' => 'Transfer',
                    'keterangan' => $finalNote ?? 'Setoran tabungan via Transfer',
                    'id_user_pencatat' => $user->getKey(),
                    'created_at' => now(),
                ]);
            }

            DB::table('setoran_tabungan')
                ->where('id', $depositId)
                ->update([
                    'status' => $data['decision'] === 'verify' ? 'Terverifikasi' : 'Ditolak',
                    'catatan' => $finalNote,
                    'id_user_verifikator' => $user->getKey(),
                    'tanggal_verifikasi' => now(),
                    'id_transaksi' => $transactionId,
                    'updated_at' => now(),
                ]);
        });

        return back()->with('status', 'Status setoran tabungan transfer berhasil diperbarui.');
    }

    public function savingsTransferProof(int $depositId): StreamedResponse
    {
        $user = $this->userFor(['admin', 'parent']);
        abort_unless(Schema::hasTable('setoran_tabungan'), 404);

        $deposit = DB::table('setoran_tabungan as st')
            ->join('siswa as s', 's.id_siswa', '=', 'st.id_siswa')
            ->where('st.id', $depositId)
            ->first(['st.bukti_transfer', 's.id_keluarga']);
        abort_unless($deposit !== null && $deposit->bukti_transfer !== null, 404);

        if ($user->role === 'parent') {
            abort_unless((int) $deposit->id_keluarga === $this->familyId($user), 403);
        }

        return $this->privateFileResponse($deposit->bukti_transfer);
    }

    public function generateMonthlyBills(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'periode' => ['required', 'date_format:Y-m'],
            'jatuh_tempo' => ['required', 'date'],
        ]);
        $schoolYear = DB::table('tahun_ajaran')->where('is_aktif', true)->first();
        abort_unless($schoolYear !== null, 503, 'Tahun ajaran aktif belum tersedia.');
        $period = $data['periode'].'-01';
        if ($data['jatuh_tempo'] < $period) {
            throw ValidationException::withMessages([
                'jatuh_tempo' => 'Tanggal jatuh tempo tidak boleh sebelum periode tagihan.',
            ]);
        }

        $created = DB::transaction(function () use ($data, $period, $schoolYear): int {
            $rosterColumns = ['r.id_rombel', 'k.kelompok'];
            if (Schema::hasColumn('rombel_siswa', 'id_diskon')) {
                $rosterColumns[] = 'r.id_diskon';
            }
            $rosters = DB::table('rombel_siswa as r')
                ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                ->where('r.status', 'Aktif')
                ->where('k.id_tahun_ajaran', $schoolYear->id_tahun_ajaran)
                ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
                ->get($rosterColumns);
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

                    $discount = $this->calculateRosterDiscount(
                        $roster->id_diskon ?? null,
                        (float) $tariff->nominal
                    );

                    DB::table('tagihan')->insert([
                        'id_rombel' => $roster->id_rombel,
                        'id_jenis_biaya' => $tariff->id_jenis_biaya,
                        'periode' => $period,
                        'nominal' => $tariff->nominal,
                        'diskon' => $discount,
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

    public function createStudentBill(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_rombel' => ['required', 'integer', Rule::exists('rombel_siswa', 'id_rombel')->where('status', 'Aktif')],
            'id_jenis_biaya' => ['required', 'integer'],
            'periode' => ['required', 'date_format:Y-m'],
            'jatuh_tempo' => ['required', 'date'],
        ]);

        $schoolYear = DB::table('tahun_ajaran')->where('is_aktif', true)->first();
        abort_unless($schoolYear !== null, 503, 'Tahun ajaran aktif belum tersedia.');
        $period = $data['periode'].'-01';
        if ($data['jatuh_tempo'] < $period) {
            throw ValidationException::withMessages([
                'jatuh_tempo' => 'Tanggal jatuh tempo tidak boleh sebelum periode tagihan.',
            ]);
        }

        DB::transaction(function () use ($data, $period, $schoolYear): void {
            $rosterColumns = ['r.id_rombel', 'k.kelompok'];
            if (Schema::hasColumn('rombel_siswa', 'id_diskon')) {
                $rosterColumns[] = 'r.id_diskon';
            }
            $roster = DB::table('rombel_siswa as r')
                ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                ->where('r.id_rombel', $data['id_rombel'])
                ->where('r.status', 'Aktif')
                ->where('k.id_tahun_ajaran', $schoolYear->id_tahun_ajaran)
                ->lockForUpdate()
                ->first($rosterColumns);

            if (! $roster) {
                throw ValidationException::withMessages([
                    'id_rombel' => 'Pilih siswa aktif pada tahun ajaran yang sedang berjalan.',
                ]);
            }

            $tariff = DB::table('tarif as tf')
                ->join('jenis_biaya as jb', 'jb.id_jenis_biaya', '=', 'tf.id_jenis_biaya')
                ->where('tf.id_tahun_ajaran', $schoolYear->id_tahun_ajaran)
                ->where('tf.id_jenis_biaya', $data['id_jenis_biaya'])
                ->where('tf.kelompok', $roster->kelompok)
                ->where('jb.kategori', 'Bulanan')
                ->first(['tf.id_jenis_biaya', 'tf.nominal', 'jb.nama']);

            if (! $tariff) {
                throw ValidationException::withMessages([
                    'id_jenis_biaya' => 'Tarif bulanan tidak sesuai dengan kelompok siswa dan tahun ajaran aktif.',
                ]);
            }

            $exists = DB::table('tagihan')
                ->where('id_rombel', $roster->id_rombel)
                ->where('id_jenis_biaya', $tariff->id_jenis_biaya)
                ->whereDate('periode', $period)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'periode' => 'Tagihan untuk siswa, jenis biaya, dan periode tersebut sudah ada.',
                ]);
            }

            $discount = $this->calculateRosterDiscount(
                $roster->id_diskon ?? null,
                (float) $tariff->nominal
            );

            DB::table('tagihan')->insert([
                'id_rombel' => $roster->id_rombel,
                'id_jenis_biaya' => $tariff->id_jenis_biaya,
                'periode' => $period,
                'nominal' => $tariff->nominal,
                'diskon' => $discount,
                'jatuh_tempo' => $data['jatuh_tempo'],
                'created_at' => now(),
            ]);
        });

        return back()->with('status', 'Tagihan siswa berhasil dibuat sesuai tarif bulanan aktif.');
    }

    public function linkParentToFamily(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_keluarga' => ['required', 'integer', Rule::exists('keluarga', 'id_keluarga')],
            'id_user' => ['required', 'integer', Rule::exists('users', 'id_user')],
        ]);

        DB::transaction(function () use ($data): void {
            $family = DB::table('keluarga')
                ->where('id_keluarga', $data['id_keluarga'])
                ->lockForUpdate()
                ->first();
            abort_unless($family !== null, 404);

            if ($family->id_user !== null) {
                throw ValidationException::withMessages([
                    'id_keluarga' => 'Data keluarga ini sudah terhubung ke akun.',
                ]);
            }

            $parent = DB::table('users')
                ->where('id_user', $data['id_user'])
                ->where('role', 'orang_tua')
                ->where('is_active', true)
                ->lockForUpdate()
                ->first();
            if (! $parent) {
                throw ValidationException::withMessages([
                    'id_user' => 'Pilih akun orang tua yang aktif.',
                ]);
            }

            if (DB::table('keluarga')->where('id_user', $parent->id_user)->exists()) {
                throw ValidationException::withMessages([
                    'id_user' => 'Akun orang tua ini sudah terhubung ke data keluarga lain.',
                ]);
            }

            if (! DB::table('siswa')->where('id_keluarga', $family->id_keluarga)->exists()) {
                throw ValidationException::withMessages([
                    'id_keluarga' => 'Data keluarga belum memiliki siswa.',
                ]);
            }

            DB::table('keluarga')
                ->where('id_keluarga', $family->id_keluarga)
                ->whereNull('id_user')
                ->update([
                    'id_user' => $parent->id_user,
                    'updated_at' => now(),
                ]);
        });

        return redirect()->route('dashboard.feature', ['section' => 'keluarga'])
            ->with('status', 'Akun orang tua berhasil dihubungkan ke data keluarga.');
    }

    public function createAndLinkParent(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_keluarga' => ['required', 'integer', Rule::exists('keluarga', 'id_keluarga')],
            'name' => ['required', 'string', 'max:100'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
        ], [
            'password' => PasswordPolicy::MESSAGE,
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        DB::transaction(function () use ($data): void {
            $family = DB::table('keluarga')
                ->where('id_keluarga', $data['id_keluarga'])
                ->lockForUpdate()
                ->first();
            abort_unless($family !== null, 404);

            if ($family->id_user !== null) {
                throw ValidationException::withMessages([
                    'id_keluarga' => 'Data keluarga ini sudah terhubung ke akun.',
                ]);
            }

            if (! DB::table('siswa')->where('id_keluarga', $family->id_keluarga)->exists()) {
                throw ValidationException::withMessages([
                    'id_keluarga' => 'Data keluarga belum memiliki siswa.',
                ]);
            }

            $parent = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'role' => 'parent',
                'must_change_password' => true,
            ]);

            DB::table('keluarga')
                ->where('id_keluarga', $family->id_keluarga)
                ->whereNull('id_user')
                ->update(['id_user' => $parent->getKey(), 'updated_at' => now()]);
        });

        return redirect()->route('dashboard.feature', ['section' => 'keluarga'])
            ->with('status', 'Akun orang tua berhasil dibuat dan dihubungkan ke data keluarga.');
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

    public function storeTeacher(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'nuptk' => ['nullable', 'string', 'max:16', Rule::unique('guru', 'nuptk')],
            'nik' => ['nullable', 'digits:16', Rule::unique('guru', 'nik')],
            'jenis_kelamin' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'tempat_lahir' => ['nullable', 'string', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date', 'before_or_equal:today'],
            'no_hp' => ['nullable', 'string', 'max:16'],
            'pendidikan' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:30'],
            'status_kepegawaian' => ['nullable', 'string', 'max:20'],
            'tanggal_mulai' => ['nullable', 'date'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'id_user' => ['nullable', 'integer', Rule::exists('users', 'id_user'), Rule::unique('guru', 'id_user')],
        ]);

        $payload = [
            'nama_lengkap' => $data['nama_lengkap'],
            'gelar_belakang' => $data['gelar_belakang'] ?? null,
            'nuptk' => $data['nuptk'] ?? null,
            'nik' => $data['nik'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'],
            'tempat_lahir' => $data['tempat_lahir'] ?? null,
            'tanggal_lahir' => $data['tanggal_lahir'] ?? null,
            'no_hp' => $data['no_hp'] ?? null,
            'pendidikan' => $data['pendidikan'] ?? 'Sarjana (S1/D4)',
            'jabatan' => $data['jabatan'] ?? 'Guru',
            'status_kepegawaian' => $data['status_kepegawaian'] ?? 'Tetap',
            'tanggal_mulai' => $data['tanggal_mulai'] ?? now()->toDateString(),
            'alamat' => $data['alamat'] ?? null,
            'id_user' => ($data['id_user'] ?? null) ?: null,
            'status' => 'Aktif',
            'created_at' => now(),
            'updated_at' => now(),
        ];

        DB::table('guru')->insert($payload);

        return back()->with('status', 'Data guru baru berhasil ditambahkan.');
    }

    public function updateTeacher(Request $request, int $teacherId): RedirectResponse
    {
        $this->userFor(['admin']);
        abort_unless(DB::table('guru')->where('id_guru', $teacherId)->exists(), 404);

        $data = $request->validate([
            'nama_lengkap' => ['required', 'string', 'max:100'],
            'gelar_belakang' => ['nullable', 'string', 'max:50'],
            'nuptk' => ['nullable', 'string', 'max:16', Rule::unique('guru', 'nuptk')->ignore($teacherId, 'id_guru')],
            'nik' => ['nullable', 'digits:16', Rule::unique('guru', 'nik')->ignore($teacherId, 'id_guru')],
            'jenis_kelamin' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'no_hp' => ['nullable', 'string', 'max:16'],
            'pendidikan' => ['nullable', 'string', 'max:30'],
            'jabatan' => ['nullable', 'string', 'max:30'],
            'alamat' => ['nullable', 'string', 'max:2000'],
            'status' => ['required', Rule::in(['Aktif', 'Nonaktif'])],
            'id_user' => ['nullable', 'integer', Rule::exists('users', 'id_user'), Rule::unique('guru', 'id_user')->ignore($teacherId, 'id_guru')],
        ]);

        DB::table('guru')->where('id_guru', $teacherId)->update([
            'nama_lengkap' => $data['nama_lengkap'],
            'gelar_belakang' => $data['gelar_belakang'] ?? null,
            'nuptk' => $data['nuptk'] ?? null,
            'nik' => $data['nik'] ?? null,
            'jenis_kelamin' => $data['jenis_kelamin'],
            'no_hp' => $data['no_hp'] ?? null,
            'pendidikan' => $data['pendidikan'] ?? null,
            'jabatan' => $data['jabatan'] ?? 'Guru',
            'alamat' => $data['alamat'] ?? null,
            'status' => $data['status'],
            'id_user' => ($data['id_user'] ?? null) ?: null,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Data guru berhasil diperbarui.');
    }

    public function assignTeacherClass(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_guru' => ['required', 'integer', Rule::exists('guru', 'id_guru')],
            'id_kelas' => ['required', 'integer', Rule::exists('kelas', 'id_kelas')],
            'peran' => ['required', Rule::in(['Wali Kelas', 'Pendamping'])],
        ]);

        DB::transaction(function () use ($data): void {
            DB::table('guru_kelas')->updateOrInsert(
                [
                    'id_guru' => (int) $data['id_guru'],
                    'id_kelas' => (int) $data['id_kelas'],
                ],
                [
                    'peran' => $data['peran'],
                ]
            );
        });

        return back()->with('status', 'Penugasan kelas guru berhasil disimpan.');
    }

    public function storeClass(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_tahun_ajaran' => ['required', 'integer', Rule::exists('tahun_ajaran', 'id_tahun_ajaran')],
            'kelompok' => ['required', Rule::in(['KB', 'TK A', 'TK B'])],
            'nama_kelas' => ['required', 'string', 'max:50'],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:30'],
            'id_wali_kelas' => ['nullable', 'integer', Rule::exists('guru', 'id_guru')],
        ]);

        $exists = DB::table('kelas')
            ->where('id_tahun_ajaran', $data['id_tahun_ajaran'])
            ->where('nama_kelas', $data['nama_kelas'])
            ->exists();
        if ($exists) {
            throw ValidationException::withMessages([
                'nama_kelas' => 'Nama kelas sudah digunakan pada tahun ajaran tersebut.',
            ]);
        }

        DB::transaction(function () use ($data): void {
            $payload = [
                'id_tahun_ajaran' => (int) $data['id_tahun_ajaran'],
                'kelompok' => $data['kelompok'],
                'nama_kelas' => $data['nama_kelas'],
                'created_at' => now(),
            ];
            if (Schema::hasColumn('kelas', 'kapasitas')) {
                $payload['kapasitas'] = (int) $data['kapasitas'];
            }
            $classId = DB::table('kelas')->insertGetId($payload);

            if (! empty($data['id_wali_kelas']) && Schema::hasTable('guru_kelas')) {
                DB::table('guru_kelas')->updateOrInsert(
                    ['id_guru' => (int) $data['id_wali_kelas'], 'id_kelas' => $classId],
                    ['peran' => 'Wali Kelas']
                );
            }
        });

        return back()->with('status', 'Ruang kelas baru berhasil ditambahkan.');
    }

    public function updateClass(Request $request, int $classId): RedirectResponse
    {
        $this->userFor(['admin']);
        $schoolClass = DB::table('kelas')->where('id_kelas', $classId)->first();
        abort_unless($schoolClass !== null, 404);

        $data = $request->validate([
            'nama_kelas' => ['required', 'string', 'max:50'],
            'kelompok' => ['required', Rule::in(['KB', 'TK A', 'TK B'])],
            'kapasitas' => ['required', 'integer', 'min:1', 'max:30'],
            'id_wali_kelas' => ['nullable', 'integer', Rule::exists('guru', 'id_guru')],
        ]);

        $activeCount = Schema::hasTable('rombel_siswa')
            ? DB::table('rombel_siswa')->where('id_kelas', $classId)->where('status', 'Aktif')->count()
            : 0;
        if ((int) $data['kapasitas'] < $activeCount) {
            throw ValidationException::withMessages([
                'kapasitas' => "Kapasitas tidak boleh lebih kecil dari jumlah siswa aktif saat ini ({$activeCount} siswa).",
            ]);
        }

        DB::transaction(function () use ($classId, $data): void {
            $payload = [
                'nama_kelas' => $data['nama_kelas'],
                'kelompok' => $data['kelompok'],
            ];
            if (Schema::hasColumn('kelas', 'kapasitas')) {
                $payload['kapasitas'] = (int) $data['kapasitas'];
            }
            DB::table('kelas')->where('id_kelas', $classId)->update($payload);

            if (! empty($data['id_wali_kelas']) && Schema::hasTable('guru_kelas')) {
                DB::table('guru_kelas')
                    ->where('id_kelas', $classId)
                    ->where('peran', 'Wali Kelas')
                    ->where('id_guru', '<>', (int) $data['id_wali_kelas'])
                    ->delete();
                DB::table('guru_kelas')->updateOrInsert(
                    ['id_guru' => (int) $data['id_wali_kelas'], 'id_kelas' => $classId],
                    ['peran' => 'Wali Kelas']
                );
            }
        });

        return back()->with('status', 'Data ruang kelas berhasil diperbarui.');
    }

    public function storeSchoolYear(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:20'],
            'tahun_mulai' => ['required', 'integer', 'min:2020', 'max:2099'],
            'tanggal_mulai' => ['nullable', 'date'],
            'tanggal_selesai' => ['nullable', 'date', 'after:tanggal_mulai'],
            'is_aktif' => ['required', 'boolean'],
        ]);

        DB::transaction(function () use ($data): void {
            if ($data['is_aktif']) {
                DB::table('tahun_ajaran')->where('is_aktif', true)->update(['is_aktif' => false]);
            }

            $existing = DB::table('tahun_ajaran')->where('nama', $data['nama'])->first();
            $payload = [
                'nama' => $data['nama'],
                'tahun_mulai' => (int) $data['tahun_mulai'],
                'is_aktif' => (bool) $data['is_aktif'],
            ];
            if (Schema::hasColumn('tahun_ajaran', 'tanggal_mulai')) {
                $payload['tanggal_mulai'] = $data['tanggal_mulai'] ?? ($data['tahun_mulai'].'-07-01');
            }
            if (Schema::hasColumn('tahun_ajaran', 'tanggal_selesai')) {
                $payload['tanggal_selesai'] = $data['tanggal_selesai'] ?? (((int) $data['tahun_mulai'] + 1).'-06-30');
            }

            if ($existing) {
                DB::table('tahun_ajaran')
                    ->where('id_tahun_ajaran', $existing->id_tahun_ajaran)
                    ->update($payload);
            } else {
                DB::table('tahun_ajaran')->insert($payload + ['created_at' => now()]);
            }
        });

        return back()->with('status', 'Data tahun ajaran berhasil disimpan.');
    }

    public function mergeDuplicateStudents(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_siswa_utama' => ['required', 'integer', Rule::exists('siswa', 'id_siswa')],
            'id_siswa_duplikat' => ['required', 'integer', Rule::exists('siswa', 'id_siswa'), 'different:id_siswa_utama'],
        ]);

        DB::transaction(function () use ($data): void {
            $primaryId = (int) $data['id_siswa_utama'];
            $duplicateId = (int) $data['id_siswa_duplikat'];

            if (Schema::hasTable('transaksi_tabungan')) {
                DB::table('transaksi_tabungan')
                    ->where('id_siswa', $duplicateId)
                    ->update(['id_siswa' => $primaryId]);
            }

            if (Schema::hasTable('pendaftaran')) {
                $duplicateApps = DB::table('pendaftaran')->where('id_siswa', $duplicateId)->get();
                foreach ($duplicateApps as $app) {
                    $conflict = DB::table('pendaftaran')
                        ->where('id_siswa', $primaryId)
                        ->where('id_tahun_ajaran', $app->id_tahun_ajaran)
                        ->exists();
                    if ($conflict) {
                        if (Schema::hasTable('dokumen_pendaftaran')) {
                            DB::table('dokumen_pendaftaran')->where('id_pendaftaran', $app->id_pendaftaran)->delete();
                        }
                        DB::table('pendaftaran')->where('id_pendaftaran', $app->id_pendaftaran)->delete();
                    } else {
                        DB::table('pendaftaran')
                            ->where('id_pendaftaran', $app->id_pendaftaran)
                            ->update(['id_siswa' => $primaryId]);
                    }
                }
            }

            if (Schema::hasTable('rombel_siswa')) {
                $hasActivePrimary = DB::table('rombel_siswa')
                    ->where('id_siswa', $primaryId)
                    ->where('status', 'Aktif')
                    ->exists();
                $duplicateRombels = DB::table('rombel_siswa')->where('id_siswa', $duplicateId)->get();
                foreach ($duplicateRombels as $rombel) {
                    $sameClassConflict = DB::table('rombel_siswa')
                        ->where('id_siswa', $primaryId)
                        ->where('id_kelas', $rombel->id_kelas)
                        ->exists();
                    if ($sameClassConflict) {
                        DB::table('rombel_siswa')->where('id_rombel', $rombel->id_rombel)->delete();
                        continue;
                    }

                    $newStatus = ($rombel->status === 'Aktif' && $hasActivePrimary) ? 'Pindah' : $rombel->status;
                    DB::table('rombel_siswa')
                        ->where('id_rombel', $rombel->id_rombel)
                        ->update([
                            'id_siswa' => $primaryId,
                            'status' => $newStatus,
                        ]);
                }
            }

            DB::table('siswa')->where('id_siswa', $duplicateId)->delete();
        });

        return back()->with('status', 'Data siswa ganda berhasil digabungkan ke data siswa utama.');
    }

    public function mergeDuplicateFamilies(Request $request): RedirectResponse
    {
        $this->userFor(['admin']);
        $data = $request->validate([
            'id_keluarga_utama' => ['required', 'integer', Rule::exists('keluarga', 'id_keluarga')],
            'id_keluarga_duplikat' => ['required', 'integer', Rule::exists('keluarga', 'id_keluarga'), 'different:id_keluarga_utama'],
        ]);

        DB::transaction(function () use ($data): void {
            $primaryId = (int) $data['id_keluarga_utama'];
            $duplicateId = (int) $data['id_keluarga_duplikat'];

            DB::table('siswa')->where('id_keluarga', $duplicateId)->update(['id_keluarga' => $primaryId]);

            if (Schema::hasTable('wali')) {
                $duplicateWalis = DB::table('wali')->where('id_keluarga', $duplicateId)->get();
                foreach ($duplicateWalis as $wali) {
                    $conflict = DB::table('wali')
                        ->where('id_keluarga', $primaryId)
                        ->where('hubungan', $wali->hubungan)
                        ->exists();
                    if ($conflict) {
                        DB::table('wali')->where('id_wali', $wali->id_wali)->delete();
                    } else {
                        DB::table('wali')->where('id_wali', $wali->id_wali)->update(['id_keluarga' => $primaryId]);
                    }
                }
            }

            if (Schema::hasTable('kontak_keluarga')) {
                $duplicateContacts = DB::table('kontak_keluarga')->where('id_keluarga', $duplicateId)->get();
                foreach ($duplicateContacts as $contact) {
                    $conflict = DB::table('kontak_keluarga')
                        ->where('id_keluarga', $primaryId)
                        ->where('nomor', $contact->nomor)
                        ->exists();
                    if ($conflict) {
                        DB::table('kontak_keluarga')->where('id_kontak', $contact->id_kontak)->delete();
                    } else {
                        $nextOrder = ((int) DB::table('kontak_keluarga')->where('id_keluarga', $primaryId)->max('urutan')) + 1;
                        DB::table('kontak_keluarga')->where('id_kontak', $contact->id_kontak)->update([
                            'id_keluarga' => $primaryId,
                            'urutan' => $nextOrder,
                        ]);
                    }
                }
            }

            $primaryFamily = DB::table('keluarga')->where('id_keluarga', $primaryId)->first();
            $duplicateFamily = DB::table('keluarga')->where('id_keluarga', $duplicateId)->first();
            if ($primaryFamily && $duplicateFamily && $primaryFamily->id_user === null && $duplicateFamily->id_user !== null) {
                DB::table('keluarga')->where('id_keluarga', $duplicateId)->update(['id_user' => null]);
                DB::table('keluarga')->where('id_keluarga', $primaryId)->update(['id_user' => $duplicateFamily->id_user]);
            }

            DB::table('keluarga')->where('id_keluarga', $duplicateId)->delete();
        });

        return back()->with('status', 'Data keluarga ganda berhasil digabungkan.');
    }

    public function destroyTeacher(int $teacherId): RedirectResponse
    {
        $this->userFor(['admin']);
        $teacher = DB::table('guru')->where('id_guru', $teacherId)->first();
        abort_unless($teacher !== null, 404);

        $this->assertNoDeleteDependencies($teacherId, [
            ['guru_kelas', 'id_guru', 'penugasan kelas'],
            ['rencana_pembelajaran', 'id_guru', 'rencana pembelajaran'],
            ['tugas', 'id_guru', 'data tugas'],
            ['portofolio', 'id_guru', 'portofolio siswa'],
            ['rapor', 'id_guru_kelas', 'rapor siswa'],
        ], 'data guru');

        DB::table('guru')->where('id_guru', $teacherId)->delete();

        return back()->with('status', 'Data guru berhasil dihapus.');
    }

    public function destroyClass(int $classId): RedirectResponse
    {
        $this->userFor(['admin']);
        $schoolClass = DB::table('kelas')->where('id_kelas', $classId)->first();
        abort_unless($schoolClass !== null, 404);

        $this->assertNoDeleteDependencies($classId, [
            ['rombel_siswa', 'id_kelas', 'data rombel siswa'],
            ['guru_kelas', 'id_kelas', 'penugasan wali kelas'],
            ['rencana_pembelajaran', 'id_kelas', 'rencana pembelajaran'],
            ['tugas', 'id_kelas', 'data tugas'],
            ['kegiatan_kelas', 'id_kelas', 'kegiatan kelas'],
        ], 'data kelas');

        DB::table('kelas')->where('id_kelas', $classId)->delete();

        return back()->with('status', 'Data kelas berhasil dihapus.');
    }

    public function destroyTariff(int $tariffId): RedirectResponse
    {
        $this->userFor(['admin']);
        $tariff = DB::table('tarif')->where('id_tarif', $tariffId)->first();
        abort_unless($tariff !== null, 404);

        DB::table('tarif')->where('id_tarif', $tariffId)->delete();

        return back()->with('status', 'Tarif biaya berhasil dihapus.');
    }

    public function updateLessonPlan(Request $request, int $lessonPlanId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $plan = DB::table('rencana_pembelajaran')->where('id_rencana', $lessonPlanId)->first();
        abort_unless($plan !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $plan->id_kelas);
        }

        $data = $request->validate([
            'tanggal' => ['required', 'date'],
            'jenis' => ['nullable', Rule::in(['RPPH', 'RPPM', 'Modul Ajar'])],
            'tema' => ['required', 'string', 'max:150'],
            'subtema' => ['nullable', 'string', 'max:150'],
            'tujuan' => ['nullable', 'string', 'max:5000'],
            'kegiatan' => ['nullable', 'string', 'max:5000'],
            'alat_bahan' => ['nullable', 'string', 'max:5000'],
            'status' => ['required', Rule::in(['Draft', 'Final'])],
        ]);

        $payload = [
            'tanggal' => $data['tanggal'],
            'tema' => $data['tema'],
            'subtema' => $data['subtema'] ?? null,
            'tujuan' => $data['tujuan'] ?? null,
            'kegiatan' => $data['kegiatan'] ?? null,
            'alat_bahan' => $data['alat_bahan'] ?? null,
            'status' => $data['status'],
            'updated_at' => now(),
        ];
        if (Schema::hasColumn('rencana_pembelajaran', 'jenis') && filled($data['jenis'] ?? null)) {
            $payload['jenis'] = $data['jenis'];
        }

        DB::table('rencana_pembelajaran')->where('id_rencana', $lessonPlanId)->update($payload);

        return back()->with('status', 'Rencana pembelajaran berhasil diperbarui.');
    }

    public function destroyLessonPlan(int $lessonPlanId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $plan = DB::table('rencana_pembelajaran')->where('id_rencana', $lessonPlanId)->first();
        abort_unless($plan !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $plan->id_kelas);
        }

        DB::table('rencana_pembelajaran')->where('id_rencana', $lessonPlanId)->delete();

        return back()->with('status', 'Rencana pembelajaran berhasil dihapus.');
    }

    public function updateTask(Request $request, int $taskId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $task = DB::table('tugas')->where('id_tugas', $taskId)->first();
        abort_unless($task !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $task->id_kelas);
        }

        $data = $request->validate([
            'judul' => ['required', 'string', 'max:150'],
            'tenggat' => ['required', 'date'],
            'deskripsi' => ['nullable', 'string', 'max:5000'],
        ]);

        if ($data['tenggat'] < $task->tanggal_diberikan) {
            throw ValidationException::withMessages([
                'tenggat' => 'Tenggat tidak boleh lebih awal dari tanggal diberikan.',
            ]);
        }

        DB::table('tugas')->where('id_tugas', $taskId)->update([
            'judul' => $data['judul'],
            'tenggat' => $data['tenggat'],
            'deskripsi' => $data['deskripsi'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Tugas berhasil diperbarui.');
    }

    public function destroyTask(int $taskId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $task = DB::table('tugas')->where('id_tugas', $taskId)->first();
        abort_unless($task !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $task->id_kelas);
        }

        $this->assertNoDeleteDependencies($taskId, [
            ['pengumpulan_tugas', 'id_tugas', 'pengumpulan tugas siswa'],
        ], 'tugas');

        if ($task->lampiran) {
            Storage::disk('local')->delete($task->lampiran);
        }

        DB::table('tugas')->where('id_tugas', $taskId)->delete();

        return back()->with('status', 'Tugas berhasil dihapus.');
    }

    public function updatePortfolio(Request $request, int $portfolioId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $portfolio = DB::table('portofolio as p')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 'p.id_rombel')
            ->where('p.id_portofolio', $portfolioId)
            ->first(['p.*', 'r.id_kelas']);
        abort_unless($portfolio !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $portfolio->id_kelas);
        }

        $data = $request->validate([
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'narasi' => ['required', 'string', 'max:5000'],
            'foto' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'foto.max' => 'Ukuran foto melebihi batas maksimal 5 MB.',
        ]);

        $photoPath = $portfolio->foto;
        if ($request->hasFile('foto')) {
            $newPath = $this->imageUploads->store($request->file('foto'), 'portofolio', 'foto');
            if ($photoPath) {
                Storage::disk('local')->delete($photoPath);
            }
            $photoPath = $newPath;
        }

        DB::table('portofolio')->where('id_portofolio', $portfolioId)->update([
            'tanggal' => $data['tanggal'],
            'narasi' => $data['narasi'],
            'foto' => $photoPath,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Portofolio perkembangan siswa berhasil diperbarui.');
    }

    public function destroyPortfolio(int $portfolioId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $portfolio = DB::table('portofolio as p')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 'p.id_rombel')
            ->where('p.id_portofolio', $portfolioId)
            ->first(['p.id_portofolio', 'p.foto', 'r.id_kelas']);
        abort_unless($portfolio !== null, 404);

        if ($user->role === 'teacher') {
            $this->assertTeacherClass($this->teacherId($user), (int) $portfolio->id_kelas);
        }

        if ($portfolio->foto) {
            Storage::disk('local')->delete($portfolio->foto);
        }

        DB::table('portofolio')->where('id_portofolio', $portfolioId)->delete();

        return back()->with('status', 'Portofolio perkembangan siswa berhasil dihapus.');
    }

    public function updateActivity(Request $request, int $activityId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $activity = DB::table('kegiatan')->where('id_kegiatan', $activityId)->first();
        abort_unless($activity !== null, 404);

        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $authorized = (int) $activity->id_user_pembuat === $user->getKey()
                || DB::table('kegiatan_kelas as kk')
                    ->join('guru_kelas as gk', 'gk.id_kelas', '=', 'kk.id_kelas')
                    ->where('kk.id_kegiatan', $activityId)
                    ->where('gk.id_guru', $teacherId)
                    ->exists();
            abort_unless($authorized, 403);
        }

        $data = $request->validate([
            'nama_kegiatan' => ['required', 'string', 'max:150'],
            'jenis' => ['required', Rule::in(['Dalam Kelas', 'Luar Kelas', 'Field Trip'])],
            'tanggal' => ['required', 'date'],
            'lokasi' => ['nullable', 'string', 'max:150'],
        ]);

        DB::table('kegiatan')->where('id_kegiatan', $activityId)->update([
            'nama_kegiatan' => $data['nama_kegiatan'],
            'jenis' => $data['jenis'],
            'tanggal' => $data['tanggal'],
            'lokasi' => $data['lokasi'] ?? null,
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Data kegiatan berhasil diperbarui.');
    }

    public function destroyActivity(int $activityId): RedirectResponse
    {
        $user = $this->userFor(['teacher', 'admin']);
        $activity = DB::table('kegiatan')->where('id_kegiatan', $activityId)->first();
        abort_unless($activity !== null, 404);

        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $authorized = (int) $activity->id_user_pembuat === $user->getKey()
                || DB::table('kegiatan_kelas as kk')
                    ->join('guru_kelas as gk', 'gk.id_kelas', '=', 'kk.id_kelas')
                    ->where('kk.id_kegiatan', $activityId)
                    ->where('gk.id_guru', $teacherId)
                    ->exists();
            abort_unless($authorized, 403);
        }

        $this->assertNoDeleteDependencies($activityId, [
            ['tagihan', 'id_kegiatan', 'tagihan kegiatan siswa'],
        ], 'kegiatan');

        $photoPaths = DB::table('dokumentasi_kegiatan')
            ->where('id_kegiatan', $activityId)
            ->pluck('file_path')
            ->filter()
            ->all();

        DB::transaction(function () use ($activityId): void {
            DB::table('dokumentasi_kegiatan')->where('id_kegiatan', $activityId)->delete();
            DB::table('kegiatan_kelas')->where('id_kegiatan', $activityId)->delete();
            DB::table('kegiatan')->where('id_kegiatan', $activityId)->delete();
        });

        if ($photoPaths !== []) {
            Storage::disk('local')->delete($photoPaths);
        }

        return back()->with('status', 'Kegiatan beserta dokumentasinya berhasil dihapus.');
    }

    public function updateStudentBill(Request $request, int $billId): RedirectResponse
    {
        $this->userFor(['admin']);
        $bill = DB::table('tagihan')->where('id_tagihan', $billId)->first();
        abort_unless($bill !== null, 404);

        $data = $request->validate([
            'nominal' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'diskon' => ['required', 'numeric', 'min:0', 'max:999999999'],
            'jatuh_tempo' => ['required', 'date'],
        ]);

        if ((float) $data['diskon'] > (float) $data['nominal']) {
            throw ValidationException::withMessages([
                'diskon' => 'Diskon tidak boleh lebih besar dari nominal tagihan.',
            ]);
        }

        $paidAmount = (float) DB::table('alokasi_pembayaran as ap')
            ->join('pembayaran as p', 'p.id_pembayaran', '=', 'ap.id_pembayaran')
            ->where('ap.id_tagihan', $billId)
            ->where('p.status', 'Terverifikasi')
            ->sum('ap.jumlah');

        $newTotal = (float) $data['nominal'] - (float) $data['diskon'];
        if ($newTotal < $paidAmount) {
            throw ValidationException::withMessages([
                'nominal' => 'Total tagihan baru tidak boleh lebih kecil dari jumlah yang sudah dibayar (Rp '.number_format($paidAmount, 0, ',', '.').').',
            ]);
        }

        DB::table('tagihan')->where('id_tagihan', $billId)->update([
            'nominal' => $data['nominal'],
            'diskon' => $data['diskon'],
            'jatuh_tempo' => $data['jatuh_tempo'],
            'updated_at' => now(),
        ]);

        return back()->with('status', 'Tagihan siswa berhasil diperbarui.');
    }

    public function destroyStudentBill(int $billId): RedirectResponse
    {
        $this->userFor(['admin']);
        $bill = DB::table('tagihan')->where('id_tagihan', $billId)->first();
        abort_unless($bill !== null, 404);

        if (Schema::hasTable('alokasi_pembayaran') && Schema::hasTable('pembayaran')) {
            $hasActivePayment = DB::table('alokasi_pembayaran as a')
                ->join('pembayaran as p', 'p.id_pembayaran', '=', 'a.id_pembayaran')
                ->where('a.id_tagihan', $billId)
                ->whereNotIn('p.status', ['Dibatalkan', 'Ditolak'])
                ->exists();

            if ($hasActivePayment) {
                throw ValidationException::withMessages([
                    'delete' => 'Tidak dapat menghapus tagihan karena masih memiliki pembayaran terverifikasi atau menunggu verifikasi.',
                ]);
            }
        }

        try {
            DB::transaction(function () use ($billId): void {
                if (Schema::hasTable('alokasi_pembayaran') && Schema::hasTable('pembayaran')) {
                    $canceledPaymentIds = DB::table('alokasi_pembayaran as a')
                        ->join('pembayaran as p', 'p.id_pembayaran', '=', 'a.id_pembayaran')
                        ->where('a.id_tagihan', $billId)
                        ->whereIn('p.status', ['Dibatalkan', 'Ditolak'])
                        ->pluck('a.id_pembayaran')
                        ->unique()
                        ->all();

                    if ($canceledPaymentIds !== []) {
                        DB::table('alokasi_pembayaran')
                            ->where('id_tagihan', $billId)
                            ->whereIn('id_pembayaran', $canceledPaymentIds)
                            ->delete();

                        foreach ($canceledPaymentIds as $paymentId) {
                            $stillUsed = DB::table('alokasi_pembayaran')
                                ->where('id_pembayaran', $paymentId)
                                ->exists();
                            if (! $stillUsed) {
                                DB::table('pembayaran')
                                    ->where('id_pembayaran', $paymentId)
                                    ->whereIn('status', ['Dibatalkan', 'Ditolak'])
                                    ->delete();
                            }
                        }
                    }
                }

                DB::table('tagihan')->where('id_tagihan', $billId)->delete();
            });
        } catch (QueryException $exception) {
            $sqlState = $exception->errorInfo[0] ?? '';
            $message = $exception->errorInfo[2] ?? $exception->getMessage();
            if ($sqlState === '45000' || str_contains($message, '1644')) {
                $cleanMessage = preg_replace('/^.*1644\s*/', '', (string) $message);
                throw ValidationException::withMessages(['delete' => $cleanMessage ?: 'Tagihan ini tidak dapat dihapus.']);
            }
            throw $exception;
        }

        return back()->with('status', 'Tagihan siswa berhasil dihapus.');
    }

    private function calculateRosterDiscount(?int $discountId, float $nominal): float
    {
        if (! $discountId || ! Schema::hasTable('diskon')) {
            return 0.0;
        }

        $discount = DB::table('diskon')->where('id_diskon', $discountId)->first();
        if (! $discount) {
            return 0.0;
        }

        $type = $discount->tipe ?? $discount->jenis ?? 'Nominal';
        if ($type === 'Persen') {
            return round($nominal * ((float) $discount->nilai / 100), 2);
        }

        return min((float) $discount->nilai, $nominal);
    }

    private function assertClassHasCapacity(int $classId, int $studentId): void
    {
        $class = DB::table('kelas')->where('id_kelas', $classId)->first();
        if (! $class) {
            return;
        }
        if ($class->nama_kelas === 'Belum Ditentukan' || ! isset($class->kapasitas)) {
            return;
        }

        $currentCount = DB::table('rombel_siswa')
            ->where('id_kelas', $classId)
            ->where('status', 'Aktif')
            ->where('id_siswa', '<>', $studentId)
            ->count();

        if ($currentCount >= (int) $class->kapasitas) {
            throw ValidationException::withMessages([
                'id_kelas' => "Kapasitas kelas {$class->nama_kelas} sudah penuh ({$currentCount}/{$class->kapasitas}).",
            ]);
        }
    }

    private function resolveTeacherIdForClass(User $user, int $classId): int
    {
        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $this->assertTeacherClass($teacherId, $classId);

            return $teacherId;
        }

        if (Schema::hasColumn('kelas', 'id_wali_kelas')) {
            $waliId = DB::table('kelas')->where('id_kelas', $classId)->value('id_wali_kelas');
            if ($waliId) {
                return (int) $waliId;
            }
         }
        if (Schema::hasTable('guru_kelas')) {
            $assignedId = DB::table('guru_kelas')->where('id_kelas', $classId)->value('id_guru');
            if ($assignedId) {
                return (int) $assignedId;
            }
        }

        return (int) (DB::table('guru')->orderBy('id_guru')->value('id_guru') ?? 1);
    }

    private function resolveTeacherIdForRoster(User $user, int $rosterId): int
    {
        if ($user->role === 'teacher') {
            $teacherId = $this->teacherId($user);
            $this->assertTeacherRoster($teacherId, $rosterId);

            return $teacherId;
        }

        $classId = (int) DB::table('rombel_siswa')->where('id_rombel', $rosterId)->value('id_kelas');

        return $this->resolveTeacherIdForClass($user, $classId);
    }

    private function userFor(array $roles): User
    {
        $user = auth()->user();
        abort_unless($user instanceof User && \in_array($user->role, $roles, true), 403);

        return $user;
    }

    private function privateFileResponse(string $path): StreamedResponse
    {
        $disk = Storage::disk('local');
        abort_unless($disk->exists($path), 404);
        $stream = $disk->readStream($path);

        if (! \is_resource($stream)) {
            throw new RuntimeException('Unable to read stored file for response.');
        }

        $mimeType = mime_content_type($disk->path($path));

        return response()->stream(function () use ($stream): void {
            try {
                fpassthru($stream);
            } finally {
                fclose($stream);
            }
        }, 200, [
            'Content-Type' => $mimeType !== false ? $mimeType : 'application/octet-stream',
            'Content-Length' => (string) $disk->size($path),
            'Content-Disposition' => HeaderUtils::makeDisposition(
                HeaderUtils::DISPOSITION_INLINE,
                basename($path)
            ),
        ]);
    }

    private function assertNoDeleteDependencies(int $recordId, array $references, string $entity): void
    {
        foreach ($references as [$table, $column, $label]) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, $column)) {
                continue;
            }

            if (DB::table($table)->where($column, $recordId)->exists()) {
                throw ValidationException::withMessages([
                    'delete' => "Tidak dapat menghapus {$entity} karena masih digunakan pada {$label}.",
                ]);
            }
        }
    }

    private function teacherId(User $user): int
    {
        $teacherId = DB::table('guru')->where('id_user', $user->getKey())->value('id_guru');
        abort_unless($teacherId, 403, 'Akun guru belum terhubung ke data guru.');

        return (int) $teacherId;
    }

    private function assertTeacherClass(int $teacherId, int $classId): void
    {
        abort_unless(DB::table('guru_kelas as gk')
            ->join('kelas as k', 'k.id_kelas', '=', 'gk.id_kelas')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('gk.id_guru', $teacherId)
            ->where('gk.id_kelas', $classId)
            ->where('ta.is_aktif', true)
            ->exists(), 403);
    }

    private function assertTeacherRoster(int $teacherId, int $rosterId): void
    {
        abort_unless(DB::table('rombel_siswa as r')
            ->join('guru_kelas as gk', 'gk.id_kelas', '=', 'r.id_kelas')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->where('r.id_rombel', $rosterId)
            ->where('r.status', 'Aktif')
            ->where('gk.id_guru', $teacherId)
            ->where('ta.is_aktif', true)
            ->exists(), 403);
    }

    private function familyId(User $user): int
    {
        $familyId = DB::table('keluarga')->where('id_user', $user->getKey())->value('id_keluarga');
        abort_unless($familyId, 403, 'Akun orang tua belum terhubung ke data keluarga.');

        return (int) $familyId;
    }

    private function assertRegistrationDocumentsValid(int $registrationId): void
    {
        $requiredDocuments = array_values(config('registration.required_documents'));
        $validDocuments = DB::table('dokumen_pendaftaran')
            ->where('id_pendaftaran', $registrationId)
            ->where('status_verifikasi', 'Valid')
            ->pluck('jenis')
            ->all();

        if (array_diff($requiredDocuments, $validDocuments) !== []) {
            throw ValidationException::withMessages([
                'decision' => 'Semua dokumen wajib harus tersedia dan berstatus valid sebelum pendaftaran dapat diverifikasi atau diterima.',
            ]);
        }
    }
}
