<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageUploadService;
use App\Support\PasswordPolicy;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Throwable;

class RegistrationController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploads) {}

    public function create(): View
    {
        $registrationOpen = $this->registrationIsOpen();
        $schoolYear = $registrationOpen
            ? DB::table('tahun_ajaran')->where('is_aktif', true)->first()
            : null;

        if ($registrationOpen) {
            abort_unless($schoolYear, 503, 'Tahun ajaran aktif belum tersedia.');
        }

        return view('registration.create', [
            'schoolYear' => $schoolYear,
            'registrationOpen' => $registrationOpen,
            'requiredDocuments' => config('registration.required_documents'),
        ]);
    }

    public function success(Request $request): View
    {
        abort_unless($request->session()->has('registration_submitted'), 404);

        return view('registration.success');
    }

    public function store(Request $request): RedirectResponse
    {
        if (! $this->registrationIsOpen()) {
            return redirect()->route('registration.create')
                ->withErrors(['registration' => 'Pendaftaran siswa baru sedang ditutup.']);
        }

        $requiredDocuments = config('registration.required_documents');
        $rules = [
            'student_name' => ['required', 'string', 'max:100'],
            'student_nickname' => ['nullable', 'string', 'max:100'],
            'student_gender' => ['required', Rule::in(['Laki-Laki', 'Perempuan'])],
            'student_birthplace' => ['nullable', 'string', 'max:100'],
            'student_birthdate' => ['required', 'date', 'before_or_equal:today'],
            'group' => ['required', Rule::in(['KB', 'TK A', 'TK B'])],
            'guardian_name' => ['required', 'string', 'max:100'],
            'guardian_relationship' => ['required', Rule::in(['Ayah', 'Ibu', 'Wali'])],
            'guardian_phone' => ['required', 'string', 'max:16'],
            'family_address' => ['required', 'string', 'max:2000'],
            'family_card_number' => ['nullable', 'digits:16'],
            'email' => ['required', 'email', 'max:150', 'unique:users,email'],
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
            'documents' => ['required', 'array:'.implode(',', array_keys($requiredDocuments))],
        ];
        $messages = [
            'student_birthdate.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'family_card_number.digits' => 'Nomor KK harus terdiri dari 16 angka.',
            'password' => PasswordPolicy::MESSAGE,
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ];
        foreach ($requiredDocuments as $key => $label) {
            $rules["documents.{$key}"] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
            $messages["documents.{$key}.required"] = "Berkas {$label} wajib diunggah.";
            $messages["documents.{$key}.mimes"] = "Berkas {$label} harus berformat PDF, JPG, atau PNG.";
            $messages["documents.{$key}.max"] = "Ukuran berkas {$label} maksimal 5 MB.";
        }

        $data = $request->validate($rules, $messages);

        $schoolYear = DB::table('tahun_ajaran')->where('is_aktif', true)->first();
        abort_unless($schoolYear, 503, 'Tahun ajaran aktif belum tersedia.');

        $documentPaths = [];
        try {
            foreach ($requiredDocuments as $key => $label) {
                $documentPaths[$key] = $this->imageUploads->store(
                    $data['documents'][$key],
                    'pendaftaran',
                    "documents.{$key}"
                );
            }

            DB::transaction(function () use ($data, $schoolYear, $documentPaths, $requiredDocuments): void {
                if (! $this->registrationIsOpen(lockForUpdate: true)) {
                    throw ValidationException::withMessages([
                        'registration' => 'Pendaftaran siswa baru sedang ditutup.',
                    ]);
                }

                $user = User::create([
                    'name' => $data['guardian_name'],
                    'email' => $data['email'],
                    'password' => $data['password'],
                    'role' => 'parent',
                ]);

                $familyId = DB::table('keluarga')->insertGetId([
                    'id_user' => $user->getKey(),
                    'no_kk' => $data['family_card_number'] ?? null,
                    'alamat' => $data['family_address'],
                    'created_at' => now(),
                ]);

                DB::table('wali')->insert([
                    'id_keluarga' => $familyId,
                    'hubungan' => $data['guardian_relationship'],
                    'nama_lengkap' => $data['guardian_name'],
                    'created_at' => now(),
                ]);

                DB::table('kontak_keluarga')->insert([
                    'id_keluarga' => $familyId,
                    'nomor' => $data['guardian_phone'],
                    'urutan' => 1,
                ]);

                $studentId = DB::table('siswa')->insertGetId([
                    'id_keluarga' => $familyId,
                    'nama_lengkap' => $data['student_name'],
                    'nama_panggilan' => $data['student_nickname'] ?? null,
                    'tempat_lahir' => $data['student_birthplace'] ?? null,
                    'tanggal_lahir' => $data['student_birthdate'],
                    'jenis_kelamin' => $data['student_gender'],
                    'alamat_domisili' => $data['family_address'],
                    'created_at' => now(),
                ]);

                $registrationId = DB::table('pendaftaran')->insertGetId([
                    'id_siswa' => $studentId,
                    'id_tahun_ajaran' => $schoolYear->id_tahun_ajaran,
                    'kelompok' => $data['group'],
                    'status' => 'Diajukan',
                    'created_at' => now(),
                ]);

                foreach ($requiredDocuments as $key => $label) {
                    DB::table('dokumen_pendaftaran')->insert([
                        'id_pendaftaran' => $registrationId,
                        'jenis' => $label,
                        'file_path' => $documentPaths[$key],
                        'status_verifikasi' => 'Menunggu',
                        'created_at' => now(),
                    ]);
                }

            });
        } catch (Throwable $exception) {
            foreach ($documentPaths as $path) {
                if (! Storage::disk('local')->delete($path)) {
                    report(new RuntimeException('Failed to clean up a registration document after registration failed.'));
                }
            }

            throw $exception;
        }

        $request->session()->regenerate();

        return redirect()->route('registration.success')
            ->with('registration_submitted', true);
    }

    private function registrationIsOpen(bool $lockForUpdate = false): bool
    {
        abort_unless(
            Schema::hasTable('registration_settings'),
            503,
            'Pengaturan pendaftaran belum tersedia. Terapkan migrasi terlebih dahulu.'
        );

        $query = DB::table('registration_settings')->where('id', 1);
        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        $isOpen = $query->value('is_open');
        abort_if($isOpen === null, 503, 'Pengaturan pendaftaran belum diinisialisasi.');

        return (bool) $isOpen;
    }
}
