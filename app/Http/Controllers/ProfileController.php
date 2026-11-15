<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImageUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProfileController extends Controller
{
    public function __construct(private readonly ImageUploadService $imageUploads) {}

    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email',
                'max:150',
                Rule::unique($user->getTable(), 'email')->ignore($user->getKey(), $user->getKeyName()),
            ],
        ], [
            'name.required' => 'Nama wajib diisi.',
            'name.max' => 'Nama maksimal 100 karakter.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Masukkan alamat email yang valid.',
            'email.max' => 'Email maksimal 150 karakter.',
            'email.unique' => 'Email tersebut sudah digunakan akun lain.',
        ]);

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->saveOrFail();

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Profil berhasil diperbarui.');
    }

    public function updatePhoto(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'photo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.required' => 'Pilih foto profil terlebih dahulu.',
            'photo.image' => 'File yang dipilih harus berupa gambar.',
            'photo.mimes' => 'Foto harus berformat JPG, PNG, atau WEBP.',
            'photo.max' => 'Ukuran foto maksimal 5 MB.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $previousPath = $user->profile_photo_path;
        $newPath = $this->imageUploads->store($validated['photo'], 'profile-photos', 'photo');

        $user->profile_photo_path = $newPath;
        $user->saveOrFail();

        if (is_string($previousPath)
            && str_starts_with($previousPath, 'profile-photos/')
            && Storage::disk('local')->exists($previousPath)
            && ! Storage::disk('local')->delete($previousPath)) {
            Log::warning('Unable to remove previous profile photo.', [
                'user_id' => $user->getKey(),
                'path' => $previousPath,
            ]);
        }

        return redirect()
            ->route('profile.edit')
            ->with('status', 'Foto profil berhasil diperbarui.');
    }

    public function photo(User $user, Request $request): StreamedResponse
    {
        abort_unless($user->is_active || $request->user()->is($user), 404);
        abort_unless(
            is_string($user->profile_photo_path)
                && str_starts_with($user->profile_photo_path, 'profile-photos/')
                && Storage::disk('local')->exists($user->profile_photo_path),
            404
        );

        return Storage::disk('local')->response($user->profile_photo_path);
    }
}
