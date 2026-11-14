<?php

use App\Http\Controllers\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('/login', 'welcome')->name('login');
Route::post('/login', LoginController::class)->name('login.authenticate');
Route::view('/dashboard', 'dashboard')->name('dashboard');
