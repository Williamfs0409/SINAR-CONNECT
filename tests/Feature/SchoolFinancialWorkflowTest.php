<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class SchoolFinancialWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_an_individual_bill_without_creating_duplicates(): void
    {
        $this->createBillingTables();
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        DB::table('tahun_ajaran')->insert(['id_tahun_ajaran' => 1, 'nama' => '2026-2027', 'is_aktif' => true]);
        DB::table('kelas')->insert(['id_kelas' => 1, 'id_tahun_ajaran' => 1, 'kelompok' => 'TK A', 'nama_kelas' => 'Kelas A']);
        DB::table('siswa')->insert(['id_siswa' => 1, 'nama_lengkap' => 'Siswa Uji']);
        DB::table('rombel_siswa')->insert(['id_rombel' => 1, 'id_siswa' => 1, 'id_kelas' => 1, 'status' => 'Aktif']);
        DB::table('jenis_biaya')->insert(['id_jenis_biaya' => 1, 'nama' => 'SPP', 'kategori' => 'Bulanan']);
        DB::table('tarif')->insert([
            'id_tarif' => 1,
            'id_tahun_ajaran' => 1,
            'id_jenis_biaya' => 1,
            'kelompok' => 'TK A',
            'nominal' => 350000,
        ]);

        $input = [
            'id_rombel' => 1,
            'id_jenis_biaya' => 1,
            'periode' => '2026-11',
            'jatuh_tempo' => '2026-11-30',
        ];

        $this->actingAs($admin)
            ->post(route('database.bills.store'), $input)
            ->assertRedirect();
        $this->assertDatabaseHas('tagihan', [
            'id_rombel' => 1,
            'id_jenis_biaya' => 1,
            'periode' => '2026-11-01',
            'nominal' => 350000,
        ]);

        $this->from(route('dashboard.feature', ['section' => 'tagihan']))
            ->post(route('database.bills.store'), $input)
            ->assertSessionHasErrors('periode');
        $this->assertSame(1, DB::table('tagihan')->count());
    }

    public function test_parent_cannot_submit_cash_as_a_payment_method(): void
    {
        $parent = User::factory()->create(['role' => 'parent', 'is_active' => true]);

        $this->actingAs($parent)
            ->post(route('database.payment.store'), [
                'id_tagihan' => 1,
                'jumlah' => 100000,
                'metode' => 'Tunai',
            ])
            ->assertSessionHasErrors('metode');
    }

    public function test_admin_payment_review_is_single_use_and_records_the_verifier(): void
    {
        Schema::create('pembayaran', function (Blueprint $table): void {
            $table->increments('id_pembayaran');
            $table->string('status');
            $table->unsignedBigInteger('id_user_verifikator')->nullable();
            $table->timestamp('tanggal_verifikasi')->nullable();
            $table->string('catatan')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        DB::table('pembayaran')->insert([
            'id_pembayaran' => 1,
            'status' => 'Menunggu Verifikasi',
        ]);

        $this->actingAs($admin)
            ->patch(route('database.payment.review', ['payment' => 1]), ['decision' => 'verify'])
            ->assertRedirect();

        $this->assertDatabaseHas('pembayaran', [
            'id_pembayaran' => 1,
            'status' => 'Terverifikasi',
            'id_user_verifikator' => $admin->getKey(),
        ]);

        $this->from(route('dashboard.feature', ['section' => 'pembayaran']))
            ->patch(route('database.payment.review', ['payment' => 1]), ['decision' => 'reject'])
            ->assertSessionHasErrors('decision');

        $this->assertDatabaseHas('pembayaran', [
            'id_pembayaran' => 1,
            'status' => 'Terverifikasi',
        ]);
    }

    public function test_admin_can_link_an_unlinked_parent_to_a_family_with_students(): void
    {
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
            $table->string('nama_lengkap');
        });

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $parent = User::factory()->create(['role' => 'parent', 'is_active' => true]);
        DB::table('keluarga')->insert(['id_keluarga' => 1, 'id_user' => null]);
        DB::table('siswa')->insert(['id_siswa' => 1, 'id_keluarga' => 1, 'nama_lengkap' => 'Siswa Uji']);

        $this->actingAs($admin)
            ->post(route('database.family.link-parent'), ['id_user' => $parent->id_user, 'id_keluarga' => 1])
            ->assertRedirect();
        $this->assertDatabaseHas('keluarga', ['id_keluarga' => 1, 'id_user' => $parent->id_user]);

        $otherParent = User::factory()->create(['role' => 'parent', 'is_active' => true]);
        $this->post(route('database.family.link-parent'), ['id_user' => $otherParent->id_user, 'id_keluarga' => 1])
            ->assertSessionHasErrors('id_keluarga');
        $this->assertDatabaseHas('keluarga', ['id_keluarga' => 1, 'id_user' => $parent->id_user]);
    }

    public function test_admin_can_create_and_link_a_parent_account_to_an_existing_family(): void
    {
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
            $table->string('nama_lengkap');
        });

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        DB::table('keluarga')->insert(['id_keluarga' => 1, 'id_user' => null]);
        DB::table('siswa')->insert(['id_siswa' => 1, 'id_keluarga' => 1, 'nama_lengkap' => 'Siswa Uji']);

        $this->actingAs($admin)
            ->post(route('database.family.create-parent'), [
                'id_keluarga' => 1,
                'name' => 'Orang Tua Uji',
                'email' => 'parent@example.test',
                'password' => 'Secure-Pass123!',
                'password_confirmation' => 'Secure-Pass123!',
            ])
            ->assertRedirect(route('dashboard.feature', ['section' => 'keluarga']));

        $parent = User::where('email', 'parent@example.test')->firstOrFail();
        $this->assertSame('parent', $parent->role);
        $this->assertTrue($parent->is_active);
        $this->assertTrue($parent->must_change_password);
        $this->assertTrue(password_verify('Secure-Pass123!', $parent->password));
        $this->assertDatabaseHas('keluarga', [
            'id_keluarga' => 1,
            'id_user' => $parent->getKey(),
        ]);
    }

    public function test_admin_can_set_field_trip_cost_before_bills_are_created(): void
    {
        Schema::create('kegiatan', function (Blueprint $table): void {
            $table->increments('id_kegiatan');
            $table->string('jenis');
            $table->decimal('biaya', 12, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('tagihan', function (Blueprint $table): void {
            $table->increments('id_tagihan');
            $table->unsignedInteger('id_kegiatan')->nullable();
        });

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        DB::table('kegiatan')->insert([
            'id_kegiatan' => 1,
            'jenis' => 'Field Trip',
            'biaya' => null,
        ]);

        $this->actingAs($admin)
            ->patch(route('database.activity.cost.update', ['activity' => 1]), ['biaya' => 75000])
            ->assertRedirect();

        $this->assertDatabaseHas('kegiatan', ['id_kegiatan' => 1, 'biaya' => 75000]);
    }

    public function test_field_trip_cost_cannot_change_after_bills_are_created(): void
    {
        Schema::create('kegiatan', function (Blueprint $table): void {
            $table->increments('id_kegiatan');
            $table->string('jenis');
            $table->decimal('biaya', 12, 2)->nullable();
            $table->timestamps();
        });
        Schema::create('tagihan', function (Blueprint $table): void {
            $table->increments('id_tagihan');
            $table->unsignedInteger('id_kegiatan')->nullable();
        });

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        DB::table('kegiatan')->insert([
            'id_kegiatan' => 1,
            'jenis' => 'Field Trip',
            'biaya' => 75000,
        ]);
        DB::table('tagihan')->insert(['id_kegiatan' => 1]);

        $this->actingAs($admin)
            ->from(route('dashboard.feature', ['section' => 'kegiatan']))
            ->patch(route('database.activity.cost.update', ['activity' => 1]), ['biaya' => 80000])
            ->assertSessionHasErrors('biaya');

        $this->assertDatabaseHas('kegiatan', ['id_kegiatan' => 1, 'biaya' => 75000]);
    }

    public function test_parent_account_is_not_created_for_a_family_without_students(): void
    {
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable()->unique();
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
            $table->string('nama_lengkap');
        });

        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        DB::table('keluarga')->insert(['id_keluarga' => 1, 'id_user' => null]);

        $this->actingAs($admin)
            ->from(route('dashboard.feature', ['section' => 'keluarga']))
            ->post(route('database.family.create-parent'), [
                'id_keluarga' => 1,
                'name' => 'Orang Tua Uji',
                'email' => 'parent@example.test',
                'password' => 'Secure-Pass123!',
                'password_confirmation' => 'Secure-Pass123!',
            ])
            ->assertSessionHasErrors('id_keluarga');

        $this->assertDatabaseMissing('users', ['email' => 'parent@example.test']);
        $this->assertDatabaseHas('keluarga', ['id_keluarga' => 1, 'id_user' => null]);
    }

    public function test_admin_can_update_parent_payment_account_details(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('database.payment-settings.update'), [
                'bank_name' => 'Bank Uji',
                'account_holder' => 'Sekolah Uji',
                'account_number' => '1234567890',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('payment_settings', [
            'id' => 1,
            'bank_name' => 'Bank Uji',
            'account_holder' => 'Sekolah Uji',
            'account_number' => '1234567890',
        ]);
    }

    public function test_admin_can_delete_a_student_without_related_records(): void
    {
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->string('nama_lengkap');
        });
        DB::table('siswa')->insert(['id_siswa' => 1, 'nama_lengkap' => 'Siswa Uji']);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->delete(route('database.student.destroy', ['student' => 1]))
            ->assertRedirect();

        $this->assertDatabaseMissing('siswa', ['id_siswa' => 1]);
    }

    public function test_admin_cannot_delete_a_student_with_enrollment_history(): void
    {
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->string('nama_lengkap');
        });
        Schema::create('pendaftaran', function (Blueprint $table): void {
            $table->increments('id_pendaftaran');
            $table->unsignedInteger('id_siswa');
        });
        DB::table('siswa')->insert(['id_siswa' => 1, 'nama_lengkap' => 'Siswa Uji']);
        DB::table('pendaftaran')->insert(['id_pendaftaran' => 1, 'id_siswa' => 1]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->delete(route('database.student.destroy', ['student' => 1]))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('siswa', ['id_siswa' => 1]);
    }

    public function test_admin_cannot_delete_a_family_that_still_has_students(): void
    {
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable()->unique();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
            $table->string('nama_lengkap');
        });
        DB::table('keluarga')->insert(['id_keluarga' => 1, 'id_user' => null]);
        DB::table('siswa')->insert(['id_siswa' => 1, 'id_keluarga' => 1, 'nama_lengkap' => 'Siswa Uji']);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->delete(route('database.family.destroy', ['family' => 1]))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('keluarga', ['id_keluarga' => 1]);
    }

    public function test_admin_cannot_delete_the_account_they_are_using(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->delete(route('database.user.destroy', ['user' => $admin->id_user]))
            ->assertSessionHasErrors('delete');

        $this->assertDatabaseHas('users', ['id_user' => $admin->id_user]);
    }

    public function test_admin_can_update_family_contact_details(): void
    {
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
        });
        Schema::create('kontak_keluarga', function (Blueprint $table): void {
            $table->increments('id_kontak');
            $table->unsignedInteger('id_keluarga');
            $table->string('nomor', 16);
            $table->unsignedTinyInteger('urutan')->default(1);
        });
        DB::table('keluarga')->insert(['id_keluarga' => 1, 'id_user' => null]);
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('database.family.update', ['family' => 1]), [
                'no_kk' => '1234567890123456',
                'alamat' => 'Alamat keluarga uji',
                'wali_name' => 'Wali Uji',
                'hubungan' => 'Wali',
                'nomor' => '081234567890',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('keluarga', [
            'id_keluarga' => 1,
            'no_kk' => '1234567890123456',
            'alamat' => 'Alamat keluarga uji',
        ]);
        $this->assertDatabaseHas('wali', ['id_keluarga' => 1, 'nama_lengkap' => 'Wali Uji']);
        $this->assertDatabaseHas('kontak_keluarga', ['id_keluarga' => 1, 'nomor' => '081234567890']);
    }

    public function test_admin_can_update_user_name_and_email(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $parent = User::factory()->create(['role' => 'parent', 'is_active' => true]);

        $this->actingAs($admin)
            ->patch(route('database.user.profile.update', ['user' => $parent->id_user]), [
                'name' => 'Orang Tua Uji',
                'email' => 'orangtua-uji@example.com',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'id_user' => $parent->id_user,
            'name' => 'Orang Tua Uji',
            'email' => 'orangtua-uji@example.com',
        ]);
    }

    private function createBillingTables(): void
    {
        Schema::create('tahun_ajaran', function (Blueprint $table): void {
            $table->increments('id_tahun_ajaran');
            $table->string('nama');
            $table->boolean('is_aktif');
        });
        Schema::create('kelas', function (Blueprint $table): void {
            $table->increments('id_kelas');
            $table->unsignedInteger('id_tahun_ajaran');
            $table->string('kelompok');
            $table->string('nama_kelas');
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->string('nama_lengkap');
        });
        Schema::create('rombel_siswa', function (Blueprint $table): void {
            $table->increments('id_rombel');
            $table->unsignedInteger('id_siswa');
            $table->unsignedInteger('id_kelas');
            $table->string('status');
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
    }
}
