<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolFeatureExtendedTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_student_and_place_in_class_with_discount(): void
    {
        $this->createCoreSchoolTables();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        DB::table('tahun_ajaran')->insert([
            'id_tahun_ajaran' => 3,
            'nama' => '2026-2027',
            'tahun_mulai' => 2026,
            'is_aktif' => true,
        ]);
        DB::table('kelas')->insert([
            [
                'id_kelas' => 3,
                'id_tahun_ajaran' => 3,
                'kelompok' => 'TK B',
                'nama_kelas' => 'Belum Ditentukan',
                'kapasitas' => null,
            ],
            [
                'id_kelas' => 19,
                'id_tahun_ajaran' => 3,
                'kelompok' => 'TK B',
                'nama_kelas' => 'TK B Jannah',
                'kapasitas' => 12,
            ],
        ]);
        DB::table('diskon')->insert([
            'id_diskon' => 1,
            'nama' => 'Anak Guru',
            'tipe' => 'Persen',
            'nilai' => 50,
            'kategori_biaya' => 'Bulanan',
        ]);

        $this->actingAs($admin)
            ->post(route('database.student.store'), [
                'nama_lengkap' => 'Ahmad Zaki Alfarizi',
                'nama_panggilan' => 'Zaki',
                'nik' => '1271031505210009',
                'nisn' => '0123456789',
                'no_induk' => '2026.999',
                'tempat_lahir' => 'Medan',
                'tanggal_lahir' => '2021-05-15',
                'jenis_kelamin' => 'Laki-Laki',
                'agama' => 'Islam',
                'anak_ke' => 1,
                'alamat_domisili' => 'Jl. Kapten Muslim No. 12',
                'no_kk' => '1271031122334455',
                'nama_ayah' => 'Budi Alfarizi',
                'nama_ibu' => 'Siti Aminah',
                'nomor_telepon' => '081234567899',
                'id_kelas' => 3,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $student = DB::table('siswa')->where('nama_lengkap', 'Ahmad Zaki Alfarizi')->first();
        $this->assertNotNull($student);
        $this->assertDatabaseHas('wali', [
            'id_keluarga' => $student->id_keluarga,
            'hubungan' => 'Ayah',
            'nama_lengkap' => 'Budi Alfarizi',
        ]);
        $this->assertDatabaseHas('rombel_siswa', [
            'id_siswa' => $student->id_siswa,
            'id_kelas' => 3,
            'status' => 'Aktif',
        ]);

        $this->actingAs($admin)
            ->post(route('database.student.place'), [
                'id_siswa' => $student->id_siswa,
                'id_kelas' => 19,
                'id_diskon' => 1,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('rombel_siswa', [
            'id_siswa' => $student->id_siswa,
            'id_kelas' => 19,
            'id_diskon' => 1,
            'status' => 'Aktif',
        ]);
    }

    public function test_updating_family_with_both_ayah_and_ibu_does_not_violate_unique_relation(): void
    {
        $this->createCoreSchoolTables();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        DB::table('keluarga')->insert([
            'id_keluarga' => 1,
            'no_kk' => '1271030000000001',
            'alamat' => 'Alamat Lama',
        ]);
        DB::table('wali')->insert([
            ['id_wali' => 1, 'id_keluarga' => 1, 'hubungan' => 'Ayah', 'nama_lengkap' => 'Ayah Lama'],
            ['id_wali' => 2, 'id_keluarga' => 1, 'hubungan' => 'Ibu', 'nama_lengkap' => 'Ibu Lama'],
        ]);

        $this->actingAs($admin)
            ->patch(route('database.family.update', ['family' => 1]), [
                'no_kk' => '1271030000000001',
                'alamat' => 'Alamat Baru',
                'wali_name' => 'Ibu Baru Diperbarui',
                'hubungan' => 'Ibu',
                'nomor' => '081298765432',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('wali', [
            'id_keluarga' => 1,
            'hubungan' => 'Ayah',
            'nama_lengkap' => 'Ayah Lama',
        ]);
        $this->assertDatabaseHas('wali', [
            'id_keluarga' => 1,
            'hubungan' => 'Ibu',
            'nama_lengkap' => 'Ibu Baru Diperbarui',
        ]);
    }

    public function test_monthly_bill_generation_applies_student_discount(): void
    {
        $this->createCoreSchoolTables();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        DB::table('tahun_ajaran')->insert([
            'id_tahun_ajaran' => 3,
            'nama' => '2026-2027',
            'tahun_mulai' => 2026,
            'is_aktif' => true,
        ]);
        DB::table('kelas')->insert([
            'id_kelas' => 10,
            'id_tahun_ajaran' => 3,
            'kelompok' => 'TK A',
            'nama_kelas' => 'TK A Ar Rahim',
            'kapasitas' => 12,
        ]);
        DB::table('diskon')->insert([
            ['id_diskon' => 1, 'nama' => 'Anak Guru', 'tipe' => 'Persen', 'nilai' => 50, 'kategori_biaya' => 'Bulanan'],
            ['id_diskon' => 2, 'nama' => 'Kurang Mampu', 'tipe' => 'Nominal', 'nilai' => 100000, 'kategori_biaya' => 'Bulanan'],
        ]);
        DB::table('keluarga')->insert(['id_keluarga' => 1]);
        DB::table('siswa')->insert([
            ['id_siswa' => 1, 'id_keluarga' => 1, 'nama_lengkap' => 'Siswa Diskon Persen', 'tanggal_lahir' => '2021-01-01', 'jenis_kelamin' => 'Perempuan'],
            ['id_siswa' => 2, 'id_keluarga' => 1, 'nama_lengkap' => 'Siswa Diskon Nominal', 'tanggal_lahir' => '2021-02-01', 'jenis_kelamin' => 'Laki-Laki'],
        ]);
        DB::table('rombel_siswa')->insert([
            ['id_rombel' => 1, 'id_siswa' => 1, 'id_kelas' => 10, 'status' => 'Aktif', 'id_diskon' => 1],
            ['id_rombel' => 2, 'id_siswa' => 2, 'id_kelas' => 10, 'status' => 'Aktif', 'id_diskon' => 2],
        ]);
        DB::table('jenis_biaya')->insert(['id_jenis_biaya' => 1, 'nama' => 'SPP', 'kategori' => 'Bulanan']);
        DB::table('tarif')->insert([
            'id_tarif' => 1,
            'id_tahun_ajaran' => 3,
            'id_jenis_biaya' => 1,
            'kelompok' => 'TK A',
            'nominal' => 350000,
        ]);

        $this->actingAs($admin)
            ->post(route('database.bills.generate'), [
                'periode' => '2026-11',
                'jatuh_tempo' => '2026-11-10',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('tagihan', [
            'id_rombel' => 1,
            'nominal' => 350000,
            'diskon' => 175000,
        ]);
        $this->assertDatabaseHas('tagihan', [
            'id_rombel' => 2,
            'nominal' => 350000,
            'diskon' => 100000,
        ]);
    }

    public function test_admin_can_cancel_payment_with_reason_and_reject_kb_tasks(): void
    {
        $this->createCoreSchoolTables();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        DB::table('pembayaran')->insert([
            'id_pembayaran' => 1,
            'metode' => 'Tunai',
            'status' => 'Terverifikasi',
            'created_at' => now(),
        ]);

        $this->actingAs($admin)
            ->patch(route('database.payment.cancel', ['payment' => 1]), [
                'catatan' => 'Salah input nominal pembayaran',
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('pembayaran', [
            'id_pembayaran' => 1,
            'status' => 'Dibatalkan',
            'catatan' => 'Salah input nominal pembayaran',
        ]);

        DB::table('tahun_ajaran')->insert([
            'id_tahun_ajaran' => 3,
            'nama' => '2026-2027',
            'tahun_mulai' => 2026,
            'is_aktif' => true,
        ]);
        DB::table('kelas')->insert([
            'id_kelas' => 6,
            'id_tahun_ajaran' => 3,
            'kelompok' => 'KB',
            'nama_kelas' => 'PG',
            'kapasitas' => 10,
        ]);

        $this->actingAs($admin)
            ->from(route('dashboard.feature', ['section' => 'tugas']))
            ->post(route('database.task.store'), [
                'id_kelas' => 6,
                'judul' => 'Tugas KB',
                'tanggal_diberikan' => now()->toDateString(),
                'tenggat' => now()->addDays(3)->toDateString(),
            ])
            ->assertSessionHasErrors('id_kelas');
    }

    private function createCoreSchoolTables(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table): void {
            $table->increments('id_tahun_ajaran');
            $table->string('nama');
            $table->unsignedSmallInteger('tahun_mulai')->default(2026);
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->boolean('is_aktif')->default(false);
            $table->timestamps();
        });
        Schema::create('kelas', function (Blueprint $table): void {
            $table->increments('id_kelas');
            $table->unsignedInteger('id_tahun_ajaran');
            $table->string('kelompok');
            $table->string('nama_kelas');
            $table->unsignedTinyInteger('kapasitas')->nullable();
            $table->timestamps();
        });
        Schema::create('diskon', function (Blueprint $table): void {
            $table->increments('id_diskon');
            $table->string('nama');
            $table->string('tipe');
            $table->decimal('nilai', 12, 2);
            $table->string('kategori_biaya')->default('Bulanan');
        });
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->string('no_kk', 16)->nullable();
            $table->text('alamat')->nullable();
            $table->timestamps();
        });
        Schema::create('wali', function (Blueprint $table): void {
            $table->increments('id_wali');
            $table->unsignedInteger('id_keluarga');
            $table->string('hubungan');
            $table->string('nama_lengkap');
            $table->timestamps();
            $table->unique(['id_keluarga', 'hubungan']);
        });
        Schema::create('kontak_keluarga', function (Blueprint $table): void {
            $table->increments('id_kontak');
            $table->unsignedInteger('id_keluarga');
            $table->string('nomor', 16);
            $table->unsignedTinyInteger('urutan')->default(1);
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
            $table->text('alamat_domisili')->nullable();
            $table->string('no_induk', 30)->nullable();
            $table->string('nik', 16)->nullable();
            $table->string('nisn', 10)->nullable();
            $table->string('nama_lengkap');
            $table->string('nama_panggilan')->nullable();
            $table->string('tempat_lahir')->nullable();
            $table->date('tanggal_lahir')->nullable();
            $table->string('jenis_kelamin')->nullable();
            $table->string('agama')->nullable();
            $table->unsignedTinyInteger('anak_ke')->nullable();
            $table->timestamps();
        });
        Schema::create('rombel_siswa', function (Blueprint $table): void {
            $table->increments('id_rombel');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_kelas');
            $table->string('status')->default('Aktif');
            $table->unsignedInteger('id_diskon')->nullable();
            $table->date('tanggal_masuk')->nullable();
            $table->timestamps();
        });
        Schema::create('jenis_biaya', function (Blueprint $table): void {
            $table->increments('id_jenis_biaya');
            $table->string('nama');
            $table->string('kategori');
        });
        Schema::create('tarif', function (Blueprint $table): void {
            $table->increments('id_tarif');
            $table->unsignedInteger('id_tahun_ajaran');
            $table->unsignedInteger('id_jenis_biaya');
            $table->string('kelompok');
            $table->decimal('nominal', 12, 2);
            $table->timestamps();
        });
        Schema::create('tagihan', function (Blueprint $table): void {
            $table->increments('id_tagihan');
            $table->unsignedInteger('id_rombel');
            $table->unsignedInteger('id_jenis_biaya');
            $table->date('periode')->nullable();
            $table->decimal('nominal', 12, 2);
            $table->decimal('diskon', 12, 2)->default(0);
            $table->date('jatuh_tempo');
            $table->timestamps();
        });
        Schema::create('pembayaran', function (Blueprint $table): void {
            $table->increments('id_pembayaran');
            $table->string('metode')->default('Tunai');
            $table->string('status')->default('Menunggu Verifikasi');
            $table->string('catatan')->nullable();
            $table->unsignedBigInteger('id_user_verifikator')->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->timestamps();
        });
        Schema::create('tugas', function (Blueprint $table): void {
            $table->increments('id_tugas');
            $table->unsignedInteger('id_kelas');
            $table->unsignedInteger('id_guru');
            $table->string('judul');
            $table->string('kategori')->nullable();
            $table->text('deskripsi')->nullable();
            $table->date('tanggal_diberikan');
            $table->date('tenggat');
            $table->string('lampiran')->nullable();
            $table->timestamps();
        });
    }
}
