<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
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
                'password' => 'New-ParentPass123!',
                'password_confirmation' => 'New-ParentPass123!',
            ])
            ->assertRedirect(route('dashboard'));

        $user->refresh();
        $this->assertFalse($user->must_change_password);
        $this->assertTrue(Hash::check('New-ParentPass123!', $user->password));
    }

    public function test_password_reset_mail_does_not_disclose_whether_an_email_exists(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertSessionHas('status', 'Jika alamat email terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.');
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->post(route('password.email'), ['email' => 'unknown@example.com'])
            ->assertSessionHas('status', 'Jika alamat email terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.');
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

    public function test_guest_can_preview_the_teacher_dashboard_without_logging_in(): void
    {
        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Mode pratinjau')
            ->assertSee('Halo, Ibu Dewi (Guru)');
        $this->assertGuest();
    }

    public function test_guest_can_choose_each_role_dashboard_preview(): void
    {
        $this->get(route('dashboard', ['role' => 'principal']))
            ->assertOk()
            ->assertSee('Halo, Ratna (Kepala Sekolah)')
            ->assertSee('Pemantauan Akademik');

        $this->get(route('dashboard', ['role' => 'parent']))
            ->assertOk()
            ->assertSee('Halo, Bapak Andi (Orang Tua)')
            ->assertSee('Perkembangan Anak');
    }

    public function test_guest_cannot_preview_admin_dashboard_and_is_sent_to_login(): void
    {
        $this->get(route('dashboard', ['role' => 'admin']))
            ->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_guest_cannot_open_admin_management_pages_without_login(): void
    {
        $this->get(route('dashboard.feature', ['section' => 'pendaftaran', 'role' => 'admin']))
            ->assertRedirect(route('login'));
    }

    public function test_teacher_features_open_as_separate_pages(): void
    {
        foreach (['absensi', 'pembelajaran', 'rencana', 'tugas', 'nilai', 'rapor', 'dokumentasi', 'pengaturan', 'jadwal'] as $section) {
            $this->get(route('dashboard.feature', ['section' => $section]))
                ->assertOk()
                ->assertSee('role-feature-table-panel', false)
                ->assertSee('Mode pratinjau');
        }
    }

    public function test_principal_and_parent_features_open_as_separate_pages(): void
    {
        $roleSections = [
            'principal' => ['guru', 'administrasi', 'akademik', 'evaluasi', 'spp', 'laporan', 'jadwal'],
            'parent' => ['perkembangan', 'absensi', 'spp', 'tabungan', 'tugas', 'rapor', 'dokumentasi', 'siswa'],
        ];

        foreach ($roleSections as $role => $sections) {
            foreach ($sections as $section) {
                $this->get(route('dashboard.feature', ['section' => $section, 'role' => $role]))
                    ->assertOk()
                    ->assertSee('role-feature-table-panel', false);
            }
        }
    }

    public function test_feature_page_not_available_for_the_selected_role_returns_not_found(): void
    {
        $this->get(route('dashboard.feature', ['section' => 'tabungan', 'role' => 'teacher']))
            ->assertNotFound();
    }

    public function test_schedule_has_live_clock_and_date_navigation_without_view_toggle(): void
    {
        foreach (['teacher', 'parent'] as $role) {
            $this->get(route('dashboard', ['role' => $role]))
                ->assertOk()
                ->assertSee('data-live-clock', false)
                ->assertSee('data-date-step="-1"', false)
                ->assertSee('data-date-step="1"', false)
                ->assertSee('data-month-step="-1"', false)
                ->assertSee('data-month-step="1"', false)
                ->assertSee('data-date-today', false)
                ->assertDontSee('role-view-toggle', false);
        }
    }
}
