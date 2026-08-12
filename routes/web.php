<?php

use App\Http\Controllers\Auth\LogoutController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::livewire('/register', 'pages::auth.register')->name('register');
    Route::livewire('/login', 'pages::auth.login')->name('login');
});

Route::middleware('auth')->group(function () {
    Route::livewire('/dashboard', 'pages::dashboard')->name('dashboard');

    Route::livewire('/goals', 'pages::goals.index')->name('goals.index');
    Route::livewire('/goals/create', 'pages::goals.create')->name('goals.create');
    Route::livewire('/goals/{goal}/edit', 'pages::goals.edit')->name('goals.edit');

    Route::post('/logout', LogoutController::class)->name('logout');
});
