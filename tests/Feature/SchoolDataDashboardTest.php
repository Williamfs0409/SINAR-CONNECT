<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolDataDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_summary_groups_module_tabs_by_school_function(): void
    {
        $this->createSiswaData();
        DB::statement(
            'CREATE VIEW v_dashboard_kepala_sekolah AS SELECT '
            .'0 AS siswa_aktif, 0 AS pendaftar_menunggu, 0 AS penerimaan_bulan_ini, '
            .'0 AS total_tunggakan, 0 AS persen_hadir_hari_ini, 0 AS total_saldo_tabungan'
        );

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Semua fitur administrator')
            ->assertSee('Data Sekolah')
            ->assertSee('Penerimaan Siswa')
            ->assertSee('Akademik')
            ->assertSee('Keuangan')
            ->assertSee('Laporan & Pengawasan')
            ->assertSee('Data Keluarga');
    }

    public function test_admin_siswa_page_reads_database_view(): void
    {
        $this->createSiswaData();
        DB::table('siswa')->insert([
            'id_siswa' => 1,
            'id_keluarga' => 1,
            'nama_lengkap' => 'Anak Database',
            'jenis_kelamin' => 'Perempuan',
            'nama_kelas' => 'TK A',
            'kelompok' => 'A',
            'tahun_ajaran' => '2025/2026',
        ]);

        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('dashboard.feature', ['section' => 'siswa']))
            ->assertOk()
            ->assertSee('Anak Database')
            ->assertSee('Data Sekolah')
            ->assertSee('Akademik')
            ->assertSee('Keuangan')
            ->assertSee('Laporan & Pengawasan')
            ->assertSee('data-record-search', false)
            ->assertSee('Pesan masuk');
    }

    public function test_parent_siswa_page_only_shows_students_in_their_family(): void
    {
        $this->createSiswaData();
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->id('id_keluarga');
            $table->unsignedBigInteger('id_user');
        });
        Schema::create('rombel_siswa', function (Blueprint $table): void {
            $table->id('id_rombel');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
            $table->string('status');
        });

        $parent = User::factory()->create(['role' => 'parent']);
        DB::table('keluarga')->insert([
            ['id_keluarga' => 1, 'id_user' => $parent->getKey()],
            ['id_keluarga' => 2, 'id_user' => $parent->getKey() + 1],
        ]);
        DB::table('siswa')->insert([
            [
                'id_siswa' => 1,
                'id_keluarga' => 1,
                'nama_lengkap' => 'Anak Orang Tua',
                'jenis_kelamin' => 'Perempuan',
                'nama_kelas' => 'TK A',
                'kelompok' => 'A',
                'tahun_ajaran' => '2025/2026',
            ],
            [
                'id_siswa' => 2,
                'id_keluarga' => 2,
                'nama_lengkap' => 'Siswa Lain',
                'jenis_kelamin' => 'Laki-laki',
                'nama_kelas' => 'TK B',
                'kelompok' => 'B',
                'tahun_ajaran' => '2025/2026',
            ],
        ]);
        DB::table('rombel_siswa')->insert([
            ['id_rombel' => 1, 'id_siswa' => 1, 'id_kelas' => 1, 'status' => 'Aktif'],
            ['id_rombel' => 2, 'id_siswa' => 2, 'id_kelas' => 2, 'status' => 'Aktif'],
        ]);

        $this->actingAs($parent)
            ->get(route('dashboard.feature', ['section' => 'siswa']))
            ->assertOk()
            ->assertSee('Anak Orang Tua')
            ->assertSee('Anak Saya')
            ->assertSee('Keuangan Anak')
            ->assertDontSee('Data Guru')
            ->assertDontSee('Rekap Pembayaran')
            ->assertDontSee('Siswa Lain');
    }

    public function test_teacher_siswa_page_uses_the_view_without_sensitive_identity_fields(): void
    {
        $this->createSiswaData();
        Schema::create('guru', function (Blueprint $table): void {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_user');
        });
        Schema::create('guru_kelas', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_guru');
            $table->unsignedBigInteger('id_kelas');
        });
        Schema::create('rombel_siswa', function (Blueprint $table): void {
            $table->id('id_rombel');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
            $table->string('status');
        });

        DB::table('tahun_ajaran')->insert([
            'id_tahun_ajaran' => 1,
            'nama' => '2025/2026',
            'tahun_mulai' => 2025,
            'is_aktif' => true,
        ]);
        DB::table('kelas')->insert([
            'id_kelas' => 1,
            'id_tahun_ajaran' => 1,
            'kelompok' => 'TK A',
            'nama_kelas' => 'Kelas A',
        ]);
        DB::table('siswa')->insert([
            'id_siswa' => 1,
            'id_keluarga' => 1,
            'nama_lengkap' => 'Anak Guru Uji',
            'jenis_kelamin' => 'Perempuan',
            'nama_kelas' => 'Kelas A',
            'kelompok' => 'TK A',
            'tahun_ajaran' => '2025/2026',
        ]);
        $teacher = User::factory()->create(['role' => 'teacher']);
        DB::table('guru')->insert(['id_guru' => 1, 'id_user' => $teacher->getKey()]);
        DB::table('guru_kelas')->insert(['id_guru' => 1, 'id_kelas' => 1]);
        DB::table('rombel_siswa')->insert([
            'id_rombel' => 1,
            'id_siswa' => 1,
            'id_kelas' => 1,
            'status' => 'Aktif',
        ]);

        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'siswa']))
            ->assertOk()
            ->assertSee('Anak Guru Uji')
            ->assertSee('Kelas & Siswa')
            ->assertSee('Akademik')
            ->assertDontSee('Keuangan')
            ->assertDontSee('NIK')
            ->assertDontSee('NISN');
    }

    public function test_teacher_siswa_and_academic_modules_only_show_active_year_assigned_class_without_duplicates(): void
    {
        $this->createSiswaData();
        Schema::create('guru', function (Blueprint $table): void {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_user');
            $table->string('nama_lengkap')->default('Guru Uji');
        });
        Schema::create('guru_kelas', function (Blueprint $table): void {
            $table->unsignedBigInteger('id_guru');
            $table->unsignedBigInteger('id_kelas');
            $table->string('peran')->default('Wali Kelas');
        });
        Schema::create('rombel_siswa', function (Blueprint $table): void {
            $table->id('id_rombel');
            $table->unsignedBigInteger('id_siswa');
            $table->unsignedBigInteger('id_kelas');
            $table->string('status');
        });

        DB::table('tahun_ajaran')->insert([
            ['id_tahun_ajaran' => 1, 'nama' => '2024-2025', 'tahun_mulai' => 2024, 'is_aktif' => false],
            ['id_tahun_ajaran' => 2, 'nama' => '2025-2026', 'tahun_mulai' => 2025, 'is_aktif' => false],
            ['id_tahun_ajaran' => 3, 'nama' => '2026-2027', 'tahun_mulai' => 2026, 'is_aktif' => true],
        ]);
        DB::table('kelas')->insert([
            ['id_kelas' => 1, 'id_tahun_ajaran' => 1, 'kelompok' => 'KB', 'nama_kelas' => 'PG'],
            ['id_kelas' => 2, 'id_tahun_ajaran' => 2, 'kelompok' => 'TK A', 'nama_kelas' => 'Abu Bakar'],
            ['id_kelas' => 3, 'id_tahun_ajaran' => 3, 'kelompok' => 'TK B', 'nama_kelas' => 'Marwah'],
            ['id_kelas' => 4, 'id_tahun_ajaran' => 3, 'kelompok' => 'TK B', 'nama_kelas' => 'Safa'],
        ]);

        DB::table('siswa')->insert([
            [
                'id_siswa' => 1,
                'id_keluarga' => 1,
                'nama_lengkap' => 'Arsyila Qiana Afiqah',
                'jenis_kelamin' => 'Perempuan',
                'nama_kelas' => 'Marwah',
                'kelompok' => 'TK B',
                'tahun_ajaran' => '2026-2027',
            ],
            [
                'id_siswa' => 2,
                'id_keluarga' => 2,
                'nama_lengkap' => 'Muhammad Ghiffari Lubis',
                'jenis_kelamin' => 'Laki-Laki',
                'nama_kelas' => 'Safa',
                'kelompok' => 'TK B',
                'tahun_ajaran' => '2026-2027',
            ],
        ]);

        DB::table('rombel_siswa')->insert([
            ['id_rombel' => 10, 'id_siswa' => 1, 'id_kelas' => 1, 'status' => 'Lulus'],
            ['id_rombel' => 11, 'id_siswa' => 1, 'id_kelas' => 2, 'status' => 'Aktif'],
            ['id_rombel' => 12, 'id_siswa' => 1, 'id_kelas' => 3, 'status' => 'Aktif'],
            ['id_rombel' => 20, 'id_siswa' => 2, 'id_kelas' => 4, 'status' => 'Aktif'],
        ]);

        $teacher = User::factory()->create(['role' => 'teacher', 'name' => 'Lidya Wuri']);
        DB::table('guru')->insert(['id_guru' => 1, 'id_user' => $teacher->getKey(), 'nama_lengkap' => 'Lidya Wuri']);
        DB::table('guru_kelas')->insert([
            ['id_guru' => 1, 'id_kelas' => 2, 'peran' => 'Wali Kelas'],
            ['id_guru' => 1, 'id_kelas' => 3, 'peran' => 'Wali Kelas'],
        ]);

        $response = $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'siswa']))
            ->assertOk()
            ->assertSee('Arsyila Qiana Afiqah')
            ->assertSee('Marwah')
            ->assertSee('2026-2027')
            ->assertSee('1 data total')
            ->assertDontSee('Abu Bakar')
            ->assertDontSee('2025-2026')
            ->assertDontSee('2024-2025')
            ->assertDontSee('Muhammad Ghiffari Lubis');

        $this->assertSame(1, substr_count($response->getContent(), 'data-record-row'));

        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'siswa', 'q' => 'Abu Bakar']))
            ->assertOk()
            ->assertSee('0 data total')
            ->assertDontSee('Arsyila Qiana Afiqah');

        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'siswa', 'q' => 'Ghiffari']))
            ->assertOk()
            ->assertSee('0 data total')
            ->assertDontSee('Muhammad Ghiffari Lubis');

        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'kelas']))
            ->assertOk()
            ->assertSee('Marwah')
            ->assertSee('1 data total')
            ->assertDontSee('Abu Bakar')
            ->assertDontSee('Safa');

        DB::statement(
            'CREATE VIEW v_rekap_kehadiran_bulanan AS '
            ."SELECT 1 AS id_siswa, 'Arsyila Qiana Afiqah' AS nama_lengkap, 3 AS id_kelas, 'Marwah' AS nama_kelas, 2026 AS tahun, 11 AS bulan, 10 AS hadir, 0 AS izin, 0 AS sakit, 0 AS alfa, 100 AS persen_hadir "
            .'UNION ALL '
            ."SELECT 1 AS id_siswa, 'Arsyila Qiana Afiqah' AS nama_lengkap, 2 AS id_kelas, 'Abu Bakar' AS nama_kelas, 2025 AS tahun, 11 AS bulan, 8 AS hadir, 1 AS izin, 0 AS sakit, 0 AS alfa, 88.9 AS persen_hadir"
        );
        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'absensi']))
            ->assertOk()
            ->assertSee('Marwah')
            ->assertSee('1 data total')
            ->assertDontSee('Abu Bakar');

        DB::statement(
            'CREATE VIEW v_rapor_iqro AS '
            ."SELECT 12 AS id_rombel, 1 AS id_siswa, 1 AS id_indikator, 'Hafalan' AS kategori, 'Surah TK B' AS indikator, 1 AS urutan, 'BSB' AS semester_1, NULL AS semester_2 "
            .'UNION ALL '
            ."SELECT 11 AS id_rombel, 1 AS id_siswa, 2 AS id_indikator, 'Hafalan' AS kategori, 'Surah TK A Lama' AS indikator, 2 AS urutan, 'BSH' AS semester_1, NULL AS semester_2"
        );
        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'nilai']))
            ->assertOk()
            ->assertSee('Surah TK B')
            ->assertSee('1 data total')
            ->assertDontSee('Surah TK A Lama');

        DB::statement(
            'CREATE VIEW v_rapor_lengkap AS '
            ."SELECT 101 AS id_rapor, 12 AS id_rombel, 1 AS id_siswa, 'Arsyila Qiana Afiqah' AS nama_lengkap, 'Marwah' AS nama_kelas, 'TK B' AS kelompok, '2026-2027' AS tahun_ajaran, 'Ganjil' AS semester, 'Draft' AS status, '2026-12-20' AS tanggal_rapor, 110 AS tinggi_badan, 18 AS berat_badan, 0 AS sakit, 0 AS izin, 0 AS tanpa_keterangan, '' AS narasi_agama_budi_pekerti, '' AS narasi_jati_diri, '' AS narasi_literasi_steam, '' AS narasi_p5, '' AS refleksi_orang_tua, 'Lidya Wuri' AS guru_kelas, 'Lidya Wuri' AS guru_iqro, 'Kepsek' AS kepala_sekolah "
            .'UNION ALL '
            ."SELECT 100 AS id_rapor, 11 AS id_rombel, 1 AS id_siswa, 'Arsyila Qiana Afiqah' AS nama_lengkap, 'Abu Bakar' AS nama_kelas, 'TK A' AS kelompok, '2025-2026' AS tahun_ajaran, 'Genap' AS semester, 'Final' AS status, '2026-06-20' AS tanggal_rapor, 105 AS tinggi_badan, 16 AS berat_badan, 0 AS sakit, 0 AS izin, 0 AS tanpa_keterangan, '' AS narasi_agama_budi_pekerti, '' AS narasi_jati_diri, '' AS narasi_literasi_steam, '' AS narasi_p5, '' AS refleksi_orang_tua, 'Guru Lama' AS guru_kelas, 'Guru Lama' AS guru_iqro, 'Kepsek' AS kepala_sekolah"
        );
        $this->actingAs($teacher)
            ->get(route('dashboard.feature', ['section' => 'rapor']))
            ->assertOk()
            ->assertSee('Marwah')
            ->assertSee('1 data total')
            ->assertDontSee('Abu Bakar')
            ->assertDontSee('2025-2026');
    }

    public function test_parent_payment_form_displays_school_transfer_account(): void
    {
        $parentForm = view('dashboard.feature-actions', [
            'section' => 'pembayaran',
            'role' => 'parent',
            'actions' => [
                'paymentSettings' => (object) [
                    'bank_name' => 'Bank Syariah Indonesia (BSI)',
                    'account_holder' => 'Sri Hariati',
                    'account_number' => '7276048637',
                ],
                'payableBills' => collect([(object) [
                    'id_tagihan' => 1,
                    'nama_lengkap' => 'Siswa Uji',
                    'jenis_biaya' => 'SPP',
                    'sisa' => 350000,
                ]]),
            ],
        ])->render();

        $this->assertStringContainsString('Bank Syariah Indonesia (BSI)', $parentForm);
        $this->assertStringContainsString('7276048637', $parentForm);
        $this->assertStringContainsString('Sri Hariati', $parentForm);
        $this->assertStringContainsString('<option value="Transfer" selected>Transfer</option>', $parentForm);
        $this->assertStringNotContainsString('<option value="Tunai">', $parentForm);
        $this->assertStringContainsString('data-copy-target="payment-account-number"', $parentForm);

        $adminForm = view('dashboard.feature-actions', [
            'section' => 'pembayaran',
            'role' => 'admin',
            'actions' => [],
        ])->render();

        $this->assertStringNotContainsString('7276048637', $adminForm);
    }

    public function test_admin_user_form_has_password_visibility_toggles(): void
    {
        $form = view('dashboard.feature-actions', [
            'section' => 'pengguna',
            'role' => 'admin',
            'actions' => [],
        ])->render();

        $this->assertStringContainsString('aria-controls="database-user-password"', $form);
        $this->assertStringContainsString('aria-controls="database-user-password-confirmation"', $form);
        $this->assertSame(2, substr_count($form, 'class="password-toggle"'));
    }

    public function test_admin_can_create_a_parent_account_from_the_existing_family_page(): void
    {
        $form = view('dashboard.feature-actions', [
            'section' => 'keluarga',
            'role' => 'admin',
            'actions' => [
                'unlinkedFamilies' => collect([(object) [
                    'id_keluarga' => 12,
                    'studentNames' => 'Siswa Uji',
                ]]),
                'unlinkedParents' => collect(),
            ],
        ])->render();

        $this->assertStringContainsString(route('database.family.create-parent'), $form);
        $this->assertStringContainsString('Keluarga #12 · Siswa Uji', $form);
        $this->assertStringContainsString('name="email"', $form);
        $this->assertStringContainsString('name="password_confirmation"', $form);
        $this->assertStringContainsString('Buat akun dan hubungkan', $form);
    }

    public function test_admin_can_manage_field_trip_costs_and_generate_bills(): void
    {
        $form = view('dashboard.feature-actions', [
            'section' => 'kegiatan',
            'role' => 'admin',
            'actions' => [
                'fieldTrips' => collect([(object) [
                    'id_kegiatan' => 4,
                    'nama_kegiatan' => 'Kunjungan Edukasi',
                    'tanggal' => '2026-12-10',
                    'lokasi' => 'Medan',
                    'biaya' => 50000,
                    'tagihan_dibuat' => 0,
                ]]),
                'activityPhotos' => collect(),
            ],
        ])->render();

        $this->assertStringContainsString(route('database.activity.cost.update', ['activity' => 4]), $form);
        $this->assertStringContainsString(route('database.activity.bills.generate', ['activity' => 4]), $form);
        $this->assertStringContainsString('name="biaya"', $form);
        $this->assertStringContainsString('Terbitkan tagihan', $form);
    }

    private function createSiswaData(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table): void {
            $table->id('id_tahun_ajaran');
            $table->string('nama');
            $table->unsignedSmallInteger('tahun_mulai');
            $table->boolean('is_aktif')->default(false);
        });
        Schema::create('kelas', function (Blueprint $table): void {
            $table->id('id_kelas');
            $table->unsignedBigInteger('id_tahun_ajaran');
            $table->string('kelompok');
            $table->string('nama_kelas');
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id('id_siswa');
            $table->unsignedBigInteger('id_keluarga');
            $table->string('nama_lengkap');
            $table->string('nama_panggilan')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin');
            $table->string('alamat_domisili')->nullable();
            $table->string('nama_kelas');
            $table->string('kelompok');
            $table->string('tahun_ajaran');
        });

        DB::statement(
            'CREATE VIEW v_siswa_terlindungi AS SELECT id_siswa, nama_lengkap, jenis_kelamin, '
            .'NULL AS nik_samar, NULL AS nisn_samar, nama_kelas, tahun_ajaran, kelompok FROM siswa'
        );
        DB::statement(
            'CREATE VIEW v_siswa_guru AS SELECT id_siswa, nama_lengkap, nama_kelas, kelompok, tahun_ajaran FROM siswa'
        );
    }
}
