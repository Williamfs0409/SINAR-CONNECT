<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\TeacherAccountsSeeder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class TeacherAccountsSeederTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::dropIfExists('users');
        Schema::dropIfExists('guru');
        Schema::create('users', function (Blueprint $table): void {
            $table->id('id_user');
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password');
            $table->string('role');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('guru', function (Blueprint $table): void {
            $table->id('id_guru');
            $table->unsignedBigInteger('id_user')->nullable();
        });

        config(['admin.teacher_bootstrap_password' => '12345']);
    }

    public function test_it_updates_teacher_and_principal_accounts_and_skips_other_roles(): void
    {
        $teacher = User::create([
            'name' => 'Teacher',
            'email' => 'teacher@example.test',
            'password' => 'old-password',
            'role' => 'teacher',
        ]);
        $admin = User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'admin-password',
            'role' => 'admin',
        ]);
        $principal = User::create([
            'name' => 'Principal',
            'email' => 'principal@example.test',
            'password' => 'principal-password',
            'role' => 'principal',
        ]);
        DB::table('guru')->insert([
            ['id_user' => $teacher->id_user],
            ['id_user' => $admin->id_user],
            ['id_user' => $principal->id_user],
        ]);

        $this->seed(TeacherAccountsSeeder::class);

        $this->assertTrue(Hash::check('12345', $teacher->fresh()->password));
        $this->assertTrue($teacher->fresh()->is_active);
        $this->assertTrue(Hash::check('12345', $principal->fresh()->password));
        $this->assertTrue($principal->fresh()->is_active);
        $this->assertSame('principal', $principal->fresh()->role);
        $this->assertTrue(Hash::check('admin-password', $admin->fresh()->password));
        $this->assertSame('admin', $admin->fresh()->role);

        $this->seed(TeacherAccountsSeeder::class);

        $this->assertDatabaseCount('users', 3);
    }
}
