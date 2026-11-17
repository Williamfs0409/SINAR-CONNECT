<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordSecurityController extends Controller
{
    public function showForceChange(Request $request): View|RedirectResponse
    {
        if (! $request->user()->must_change_password) {
            return redirect()->route('dashboard');
        }

        return view('auth.passwords.force-change');
    }

    public function forceChange(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'current_password' => ['required', 'current_password'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password' => 'Kata sandi baru minimal 12 karakter dan harus memuat huruf besar, huruf kecil, angka, serta simbol.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        /** @var User $user */
        $user = $request->user();
        $user->forceFill([
            'password' => $validated['password'],
            'must_change_password' => false,
            'remember_token' => Str::random(60),
        ])->saveOrFail();

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diperbarui.');
    }

    public function showRequestForm(): View
    {
        return view('auth.passwords.email');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $request->validate(['email' => ['required', 'email', 'max:150']]);

        Password::sendResetLink($request->only('email'));

        return back()->with(
            'status',
            'Jika alamat email terdaftar, tautan untuk mengatur ulang kata sandi akan dikirim.'
        );
    }

    public function showResetForm(Request $request, string $token): View
    {
        return view('auth.passwords.reset', [
            'token' => $token,
            'email' => $request->query('email'),
        ]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'email', 'max:150'],
            'password' => ['required', 'confirmed', PasswordRule::min(12)->letters()->mixedCase()->numbers()->symbols()],
        ], [
            'password' => 'Kata sandi baru minimal 12 karakter dan harus memuat huruf besar, huruf kecil, angka, serta simbol.',
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'must_change_password' => false,
                    'remember_token' => Str::random(60),
                ])->saveOrFail();
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Kata sandi berhasil diatur ulang. Silakan masuk.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Tautan pengaturan ulang tidak valid atau sudah kedaluwarsa.']);
    }
}
