<?php

namespace App\Http\Controllers;

use App\Models\PasswordResetRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class PasswordResetRequestsController extends Controller
{
    public function index(Request $request): View
    {
        $this->administrator($request);
        $this->ensureRequestTableExists();

        $requests = PasswordResetRequest::query()
            ->with('user:id_user,name,email,role')
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 WHEN status = 'approved' THEN 1 ELSE 2 END")
            ->latest()
            ->paginate(25);

        return view('admin.password-reset-requests', compact('requests'));
    }

    public function approve(Request $request, int $requestId): RedirectResponse
    {
        $administrator = $this->administrator($request);
        $this->ensureRequestTableExists();

        if (! Schema::hasColumn('users', 'must_change_password') || ! Schema::hasTable('password_reset_tokens')) {
            abort(503, 'Terapkan migrasi keamanan kata sandi sebelum menyetujui permintaan.');
        }

        DB::transaction(function () use ($administrator, $requestId): void {
            $resetRequest = PasswordResetRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($resetRequest->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'Permintaan ini sudah ditinjau.',
                ]);
            }

            $user = User::query()
                ->whereKey($resetRequest->user_id)
                ->lockForUpdate()
                ->firstOrFail();

            if (! $user->is_active) {
                throw ValidationException::withMessages([
                    'request' => 'Akun yang diminta tidak aktif.',
                ]);
            }

            Password::broker()->deleteToken($user);
            $user->forceFill([
                'password' => Str::random(64),
                'must_change_password' => true,
                'remember_token' => Str::random(60),
            ])->saveOrFail();

            if (Schema::hasTable('sessions')) {
                DB::table('sessions')->where('user_id', $user->getKey())->delete();
            }

            $resetRequest->forceFill([
                'status' => 'approved',
                'reviewed_by' => $administrator->getKey(),
                'reviewed_at' => now(),
            ])->saveOrFail();
        });

        return redirect()
            ->route('admin.password-reset-requests.index')
            ->with('status', 'Permintaan disetujui. Pemilik yang menunggu akan langsung diarahkan; jika memakai browser yang sama untuk admin, logout lalu masukkan email itu kembali di halaman lupa kata sandi.');
    }

    public function reject(Request $request, int $requestId): RedirectResponse
    {
        $administrator = $this->administrator($request);
        $this->ensureRequestTableExists();

        DB::transaction(function () use ($administrator, $requestId): void {
            $resetRequest = PasswordResetRequest::query()
                ->whereKey($requestId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($resetRequest->status !== 'pending') {
                throw ValidationException::withMessages([
                    'request' => 'Permintaan ini sudah ditinjau.',
                ]);
            }

            $resetRequest->forceFill([
                'status' => 'rejected',
                'reviewed_by' => $administrator->getKey(),
                'reviewed_at' => now(),
            ])->saveOrFail();
        });

        return redirect()
            ->route('admin.password-reset-requests.index')
            ->with('status', 'Permintaan reset kata sandi ditolak.');
    }

    private function administrator(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User && in_array($user->role, ['admin', 'principal'], true), 403);

        return $user;
    }

    private function ensureRequestTableExists(): void
    {
        abort_unless(
            Schema::hasTable('password_reset_requests'),
            503,
            'Terapkan migrasi permintaan reset kata sandi terlebih dahulu.'
        );
    }
}
