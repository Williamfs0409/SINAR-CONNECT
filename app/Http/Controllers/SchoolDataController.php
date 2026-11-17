<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class SchoolDataController extends Controller
{
    private const ROLE_MODULES = [
        'admin' => [
            'siswa', 'pendaftaran', 'guru', 'kelas', 'absensi', 'pembelajaran',
            'tugas', 'nilai', 'perkembangan', 'rapor', 'kegiatan', 'tagihan', 'pembayaran',
            'tabungan', 'saldo-tabungan', 'tunggakan', 'rekap-pembayaran', 'rekap-pendaftar', 'tarif',
            'keluarga', 'pengguna', 'kesiapan', 'kualitas', 'duplikat-siswa', 'audit',
        ],
        'principal' => [
            'siswa', 'pendaftaran', 'guru', 'kelas', 'absensi', 'pembelajaran',
            'tugas', 'nilai', 'perkembangan', 'rapor', 'kegiatan', 'tagihan', 'pembayaran',
            'tabungan', 'saldo-tabungan', 'tunggakan', 'rekap-pembayaran', 'rekap-pendaftar', 'tarif',
            'kesiapan', 'kualitas',
        ],
        'teacher' => [
            'siswa', 'kelas', 'absensi', 'pembelajaran', 'tugas', 'nilai',
            'perkembangan', 'rapor', 'kegiatan',
        ],
        'parent' => [
            'siswa', 'pendaftaran', 'absensi', 'tugas', 'rapor', 'kegiatan', 'tagihan',
            'pembayaran', 'tabungan', 'saldo-tabungan', 'tunggakan', 'perkembangan',
        ],
    ];

    private const MODULE_GROUPS = [
        'data' => [
            'title' => 'Data Sekolah',
            'role_titles' => ['teacher' => 'Kelas & Siswa', 'parent' => 'Anak Saya'],
            'modules' => ['siswa', 'guru', 'kelas', 'keluarga', 'pengguna'],
        ],
        'admissions' => [
            'title' => 'Penerimaan Siswa',
            'modules' => ['pendaftaran'],
        ],
        'academic' => [
            'title' => 'Akademik',
            'role_titles' => ['parent' => 'Belajar & Perkembangan'],
            'modules' => ['absensi', 'pembelajaran', 'tugas', 'nilai', 'perkembangan', 'rapor'],
        ],
        'activities' => [
            'title' => 'Kegiatan',
            'modules' => ['kegiatan'],
        ],
        'finance' => [
            'title' => 'Keuangan',
            'role_titles' => ['parent' => 'Keuangan Anak'],
            'modules' => [
                'tagihan', 'pembayaran', 'tabungan', 'saldo-tabungan', 'tunggakan',
                'rekap-pembayaran', 'tarif',
            ],
        ],
        'reports' => [
            'title' => 'Laporan & Pengawasan',
            'modules' => ['rekap-pendaftar', 'kesiapan', 'kualitas', 'duplikat-siswa', 'audit'],
        ],
    ];

    private const MODULES = [
        'siswa' => [
            'title' => 'Data Siswa',
            'description' => 'Data siswa yang sudah disamarkan sesuai view perlindungan database.',
            'view' => 'v_siswa_terlindungi',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'nama_kelas' => 'Kelas',
                'kelompok' => 'Kelompok',
                'tahun_ajaran' => 'Tahun ajaran',
                'nik_samar' => 'NIK',
                'nisn_samar' => 'NISN',
            ],
            'key' => 'id_siswa',
            'scope' => 'student',
            'order' => 'nama_lengkap',
        ],
        'pendaftaran' => [
            'title' => 'Pendaftaran',
            'description' => 'Status pendaftaran dan kelengkapan dokumen.',
            'view' => 'v_pendaftaran_detail',
            'columns' => [
                'no_pendaftaran' => 'Nomor pendaftaran',
                'nama_lengkap' => 'Nama siswa',
                'kelompok_tujuan' => 'Kelompok tujuan',
                'tahun_ajaran' => 'Tahun ajaran',
                'status' => 'Status',
                'tanggal_daftar' => 'Tanggal daftar',
                'dokumen_valid' => 'Dokumen valid',
                'dokumen_diunggah' => 'Dokumen diunggah',
            ],
            'scope' => 'student',
            'order' => 'tanggal_daftar',
            'direction' => 'desc',
        ],
        'perkembangan' => [
            'title' => 'Perkembangan Siswa',
            'description' => 'Portofolio foto dan catatan perkembangan yang dibuat guru.',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'nama_kelas' => 'Kelas',
                'tanggal' => 'Tanggal',
                'narasi' => 'Catatan perkembangan',
                'foto' => 'Dokumentasi',
            ],
        ],
        'guru' => [
            'title' => 'Data Guru',
            'description' => 'Data kepegawaian dan kelas yang diampu.',
            'view' => 'v_guru_lengkap',
            'columns' => [
                'nama_dengan_gelar' => 'Nama guru',
                'jabatan' => 'Jabatan',
                'status' => 'Status',
                'kelas_diampu' => 'Kelas diampu',
                'email' => 'Email',
            ],
            'scope' => 'all',
            'order' => 'nama_lengkap',
        ],
        'absensi' => [
            'title' => 'Kehadiran',
            'description' => 'Rekap kehadiran per siswa dan bulan.',
            'view' => 'v_rekap_kehadiran_bulanan',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'nama_kelas' => 'Kelas',
                'tahun' => 'Tahun',
                'bulan' => 'Bulan',
                'hadir' => 'Hadir',
                'izin' => 'Izin',
                'sakit' => 'Sakit',
                'alfa' => 'Alfa',
                'persen_hadir' => 'Kehadiran (%)',
            ],
            'scope' => 'student',
            'order' => 'tahun',
            'direction' => 'desc',
        ],
        'tugas' => [
            'title' => 'Tugas',
            'description' => 'Tugas kelas dan rekap pengumpulan.',
            'view' => 'v_status_pengumpulan_tugas',
            'columns' => [
                'judul' => 'Tugas',
                'nama_kelas' => 'Kelas',
                'tenggat' => 'Tenggat',
                'total_siswa' => 'Jumlah siswa',
                'sudah_mengumpulkan' => 'Sudah mengumpulkan',
                'terlambat' => 'Terlambat',
                'sudah_dinilai' => 'Sudah dinilai',
            ],
            'scope' => 'class',
            'order' => 'tenggat',
        ],
        'rapor' => [
            'title' => 'Rapor',
            'description' => 'Status rapor dan ringkasan kehadiran semester.',
            'view' => 'v_rapor_lengkap',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'nama_kelas' => 'Kelas',
                'tahun_ajaran' => 'Tahun ajaran',
                'semester' => 'Semester',
                'status' => 'Status rapor',
                'tanggal_rapor' => 'Tanggal rapor',
                'sakit' => 'Sakit',
                'izin' => 'Izin',
                'tanpa_keterangan' => 'Alfa',
            ],
            'scope' => 'student',
            'order' => 'tanggal_rapor',
            'direction' => 'desc',
        ],
        'tagihan' => [
            'title' => 'Tagihan',
            'description' => 'Tagihan, pembayaran terverifikasi, dan sisa tagihan.',
            'view' => 'v_tagihan_detail',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'nama_kelas' => 'Kelas',
                'jenis_biaya' => 'Jenis biaya',
                'periode' => 'Periode',
                'total_tagihan' => 'Total tagihan',
                'dibayar' => 'Dibayar',
                'sisa' => 'Sisa',
                'jatuh_tempo' => 'Jatuh tempo',
                'status_bayar' => 'Status',
            ],
            'scope' => 'student',
            'order' => 'jatuh_tempo',
        ],
        'tabungan' => [
            'title' => 'Riwayat Tabungan',
            'description' => 'Setoran dan penarikan tabungan yang tercatat.',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'tanggal' => 'Tanggal transaksi',
                'jenis' => 'Jenis',
                'jumlah' => 'Jumlah',
                'metode' => 'Metode',
                'keterangan' => 'Keterangan',
            ],
            'scope' => 'student',
            'order' => 'tanggal',
            'direction' => 'desc',
        ],
        'saldo-tabungan' => [
            'title' => 'Saldo Tabungan',
            'description' => 'Ringkasan saldo dan jumlah transaksi tabungan siswa.',
            'view' => 'v_saldo_tabungan',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'jumlah_transaksi' => 'Jumlah transaksi',
                'saldo' => 'Saldo',
                'transaksi_terakhir' => 'Transaksi terakhir',
            ],
            'scope' => 'student',
            'order' => 'nama_lengkap',
        ],
        'tunggakan' => [
            'title' => 'Tunggakan',
            'description' => 'Tagihan yang belum lunas dan lama keterlambatannya.',
            'view' => 'v_tunggakan',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'nama_kelas' => 'Kelas',
                'jumlah_tagihan_menunggak' => 'Tagihan belum lunas',
                'total_tunggakan' => 'Total tunggakan',
                'jatuh_tempo_tertua' => 'Jatuh tempo tertua',
                'keterlambatan_terlama_hari' => 'Terlambat (hari)',
            ],
            'scope' => 'student',
            'order' => 'jatuh_tempo_tertua',
            'direction' => 'asc',
        ],
        'rekap-pembayaran' => [
            'title' => 'Rekap Pembayaran',
            'description' => 'Penerimaan pembayaran per bulan, jenis biaya, dan metode.',
            'view' => 'v_rekap_pembayaran_bulanan',
            'columns' => [
                'bulan' => 'Bulan',
                'jenis_biaya' => 'Jenis biaya',
                'metode' => 'Metode',
                'jumlah_kuitansi' => 'Jumlah kuitansi',
                'total_diterima' => 'Total diterima',
            ],
            'scope' => 'all',
            'order' => 'bulan',
            'direction' => 'desc',
        ],
        'rekap-pendaftar' => [
            'title' => 'Rekap Pendaftar',
            'description' => 'Jumlah pendaftar berdasarkan tahun ajaran dan kelompok.',
            'view' => 'v_rekap_pendaftar',
            'columns' => [
                'tahun_ajaran' => 'Tahun ajaran',
                'kelompok' => 'Kelompok',
                'total_pendaftar' => 'Total pendaftar',
                'diajukan' => 'Diajukan',
                'diverifikasi' => 'Diverifikasi',
                'diterima' => 'Diterima',
                'ditolak' => 'Ditolak',
            ],
            'scope' => 'all',
            'order' => 'tahun_ajaran',
            'direction' => 'desc',
        ],
        'duplikat-siswa' => [
            'title' => 'Pemeriksaan Data Ganda',
            'description' => 'Kandidat data siswa ganda untuk ditinjau administrator.',
            'view' => 'v_kandidat_duplikat_siswa',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'tanggal_lahir' => 'Tanggal lahir',
                'jumlah' => 'Jumlah catatan',
                'daftar_id_siswa' => 'ID catatan terkait',
            ],
            'scope' => 'all',
            'order' => 'nama_lengkap',
        ],
        'audit' => [
            'title' => 'Riwayat Perubahan',
            'description' => 'Aktivitas perubahan data yang tercatat pada sistem.',
            'columns' => [
                'waktu' => 'Waktu',
                'tabel' => 'Bagian',
                'id_record' => 'ID data',
                'aksi' => 'Aktivitas',
                'app_user_id' => 'ID pengguna',
                'db_user' => 'Akun sistem',
            ],
            'scope' => 'all',
            'order' => 'waktu',
            'direction' => 'desc',
        ],
        'tarif' => [
            'title' => 'Tarif Biaya',
            'description' => 'Daftar tarif berdasarkan tahun ajaran, kelompok, dan jenis biaya.',
            'columns' => [
                'tahun_ajaran' => 'Tahun ajaran',
                'kelompok' => 'Kelompok',
                'nama' => 'Jenis biaya',
                'kategori' => 'Kategori',
                'nominal' => 'Nominal',
            ],
            'scope' => 'all',
            'order' => 'tahun_ajaran',
            'direction' => 'desc',
        ],
        'kesiapan' => [
            'title' => 'Kesiapan Data',
            'description' => 'Pemeriksaan kelengkapan data operasional sekolah.',
            'view' => 'v_kesiapan_data',
            'columns' => [
                'komponen' => 'Komponen',
                'terisi' => 'Terisi',
                'dibutuhkan' => 'Dibutuhkan',
                'status' => 'Status',
                'keterangan' => 'Keterangan',
            ],
            'scope' => 'all',
            'order' => 'komponen',
        ],
        'kualitas' => [
            'title' => 'Kualitas Data',
            'description' => 'Catatan kelengkapan data untuk tindak lanjut administrator.',
            'view' => 'v_kualitas_data',
            'columns' => [
                'entitas' => 'Data',
                'nama' => 'Nama',
                'masalah' => 'Catatan',
            ],
            'scope' => 'all',
            'order' => 'entitas',
        ],
        'keluarga' => [
            'title' => 'Data Keluarga',
            'description' => 'Kontak keluarga dan wali siswa.',
            'view' => 'v_keluarga_lengkap',
            'columns' => [
                'id_keluarga' => 'ID keluarga',
                'jumlah_siswa' => 'Jumlah siswa',
                'akun_orangtua' => 'Akun orang tua',
                'nama_ayah' => 'Ayah',
                'nama_ibu' => 'Ibu',
                'nama_wali' => 'Wali',
                'no_hp_utama' => 'Nomor kontak',
            ],
            'scope' => 'all',
            'order' => 'id_keluarga',
        ],
    ];

    public function show(?string $section = null): View
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $role = $user->role;
        abort_unless(array_key_exists($role, self::ROLE_MODULES), 403);
        $allowedModules = self::ROLE_MODULES[$role] ?? [];
        $section ??= 'ringkasan';
        abort_unless($section === 'ringkasan' || in_array($section, $allowedModules, true), 404);

        $scope = $this->scopeFor($user, $role);
        $module = $section === 'ringkasan' ? null : $this->moduleDefinition($section, $role);
        $records = $module ? $this->recordsFor($section, $module, $scope, $role, $user) : null;
        $messageCenterReady = Schema::hasTable('messages');
        $passwordResetAttentionCount = $role === 'admin' && Schema::hasTable('password_reset_requests')
            ? DB::table('password_reset_requests')->whereIn('status', ['pending', 'approved'])->count()
            : 0;

        return view('dashboard.school-dashboard', [
            'section' => $section,
            'role' => $role,
            'roleName' => $this->roleName($role),
            'user' => $user,
            'modules' => $this->menuFor($allowedModules),
            'moduleGroups' => $this->moduleGroupsFor($allowedModules, $role),
            'module' => $module,
            'records' => $records,
            'actions' => $this->actionDataFor($section, $user, $role, $scope),
            'hasLinkedStudents' => $role !== 'parent' || $scope['studentIds'] !== [],
            'cards' => $section === 'ringkasan' ? $this->summaryFor($role, $scope) : [],
            'messageCenterReady' => $messageCenterReady,
            'passwordResetAttentionCount' => $passwordResetAttentionCount,
            'unreadMessageCount' => $messageCenterReady ? Message::query()
                ->where('recipient_id', $user->getKey())
                ->whereNull('read_at')
                ->count() : 0,
            'recentUnreadMessages' => $messageCenterReady ? Message::query()
                ->with('sender:id_user,name')
                ->where('recipient_id', $user->getKey())
                ->whereNull('read_at')
                ->latest()
                ->limit(5)
                ->get() : collect(),
        ]);
    }

    private function actionDataFor(string $section, User $user, string $role, array $scope): array
    {
        $data = [];
        $classes = DB::table('kelas as k')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->select(['k.id_kelas', 'k.nama_kelas', 'k.kelompok', 'ta.nama as tahun_ajaran'])
            ->orderByDesc('ta.tahun_mulai')
            ->orderBy('k.nama_kelas');

        if ($role === 'teacher' && $section === 'kegiatan') {
            $classes->where('ta.is_aktif', true)
                ->where('k.nama_kelas', '<>', 'Belum Ditentukan');
        } elseif ($role === 'parent' || $role === 'teacher') {
            $classes->whereIn('k.id_kelas', $scope['classIds']);
        }
        $data['classes'] = $classes->get();

        if (in_array($role, ['teacher', 'parent'], true)) {
            $students = DB::table('rombel_siswa as r')
                ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                ->where('r.status', 'Aktif')
                ->select([
                    'r.id_rombel',
                    'r.id_siswa',
                    'r.id_kelas',
                    's.nama_lengkap',
                    'k.nama_kelas',
                    'k.kelompok',
                ])
                ->orderBy('s.nama_lengkap');
            $students->whereIn('r.id_siswa', $scope['studentIds']);
            $data['rosters'] = $students->get();
        }

        if ($section === 'absensi' && $role === 'teacher') {
            $selectedClass = request()->query('kelas');
            if ($selectedClass !== null) {
                abort_unless($data['classes']->contains('id_kelas', (int) $selectedClass), 403);
            } else {
                $selectedClass = $data['classes']->first()->id_kelas ?? null;
            }

            $date = request()->query('tanggal', now('Asia/Jakarta')->toDateString());
            $data['attendanceDate'] = $date;
            $data['selectedClass'] = $selectedClass;
            $data['attendanceRoster'] = $selectedClass
                ? DB::table('rombel_siswa as r')
                    ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                    ->leftJoin('absensi as a', function ($join) use ($date): void {
                        $join->on('a.id_rombel', '=', 'r.id_rombel')->where('a.tanggal', '=', $date);
                    })
                    ->where('r.id_kelas', $selectedClass)
                    ->where('r.status', 'Aktif')
                    ->orderBy('s.nama_lengkap')
                    ->get([
                        'r.id_rombel',
                        's.nama_lengkap',
                        'a.status as status_absensi',
                        'a.keterangan',
                    ])
                : collect();
        }

        if ($section === 'pendaftaran' && $role === 'admin') {
            abort_unless(
                Schema::hasTable('registration_settings'),
                503,
                'Pengaturan pendaftaran belum tersedia. Terapkan migrasi terlebih dahulu.'
            );
            $registrationSettings = DB::table('registration_settings')
                ->where('id', 1)
                ->first();
            abort_if($registrationSettings === null, 503, 'Pengaturan pendaftaran belum diinisialisasi.');
            $data['registrationIsOpen'] = (bool) $registrationSettings->is_open;
            $data['applications'] = DB::table('pendaftaran as p')
                ->join('v_pendaftaran_detail as d', 'd.id_pendaftaran', '=', 'p.id_pendaftaran')
                ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
                ->leftJoin('keluarga as f', 'f.id_keluarga', '=', 's.id_keluarga')
                ->leftJoin('users as u', 'u.id_user', '=', 'f.id_user')
                ->whereIn('p.status', ['Diajukan', 'Diverifikasi'])
                ->orderByDesc('p.created_at')
                ->select([
                    'p.id_pendaftaran',
                    'p.id_tahun_ajaran',
                    'p.kelompok',
                    'p.status',
                    'd.nama_lengkap',
                    'd.no_pendaftaran',
                    'd.tahun_ajaran',
                    'd.dokumen_valid',
                    'd.dokumen_diunggah',
                    's.nama_panggilan',
                    's.tempat_lahir',
                    's.tanggal_lahir',
                    's.jenis_kelamin',
                    'f.no_kk',
                    'f.alamat',
                    'u.email',
                ])
                ->selectSub(
                    DB::table('wali as w')
                        ->select('w.nama_lengkap')
                        ->whereColumn('w.id_keluarga', 'f.id_keluarga')
                        ->orderBy('w.created_at')
                        ->limit(1),
                    'nama_wali'
                )
                ->selectSub(
                    DB::table('wali as w')
                        ->select('w.hubungan')
                        ->whereColumn('w.id_keluarga', 'f.id_keluarga')
                        ->orderBy('w.created_at')
                        ->limit(1),
                    'hubungan_wali'
                )
                ->selectSub(
                    DB::table('kontak_keluarga as kk')
                        ->select('kk.nomor')
                        ->whereColumn('kk.id_keluarga', 'f.id_keluarga')
                        ->orderBy('kk.urutan')
                        ->limit(1),
                    'nomor_wali'
                )
                ->get();
            $data['registrationDocuments'] = DB::table('dokumen_pendaftaran as d')
                ->join('pendaftaran as p', 'p.id_pendaftaran', '=', 'd.id_pendaftaran')
                ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
                ->whereIn('p.status', ['Diajukan', 'Diverifikasi'])
                ->orderBy('d.created_at')
                ->get([
                    'd.id_dokumen',
                    'd.id_pendaftaran',
                    'd.jenis',
                    'd.file_path',
                    'd.status_verifikasi',
                    'd.catatan',
                    's.nama_lengkap',
                ])
                ->map(function ($document) {
                    $disk = Storage::disk('local');
                    $document->file_size_bytes = $document->file_path && $disk->exists($document->file_path)
                        ? $disk->size($document->file_path)
                        : null;

                    return $document;
                });
        }

        if ($section === 'pendaftaran' && $role === 'parent') {
            $applications = DB::table('pendaftaran as p')
                ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
                ->whereIn('p.id_siswa', $scope['studentIds'])
                ->whereIn('p.status', ['Diajukan', 'Diverifikasi'])
                ->orderByDesc('p.created_at')
                ->get([
                    'p.id_pendaftaran',
                    'p.status',
                    's.nama_lengkap',
                ]);
            $applicationIds = $applications->pluck('id_pendaftaran')->all();
            $documents = $applicationIds === []
                ? collect()
                : DB::table('dokumen_pendaftaran')
                    ->whereIn('id_pendaftaran', $applicationIds)
                    ->get(['id_dokumen', 'id_pendaftaran', 'jenis', 'status_verifikasi', 'catatan'])
                    ->groupBy('id_pendaftaran');

            $data['parentApplications'] = $applications->map(function ($application) use ($documents): object {
                $applicationDocuments = $documents->get($application->id_pendaftaran, collect())->keyBy('jenis');
                $application->requiredDocuments = collect(config('registration.required_documents'))
                    ->map(function ($label, $key) use ($applicationDocuments): array {
                        return [
                            'key' => $key,
                            'label' => $label,
                            'document' => $applicationDocuments->get($label),
                        ];
                    })
                    ->values();

                return $application;
            });
        }

        if ($section === 'siswa' && $role === 'admin') {
            $data['studentProfiles'] = DB::table('siswa')
                ->orderBy('nama_lengkap')
                ->paginate(30, [
                    'id_siswa',
                    'nama_lengkap',
                    'nama_panggilan',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'jenis_kelamin',
                    'alamat_domisili',
                ], 'profile_page')
                ->withQueryString();
        }

        if ($section === 'tugas') {
            if ($role === 'teacher') {
                $data['submissions'] = DB::table('pengumpulan_tugas as pt')
                    ->join('tugas as t', 't.id_tugas', '=', 'pt.id_tugas')
                    ->join('rombel_siswa as r', 'r.id_rombel', '=', 'pt.id_rombel')
                    ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                    ->whereIn('t.id_kelas', $scope['classIds'])
                    ->orderByDesc('pt.dikumpulkan_pada')
                    ->get([
                        'pt.id_pengumpulan',
                        't.id_kelas',
                        't.judul',
                        's.nama_lengkap',
                        'pt.dikumpulkan_pada',
                        DB::raw("CASE WHEN pt.nilai IS NULL THEN 'Menunggu Penilaian' ELSE 'Dinilai' END as status"),
                        'pt.nilai',
                        'pt.umpan_balik',
                        'pt.file_path',
                    ]);
            } elseif ($role === 'parent') {
                $data['parentTasks'] = DB::table('tugas as t')
                    ->join('rombel_siswa as r', 'r.id_kelas', '=', 't.id_kelas')
                    ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                    ->leftJoin('pengumpulan_tugas as pt', function ($join): void {
                        $join->on('pt.id_tugas', '=', 't.id_tugas')
                            ->on('pt.id_rombel', '=', 'r.id_rombel');
                    })
                    ->whereIn('r.id_siswa', $scope['studentIds'])
                    ->where('r.status', 'Aktif')
                    ->orderByDesc('t.tenggat')
                    ->get([
                        't.id_tugas',
                        'r.id_siswa',
                        't.judul',
                        't.lampiran as has_attachment',
                        's.nama_lengkap',
                        't.tenggat',
                        'pt.id_pengumpulan',
                        'pt.dikumpulkan_pada',
                        'pt.file_path as submission_file',
                    ]);
            }
        }

        if ($section === 'nilai' && $role === 'teacher') {
            $data['indicators'] = DB::table('indikator_iqro')->orderBy('kelompok')->orderBy('urutan')->get([
                'id_indikator', 'kelompok', 'kategori', 'nama',
            ]);
        }

        if ($section === 'rapor' && $role === 'parent') {
            $data['parentReports'] = DB::table('v_rapor_lengkap')
                ->whereIn('id_siswa', $scope['studentIds'])
                ->whereNotNull('id_rapor')
                ->orderByDesc('tanggal_rapor')
                ->get([
                    'id_rapor', 'id_siswa', 'nama_lengkap', 'semester', 'status',
                    'narasi_agama_budi_pekerti', 'narasi_jati_diri',
                    'narasi_literasi_steam', 'narasi_p5', 'refleksi_orang_tua',
                ]);
        }

        if ($section === 'kegiatan') {
            $activities = DB::table('v_laporan_kegiatan')
                ->orderByDesc('tanggal');
            if (in_array($role, ['teacher', 'parent'], true)) {
                $activities->where(function (Builder $where) use ($scope, $role, $user): void {
                    $where->where('seluruh_sekolah', true)
                        ->orWhereExists(function (Builder $activityClass) use ($scope): void {
                            $activityClass->selectRaw('1')
                                ->from('kegiatan_kelas as kk')
                                ->whereColumn('kk.id_kegiatan', 'v_laporan_kegiatan.id_kegiatan')
                                ->whereIn('kk.id_kelas', $scope['classIds']);
                        });
                    if ($role === 'teacher') {
                        $where->orWhereExists(function (Builder $createdActivity) use ($user): void {
                            $createdActivity->selectRaw('1')
                                ->from('kegiatan as k')
                                ->whereColumn('k.id_kegiatan', 'v_laporan_kegiatan.id_kegiatan')
                                ->where('k.id_user_pembuat', $user->getKey());
                        });
                    }
                });
            }
            $data['activities'] = $activities->get([
                'id_kegiatan', 'nama_kegiatan', 'tanggal', 'seluruh_sekolah',
            ]);
            $data['activityPhotos'] = DB::table('dokumentasi_kegiatan as d')
                ->join('kegiatan as k', 'k.id_kegiatan', '=', 'd.id_kegiatan')
                ->leftJoin('kegiatan_kelas as kk', 'kk.id_kegiatan', '=', 'k.id_kegiatan')
                ->where(function (Builder $query) use ($role, $scope, $user): void {
                    if ($role === 'teacher') {
                        $query->where('k.seluruh_sekolah', true)
                            ->orWhereIn('kk.id_kelas', $scope['classIds'])
                            ->orWhere('k.id_user_pembuat', $user->getKey());
                    } elseif ($role === 'parent') {
                        $query->where('k.seluruh_sekolah', true)
                            ->orWhereIn('kk.id_kelas', $scope['classIds']);
                    }
                })
                ->orderByDesc('d.created_at')
                ->get([
                    'd.id_dokumentasi', 'd.tahap', 'd.keterangan', 'd.created_at',
                    'k.nama_kegiatan', 'k.tanggal',
                ]);
        }

        if (in_array($section, ['tagihan', 'pembayaran'], true)) {
            $bills = DB::table('v_tagihan_detail')->orderByDesc('jatuh_tempo');
            if ($role === 'parent') {
                $bills->whereIn('id_siswa', $scope['studentIds']);
            }
            $data['bills'] = $bills->get([
                'id_tagihan', 'id_siswa', 'nama_lengkap', 'jenis_biaya',
                'periode', 'total_tagihan', 'dibayar', 'sisa', 'jatuh_tempo',
            ]);
        }

        if ($section === 'tagihan' && $role === 'admin') {
            $schoolYear = DB::table('tahun_ajaran')->where('is_aktif', true)->first();
            $data['activeSchoolYear'] = $schoolYear;
            $data['billRosters'] = $schoolYear
                ? DB::table('rombel_siswa as r')
                    ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                    ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                    ->where('r.status', 'Aktif')
                    ->where('k.id_tahun_ajaran', $schoolYear->id_tahun_ajaran)
                    ->orderBy('s.nama_lengkap')
                    ->get([
                        'r.id_rombel', 's.nama_lengkap', 'k.nama_kelas', 'k.kelompok',
                    ])
                : collect();
            $data['monthlyTariffs'] = $schoolYear
                ? DB::table('tarif as tf')
                    ->join('jenis_biaya as jb', 'jb.id_jenis_biaya', '=', 'tf.id_jenis_biaya')
                    ->where('tf.id_tahun_ajaran', $schoolYear->id_tahun_ajaran)
                    ->where('jb.kategori', 'Bulanan')
                    ->orderBy('tf.kelompok')
                    ->orderBy('jb.nama')
                    ->get(['tf.id_jenis_biaya', 'tf.kelompok', 'tf.nominal', 'jb.nama'])
                : collect();
        }

        if ($section === 'pembayaran' && $role === 'admin') {
            $data['pendingPayments'] = DB::table('v_pembayaran_detail')
                ->where('status', 'Menunggu Verifikasi')
                ->orderByDesc('tanggal_bayar')
                ->get([
                    'id_pembayaran', 'no_kuitansi', 'tanggal_bayar',
                    'metode', 'total_bayar',
                ]);
        }

        if ($section === 'kegiatan' && $role === 'admin') {
            $data['fieldTrips'] = DB::table('kegiatan as k')
                ->where('k.jenis', 'Field Trip')
                ->orderByDesc('k.tanggal')
                ->select([
                    'k.id_kegiatan',
                    'k.nama_kegiatan',
                    'k.tanggal',
                    'k.lokasi',
                    'k.biaya',
                ])
                ->selectSub(
                    DB::table('tagihan as t')
                        ->whereColumn('t.id_kegiatan', 'k.id_kegiatan')
                        ->selectRaw('COUNT(*)'),
                    'tagihan_dibuat'
                )
                ->get();
        }

        if ($section === 'pembayaran') {
            $data['paymentSettings'] = DB::table('payment_settings')->where('id', 1)->first();
            $data['payableBills'] = collect($data['bills'] ?? [])
                ->filter(fn (object $bill): bool => (float) $bill->sisa > 0)
                ->values();
        }

        if ($section === 'keluarga' && $role === 'admin') {
            $data['unlinkedFamilies'] = DB::table('keluarga as f')
                ->join('siswa as s', 's.id_keluarga', '=', 'f.id_keluarga')
                ->whereNull('f.id_user')
                ->orderBy('f.id_keluarga')
                ->get(['f.id_keluarga', 's.nama_lengkap'])
                ->groupBy('id_keluarga')
                ->map(fn ($students, $familyId): object => (object) [
                    'id_keluarga' => $familyId,
                    'studentNames' => $students->pluck('nama_lengkap')->filter()->implode(', '),
                ])
                ->values();
            $data['unlinkedParents'] = DB::table('users as u')
                ->leftJoin('keluarga as f', 'f.id_user', '=', 'u.id_user')
                ->where('u.role', 'orang_tua')
                ->where('u.is_active', true)
                ->whereNull('f.id_keluarga')
                ->orderBy('u.name')
                ->get(['u.id_user', 'u.name', 'u.email']);
        }

        if ($section === 'tabungan' && $role === 'admin') {
            $data['activeStudents'] = DB::table('rombel_siswa as r')
                ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                ->where('r.status', 'Aktif')
                ->orderBy('s.nama_lengkap')
                ->get(['s.id_siswa', 's.nama_lengkap']);
        }

        if ($section === 'tarif' && $role === 'admin') {
            $data['schoolYears'] = DB::table('tahun_ajaran')
                ->orderByDesc('tahun_mulai')
                ->get(['id_tahun_ajaran', 'nama']);
            $data['feeTypes'] = DB::table('jenis_biaya')
                ->orderBy('kategori')
                ->orderBy('nama')
                ->get(['id_jenis_biaya', 'nama', 'kategori']);
        }

        if ($section === 'perkembangan' && $role === 'teacher') {
            $data['portfolioRosters'] = $data['rosters'] ?? collect();
        }

        return $data;
    }

    private function scopeFor(User $user, string $role): array
    {
        $scope = ['studentIds' => [], 'classIds' => []];

        if ($role === 'teacher') {
            $teacherId = DB::table('guru')->where('id_user', $user->getKey())->value('id_guru');
            if ($teacherId !== null) {
                $scope['classIds'] = DB::table('guru_kelas')
                    ->where('id_guru', $teacherId)
                    ->pluck('id_kelas')
                    ->all();
            }
            if ($scope['classIds'] !== []) {
                $scope['studentIds'] = DB::table('rombel_siswa')
                    ->whereIn('id_kelas', $scope['classIds'])
                    ->where('status', 'Aktif')
                    ->pluck('id_siswa')
                    ->unique()
                    ->all();
            }
        } elseif ($role === 'parent') {
            $familyId = DB::table('keluarga')
                ->where('id_user', $user->getKey())
                ->value('id_keluarga');
            if ($familyId !== null) {
                $scope['studentIds'] = DB::table('siswa')
                    ->where('id_keluarga', $familyId)
                    ->pluck('id_siswa')
                    ->all();
            }
            if ($scope['studentIds'] !== []) {
                $scope['classIds'] = DB::table('rombel_siswa')
                    ->whereIn('id_siswa', $scope['studentIds'])
                    ->where('status', 'Aktif')
                    ->pluck('id_kelas')
                    ->unique()
                    ->all();
            }
        }

        return $scope;
    }

    private function menuFor(array $allowedModules): array
    {
        $menu = [];
        foreach ($allowedModules as $key) {
            $definition = self::MODULES[$key] ?? null;
            if ($definition === null) {
                $definition = $this->customModuleDefinition($key);
            }
            if ($definition !== null) {
                $menu[$key] = $definition['title'];
            }
        }

        return $menu;
    }

    private function moduleGroupsFor(array $allowedModules, string $role): array
    {
        $menu = $this->menuFor($allowedModules);
        $groups = [];

        foreach (self::MODULE_GROUPS as $group) {
            $groupModules = [];
            foreach ($group['modules'] as $module) {
                if (isset($menu[$module])) {
                    $groupModules[$module] = $menu[$module];
                }
            }

            if ($groupModules !== []) {
                $groups[] = [
                    'title' => $group['role_titles'][$role] ?? $group['title'],
                    'modules' => $groupModules,
                ];
            }
        }

        return $groups;
    }

    private function moduleDefinition(string $section, string $role): array
    {
        if ($section === 'siswa' && $role === 'teacher') {
            return [
                'title' => 'Data Siswa',
                'description' => 'Data siswa dan kelas yang Anda ampu tanpa informasi identitas sensitif.',
                'view' => 'v_siswa_guru',
                'columns' => [
                    'nama_lengkap' => 'Nama siswa',
                    'nama_kelas' => 'Kelas',
                    'kelompok' => 'Kelompok',
                    'tahun_ajaran' => 'Tahun ajaran',
                ],
                'key' => 'id_siswa',
                'scope' => 'student',
                'order' => 'nama_lengkap',
            ];
        }

        if ($section === 'tugas' && $role === 'parent') {
            return [
                'title' => 'Tugas Anak',
                'description' => 'Status pengumpulan tugas untuk anak yang terhubung ke akun ini.',
                'view' => 'v_pengumpulan_detail',
                'columns' => [
                    'judul' => 'Tugas',
                    'nama_lengkap' => 'Nama siswa',
                    'dikumpulkan_pada' => 'Waktu pengumpulan',
                    'tenggat' => 'Tenggat',
                    'status' => 'Status',
                    'nilai' => 'Nilai',
                ],
                'scope' => 'student',
                'order' => 'tenggat',
                'direction' => 'desc',
            ];
        }

        return self::MODULES[$section] ?? $this->customModuleDefinition($section) ?? abort(404);
    }

    private function customModuleDefinition(string $section): ?array
    {
        return match ($section) {
            'kelas' => ['title' => 'Data Kelas', 'description' => 'Kelas, tahun ajaran, dan siswa aktif.', 'columns' => [
                'nama_kelas' => 'Nama kelas', 'kelompok' => 'Kelompok', 'tahun_ajaran' => 'Tahun ajaran',
                'wali_kelas' => 'Wali kelas', 'jumlah_siswa' => 'Siswa aktif',
            ]],
            'pembelajaran' => ['title' => 'Rencana Pembelajaran', 'description' => 'Rencana pembelajaran berdasarkan kelas dan guru.', 'columns' => [
                'tanggal' => 'Tanggal', 'nama_kelas' => 'Kelas', 'tema' => 'Tema', 'subtema' => 'Subtema',
                'nama_guru' => 'Guru', 'status' => 'Status',
            ]],
            'nilai' => ['title' => 'Nilai Iqro', 'description' => 'Capaian indikator Iqro tiap siswa dan semester.', 'columns' => [
                'nama_lengkap' => 'Nama siswa', 'kategori' => 'Kategori', 'indikator' => 'Indikator',
                'semester_1' => 'Ganjil', 'semester_2' => 'Genap',
            ]],
            'kegiatan' => ['title' => 'Dokumentasi Kegiatan', 'description' => 'Foto kegiatan kelas yang diunggah guru dan dapat dilihat oleh orang tua siswa terkait.', 'columns' => [
                'nama_kegiatan' => 'Kegiatan', 'jenis' => 'Jenis', 'tanggal' => 'Tanggal', 'lokasi' => 'Lokasi',
                'kelas_terlibat' => 'Kelas', 'laporan_sebelum' => 'Sebelum', 'laporan_selama' => 'Selama',
                'laporan_sesudah' => 'Sesudah',
            ]],
            'pembayaran' => ['title' => 'Pembayaran', 'description' => 'Kuitansi dan status verifikasi pembayaran.', 'columns' => [
                'no_kuitansi' => 'Kuitansi', 'tanggal_bayar' => 'Tanggal', 'metode' => 'Metode',
                'status' => 'Status', 'jumlah_tagihan' => 'Jumlah tagihan', 'total_bayar' => 'Total bayar',
            ]],
            'pengguna' => ['title' => 'Pengguna', 'description' => 'Akun yang terdaftar pada sistem.', 'columns' => [
                'name' => 'Nama', 'email' => 'Email', 'role' => 'Peran', 'is_active' => 'Status akun',
            ]],
            default => null,
        };
    }

    private function recordsFor(string $section, array $module, array $scope, string $role, User $user): mixed
    {
        $query = match ($section) {
            'kelas' => $this->classQuery($scope, $role),
            'keluarga' => $this->familyQuery(),
            'pembelajaran' => $this->lessonQuery($scope, $role),
            'nilai' => $this->iqroQuery($scope, $role),
            'perkembangan' => $this->portfolioQuery($scope, $role),
            'kegiatan' => $this->activityQuery($scope, $role, $user->getKey()),
            'pembayaran' => $role === 'parent'
                ? $this->parentPaymentsQuery($scope)
                : DB::table('v_pembayaran_detail')
                    ->select(array_keys($module['columns']))
                    ->orderByDesc('tanggal_bayar'),
            'tarif' => DB::table('tarif as tf')
                ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'tf.id_tahun_ajaran')
                ->join('jenis_biaya as jb', 'jb.id_jenis_biaya', '=', 'tf.id_jenis_biaya')
                ->select([
                    'tf.id_tarif',
                    'ta.nama as tahun_ajaran',
                    'tf.kelompok',
                    'jb.nama',
                    'jb.kategori',
                    'tf.nominal',
                ])
                ->orderByDesc('ta.tahun_mulai')
                ->orderBy('tf.kelompok'),
            'audit' => DB::table('audit_log')
                ->select(['waktu', 'tabel', 'id_record', 'aksi', 'app_user_id', 'db_user'])
                ->orderByDesc('waktu'),
            'pengguna' => DB::table('users')->select(['id_user', ...array_keys($module['columns'])])->orderBy('name'),
            'tabungan' => $this->savingsQuery($scope, $role),
            'tugas' => $role === 'parent'
                ? $this->parentTasksQuery($scope)
                : $this->viewQuery($module, $scope, $role),
            default => $this->viewQuery($module, $scope, $role),
        };

        if (in_array($section, ['kelas', 'pembelajaran', 'nilai', 'perkembangan', 'kegiatan', 'pembayaran', 'pengguna', 'tabungan', 'tarif', 'audit'], true)) {
            return $query->paginate(30)->withQueryString();
        }

        $direction = $module['direction'] ?? 'asc';

        return $query->orderBy($module['order'] ?? array_key_first($module['columns']), $direction)
            ->paginate(30)
            ->withQueryString();
    }

    private function viewQuery(array $module, array $scope, string $role): Builder
    {
        $columns = array_keys($module['columns']);
        if (isset($module['key'])) {
            $columns[] = $module['key'];
        }
        $query = DB::table($module['view'])->select(array_unique($columns));

        if (in_array($role, ['teacher', 'parent'], true) && ($module['scope'] ?? 'all') === 'student') {
            $query->whereIn('id_siswa', $scope['studentIds']);
        } elseif (in_array($role, ['teacher', 'parent'], true) && ($module['scope'] ?? 'all') === 'class') {
            $query->whereIn('id_kelas', $scope['classIds']);
        }

        return $query;
    }

    private function familyQuery(): Builder
    {
        return DB::table('keluarga as f')
            ->leftJoin('v_keluarga_lengkap as vf', 'vf.id_keluarga', '=', 'f.id_keluarga')
            ->leftJoin('users as u', 'u.id_user', '=', 'f.id_user')
            ->select([
                'f.id_keluarga',
                'vf.nama_ayah',
                'vf.nama_ibu',
                'vf.no_hp_utama',
                'u.name as akun_orangtua',
                'f.no_kk',
                'f.alamat',
            ])
            ->selectSub(
                DB::table('siswa as s')
                    ->whereColumn('s.id_keluarga', 'f.id_keluarga')
                    ->selectRaw('COUNT(*)'),
                'jumlah_siswa'
            )
            ->selectSub(
                DB::table('wali as w')
                    ->whereColumn('w.id_keluarga', 'f.id_keluarga')
                    ->where('w.hubungan', 'Wali')
                    ->orderBy('w.id_wali')
                    ->limit(1)
                    ->select('w.nama_lengkap'),
                'nama_wali'
            )
            ->selectSub(
                DB::table('wali as w')
                    ->whereColumn('w.id_keluarga', 'f.id_keluarga')
                    ->orderBy('w.id_wali')
                    ->limit(1)
                    ->select('w.id_wali'),
                'edit_wali_id'
            )
            ->selectSub(
                DB::table('wali as w')
                    ->whereColumn('w.id_keluarga', 'f.id_keluarga')
                    ->orderBy('w.id_wali')
                    ->limit(1)
                    ->select('w.nama_lengkap'),
                'edit_wali_name'
            )
            ->selectSub(
                DB::table('wali as w')
                    ->whereColumn('w.id_keluarga', 'f.id_keluarga')
                    ->orderBy('w.id_wali')
                    ->limit(1)
                    ->select('w.hubungan'),
                'edit_wali_relationship'
            )
            ->selectSub(
                DB::table('kontak_keluarga as k')
                    ->whereColumn('k.id_keluarga', 'f.id_keluarga')
                    ->orderBy('k.urutan')
                    ->limit(1)
                    ->select('k.nomor'),
                'edit_primary_phone'
            )
            ->orderBy('f.id_keluarga');
    }

    private function classQuery(array $scope, string $role): Builder
    {
        $query = DB::table('kelas as k')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->leftJoin('guru_kelas as gk', function ($join): void {
                $join->on('gk.id_kelas', '=', 'k.id_kelas')->where('gk.peran', '=', 'Wali Kelas');
            })
            ->leftJoin('guru as g', 'g.id_guru', '=', 'gk.id_guru')
            ->select([
                'k.nama_kelas',
                'k.kelompok',
                'ta.nama as tahun_ajaran',
                'g.nama_lengkap as wali_kelas',
            ])
            ->selectSub(
                DB::table('rombel_siswa as r')
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('r.id_kelas', 'k.id_kelas')
                    ->where('r.status', 'Aktif'),
                'jumlah_siswa'
            );

        if ($role === 'teacher' || $role === 'parent') {
            $query->whereIn('k.id_kelas', $scope['classIds']);
        }

        return $query->orderByDesc('ta.tahun_mulai')->orderBy('k.nama_kelas');
    }

    private function lessonQuery(array $scope, string $role): Builder
    {
        $query = DB::table('rencana_pembelajaran as rp')
            ->join('kelas as k', 'k.id_kelas', '=', 'rp.id_kelas')
            ->join('guru as g', 'g.id_guru', '=', 'rp.id_guru')
            ->select([
                'rp.tanggal',
                'k.nama_kelas',
                'rp.tema',
                'rp.subtema',
                'g.nama_lengkap as nama_guru',
                'rp.status',
            ]);

        if ($role === 'teacher') {
            $query->whereIn('rp.id_kelas', $scope['classIds'])
                ->where('g.id_user', auth()->id());
        }

        return $query->orderByDesc('rp.tanggal');
    }

    private function iqroQuery(array $scope, string $role): Builder
    {
        $query = DB::table('v_rapor_iqro as i')
            ->join('siswa as s', 's.id_siswa', '=', 'i.id_siswa')
            ->select([
                's.nama_lengkap',
                'i.kategori',
                'i.indikator',
                'i.semester_1',
                'i.semester_2',
            ]);

        if (in_array($role, ['teacher', 'parent'], true)) {
            $query->whereIn('i.id_siswa', $scope['studentIds']);
        }

        return $query->orderBy('s.nama_lengkap')->orderBy('i.urutan');
    }

    private function portfolioQuery(array $scope, string $role): Builder
    {
        $query = DB::table('portofolio as p')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 'p.id_rombel')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->select([
                'p.id_portofolio',
                's.nama_lengkap',
                'k.nama_kelas',
                'p.tanggal',
                'p.narasi',
                'p.foto',
            ]);

        if ($role === 'teacher') {
            $query->whereIn('r.id_kelas', $scope['classIds']);
        } elseif ($role === 'parent') {
            $query->whereIn('r.id_siswa', $scope['studentIds']);
        }

        return $query->orderByDesc('p.tanggal');
    }

    private function activityQuery(array $scope, string $role, int $userId): Builder
    {
        $query = DB::table('v_laporan_kegiatan')->select([
            'nama_kegiatan', 'jenis', 'tanggal', 'lokasi', 'kelas_terlibat',
            'laporan_sebelum', 'laporan_selama', 'laporan_sesudah',
        ]);

        if (in_array($role, ['teacher', 'parent'], true)) {
            $query->where(function (Builder $where) use ($scope, $role, $userId): void {
                $where->where('seluruh_sekolah', true)
                    ->orWhereExists(function (Builder $classActivity) use ($scope): void {
                        $classActivity->selectRaw('1')
                            ->from('kegiatan_kelas as kk')
                            ->whereColumn('kk.id_kegiatan', 'v_laporan_kegiatan.id_kegiatan')
                            ->whereIn('kk.id_kelas', $scope['classIds']);
                    });
                if ($role === 'teacher') {
                    $where->orWhereExists(function (Builder $createdActivity) use ($userId): void {
                        $createdActivity->selectRaw('1')
                            ->from('kegiatan as k')
                            ->whereColumn('k.id_kegiatan', 'v_laporan_kegiatan.id_kegiatan')
                            ->where('k.id_user_pembuat', $userId);
                    });
                }
            });
        }

        return $query->orderByDesc('tanggal');
    }

    private function parentPaymentsQuery(array $scope): Builder
    {
        return DB::table('pembayaran as p')
            ->join('alokasi_pembayaran as a', 'a.id_pembayaran', '=', 'p.id_pembayaran')
            ->join('tagihan as t', 't.id_tagihan', '=', 'a.id_tagihan')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 't.id_rombel')
            ->whereIn('r.id_siswa', $scope['studentIds'])
            ->select([
                'p.id_pembayaran as no_kuitansi',
                'p.tanggal_bayar',
                'p.metode',
                'p.status',
            ])
            ->selectRaw('COUNT(DISTINCT t.id_tagihan) as jumlah_tagihan')
            ->selectRaw('SUM(a.jumlah) as total_bayar')
            ->groupBy('p.id_pembayaran', 'p.tanggal_bayar', 'p.metode', 'p.status')
            ->orderByDesc('p.tanggal_bayar');
    }

    private function parentTasksQuery(array $scope): Builder
    {
        return DB::table('tugas as t')
            ->join('rombel_siswa as r', 'r.id_kelas', '=', 't.id_kelas')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->leftJoin('pengumpulan_tugas as pt', function ($join): void {
                $join->on('pt.id_tugas', '=', 't.id_tugas')
                    ->on('pt.id_rombel', '=', 'r.id_rombel');
            })
            ->whereIn('r.id_siswa', $scope['studentIds'])
            ->where('r.status', 'Aktif')
            ->select([
                't.judul',
                's.nama_lengkap',
                'pt.dikumpulkan_pada',
                't.tenggat',
                'pt.nilai',
            ])
            ->selectRaw(
                "CASE WHEN pt.id_pengumpulan IS NULL THEN 'Belum dikumpulkan' "
                ."WHEN pt.nilai IS NOT NULL THEN 'Dinilai' "
                ."WHEN DATE(pt.dikumpulkan_pada) > t.tenggat THEN 'Terlambat' "
                ."ELSE 'Dikumpulkan' END as status"
            )
            ->orderByDesc('t.tenggat');
    }

    private function savingsQuery(array $scope, string $role): Builder
    {
        $query = DB::table('transaksi_tabungan as tt')
            ->join('siswa as s', 's.id_siswa', '=', 'tt.id_siswa')
            ->select([
                's.nama_lengkap',
                'tt.tanggal',
                'tt.jenis',
                'tt.jumlah',
                'tt.metode',
                'tt.keterangan',
            ]);

        if ($role === 'parent') {
            $query->whereIn('tt.id_siswa', $scope['studentIds']);
        }

        return $query->orderByDesc('tt.tanggal');
    }

    private function summaryFor(string $role, array $scope): array
    {
        if (in_array($role, ['admin', 'principal'], true)) {
            $overview = DB::table('v_dashboard_kepala_sekolah')->first();

            return [
                ['label' => 'Siswa aktif', 'value' => $overview->siswa_aktif ?? 0],
                ['label' => 'Pendaftaran menunggu', 'value' => $overview->pendaftar_menunggu ?? 0],
                ['label' => 'Penerimaan bulan ini', 'value' => $this->rupiah($overview->penerimaan_bulan_ini ?? 0)],
                ['label' => 'Total tunggakan', 'value' => $this->rupiah($overview->total_tunggakan ?? 0)],
                ['label' => 'Kehadiran hari ini', 'value' => ($overview->persen_hadir_hari_ini ?? 0).'%'],
                ['label' => 'Saldo tabungan', 'value' => $this->rupiah($overview->total_saldo_tabungan ?? 0)],
            ];
        }

        if ($role === 'teacher') {
            $activeStudents = DB::table('rombel_siswa')
                ->whereIn('id_kelas', $scope['classIds'])
                ->where('status', 'Aktif')
                ->distinct()
                ->count('id_siswa');
            $todayAttendance = DB::table('absensi')
                ->whereIn('id_rombel', DB::table('rombel_siswa')
                    ->select('id_rombel')
                    ->whereIn('id_kelas', $scope['classIds'])
                    ->where('status', 'Aktif'))
                ->whereDate('tanggal', now('Asia/Jakarta')->toDateString())
                ->count();

            return [
                ['label' => 'Siswa di kelas', 'value' => $activeStudents],
                ['label' => 'Absensi tercatat hari ini', 'value' => $todayAttendance],
                ['label' => 'Rencana pembelajaran', 'value' => DB::table('rencana_pembelajaran')->whereIn('id_kelas', $scope['classIds'])->count()],
            ];
        }

        $unpaid = DB::table('v_tagihan_detail')
            ->whereIn('id_siswa', $scope['studentIds'])
            ->where('sisa', '>', 0)
            ->sum('sisa');

        return [
            ['label' => 'Anak terhubung', 'value' => count($scope['studentIds'])],
            ['label' => 'Tagihan belum lunas', 'value' => $this->rupiah($unpaid)],
            ['label' => 'Saldo tabungan', 'value' => $this->rupiah(
                DB::table('v_saldo_tabungan')->whereIn('id_siswa', $scope['studentIds'])->sum('saldo')
            )],
        ];
    }

    private function roleName(string $role): string
    {
        return [
            'admin' => 'Administrator',
            'principal' => 'Kepala Sekolah',
            'teacher' => 'Guru',
            'parent' => 'Orang Tua',
        ][$role] ?? 'Pengguna';
    }

    private function rupiah(mixed $amount): string
    {
        return 'Rp '.number_format((float) $amount, 0, ',', '.');
    }
}
