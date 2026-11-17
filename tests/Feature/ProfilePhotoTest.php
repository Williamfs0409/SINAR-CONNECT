<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfilePhotoTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_upload_and_view_a_profile_photo(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['name' => 'Admin Sekolah']);

        $this->actingAs($user)
            ->post(route('profile.photo.update'), [
                'photo' => $this->fakePng('profile.png'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHas('status', 'Foto profil berhasil diperbarui.');

        $photoPath = $user->fresh()->profile_photo_path;
        $this->assertIsString($photoPath);
        Storage::disk('local')->assertExists($photoPath);

        $this->actingAs($user)
            ->get(route('profile.photo', ['user' => $user->getKey()]))
            ->assertOk();

        $this->actingAs($user)
            ->get(route('profile.edit'))
            ->assertOk()
            ->assertSee('Simpan foto profil');
    }

    public function test_profile_photo_upload_rejects_non_image_files(): void
    {
        Storage::fake('local');
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->post(route('profile.photo.update'), [
                'photo' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
            ])
            ->assertRedirect(route('profile.edit'))
            ->assertSessionHasErrors('photo');

        $this->assertNull($user->fresh()->profile_photo_path);
        $this->assertSame([], Storage::disk('local')->allFiles('profile-photos'));
    }

    public function test_uploading_a_new_photo_replaces_the_previous_file(): void
    {
        Storage::fake('local');
        $previousPath = 'profile-photos/previous.png';
        Storage::disk('local')->put($previousPath, 'previous photo');
        $user = User::factory()->create(['profile_photo_path' => $previousPath]);

        $this->actingAs($user)
            ->post(route('profile.photo.update'), [
                'photo' => $this->fakePng('replacement.png'),
            ])
            ->assertRedirect(route('profile.edit'));

        $newPath = $user->fresh()->profile_photo_path;
        $this->assertNotSame($previousPath, $newPath);
        Storage::disk('local')->assertMissing($previousPath);
        Storage::disk('local')->assertExists($newPath);
    }

    public function test_profile_photo_routes_require_authentication(): void
    {
        $user = User::factory()->create();

        $this->get(route('profile.edit'))
            ->assertRedirect(route('login'));

        $this->get(route('profile.photo', ['user' => $user->getKey()]))
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_can_update_their_profile_details_without_changing_their_role(): void
    {
        $user = User::factory()->create([
        'name' => 'Nama Lama',
        'email' => 'lama@example.test',
        'role' => 'teacher',
        ]);

        $this->actingAs($user)
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'baru@example.test',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHas('status', 'Profil berhasil diperbarui.');

        $this->assertSame('Nama Baru', $user->fresh()->name);
        $this->assertSame('baru@example.test', $user->fresh()->email);
        $this->assertSame('teacher', $user->fresh()->role);
    }

    public function test_profile_update_rejects_an_email_used_by_another_account(): void
    {
        User::factory()->create(['email' => 'dipakai@example.test']);
        $user = User::factory()->create(['email' => 'milikku@example.test']);

        $this->actingAs($user)
        ->from(route('profile.edit'))
        ->patch(route('profile.update'), [
            'name' => 'Nama Baru',
            'email' => 'dipakai@example.test',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('email');

        $this->assertSame('milikku@example.test', $user->fresh()->email);
    }

    private function fakePng(string $name): UploadedFile
    {
        $contents = base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADUlEQVR4nGP4z8AAAAMBAQDJ/pLvAAAAAElFTkSuQmCC',
            true,
        );

        if ($contents === false) {
            throw new \RuntimeException('Unable to load the PNG test fixture.');
        }

        return UploadedFile::fake()->createWithContent($name, $contents);
    }
}
