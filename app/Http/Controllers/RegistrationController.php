<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RegistrationController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploads)
    {
    }

    public function create(): View
    {
        $schoolYear = DB::table('tahun_ajaran')->where('is_aktif', true)->first();
        abort_unless($schoolYear, 503, 'Tahun ajaran aktif belum tersedia.');

        return view('registration.create', ['schoolYear' => $schoolYear]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
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
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'document_type' => ['nullable', 'required_with:document', 'string', 'max:50'],
            'document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ], [
            'student_birthdate.before_or_equal' => 'Tanggal lahir tidak boleh di masa depan.',
            'family_card_number.digits' => 'Nomor KK harus terdiri dari 16 angka.',
            'document.mimes' => 'Dokumen harus berformat PDF, JPG, atau PNG.',
        ]);

        $schoolYear = DB::table('tahun_ajaran')->where('is_aktif', true)->first();
        abort_unless($schoolYear, 503, 'Tahun ajaran aktif belum tersedia.');

        $user = DB::transaction(function () use ($data, $request, $schoolYear): User {
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

            if ($request->hasFile('document')) {
                $path = $this->imageUploads->store($request->file('document'), 'pendaftaran', 'document');
                DB::table('dokumen_pendaftaran')->insert([
                    'id_pendaftaran' => $registrationId,
                    'jenis' => $data['document_type'],
                    'file_path' => $path,
                    'status_verifikasi' => 'Menunggu',
                    'created_at' => now(),
                ]);
            }

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard.feature', ['section' => 'pendaftaran'])
            ->with('status', 'Pendaftaran berhasil dikirim. Silakan pantau statusnya melalui akun ini.');
    }
}
