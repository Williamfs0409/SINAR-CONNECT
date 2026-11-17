<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use App\Support\PasswordPolicy;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PasswordSecurityController extends Controller
{
    private const RESET_REQUEST_SESSION_KEY = 'password_reset_request_id';

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
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
        ], [
            'current_password.current_password' => 'Kata sandi saat ini tidak sesuai.',
            'password' => PasswordPolicy::MESSAGE,
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        /** @var User $user */
        $user = $request->user();
        DB::transaction(function () use ($user, $validated): void {
            $user->forceFill([
                'password' => $validated['password'],
                'must_change_password' => false,
                'remember_token' => Str::random(60),
            ])->saveOrFail();

            $this->completeApprovedPasswordResetRequests($user);
        });

        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('status', 'Kata sandi berhasil diperbarui.');
    }

    public function showRequestForm(Request $request): View|RedirectResponse
    {
        $resetRequest = $this->requestForCurrentSession($request);
        if ($resetRequest?->status === 'approved') {
            if ($this->approvalHasExpired($resetRequest)) {
                $this->markRequestExpired($resetRequest);
                $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

                return redirect()->route('password.request')
                    ->with('status', 'Masa persetujuan telah berakhir. Silakan ajukan reset kata sandi kembali.');
            }

            return redirect()->route('password.owner-reset.form');
        }

        if ($resetRequest?->status === 'pending') {
            return redirect()->route('password.reset.waiting');
        }

        if ($resetRequest?->status === 'completed') {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

            return redirect()->route('login')
                ->with('status', 'Kata sandi telah diatur. Silakan masuk.');
        }

        if ($resetRequest !== null) {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);
        }

        return view('auth.passwords.email');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $data = $request->validate(['email' => ['required', 'email', 'max:150']]);

        abort_unless(
            Schema::hasTable('password_reset_requests'),
            503,
            'Layanan verifikasi administrator belum tersedia. Silakan hubungi administrator sekolah.'
        );

        $user = User::query()
            ->where('email', mb_strtolower($data['email']))
            ->where('is_active', true)
            ->first();

        $requestResult = null;
        if ($user !== null) {
            $requestResult = DB::transaction(function () use ($user): ?array {
                $lockedUser = User::query()
                    ->whereKey($user->getKey())
                    ->lockForUpdate()
                    ->first();

                if ($lockedUser === null) {
                    return null;
                }

                $approvedRequests = PasswordResetRequest::query()
                    ->where('user_id', $lockedUser->getKey())
                    ->where('status', 'approved')
                    ->orderByDesc('reviewed_at')
                    ->lockForUpdate()
                    ->get();

                foreach ($approvedRequests as $approvedRequest) {
                    if (! $this->approvalHasExpired($approvedRequest)) {
                        DB::table('password_reset_requests')
                            ->where('user_id', $lockedUser->getKey())
                            ->where('status', 'pending')
                            ->update([
                                'status' => 'superseded',
                                'updated_at' => now(),
                            ]);

                        return ['id' => $approvedRequest->getKey(), 'approved' => true];
                    }

                    $this->markRequestExpired($approvedRequest);
                }

                $pendingRequest = PasswordResetRequest::query()
                    ->where('user_id', $lockedUser->getKey())
                    ->where('status', 'pending')
                    ->lockForUpdate()
                    ->first();

                if ($pendingRequest !== null) {
                    return ['id' => $pendingRequest->getKey(), 'approved' => false];
                }

                $resetRequest = PasswordResetRequest::query()->create([
                    'user_id' => $lockedUser->getKey(),
                    'status' => 'pending',
                ]);

                return ['id' => $resetRequest->getKey(), 'approved' => false];
            });
        }

        $currentRequestId = $request->session()->get(self::RESET_REQUEST_SESSION_KEY);
        if ($requestResult !== null) {
            $requestIdChanged = (string) $currentRequestId !== (string) $requestResult['id'];
            $request->session()->put(self::RESET_REQUEST_SESSION_KEY, $requestResult['id']);
            if ($requestIdChanged) {
                $request->session()->regenerate();
            }
        } else {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);
        }

        if ($requestResult !== null && $requestResult['approved']) {
            return redirect()->route('password.owner-reset.form');
        }

        return redirect()->route('password.reset.waiting')->with(
            'status',
            'Jika akun terdaftar, permintaan reset telah diteruskan kepada administrator.'
        );
    }

    public function waitForAdministrator(Request $request): View|RedirectResponse
    {
        $resetRequest = $this->requestForCurrentSession($request);
        if ($resetRequest === null) {
            return view('auth.passwords.waiting', ['pollForApproval' => false]);
        }

        if ($resetRequest->status === 'approved') {
            if ($this->approvalHasExpired($resetRequest)) {
                $this->markRequestExpired($resetRequest);
                $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

                return redirect()->route('password.request')
                    ->with('status', 'Masa persetujuan telah berakhir. Silakan ajukan reset kata sandi kembali.');
            }

            return redirect()->route('password.owner-reset.form');
        }

        if ($resetRequest->status === 'rejected') {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

            return redirect()->route('password.request')
                ->with('status', 'Permintaan reset tidak disetujui. Silakan hubungi administrator sekolah.');
        }

        if ($resetRequest->status === 'completed') {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

            return redirect()->route('login')
                ->with('status', 'Kata sandi telah diatur. Silakan masuk.');
        }

        if ($resetRequest->status === 'expired') {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

            return redirect()->route('password.request')
                ->with('status', 'Masa reset telah berakhir. Silakan ajukan kembali permintaan.');
        }

        return view('auth.passwords.waiting', ['pollForApproval' => true]);
    }

    public function showOwnerResetForm(Request $request): View|RedirectResponse
    {
        $resetRequest = $this->requestForCurrentSession($request);
        if ($resetRequest === null) {
            return redirect()->route('password.request')
                ->with('status', 'Sesi pemulihan tidak ditemukan. Silakan ajukan permintaan reset kembali.');
        }

        if ($resetRequest->status === 'pending') {
            return redirect()->route('password.reset.waiting');
        }

        if ($resetRequest->status !== 'approved') {
            return redirect()->route('password.reset.waiting');
        }

        if ($this->approvalHasExpired($resetRequest)) {
            $this->markRequestExpired($resetRequest);
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

            return redirect()->route('password.request')
                ->with('status', 'Masa persetujuan telah berakhir. Silakan ajukan reset kata sandi kembali.');
        }

        return view('auth.passwords.owner-reset', ['email' => $resetRequest->user->email]);
    }

    public function updateOwnerPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
        ], [
            'password' => PasswordPolicy::MESSAGE,
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $requestId = $request->session()->get(self::RESET_REQUEST_SESSION_KEY);
        if (! is_numeric($requestId)) {
            return redirect()->route('password.request')
                ->with('status', 'Sesi pemulihan tidak ditemukan. Silakan ajukan permintaan reset kembali.');
        }

        $result = DB::transaction(function () use ($requestId, $validated): string {
            $resetRequest = PasswordResetRequest::query()
                ->whereKey((int) $requestId)
                ->lockForUpdate()
                ->first();

            if ($resetRequest === null) {
                return 'unavailable';
            }

            if ($resetRequest->status !== 'approved') {
                return $resetRequest->status;
            }

            if ($this->approvalHasExpired($resetRequest)) {
                $this->markRequestExpired($resetRequest);

                return 'expired';
            }

            $user = User::query()
                ->whereKey($resetRequest->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            abort_unless($user->is_active, 403, 'Akun ini tidak aktif.');

            $user->forceFill([
                'password' => $validated['password'],
                'must_change_password' => false,
                'remember_token' => Str::random(60),
            ])->saveOrFail();

            $this->completeApprovedPasswordResetRequests($user);

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->getKey())->delete();
            }

            if (Schema::hasTable('password_reset_tokens')) {
                Password::broker()->deleteToken($user);
            }

            return 'completed';
        });

        if ($result === 'completed') {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);
            $request->session()->regenerate();

            return redirect()->route('login')
                ->with('status', 'Kata sandi berhasil dibuat. Silakan masuk dengan kata sandi baru.');
        }

        if ($result === 'expired') {
            $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

            return redirect()->route('password.request')
                ->with('status', 'Masa persetujuan telah berakhir. Silakan ajukan reset kata sandi kembali.');
        }

        if ($result === 'pending') {
            return redirect()->route('password.reset.waiting');
        }

        $request->session()->forget(self::RESET_REQUEST_SESSION_KEY);

        return redirect()->route('password.request')
            ->with('status', 'Permintaan reset tidak lagi aktif. Silakan ajukan kembali.');
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
            'password' => ['required', 'confirmed', PasswordPolicy::rule()],
        ], [
            'password' => PasswordPolicy::MESSAGE,
            'password.confirmed' => 'Konfirmasi kata sandi tidak sama.',
        ]);

        $status = Password::reset(
            $validated,
            function (User $user, string $password): void {
                DB::transaction(function () use ($user, $password): void {
                    $user->forceFill([
                        'password' => $password,
                        'must_change_password' => false,
                        'remember_token' => Str::random(60),
                    ])->saveOrFail();

                    $this->completeApprovedPasswordResetRequests($user);
                });
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('status', 'Kata sandi berhasil diatur ulang. Silakan masuk.');
        }

        return back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => 'Tautan pengaturan ulang tidak valid atau sudah kedaluwarsa.']);
    }

    private function completeApprovedPasswordResetRequests(User $user): void
    {
        if (! Schema::hasTable('password_reset_requests')) {
            return;
        }

        DB::table('password_reset_requests')
            ->where('user_id', $user->getKey())
            ->where('status', 'approved')
            ->update([
                'status' => 'completed',
                'updated_at' => now(),
            ]);
    }

    private function requestForCurrentSession(Request $request): ?PasswordResetRequest
    {
        if (! Schema::hasTable('password_reset_requests')) {
            return null;
        }

        $requestId = $request->session()->get(self::RESET_REQUEST_SESSION_KEY);
        if (! is_numeric($requestId)) {
            return null;
        }

        return PasswordResetRequest::query()
            ->with('user:id_user,email')
            ->find((int) $requestId);
    }

    private function approvalHasExpired(PasswordResetRequest $resetRequest): bool
    {
        $broker = config('auth.defaults.passwords');
        $expireMinutes = (int) config("auth.passwords.{$broker}.expire", 60);

        return $resetRequest->reviewed_at === null
            || $resetRequest->reviewed_at->lt(now()->subMinutes($expireMinutes));
    }

    private function markRequestExpired(PasswordResetRequest $resetRequest): void
    {
        $resetRequest->forceFill(['status' => 'expired'])->saveOrFail();
    }
}
