<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_administrator_can_open_or_close_public_registration(): void
    {
        $parent = User::factory()->create(['role' => 'parent']);

        $this->actingAs($parent)
            ->patch(route('database.registration.settings.update'), ['is_open' => '0'])
            ->assertForbidden();

        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->patch(route('database.registration.settings.update'), ['is_open' => '0'])
            ->assertRedirect(route('dashboard.feature', ['section' => 'pendaftaran']));
        $this->assertDatabaseHas('registration_settings', ['id' => 1, 'is_open' => false]);

        $this->patch(route('database.registration.settings.update'), ['is_open' => '1'])
            ->assertRedirect(route('dashboard.feature', ['section' => 'pendaftaran']));
        $this->assertDatabaseHas('registration_settings', ['id' => 1, 'is_open' => true]);
    }

    public function test_closed_registration_hides_the_form_and_rejects_submissions(): void
    {
        $administrator = User::factory()->create(['role' => 'admin']);

        $this->actingAs($administrator)
            ->patch(route('database.registration.settings.update'), ['is_open' => '0']);

        $this->get(route('registration.create'))
            ->assertOk()
            ->assertSee('Pendaftaran siswa baru sedang ditutup')
            ->assertDontSee('Kirim pendaftaran');

        $this->post(route('registration.store'), [])
            ->assertRedirect(route('registration.create'))
            ->assertSessionHasErrors('registration');
    }
}
