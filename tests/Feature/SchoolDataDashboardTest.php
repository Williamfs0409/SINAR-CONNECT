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
