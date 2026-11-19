<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_and_password_are_required(): void
    {
        $response = $this->post(route('login.authenticate'));

        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_email_must_have_a_valid_format(): void
    {
        $response = $this->post(route('login.authenticate'), [
            'email' => 'bukan-email',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Masukkan alamat email yang valid.',
        ]);
    }

    public function test_invalid_credentials_show_a_generic_error(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->post(route('login.authenticate'), [
            'email' => 'admin@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email atau kata sandi salah.',
        ]);
        $this->assertGuest();
    }

    public function test_unknown_email_shows_the_same_generic_error(): void
    {
        $response = $this->post(route('login.authenticate'), [
            'email' => 'unknown@example.com',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors([
            'email' => 'Email atau kata sandi salah.',
        ]);
        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_authenticate(): void
    {
        $user = User::factory()->create([
            'email' => 'inactive@example.com',
            'is_active' => false,
        ]);

        $this->post(route('login.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors([
            'email' => 'Email atau kata sandi salah.',
        ]);

        $this->assertGuest();
    }

    public function test_valid_credentials_authenticate_the_user(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->post(route('login.authenticate'), [
            'email' => 'admin@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_parent_with_temporary_password_must_change_it_before_opening_the_dashboard(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'must_change_password' => true,
        ]);

        $this->post(route('login.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('password.force'));

        $this->get(route('dashboard'))->assertRedirect(route('password.force'));
        $this->get(route('password.force'))
            ->assertOk()
            ->assertSee('Buat kata sandi pribadi');
    }

    public function test_password_security_migration_marks_existing_parent_accounts_for_a_change(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'must_change_password' => false,
        ]);
        $migration = require database_path('migrations/2026_11_17_000001_add_password_security_to_users_table.php');

        $migration->up();

        $this->assertTrue($user->fresh()->must_change_password);
    }

    public function test_forced_password_change_updates_the_password_and_unlocks_dashboard_access(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'must_change_password' => true,
        ]);

        $this->actingAs($user)
            ->put(route('password.force.update'), [
                'current_password' => 'password',
                'password' => 'Parent12',
                'password_confirmation' => 'Parent12',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('Parent12', $user->password));
    }

    public function test_forced_password_change_requires_eight_characters_mixed_case_and_a_number(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'must_change_password' => true,
        ]);

        foreach (['Parent1', 'parent123', 'PARENT123', 'Parentabc'] as $password) {
            $this->actingAs($user)
                ->put(route('password.force.update'), [
                    'current_password' => 'password',
                    'password' => $password,
                    'password_confirmation' => $password,
                ])
                ->assertSessionHasErrors('password');
        }
    }

    public function test_password_reset_request_is_sent_to_the_admin_queue_without_emailing_the_user(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect(route('password.reset.waiting'))
            ->assertSessionHas('status', 'Jika akun terdaftar, permintaan reset telah diteruskan kepada administrator.')
            ->assertSessionHas('password_reset_request_id');

        $this->assertDatabaseHas('password_reset_requests', [
            'user_id' => $user->getKey(),
            'status' => 'pending',
        ]);
        $this->assertDatabaseCount('password_reset_requests', 1);
        Notification::assertNothingSent();

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertRedirect(route('password.reset.waiting'))
            ->assertSessionHas('status', 'Jika akun terdaftar, permintaan reset telah diteruskan kepada administrator.');
        $this->assertDatabaseCount('password_reset_requests', 1);
    }

    public function test_password_reset_request_does_not_create_duplicate_pending_requests(): void
    {
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email]);
        $this->post(route('password.email'), ['email' => $user->email]);

        $this->assertDatabaseCount('password_reset_requests', 1);
    }

    public function test_password_reset_clears_the_forced_change_flag(): void
    {
        $user = User::factory()->create([
            'role' => 'parent',
            'must_change_password' => true,
        ]);
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Reset-ParentPass123!',
            'password_confirmation' => 'Reset-ParentPass123!',
        ])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('Reset-ParentPass123!', $user->password));
    }

    public function test_teacher_sees_the_teacher_dashboard_after_login(): void
    {
        $user = User::factory()->create(['role' => 'teacher']);

        $this->post(route('login.authenticate'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Kartu Ringkasan')
            ->assertSee('Jadwal Mengajar')
            ->assertSee('Data Siswa &amp; Absensi', false);
    }

    public function test_principal_sees_the_principal_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'principal']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Pemantauan Akademik')
            ->assertSee('Administrasi &amp; Agenda', false);
    }

    public function test_parent_sees_the_parent_dashboard(): void
    {
        $user = User::factory()->create(['role' => 'parent']);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Perkembangan Anak')
            ->assertSee('Pembayaran &amp; Tabungan', false);
    }

    public function test_guest_is_redirected_to_login_when_visiting_dashboard(): void
    {
        foreach ([null, 'teacher', 'principal', 'parent', 'admin'] as $role) {
            $this->get(route('dashboard', $role ? ['role' => $role] : []))
                ->assertRedirect(route('login'));
        }
        $this->assertGuest();
    }

    public function test_guest_cannot_open_admin_management_pages_without_login(): void
    {
        $this->get(route('dashboard.feature', ['section' => 'pendaftaran', 'role' => 'admin']))
            ->assertRedirect(route('login'));

        foreach (['absensi', 'pembelajaran', 'tugas', 'nilai', 'rapor'] as $section) {
            $this->get(route('dashboard.feature', ['section' => $section]))
                ->assertRedirect(route('login'));
        }
    }
}
