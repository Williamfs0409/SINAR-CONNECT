<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
