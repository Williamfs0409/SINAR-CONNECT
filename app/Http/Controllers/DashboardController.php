<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Schema;

class DashboardController extends Controller
{
    public function show(?string $section = null): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user && request()->query('role') === 'admin') {
            return redirect()->route('login');
        }

        if ($user && Schema::hasTable('siswa')) {
            return app(SchoolDataController::class)->show($section);
        }

        if ($user?->role === 'admin') {
            return app(AdminDashboardController::class)->show($section ?? 'ringkasan');
        }

        abort_if($user && ! in_array($user->role, ['teacher', 'principal', 'parent'], true), 403);

        return view('dashboard', ['section' => $section]);
    }
}
