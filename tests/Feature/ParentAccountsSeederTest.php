<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\ParentAccountsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ParentAccountsSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['admin.parent_bootstrap_password' => 'TemporaryTestPassword2026']);
        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
        });
        Schema::create('wali', function (Blueprint $table): void {
            $table->increments('id_wali');
            $table->unsignedInteger('id_keluarga');
            $table->string('hubungan');
            $table->string('nama_lengkap');
        });

        foreach (array_keys((new \ReflectionClass(ParentAccountsSeeder::class))->getConstant('ACCOUNTS')) as $familyId) {
            DB::table('keluarga')->insert(['id_keluarga' => $familyId]);
            DB::table('siswa')->insert(['id_keluarga' => $familyId]);
        }

        DB::table('wali')->insert([
            'id_keluarga' => 1,
            'hubungan' => 'Ibu',
            'nama_lengkap' => 'Nama Orang Tua dari Database',
        ]);
    }

    public function test_it_creates_and_links_parent_accounts_and_leaves_matching_accounts_unchanged_on_rerun(): void
    {
        $accountCount = count((new \ReflectionClass(ParentAccountsSeeder::class))->getConstant('ACCOUNTS'));

        $this->seed(ParentAccountsSeeder::class);

        $parent = User::where('email', 'harissabri@gmail.com')->firstOrFail();
        $this->assertSame('parent', $parent->role);
        $this->assertSame('Nama Orang Tua dari Database', $parent->name);
        $this->assertTrue($parent->must_change_password);
        $this->assertTrue(Hash::check('TemporaryTestPassword2026', $parent->password));
        $this->assertSame($accountCount, User::count());
        $this->assertDatabaseHas('keluarga', [
            'id_keluarga' => 1,
            'id_user' => $parent->getKey(),
        ]);

        $parent->password = 'changed-after-creation';
        $parent->save();
        $this->seed(ParentAccountsSeeder::class);

        $this->assertSame($accountCount, User::count());
        $this->assertTrue(Hash::check('changed-after-creation', $parent->fresh()->password));
    }

    public function test_a_family_conflict_aborts_the_whole_seed_operation(): void
    {
        $existingUser = User::factory()->create(['role' => 'teacher']);
        DB::table('keluarga')
            ->where('id_keluarga', 2)
            ->update(['id_user' => $existingUser->getKey()]);

        try {
            $this->seed(ParentAccountsSeeder::class);
            $this->fail('The seeder should reject a family linked to a different role.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('already linked to another account', $exception->getMessage());
        }

        $this->assertSame(1, User::count());
        $this->assertDatabaseHas('keluarga', ['id_keluarga' => 1, 'id_user' => null]);
    }
}
