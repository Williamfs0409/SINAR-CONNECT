<?php

namespace App\Http\Controllers;

use App\Models\Message;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Query\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'diskon-siswa', 'kapasitas-kelas', 'keluarga-tanpa-akun',
            'keluarga', 'pengguna', 'kesiapan', 'kualitas', 'duplikat-siswa', 'audit',
        ],
        'principal' => [
            'siswa', 'pendaftaran', 'guru', 'kelas', 'absensi', 'pembelajaran',
            'tugas', 'nilai', 'perkembangan', 'rapor', 'kegiatan', 'tagihan', 'pembayaran',
            'tabungan', 'saldo-tabungan', 'tunggakan', 'rekap-pembayaran', 'rekap-pendaftar', 'tarif',
            'diskon-siswa', 'kapasitas-kelas', 'kesiapan', 'kualitas',
        ],
        'teacher' => [
            'siswa', 'kelas', 'absensi', 'pembelajaran', 'tugas', 'nilai',
            'perkembangan', 'rapor', 'kegiatan',
        ],
        'parent' => [
            'siswa', 'pendaftaran', 'absensi', 'tugas', 'nilai', 'rapor', 'kegiatan', 'tagihan',
            'pembayaran', 'tabungan', 'saldo-tabungan', 'tunggakan', 'perkembangan',
        ],
    ];

    private const MODULE_GROUPS = [
        'data' => [
            'title' => 'Data Sekolah',
            'role_titles' => ['teacher' => 'Kelas & Siswa', 'parent' => 'Anak Saya'],
            'modules' => ['siswa', 'guru', 'kelas', 'kapasitas-kelas', 'keluarga', 'pengguna'],
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
                'rekap-pembayaran', 'tarif', 'diskon-siswa',
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
            'key' => 'id_guru',
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
            'teacher_scope' => 'class',
            'order' => 'tahun',
            'direction' => 'desc',
        ],
        'tugas' => [
            'title' => 'Tugas',
            'description' => 'Tugas kelas dan rekap pengumpulan.',
            'view' => 'v_status_pengumpulan_tugas',
            'key' => 'id_tugas',
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
            'teacher_scope' => 'roster',
            'order' => 'tanggal_rapor',
            'direction' => 'desc',
        ],
        'tagihan' => [
            'title' => 'Tagihan',
            'description' => 'Tagihan, pembayaran terverifikasi, dan sisa tagihan.',
            'view' => 'v_tagihan_detail',
            'key' => 'id_tagihan',
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
        'diskon-siswa' => [
            'title' => 'Diskon Siswa',
            'description' => 'Daftar siswa aktif penerima potongan biaya SPP bulanan.',
            'view' => 'v_diskon_siswa',
            'columns' => [
                'nama_lengkap' => 'Nama siswa',
                'nama_kelas' => 'Kelas',
                'diskon' => 'Kategori diskon',
                'tipe' => 'Tipe',
                'nilai' => 'Besaran potongan',
            ],
            'scope' => 'all',
            'order' => 'nama_lengkap',
        ],
        'kapasitas-kelas' => [
            'title' => 'Kapasitas Kelas',
            'description' => 'Kapasitas ruang kelas, siswa aktif, dan sisa kursi per tahun ajaran.',
            'view' => 'v_kapasitas_kelas',
            'columns' => [
                'tahun_ajaran' => 'Tahun ajaran',
                'kelompok' => 'Kelompok',
                'nama_kelas' => 'Kelas',
                'kapasitas' => 'Kapasitas',
                'siswa_aktif' => 'Siswa aktif',
                'sisa_kursi' => 'Sisa kursi',
                'status' => 'Status kapasitas',
            ],
            'scope' => 'all',
            'order' => 'tahun_ajaran',
            'direction' => 'desc',
        ],
        'keluarga-tanpa-akun' => [
            'title' => 'Keluarga Tanpa Akun',
            'description' => 'Keluarga siswa aktif yang belum dihubungkan ke akun orang tua.',
            'view' => 'v_keluarga_tanpa_akun',
            'columns' => [
                'id_keluarga' => 'ID keluarga',
                'nama_ayah' => 'Ayah',
                'nama_ibu' => 'Ibu',
                'no_hp_utama' => 'Nomor HP utama',
                'anak_aktif' => 'Siswa aktif',
            ],
            'scope' => 'all',
            'order' => 'id_keluarga',
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

    public function globalSearch(Request $request): JsonResponse
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        $role = $user->role;
        abort_unless(array_key_exists($role, self::ROLE_MODULES), 403);

        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['items' => []]);
        }

        $allowedModules = self::ROLE_MODULES[$role] ?? [];
        $scope = $this->scopeFor($user, $role);
        $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $query).'%';
        $items = [];

        if (in_array('siswa', $allowedModules, true) && Schema::hasTable('siswa')) {
            $students = DB::table('siswa as s')
                ->when(in_array($role, ['teacher', 'parent'], true), fn (Builder $q) => $q->whereIn('s.id_siswa', $scope['studentIds']))
                ->where(function (Builder $w) use ($like): void {
                    $w->where('s.nama_lengkap', 'like', $like)
                        ->orWhere('s.nama_panggilan', 'like', $like);
                    if (Schema::hasColumn('siswa', 'nik')) {
                        $w->orWhere('s.nik', 'like', $like)
                            ->orWhere('s.nisn', 'like', $like)
                            ->orWhere('s.no_induk', 'like', $like);
                    }
                })
                ->orderBy('s.nama_lengkap')
                ->limit(5)
                ->get(['s.nama_lengkap', 's.nama_panggilan', 's.jenis_kelamin']);

            foreach ($students as $student) {
                $items[] = [
                    'category' => 'Data Siswa',
                    'label' => $student->nama_lengkap,
                    'meta' => trim(($student->nama_panggilan ?: '').' · '.$student->jenis_kelamin, ' ·'),
                    'url' => route('dashboard.feature', ['section' => 'siswa', 'q' => $student->nama_lengkap]),
                ];
            }
        }

        if (in_array('guru', $allowedModules, true) && Schema::hasTable('guru')) {
            $teachers = DB::table('guru')
                ->where(function (Builder $w) use ($like): void {
                    $w->where('nama_lengkap', 'like', $like)
                        ->orWhere('nuptk', 'like', $like)
                        ->orWhere('jabatan', 'like', $like);
                })
                ->orderBy('nama_lengkap')
                ->limit(5)
                ->get(['nama_lengkap', 'jabatan']);

            foreach ($teachers as $teacher) {
                $items[] = [
                    'category' => 'Data Guru',
                    'label' => $teacher->nama_lengkap,
                    'meta' => $teacher->jabatan ?: 'Guru',
                    'url' => route('dashboard.feature', ['section' => 'guru', 'q' => $teacher->nama_lengkap]),
                ];
            }
        }

        if (in_array('kelas', $allowedModules, true) && Schema::hasTable('kelas')) {
            $classes = DB::table('kelas')
                ->when($role === 'teacher', fn (Builder $q) => $q->whereIn('id_kelas', $scope['classIds']))
                ->where(function (Builder $w) use ($like): void {
                    $w->where('nama_kelas', 'like', $like)
                        ->orWhere('kelompok', 'like', $like);
                })
                ->orderBy('nama_kelas')
                ->limit(4)
                ->get(['nama_kelas', 'kelompok']);

            foreach ($classes as $class) {
                $items[] = [
                    'category' => 'Data Kelas',
                    'label' => $class->nama_kelas,
                    'meta' => 'Kelompok '.$class->kelompok,
                    'url' => route('dashboard.feature', ['section' => 'kelas', 'q' => $class->nama_kelas]),
                ];
            }
        }

        if (in_array('keluarga', $allowedModules, true) && Schema::hasTable('v_keluarga_lengkap')) {
            $families = DB::table('v_keluarga_lengkap')
                ->where(function (Builder $w) use ($like): void {
                    $w->where('nama_ayah', 'like', $like)
                        ->orWhere('nama_ibu', 'like', $like)
                        ->orWhere('no_kk', 'like', $like)
                        ->orWhere('no_hp_utama', 'like', $like);
                })
                ->limit(5)
                ->get(['nama_ayah', 'nama_ibu', 'no_hp_utama']);

            foreach ($families as $family) {
                $label = trim(implode(' / ', array_filter([$family->nama_ayah, $family->nama_ibu]))) ?: 'Keluarga';
                $keyword = $family->nama_ayah ?: ($family->nama_ibu ?: ($family->no_hp_utama ?: $query));
                $items[] = [
                    'category' => 'Data Keluarga',
                    'label' => $label,
                    'meta' => $family->no_hp_utama ?: 'Kontak belum ada',
                    'url' => route('dashboard.feature', ['section' => 'keluarga', 'q' => $keyword]),
                ];
            }
        }

        if (in_array('pengguna', $allowedModules, true) && Schema::hasTable('users')) {
            $users = DB::table('users')
                ->where(function (Builder $w) use ($like): void {
                    $w->where('name', 'like', $like)
                        ->orWhere('email', 'like', $like)
                        ->orWhere('role', 'like', $like);
                })
                ->orderBy('name')
                ->limit(5)
                ->get(['name', 'email', 'role']);

            foreach ($users as $account) {
                $items[] = [
                    'category' => 'Pengguna',
                    'label' => $account->name,
                    'meta' => trim(($account->email ?: '').' · '.$account->role, ' ·'),
                    'url' => route('dashboard.feature', ['section' => 'pengguna', 'q' => $account->name]),
                ];
            }
        }

        if (in_array('pendaftaran', $allowedModules, true) && Schema::hasTable('pendaftaran')) {
            $registrations = DB::table('pendaftaran')
                ->when($role === 'parent', fn (Builder $q) => $q->where('id_keluarga', $scope['familyId'] ?? -1))
                ->where(function (Builder $w) use ($like): void {
                    $w->where('nama_calon', 'like', $like)
                        ->orWhere('no_pendaftaran', 'like', $like)
                        ->orWhere('status', 'like', $like);
                })
                ->orderByDesc('tanggal_daftar')
                ->limit(4)
                ->get(['nama_calon', 'no_pendaftaran', 'status']);

            foreach ($registrations as $registration) {
                $items[] = [
                    'category' => 'Pendaftaran',
                    'label' => $registration->nama_calon,
                    'meta' => trim(($registration->no_pendaftaran ?: '').' · '.$registration->status, ' ·'),
                    'url' => route('dashboard.feature', ['section' => 'pendaftaran', 'q' => $registration->nama_calon]),
                ];
            }
        }

        if (in_array('tagihan', $allowedModules, true) && Schema::hasTable('v_tagihan_detail')) {
            $invoices = DB::table('v_tagihan_detail')
                ->when($role === 'parent', fn (Builder $q) => $q->whereIn('id_siswa', $scope['studentIds']))
                ->where(function (Builder $w) use ($like): void {
                    $w->where('nama_lengkap', 'like', $like)
                        ->orWhere('jenis_biaya', 'like', $like)
                        ->orWhere('periode', 'like', $like);
                })
                ->limit(4)
                ->get(['nama_lengkap', 'jenis_biaya', 'periode', 'status_bayar']);

            foreach ($invoices as $invoice) {
                $items[] = [
                    'category' => 'Tagihan',
                    'label' => $invoice->nama_lengkap.' — '.$invoice->jenis_biaya,
                    'meta' => trim(($invoice->periode ?: '').' · '.$invoice->status_bayar, ' ·'),
                    'url' => route('dashboard.feature', ['section' => 'tagihan', 'q' => $invoice->nama_lengkap]),
                ];
            }
        }

        if (in_array('tugas', $allowedModules, true) && Schema::hasTable('tugas')) {
            $tasks = DB::table('tugas')
                ->when(in_array($role, ['teacher', 'parent'], true), fn (Builder $q) => $q->whereIn('id_kelas', $scope['classIds']))
                ->where(function (Builder $w) use ($like): void {
                    $w->where('judul', 'like', $like)
                        ->orWhere('kategori', 'like', $like);
                })
                ->orderByDesc('tenggat')
                ->limit(4)
                ->get(['judul', 'kategori', 'tenggat']);

            foreach ($tasks as $task) {
                $items[] = [
                    'category' => 'Tugas',
                    'label' => $task->judul,
                    'meta' => trim(($task->kategori ?: '').' · Tenggat '.$task->tenggat, ' ·'),
                    'url' => route('dashboard.feature', ['section' => 'tugas', 'q' => $task->judul]),
                ];
            }
        }

        if (in_array('kegiatan', $allowedModules, true) && Schema::hasTable('kegiatan')) {
            $activities = DB::table('kegiatan')
                ->where(function (Builder $w) use ($like): void {
                    $w->where('nama_kegiatan', 'like', $like)
                        ->orWhere('jenis', 'like', $like)
                        ->orWhere('lokasi', 'like', $like);
                })
                ->orderByDesc('tanggal')
                ->limit(4)
                ->get(['nama_kegiatan', 'jenis', 'tanggal']);

            foreach ($activities as $activity) {
                $items[] = [
                    'category' => 'Kegiatan',
                    'label' => $activity->nama_kegiatan,
                    'meta' => trim(($activity->jenis ?: '').' · '.$activity->tanggal, ' ·'),
                    'url' => route('dashboard.feature', ['section' => 'kegiatan', 'q' => $activity->nama_kegiatan]),
                ];
            }
        }

        return response()->json(['items' => array_slice($items, 0, 20)]);
    }

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
            ->select(['k.id_kelas', 'k.nama_kelas', 'k.kelompok', 'ta.nama as tahun_ajaran', 'ta.is_aktif as tahun_ajaran_aktif'])
            ->orderByDesc('ta.is_aktif')
            ->orderByDesc('ta.tahun_mulai')
            ->orderBy('k.kelompok')
            ->orderBy('k.nama_kelas');

        if ($role === 'teacher' && $section === 'kegiatan') {
            $classes->where('ta.is_aktif', true)
                ->where('k.nama_kelas', '<>', 'Belum Ditentukan');
        } elseif ($role === 'parent' || $role === 'teacher') {
            $classes->whereIn('k.id_kelas', $scope['classIds']);
        }
        $data['classes'] = $classes->get();

        if (in_array($role, ['teacher', 'parent', 'admin'], true) && Schema::hasTable('rombel_siswa')) {
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
            if ($role === 'teacher') {
                $students->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
                    ->whereIn('r.id_kelas', $scope['classIds'])
                    ->where('ta.is_aktif', true);
            } elseif ($role === 'parent') {
                $students->whereIn('r.id_siswa', $scope['studentIds']);
            }
            $data['rosters'] = $students->get();
        }

        if ($section === 'absensi' && in_array($role, ['teacher', 'admin'], true) && Schema::hasTable('rombel_siswa') && Schema::hasTable('absensi')) {
            $allAttendanceClasses = $role === 'admin'
                ? $data['classes']->where('nama_kelas', '<>', 'Belum Ditentukan')->values()
                : $data['classes'];
            $academicYears = $allAttendanceClasses
                ->unique('tahun_ajaran')
                ->map(fn ($item) => (object) [
                    'tahun_ajaran' => $item->tahun_ajaran,
                    'tahun_ajaran_aktif' => (bool) ($item->tahun_ajaran_aktif ?? false),
                ])
                ->values();

            $selectedClassParam = request()->query('kelas');
            $selectedYearParam = request()->query('tahun_ajaran');

            if ($selectedYearParam !== null && $academicYears->contains('tahun_ajaran', $selectedYearParam)) {
                $selectedAcademicYear = $selectedYearParam;
            } elseif ($selectedClassParam !== null && $allAttendanceClasses->contains('id_kelas', (int) $selectedClassParam)) {
                $selectedAcademicYear = $allAttendanceClasses->firstWhere('id_kelas', (int) $selectedClassParam)->tahun_ajaran;
            } else {
                $selectedAcademicYear = $academicYears->first()->tahun_ajaran ?? null;
            }

            $attendanceClasses = $selectedAcademicYear !== null
                ? $allAttendanceClasses->where('tahun_ajaran', $selectedAcademicYear)->values()
                : $allAttendanceClasses;

            if ($selectedClassParam !== null) {
                abort_unless($allAttendanceClasses->contains('id_kelas', (int) $selectedClassParam), 403);
                $selectedClass = $attendanceClasses->contains('id_kelas', (int) $selectedClassParam)
                    ? (int) $selectedClassParam
                    : ($attendanceClasses->first()->id_kelas ?? null);
            } else {
                $selectedClass = $attendanceClasses->first()->id_kelas ?? null;
            }

            $date = request()->query('tanggal', now('Asia/Jakarta')->toDateString());
            $data['attendanceDate'] = $date;
            $data['selectedClass'] = $selectedClass;
            $data['selectedAcademicYear'] = $selectedAcademicYear;
            $data['attendanceAcademicYears'] = $academicYears;
            $data['attendanceClasses'] = $attendanceClasses;
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

        if ($section === 'absensi' && $role === 'parent' && Schema::hasTable('absensi') && Schema::hasTable('rombel_siswa')) {
            $data['parentAttendanceHistory'] = DB::table('absensi as a')
                ->join('rombel_siswa as r', 'r.id_rombel', '=', 'a.id_rombel')
                ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                ->whereIn('r.id_siswa', $scope['studentIds'])
                ->orderByDesc('a.tanggal')
                ->limit(60)
                ->get([
                    's.nama_lengkap',
                    'k.nama_kelas',
                    'a.tanggal',
                    'a.status',
                    'a.keterangan',
                ]);
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
            $data['documentsByRegistration'] = $data['registrationDocuments']->groupBy('id_pendaftaran');
            $data['teachers'] = Schema::hasTable('guru')
                ? DB::table('guru')
                    ->where('status', 'Aktif')
                    ->orderBy('nama_lengkap')
                    ->get(['id_guru', 'nama_lengkap', 'gelar_belakang'])
                : collect();
        }

        if ($section === 'pendaftaran' && $role === 'parent') {
            $applications = DB::table('pendaftaran as p')
                ->join('siswa as s', 's.id_siswa', '=', 'p.id_siswa')
                ->leftJoin('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'p.id_tahun_ajaran')
                ->whereIn('p.id_siswa', $scope['studentIds'])
                ->whereIn('p.status', ['Diajukan', 'Diverifikasi', 'Ditolak'])
                ->orderByDesc('p.created_at')
                ->get([
                    'p.id_pendaftaran',
                    'p.status',
                    'p.kelompok',
                    'p.catatan_verifikasi',
                    'p.tanggal_verifikasi',
                    'ta.nama as tahun_ajaran',
                    's.id_siswa',
                    's.nama_lengkap',
                    's.nama_panggilan',
                    's.jenis_kelamin',
                    's.tempat_lahir',
                    's.tanggal_lahir',
                    's.alamat_domisili',
                ]);
            $applicationIds = $applications->pluck('id_pendaftaran')->all();
            $documents = $applicationIds === []
                ? collect()
                : DB::table('dokumen_pendaftaran')
                    ->whereIn('id_pendaftaran', $applicationIds)
                    ->get(['id_dokumen', 'id_pendaftaran', 'jenis', 'file_path', 'status_verifikasi', 'catatan'])
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
            $studentColumns = [
                'id_siswa',
                'nama_lengkap',
                'nama_panggilan',
                'tempat_lahir',
                'tanggal_lahir',
                'jenis_kelamin',
                'alamat_domisili',
            ];
            if (Schema::hasColumn('siswa', 'nik')) {
                $studentColumns = array_merge($studentColumns, [
                    'id_keluarga',
                    'no_induk',
                    'nik',
                    'nisn',
                    'agama',
                    'anak_ke',
                ]);
            }

            $studentProfileQuery = DB::table('siswa')->orderBy('nama_lengkap');
            $studentSearch = trim((string) request()->query('q', ''));
            if ($studentSearch !== '') {
                $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $studentSearch).'%';
                $studentProfileQuery->where(function (Builder $where) use ($like): void {
                    $where->where('nama_lengkap', 'like', $like)
                        ->orWhere('nama_panggilan', 'like', $like)
                        ->orWhere('alamat_domisili', 'like', $like);
                    if (Schema::hasColumn('siswa', 'nik')) {
                        $where->orWhere('no_induk', 'like', $like)
                            ->orWhere('nik', 'like', $like)
                            ->orWhere('nisn', 'like', $like);
                    }
                });
            }

            $data['studentProfiles'] = $studentProfileQuery
                ->paginate(30, $studentColumns, 'profile_page')
                ->withQueryString();

            if (Schema::hasTable('keluarga')) {
                $familyQuery = DB::table('keluarga as f')->orderBy('f.id_keluarga');
                if (Schema::hasTable('v_keluarga_lengkap')) {
                    $familyQuery->leftJoin('v_keluarga_lengkap as vf', 'vf.id_keluarga', '=', 'f.id_keluarga')
                        ->select([
                            'f.id_keluarga',
                            'f.no_kk',
                            'f.alamat',
                            'vf.nama_ayah',
                            'vf.nama_ibu',
                            DB::raw("CONCAT_WS(' / ', vf.nama_ayah, vf.nama_ibu) as wali_names"),
                        ]);
                } else {
                    $familyQuery->select([
                        'f.id_keluarga',
                        DB::raw("'' as no_kk"),
                        DB::raw("'' as wali_names"),
                    ]);
                }
                $data['allFamilies'] = $familyQuery->get();
                $data['families'] = $data['allFamilies'];
            }

            if (Schema::hasTable('rombel_siswa') && Schema::hasTable('kelas')) {
                $activeClassCols = ['k.id_kelas', 'k.nama_kelas', 'k.kelompok', 'ta.nama as tahun_ajaran'];
                if (Schema::hasColumn('kelas', 'kapasitas')) {
                    $activeClassCols[] = 'k.kapasitas';
                }
                $data['activeClasses'] = DB::table('kelas as k')
                    ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
                    ->where('ta.is_aktif', true)
                    ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
                    ->orderBy('k.kelompok')
                    ->orderBy('k.nama_kelas')
                    ->get($activeClassCols);

                $data['discounts'] = Schema::hasTable('diskon')
                    ? DB::table('diskon')->orderBy('id_diskon')->get()
                    : collect();

                $placementColumns = [
                    'r.id_rombel',
                    'r.id_siswa',
                    'r.id_kelas',
                    's.nama_lengkap',
                    'k.nama_kelas',
                    'k.kelompok',
                    'ta.nama as tahun_ajaran',
                ];
                if (Schema::hasColumn('rombel_siswa', 'id_diskon')) {
                    $placementColumns[] = 'r.id_diskon';
                }

                $data['studentPlacements'] = DB::table('rombel_siswa as r')
                    ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                    ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
                    ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
                    ->where('r.status', 'Aktif')
                    ->orderByRaw("CASE WHEN k.nama_kelas = 'Belum Ditentukan' THEN 0 ELSE 1 END")
                    ->orderBy('s.nama_lengkap')
                    ->get($placementColumns);
            }
        }

        if ($section === 'guru' && $role === 'admin' && Schema::hasTable('guru')) {
            $data['teachers'] = DB::table('guru')->orderBy('nama_lengkap')->get();
            $data['teacherUsers'] = Schema::hasTable('users')
                ? DB::table('users')
                    ->whereIn('role', ['guru', 'kepala_sekolah', 'teacher', 'principal'])
                    ->orderBy('name')
                    ->get(['id_user', 'name', 'email', 'role'])
                : collect();
            $data['activeClasses'] = Schema::hasTable('kelas') && Schema::hasTable('tahun_ajaran')
                ? DB::table('kelas as k')
                    ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
                    ->where('ta.is_aktif', true)
                    ->where('k.nama_kelas', '<>', 'Belum Ditentukan')
                    ->orderBy('k.kelompok')
                    ->orderBy('k.nama_kelas')
                    ->get(['k.id_kelas', 'k.nama_kelas', 'k.kelompok', 'ta.nama as tahun_ajaran'])
                : collect();
            $data['classAssignments'] = Schema::hasTable('guru_kelas') && Schema::hasTable('kelas')
                ? DB::table('guru_kelas as gk')
                    ->join('guru as g', 'g.id_guru', '=', 'gk.id_guru')
                    ->join('kelas as k', 'k.id_kelas', '=', 'gk.id_kelas')
                    ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
                    ->where('ta.is_aktif', true)
                    ->orderBy('k.nama_kelas')
                    ->orderBy('gk.peran')
                    ->get(['gk.id_kelas', 'gk.id_guru', 'gk.peran', 'g.nama_lengkap', 'k.nama_kelas', 'k.kelompok'])
                : collect();
        }

        if ($section === 'kelas' && $role === 'admin' && Schema::hasTable('kelas')) {
            $data['schoolYears'] = Schema::hasTable('tahun_ajaran')
                ? DB::table('tahun_ajaran')->orderByDesc('tahun_mulai')->get()
                : collect();
            $teacherCols = ['id_guru', 'nama_lengkap'];
            if (Schema::hasColumn('guru', 'gelar_belakang')) {
                $teacherCols[] = 'gelar_belakang';
            }
            if (Schema::hasColumn('guru', 'jabatan')) {
                $teacherCols[] = 'jabatan';
            }
            $data['teachers'] = Schema::hasTable('guru')
                ? DB::table('guru')
                    ->when(Schema::hasColumn('guru', 'status'), fn ($q) => $q->where('status', 'Aktif'))
                    ->orderBy('nama_lengkap')
                    ->get($teacherCols)
                : collect();
            $classCols = ['k.id_kelas', 'k.id_tahun_ajaran', 'k.kelompok', 'k.nama_kelas', 'ta.nama as tahun_ajaran'];
            if (Schema::hasColumn('kelas', 'kapasitas')) {
                $classCols[] = 'k.kapasitas';
            }
            $managedClassesQuery = DB::table('kelas as k')
                ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
                ->select($classCols)
                ->orderByDesc('ta.tahun_mulai')
                ->orderBy('k.kelompok')
                ->orderBy('k.nama_kelas');
            if (Schema::hasTable('guru_kelas')) {
                $managedClassesQuery->selectSub(
                    DB::table('guru_kelas as gk')
                        ->whereColumn('gk.id_kelas', 'k.id_kelas')
                        ->where('gk.peran', 'Wali Kelas')
                        ->limit(1)
                        ->select('gk.id_guru'),
                    'id_wali_kelas'
                );
            }
            $data['managedClasses'] = $managedClassesQuery->get();
        }

        if ($section === 'duplikat-siswa' && $role === 'admin') {
            $data['allStudents'] = Schema::hasTable('siswa')
                ? DB::table('siswa')->orderBy('nama_lengkap')->get(['id_siswa', 'nama_lengkap', 'tanggal_lahir', 'id_keluarga'])
                : collect();
            $data['students'] = $data['allStudents'];

            if (Schema::hasTable('keluarga')) {
                $dupFamilyQuery = DB::table('keluarga as f')->orderBy('f.id_keluarga');
                if (Schema::hasTable('v_keluarga_lengkap')) {
                    $dupFamilyQuery->leftJoin('v_keluarga_lengkap as vf', 'vf.id_keluarga', '=', 'f.id_keluarga')
                        ->select([
                            'f.id_keluarga',
                            'f.no_kk',
                            'f.alamat',
                            DB::raw("CONCAT_WS(' / ', vf.nama_ayah, vf.nama_ibu) as wali_names"),
                            DB::raw('COALESCE(vf.jumlah_anak, 0) as jumlah_siswa'),
                        ]);
                } else {
                    $dupFamilyQuery->select([
                        'f.id_keluarga',
                        DB::raw("'' as no_kk"),
                        DB::raw("'' as alamat"),
                        DB::raw("'' as wali_names"),
                        DB::raw('0 as jumlah_siswa'),
                    ]);
                }
                $data['allFamilies'] = $dupFamilyQuery->get();
                $data['families'] = $data['allFamilies'];
            }
        }

        if ($section === 'tugas') {
            if (in_array($role, ['teacher', 'admin'], true) && Schema::hasTable('pengumpulan_tugas')) {
                $baseSubmissionsQuery = DB::table('pengumpulan_tugas as pt')
                    ->join('tugas as t', 't.id_tugas', '=', 'pt.id_tugas')
                    ->join('rombel_siswa as r', 'r.id_rombel', '=', 'pt.id_rombel')
                    ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa');
                if ($role === 'teacher') {
                    $baseSubmissionsQuery->whereIn('t.id_kelas', $scope['classIds']);
                }
                $submissionColumns = [
                    'pt.id_pengumpulan',
                    't.id_kelas',
                    't.judul',
                    's.nama_lengkap',
                    'pt.dikumpulkan_pada',
                    'pt.dinilai_pada',
                    DB::raw("CASE WHEN pt.nilai IS NULL THEN 'Menunggu Penilaian' ELSE 'Dinilai' END as status"),
                    'pt.nilai',
                    'pt.umpan_balik',
                    'pt.file_path',
                ];
                $data['submissions'] = (clone $baseSubmissionsQuery)
                    ->whereNull('pt.nilai')
                    ->orderByDesc('pt.dikumpulkan_pada')
                    ->get($submissionColumns);
                $data['gradedSubmissions'] = (clone $baseSubmissionsQuery)
                    ->whereNotNull('pt.nilai')
                    ->orderByDesc('pt.dinilai_pada')
                    ->orderByDesc('pt.dikumpulkan_pada')
                    ->get($submissionColumns);
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

        if ($section === 'nilai' && in_array($role, ['teacher', 'admin'], true) && Schema::hasTable('indikator_iqro')) {
            $indicatorsQuery = DB::table('indikator_iqro')->orderBy('kelompok')->orderBy('urutan');
            if ($role === 'teacher') {
                $teacherGroups = $data['classes']->pluck('kelompok')->unique()->values()->all();
                if ($teacherGroups !== []) {
                    $indicatorsQuery->whereIn('kelompok', $teacherGroups);
                }
            }
            $data['indicators'] = $indicatorsQuery->get([
                'id_indikator', 'kelompok', 'kategori', 'nama', 'urutan',
            ]);
        }

        if ($section === 'rapor') {
            $reportColumns = [
                'id_rapor', 'id_rombel', 'id_siswa', 'nama_lengkap', 'nama_kelas', 'kelompok', 'tahun_ajaran',
                'semester', 'status', 'tanggal_rapor', 'tinggi_badan', 'berat_badan',
                'sakit', 'izin', 'tanpa_keterangan',
                'narasi_agama_budi_pekerti', 'narasi_jati_diri',
                'narasi_literasi_steam', 'narasi_p5', 'refleksi_orang_tua',
                'guru_kelas', 'guru_iqro', 'kepala_sekolah',
            ];
            if ($role === 'parent') {
                $data['parentReports'] = DB::table('v_rapor_lengkap')
                    ->whereIn('id_siswa', $scope['studentIds'])
                    ->whereNotNull('id_rapor')
                    ->orderByDesc('tanggal_rapor')
                    ->get($reportColumns);
            } elseif (in_array($role, ['teacher', 'principal', 'admin'], true)) {
                $detailedReportsQuery = DB::table('v_rapor_lengkap')
                    ->whereNotNull('id_rapor')
                    ->orderByDesc('tanggal_rapor');
                if ($role === 'teacher') {
                    $detailedReportsQuery->whereIn('id_rombel', $scope['rosterIds']);
                }
                $data['detailedReports'] = $detailedReportsQuery->get($reportColumns);
                if (in_array($role, ['teacher', 'admin'], true) && Schema::hasTable('guru')) {
                    $iqroCols = ['id_guru', 'nama_lengkap'];
                    if (Schema::hasColumn('guru', 'gelar_belakang')) {
                        $iqroCols[] = 'gelar_belakang';
                    }
                    if (Schema::hasColumn('guru', 'jabatan')) {
                        $iqroCols[] = 'jabatan';
                    }
                    $data['teachers'] = DB::table('guru')
                        ->when(Schema::hasColumn('guru', 'status'), fn ($q) => $q->where('status', 'Aktif'))
                        ->orderBy('nama_lengkap')
                        ->get($iqroCols);
                    $data['iqroTeachers'] = $data['teachers'];
                }
            }
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
            $data['pendingPayments'] = DB::table('v_pembayaran_detail as v')
                ->join('pembayaran as p', 'p.id_pembayaran', '=', 'v.id_pembayaran')
                ->where('v.status', 'Menunggu Verifikasi')
                ->orderByDesc('v.tanggal_bayar')
                ->get([
                    'v.id_pembayaran', 'v.no_kuitansi', 'v.tanggal_bayar',
                    'v.metode', 'v.total_bayar', 'p.catatan',
                ]);
            $data['cancellablePayments'] = DB::table('v_pembayaran_detail as v')
                ->join('pembayaran as p', 'p.id_pembayaran', '=', 'v.id_pembayaran')
                ->whereIn('v.status', ['Terverifikasi', 'Menunggu Verifikasi'])
                ->orderByDesc('v.tanggal_bayar')
                ->limit(30)
                ->get([
                    'v.id_pembayaran', 'v.no_kuitansi', 'v.tanggal_bayar',
                    'v.metode', 'v.status', 'v.total_bayar', 'p.catatan',
                ]);
            $data['activeStudents'] = DB::table('rombel_siswa as r')
                ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
                ->where('r.status', 'Aktif')
                ->orderBy('s.nama_lengkap')
                ->distinct()
                ->get(['s.id_siswa', 's.nama_lengkap']);
            $data['pendingSavingsTransfers'] = Schema::hasTable('setoran_tabungan')
                ? DB::table('setoran_tabungan as st')
                    ->join('siswa as s', 's.id_siswa', '=', 'st.id_siswa')
                    ->where('st.status', 'Menunggu Verifikasi')
                    ->orderByDesc('st.created_at')
                    ->get([
                        'st.id',
                        'st.id_siswa',
                        's.nama_lengkap',
                        'st.jumlah',
                        'st.metode',
                        'st.catatan',
                        'st.created_at',
                    ])
                : collect();
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
            $data['paymentSettings'] = Schema::hasTable('payment_settings')
                ? DB::table('payment_settings')->where('id', 1)->first()
                : null;
            $data['payableBills'] = collect($data['bills'] ?? [])
                ->filter(fn (object $bill): bool => (float) $bill->sisa > 0)
                ->values();
            if ($role === 'parent') {
                $data['parentStudents'] = DB::table('siswa')
                    ->whereIn('id_siswa', $scope['studentIds'])
                    ->orderBy('nama_lengkap')
                    ->get(['id_siswa', 'nama_lengkap']);
                $data['parentSavingsTransfers'] = Schema::hasTable('setoran_tabungan')
                    ? DB::table('setoran_tabungan as st')
                        ->join('siswa as s', 's.id_siswa', '=', 'st.id_siswa')
                        ->whereIn('st.id_siswa', $scope['studentIds'])
                        ->orderByDesc('st.created_at')
                        ->limit(20)
                        ->get([
                            'st.id',
                            's.nama_lengkap',
                            'st.jumlah',
                            'st.metode',
                            'st.status',
                            'st.catatan',
                            'st.created_at',
                        ])
                    : collect();
            }
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
        $scope = ['studentIds' => [], 'classIds' => [], 'rosterIds' => []];

        if ($role === 'teacher') {
            $teacherId = DB::table('guru')->where('id_user', $user->getKey())->value('id_guru');
            if ($teacherId !== null) {
                $scope['classIds'] = DB::table('guru_kelas as gk')
                    ->join('kelas as k', 'k.id_kelas', '=', 'gk.id_kelas')
                    ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
                    ->where('gk.id_guru', $teacherId)
                    ->where('ta.is_aktif', true)
                    ->pluck('gk.id_kelas')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
            }
            if ($scope['classIds'] !== []) {
                $activeRosters = DB::table('rombel_siswa')
                    ->whereIn('id_kelas', $scope['classIds'])
                    ->where('status', 'Aktif')
                    ->get(['id_rombel', 'id_siswa']);
                $scope['rosterIds'] = $activeRosters
                    ->pluck('id_rombel')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
                    ->all();
                $scope['studentIds'] = $activeRosters
                    ->pluck('id_siswa')
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values()
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
            'kelas' => ['title' => 'Data Kelas', 'description' => 'Kelas, kapasitas, tahun ajaran, dan siswa aktif.', 'columns' => array_filter([
                'nama_kelas' => 'Nama kelas',
                'kelompok' => 'Kelompok',
                'tahun_ajaran' => 'Tahun ajaran',
                'wali_kelas' => 'Wali kelas',
                'kapasitas' => Schema::hasColumn('kelas', 'kapasitas') ? 'Kapasitas' : null,
                'jumlah_siswa' => 'Siswa aktif',
            ])],
            'pembelajaran' => ['title' => 'Rencana Pembelajaran', 'description' => 'Rencana pembelajaran berdasarkan kelas dan guru.', 'columns' => array_filter([
                'tanggal' => 'Tanggal',
                'jenis' => Schema::hasColumn('rencana_pembelajaran', 'jenis') ? 'Jenis' : null,
                'nama_kelas' => 'Kelas',
                'tema' => 'Tema',
                'subtema' => 'Subtema',
                'tujuan' => Schema::hasColumn('rencana_pembelajaran', 'tujuan') ? 'Tujuan & kegiatan' : null,
                'nama_guru' => 'Guru',
                'status' => 'Status',
            ])],
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
                'catatan' => 'Catatan',
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
            'siswa' => $role === 'teacher'
                ? $this->teacherStudentsQuery($scope)
                : $this->viewQuery($module, $scope, $role),
            'kelas' => $this->classQuery($scope, $role),
            'keluarga' => $this->familyQuery(),
            'pembelajaran' => $this->lessonQuery($scope, $role),
            'nilai' => $this->iqroQuery($scope, $role),
            'perkembangan' => $this->portfolioQuery($scope, $role),
            'kegiatan' => $this->activityQuery($scope, $role, $user->getKey()),
            'pembayaran' => $role === 'parent'
                ? $this->parentPaymentsQuery($scope)
                : DB::table('v_pembayaran_detail as v')
                    ->join('pembayaran as p', 'p.id_pembayaran', '=', 'v.id_pembayaran')
                    ->select([
                        'v.no_kuitansi',
                        'v.tanggal_bayar',
                        'v.metode',
                        'v.status',
                        'v.jumlah_tagihan',
                        'v.total_bayar',
                        'p.catatan',
                    ])
                    ->orderByDesc('v.tanggal_bayar'),
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

        if (! in_array($section, ['kelas', 'pembelajaran', 'nilai', 'perkembangan', 'kegiatan', 'pembayaran', 'pengguna', 'tabungan', 'tarif', 'audit'], true)
            && ! ($section === 'siswa' && $role === 'teacher')) {
            $direction = $module['direction'] ?? 'asc';
            $query->orderBy($module['order'] ?? array_key_first($module['columns']), $direction);
        }

        $search = trim((string) request()->query('q', ''));
        if ($search !== '') {
            $searchableColumns = array_keys($module['columns']);
            $like = '%'.str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search).'%';
            $query = DB::query()
                ->fromSub($query, 'searchable_rows')
                ->where(function (Builder $where) use ($searchableColumns, $like): void {
                    foreach ($searchableColumns as $column) {
                        $where->orWhere($column, 'like', $like);
                    }
                });
        }

        return $query->paginate(30)->withQueryString();
    }

    private function viewQuery(array $module, array $scope, string $role): Builder
    {
        $columns = array_keys($module['columns']);
        if (isset($module['key'])) {
            $columns[] = $module['key'];
        }
        $query = DB::table($module['view'])->select(array_unique($columns));

        $effectiveScope = $role === 'teacher'
            ? ($module['teacher_scope'] ?? $module['scope'] ?? 'all')
            : ($module['scope'] ?? 'all');

        if (in_array($role, ['teacher', 'parent'], true) && $effectiveScope === 'student') {
            $query->whereIn('id_siswa', $scope['studentIds']);
        } elseif (in_array($role, ['teacher', 'parent'], true) && $effectiveScope === 'class') {
            $query->whereIn('id_kelas', $scope['classIds']);
        } elseif ($role === 'teacher' && $effectiveScope === 'roster') {
            $query->whereIn('id_rombel', $scope['rosterIds']);
        }

        return $query;
    }

    private function teacherStudentsQuery(array $scope): Builder
    {
        return DB::table('rombel_siswa as r')
            ->join('siswa as s', 's.id_siswa', '=', 'r.id_siswa')
            ->join('kelas as k', 'k.id_kelas', '=', 'r.id_kelas')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->whereIn('r.id_kelas', $scope['classIds'])
            ->where('r.status', 'Aktif')
            ->where('ta.is_aktif', true)
            ->select([
                's.id_siswa',
                's.nama_lengkap',
                'k.nama_kelas',
                'k.kelompok',
                'ta.nama as tahun_ajaran',
            ])
            ->distinct()
            ->orderBy('s.nama_lengkap')
            ->orderBy('k.nama_kelas');
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
        $select = [
            'k.id_kelas',
            'k.nama_kelas',
            'k.kelompok',
            'ta.nama as tahun_ajaran',
            'g.nama_lengkap as wali_kelas',
        ];
        if (Schema::hasColumn('kelas', 'kapasitas')) {
            $select[] = 'k.kapasitas';
        }

        $query = DB::table('kelas as k')
            ->join('tahun_ajaran as ta', 'ta.id_tahun_ajaran', '=', 'k.id_tahun_ajaran')
            ->leftJoin('guru_kelas as gk', function ($join): void {
                $join->on('gk.id_kelas', '=', 'k.id_kelas')->where('gk.peran', '=', 'Wali Kelas');
            })
            ->leftJoin('guru as g', 'g.id_guru', '=', 'gk.id_guru')
            ->select($select)
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
        $select = [
            'rp.id_rencana',
            'rp.tanggal',
            'k.nama_kelas',
            'rp.tema',
            'rp.subtema',
            'rp.kegiatan',
            'rp.alat_bahan',
            'g.nama_lengkap as nama_guru',
            'rp.status',
        ];
        if (Schema::hasColumn('rencana_pembelajaran', 'jenis')) {
            $select[] = 'rp.jenis';
        }
        if (Schema::hasColumn('rencana_pembelajaran', 'tujuan')) {
            $select[] = 'rp.tujuan';
        }

        $query = DB::table('rencana_pembelajaran as rp')
            ->join('kelas as k', 'k.id_kelas', '=', 'rp.id_kelas')
            ->join('guru as g', 'g.id_guru', '=', 'rp.id_guru')
            ->select($select);

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

        if ($role === 'teacher') {
            $query->whereIn('i.id_rombel', $scope['rosterIds']);
        } elseif ($role === 'parent') {
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
            'id_kegiatan', 'nama_kegiatan', 'jenis', 'tanggal', 'lokasi', 'kelas_terlibat',
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
        $receiptExpr = DB::connection()->getDriverName() === 'mysql'
            ? 'fn_no_kuitansi(p.id_pembayaran, p.tanggal_bayar)'
            : "'KWT-' || COALESCE(strftime('%Y', p.tanggal_bayar), '2026') || '-' || printf('%05d', p.id_pembayaran)";

        return DB::table('pembayaran as p')
            ->join('alokasi_pembayaran as a', 'a.id_pembayaran', '=', 'p.id_pembayaran')
            ->join('tagihan as t', 't.id_tagihan', '=', 'a.id_tagihan')
            ->join('rombel_siswa as r', 'r.id_rombel', '=', 't.id_rombel')
            ->whereIn('r.id_siswa', $scope['studentIds'])
            ->select([
                DB::raw("{$receiptExpr} as no_kuitansi"),
                'p.tanggal_bayar',
                'p.metode',
                'p.status',
                'p.catatan',
            ])
            ->selectRaw('COUNT(DISTINCT t.id_tagihan) as jumlah_tagihan')
            ->selectRaw('SUM(a.jumlah) as total_bayar')
            ->groupBy('p.id_pembayaran', 'p.tanggal_bayar', 'p.metode', 'p.status', 'p.catatan')
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
