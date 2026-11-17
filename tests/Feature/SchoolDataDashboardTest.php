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
            ->assertDontSee('Siswa Lain');
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

    private function createSiswaData(): void
    {
        Schema::create('siswa', function (Blueprint $table): void {
            $table->id('id_siswa');
            $table->unsignedBigInteger('id_keluarga');
            $table->string('nama_lengkap');
            $table->string('jenis_kelamin');
            $table->string('nama_kelas');
            $table->string('kelompok');
            $table->string('tahun_ajaran');
        });

        DB::statement(
            'CREATE VIEW v_siswa_terlindungi AS SELECT id_siswa, nama_lengkap, jenis_kelamin, '
            .'NULL AS nik_samar, NULL AS nisn_samar, nama_kelas, tahun_ajaran, kelompok FROM siswa'
        );
    }
}
