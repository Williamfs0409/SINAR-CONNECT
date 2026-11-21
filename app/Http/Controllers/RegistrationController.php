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
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class RegistrationController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploads) {}

    public function create(Request $request): View
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
            'draftDocuments' => $this->validDraftDocuments($request),
        ]);
    }

    public function draftPreview(Request $request, string $key): StreamedResponse
    {
        $requiredDocuments = config('registration.required_documents');
        abort_unless(array_key_exists($key, $requiredDocuments), 404);

        $draft = $this->validDraftDocuments($request)[$key] ?? null;
        abort_unless($draft && Storage::disk('local')->exists($draft['path']), 404);

        return Storage::disk('local')->response($draft['path'], $draft['name'], [
            'Content-Disposition' => 'inline; filename="'.addslashes($draft['name']).'"',
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
        $draftDocuments = $this->syncDraftDocuments($request, $requiredDocuments);
        $hasAnyUploadedFile = collect(array_keys($requiredDocuments))
            ->contains(fn (string $key): bool => $request->hasFile("documents.{$key}"));

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
            'documents' => [$hasAnyUploadedFile || $draftDocuments === [] ? 'required' : 'nullable', 'array:'.implode(',', array_keys($requiredDocuments))],
        ];
        $messages = [
            'student_birthdate.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'family_card_number.digits' => 'Nomor KK harus terdiri dari 16 angka.',
            'password' => PasswordPolicy::MESSAGE,
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ];
        foreach ($requiredDocuments as $key => $label) {
            $hasDraft = isset($draftDocuments[$key]['path']) && Storage::disk('local')->exists($draftDocuments[$key]['path']);
            $rules["documents.{$key}"] = [$hasDraft ? 'nullable' : 'required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'];
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
                $uploadedFile = $data['documents'][$key] ?? null;
                if ($uploadedFile) {
                    $documentPaths[$key] = $this->imageUploads->store(
                        $uploadedFile,
                        'pendaftaran',
                        "documents.{$key}"
                    );
                } elseif (isset($draftDocuments[$key]['path']) && Storage::disk('local')->exists($draftDocuments[$key]['path'])) {
                    $extension = pathinfo($draftDocuments[$key]['path'], PATHINFO_EXTENSION) ?: 'bin';
                    $targetPath = 'pendaftaran/'.Str::random(40).'.'.$extension;
                    Storage::disk('local')->copy($draftDocuments[$key]['path'], $targetPath);
                    $documentPaths[$key] = $targetPath;
                } else {
                    throw ValidationException::withMessages([
                        "documents.{$key}" => "Berkas {$label} wajib diunggah.",
                    ]);
                }
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

        foreach ($draftDocuments as $draft) {
            if (! empty($draft['path'])) {
                Storage::disk('local')->delete($draft['path']);
            }
        }
        $request->session()->forget('registration_draft_documents');

        $request->session()->regenerate();

        return redirect()->route('registration.success')
            ->with('registration_submitted', true);
    }

    private function validDraftDocuments(Request $request): array
    {
        $drafts = $request->session()->get('registration_draft_documents', []);
        if (! is_array($drafts)) {
            return [];
        }

        return array_filter($drafts, function ($item): bool {
            return is_array($item)
                && ! empty($item['path'])
                && Storage::disk('local')->exists($item['path']);
        });
    }

    private function syncDraftDocuments(Request $request, array $requiredDocuments): array
    {
        $drafts = $this->validDraftDocuments($request);
        $sessionId = $request->session()->getId() ?: Str::random(16);
        $draftDirectory = 'registration-drafts/'.preg_replace('/[^A-Za-z0-9_-]/', '', $sessionId);

        foreach (array_keys($requiredDocuments) as $key) {
            if ($request->boolean("clear_draft_documents.{$key}") && ! $request->hasFile("documents.{$key}")) {
                if (! empty($drafts[$key]['path'])) {
                    Storage::disk('local')->delete($drafts[$key]['path']);
                }
                unset($drafts[$key]);
                continue;
            }

            if (! $request->hasFile("documents.{$key}")) {
                continue;
            }

            $file = $request->file("documents.{$key}");
            $fileValidator = Validator::make(
                ['file' => $file],
                ['file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120']]
            );

            if ($fileValidator->fails()) {
                continue;
            }

            try {
                $storedPath = $this->imageUploads->store($file, $draftDirectory, "documents.{$key}");
            } catch (ValidationException) {
                continue;
            }

            if (! empty($drafts[$key]['path']) && $drafts[$key]['path'] !== $storedPath) {
                Storage::disk('local')->delete($drafts[$key]['path']);
            }

            $drafts[$key] = [
                'path' => $storedPath,
                'name' => $file->getClientOriginalName(),
                'size' => (int) ($file->getSize() ?: Storage::disk('local')->size($storedPath)),
                'mime' => (string) ($file->getMimeType() ?: 'application/octet-stream'),
            ];
        }

        $request->session()->put('registration_draft_documents', $drafts);

        return $drafts;
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
