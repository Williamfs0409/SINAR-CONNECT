<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\LoginController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/login', 'welcome')->name('login');
Route::post('/login', LoginController::class)->name('login.authenticate');
Route::get('/dashboard', [DashboardController::class, 'show'])->name('dashboard');
Route::get('/dashboard/{section}', [DashboardController::class, 'show'])
    ->where('section', '[a-z-]+')
    ->name('dashboard.feature');
Route::post('/admin/students', [AdminDashboardController::class, 'storeStudent'])
    ->name('admin.students.store');
Route::post('/admin/students/{student}/activate', [AdminDashboardController::class, 'activateStudent'])
    ->name('admin.students.activate');
Route::patch('/admin/students/{student}', [AdminDashboardController::class, 'updateStudent'])
    ->name('admin.students.update');
Route::post('/admin/invoices', [AdminDashboardController::class, 'createInvoice'])
    ->name('admin.invoices.store');
Route::post('/admin/payments', [AdminDashboardController::class, 'recordSppPayment'])
    ->name('admin.payments.store');
Route::post('/admin/savings', [AdminDashboardController::class, 'recordSavings'])
    ->name('admin.savings.store');
Route::post('/admin/users', [AdminDashboardController::class, 'createUser'])
    ->name('admin.users.store');
Route::patch('/admin/users/{user}/role', [AdminDashboardController::class, 'updateUserRole'])
    ->name('admin.users.role');
Route::post('/logout', function (Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('home');
})->middleware('auth')->name('logout');
