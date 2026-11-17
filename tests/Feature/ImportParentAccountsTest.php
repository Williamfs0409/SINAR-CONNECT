<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class ImportParentAccountsTest extends TestCase
{
    use RefreshDatabase;

    public function test_parent_account_import_defaults_to_a_non_mutating_dry_run(): void
    {
        $this->createImportTables();
        DB::table('keluarga')->insert(['id_keluarga' => 12]);
        DB::table('siswa')->insert(['id_siswa' => 1, 'id_keluarga' => 12]);
        $csvPath = $this->writeCsv([
            ['12', 'parent@example.test', 'Ortu12345'],
        ]);

        try {
            $this->assertSame(0, Artisan::call('app:import-parent-accounts', ['file' => $csvPath]));
            $this->assertSame(0, User::count());
            $this->assertDatabaseHas('keluarga', ['id_keluarga' => 12, 'id_user' => null]);
            $this->assertStringContainsString('No database records were changed.', Artisan::output());
        } finally {
            unlink($csvPath);
        }
    }

    public function test_import_creates_and_links_parent_accounts_and_is_safe_to_repeat(): void
    {
        $this->createImportTables();
        DB::table('keluarga')->insert(['id_keluarga' => 12]);
        DB::table('siswa')->insert(['id_siswa' => 1, 'id_keluarga' => 12]);
        $csvPath = $this->writeCsv([
            ['12', 'parent@example.test', 'Ortu12345'],
        ]);

        try {
            $this->assertSame(0, Artisan::call('app:import-parent-accounts', [
                'file' => $csvPath,
                '--execute' => true,
            ]));

            $parent = User::where('email', 'parent@example.test')->firstOrFail();
            $this->assertSame('parent', $parent->role);
            $this->assertTrue($parent->must_change_password);
            $this->assertTrue(Hash::check('Ortu12345', $parent->password));
            $this->assertDatabaseHas('keluarga', [
                'id_keluarga' => 12,
                'id_user' => $parent->getKey(),
            ]);

            $this->assertSame(0, Artisan::call('app:import-parent-accounts', [
                'file' => $csvPath,
                '--execute' => true,
            ]));
            $this->assertSame(1, User::count());
            $this->assertSame($parent->getKey(), DB::table('keluarga')->where('id_keluarga', 12)->value('id_user'));
            $this->assertStringContainsString('1 already-linked account(s) left unchanged.', Artisan::output());
        } finally {
            unlink($csvPath);
        }
    }

    public function test_import_accepts_parent_name_and_relationship_columns(): void
    {
        $this->createImportTables();
        DB::table('keluarga')->insert(['id_keluarga' => 12]);
        DB::table('siswa')->insert(['id_siswa' => 1, 'id_keluarga' => 12]);
        $csvPath = $this->writeCsv(
            [['12', 'Nama Orang Tua Uji', 'Ibu', 'parent@example.test', 'Ortu12345']],
            ['id_keluarga', 'nama_orang_tua', 'hubungan', 'email', 'kata_sandi']
        );

        try {
            $this->assertSame(0, Artisan::call('app:import-parent-accounts', [
                'file' => $csvPath,
                '--execute' => true,
            ]));

            $this->assertDatabaseHas('users', [
                'email' => 'parent@example.test',
                'name' => 'Nama Orang Tua Uji',
            ]);
        } finally {
            unlink($csvPath);
        }
    }

    public function test_conflicts_abort_the_import_without_partially_creating_accounts(): void
    {
        $this->createImportTables();
        $existingParent = User::factory()->create(['role' => 'parent', 'is_active' => true]);
        DB::table('keluarga')->insert([
            ['id_keluarga' => 12, 'id_user' => null],
            ['id_keluarga' => 13, 'id_user' => $existingParent->getKey()],
        ]);
        DB::table('siswa')->insert([
            ['id_siswa' => 1, 'id_keluarga' => 12],
            ['id_siswa' => 2, 'id_keluarga' => 13],
        ]);
        $csvPath = $this->writeCsv([
            ['12', 'new-parent@example.test', 'Ortu12345'],
            ['13', 'different-parent@example.test', 'Ortu12345'],
        ]);

        try {
            $this->assertSame(1, Artisan::call('app:import-parent-accounts', [
                'file' => $csvPath,
                '--execute' => true,
            ]));

            $this->assertSame(1, User::count());
            $this->assertDatabaseHas('keluarga', ['id_keluarga' => 12, 'id_user' => null]);
            $this->assertDatabaseHas('keluarga', [
                'id_keluarga' => 13,
                'id_user' => $existingParent->getKey(),
            ]);
        } finally {
            unlink($csvPath);
        }
    }

    private function createImportTables(): void
    {
        if (! Schema::hasColumn('users', 'must_change_password')) {
            Schema::table('users', function (Blueprint $table): void {
                $table->boolean('must_change_password')->default(false);
            });
        }

        Schema::create('keluarga', function (Blueprint $table): void {
            $table->increments('id_keluarga');
            $table->unsignedBigInteger('id_user')->nullable();
            $table->timestamps();
        });
        Schema::create('siswa', function (Blueprint $table): void {
            $table->increments('id_siswa');
            $table->unsignedInteger('id_keluarga');
        });
    }

    private function writeCsv(
        array $rows,
        array $header = ['id_keluarga', 'email', 'kata_sandi']
    ): string {
        $path = tempnam(sys_get_temp_dir(), 'sinar-parent-import-');
        if ($path === false) {
            throw new RuntimeException('Unable to create a temporary CSV file.');
        }

        $file = fopen($path, 'wb');
        if ($file === false) {
            unlink($path);
            throw new RuntimeException('Unable to open the temporary CSV file.');
        }

        try {
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, $header);
            foreach ($rows as $row) {
                fputcsv($file, $row);
            }
        } finally {
            fclose($file);
        }

        return $path;
    }
}
