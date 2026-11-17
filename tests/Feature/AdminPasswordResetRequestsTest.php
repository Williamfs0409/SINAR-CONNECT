<?php

namespace Tests\Feature;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminPasswordResetRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrators_can_view_password_reset_requests(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->get(route('admin.password-reset-requests.index'))
            ->assertForbidden();
    }

    public function test_administrator_dashboard_counts_requests_waiting_for_admin_or_owner(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $parent = User::factory()->create(['role' => 'parent']);
        PasswordResetRequest::create([
            'user_id' => $parent->getKey(),
            'status' => 'pending',
        ]);
        $parentWaitingToSetPassword = User::factory()->create(['role' => 'parent']);
        PasswordResetRequest::create([
            'user_id' => $parentWaitingToSetPassword->getKey(),
            'status' => 'approved',
        ]);

        $this->actingAs($administrator)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('admin.password-reset-requests.index'), false)
            ->assertSee('2 permintaan reset kata sandi masih perlu ditindaklanjuti');
    }

    public function test_owner_can_reenter_email_after_admin_approval_without_original_session(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $parent = User::factory()->create([
            'role' => 'parent',
            'must_change_password' => false,
        ]);

        $this->post(route('password.email'), ['email' => $parent->email])
            ->assertRedirect(route('password.reset.waiting'))
            ->assertSessionHas('password_reset_request_id');
        $resetRequest = PasswordResetRequest::query()->where('user_id', $parent->getKey())->firstOrFail();

        $this->actingAs($administrator)
            ->post(route('admin.password-reset-requests.approve', $resetRequest->getKey()))
            ->assertRedirect(route('admin.password-reset-requests.index'))
            ->assertSessionMissing('password_reset_url')
            ->assertSessionMissing('temporary_password');

        $parent->refresh();
        $resetRequest->refresh();

        $this->assertFalse(Hash::check('password', $parent->password));
        $this->assertTrue($parent->must_change_password);
        $this->assertSame('approved', $resetRequest->status);
        $this->assertSame($administrator->getKey(), $resetRequest->reviewed_by);

        Auth::logout();
        $this->withSession(['password_reset_request_id' => null])
            ->get(route('password.request'))
            ->assertOk()
            ->assertSee('Masukkan email akun Anda');
        $this->post(route('password.email'), ['email' => $parent->email])
            ->assertRedirect(route('password.owner-reset.form'));
        $this->assertDatabaseCount('password_reset_requests', 1);
        $this->get(route('password.reset.waiting'))
            ->assertRedirect(route('password.owner-reset.form'));
        $this->get(route('password.owner-reset.form'))
            ->assertOk()
            ->assertSee('Buat kata sandi baru');
        $this->post(route('password.owner-reset.update'), [
            'password' => 'Owner123',
            'password_confirmation' => 'Owner123',
        ])->assertRedirect(route('login'));

        $parent->refresh();
        $this->assertFalse($parent->must_change_password);
        $this->assertTrue(Hash::check('Owner123', $parent->password));
        $this->assertSame('completed', $resetRequest->fresh()->status);
    }

    public function test_administrator_can_reject_a_request_without_changing_the_password(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);
        $parent = User::factory()->create(['role' => 'parent']);
        $originalPasswordHash = $parent->password;
        $resetRequest = PasswordResetRequest::create([
            'user_id' => $parent->getKey(),
            'status' => 'pending',
        ]);

        $this->actingAs($administrator)
            ->post(route('admin.password-reset-requests.reject', $resetRequest->getKey()))
            ->assertRedirect(route('admin.password-reset-requests.index'));

        $this->assertSame($originalPasswordHash, $parent->fresh()->password);
        $this->assertSame('rejected', $resetRequest->fresh()->status);
    }
}
