<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use RuntimeException;
use Tests\TestCase;

class AdminBootstrapSeederTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'admin.bootstrap_email' => 'administrator@tkcerdasceria.com',
            'admin.bootstrap_password' => 'test-only-password-123',
        ]);
    }

    public function test_it_creates_an_active_admin_and_does_not_reset_an_existing_admin(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'administrator@tkcerdasceria.com')->firstOrFail();
        $this->assertSame('Administrator', $admin->name);
        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->is_active);
        $this->assertTrue(Hash::check('test-only-password-123', $admin->password));

        config(['admin.bootstrap_password' => 'different-test-password-456']);
        $this->seed(DatabaseSeeder::class);

        $this->assertTrue(Hash::check('test-only-password-123', $admin->fresh()->password));
        $this->assertDatabaseCount('users', 1);
    }

    public function test_it_refuses_to_claim_an_existing_non_admin_account(): void
    {
        User::factory()->create([
            'email' => 'administrator@tkcerdasceria.com',
            'role' => 'parent',
        ]);

        $this->expectException(RuntimeException::class);

        $this->seed(DatabaseSeeder::class);
    }
}
