<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function show(?string $section = null): View
    {
        $user = auth()->user();

        if ($user?->role === 'admin') {
            return app(AdminDashboardController::class)->show($section ?? 'ringkasan');
        }

        abort_if($user && ! in_array($user->role, ['teacher', 'principal', 'parent'], true), 403);

        return view('dashboard', ['section' => $section]);
    }
}
