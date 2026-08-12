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

    Route::livewire('/chat', 'pages::chat')->name('chat');

    Route::livewire('/goals', 'pages::goals.index')->name('goals.index');
    Route::livewire('/goals/create', 'pages::goals.create')->name('goals.create');
    Route::livewire('/goals/{goal}/edit', 'pages::goals.edit')->name('goals.edit');

    Route::livewire('/tasks', 'pages::tasks.index')->name('tasks.index');
    Route::livewire('/tasks/create', 'pages::tasks.create')->name('tasks.create');
    Route::livewire('/tasks/{task}/edit', 'pages::tasks.edit')->name('tasks.edit');

    Route::livewire('/trackables', 'pages::trackables.index')->name('trackables.index');
    Route::livewire('/trackables/create', 'pages::trackables.create')->name('trackables.create');
    Route::livewire('/trackables/{trackable}', 'pages::trackables.show')->name('trackables.show');
    Route::livewire('/trackables/{trackable}/edit', 'pages::trackables.edit')->name('trackables.edit');

    Route::post('/logout', LogoutController::class)->name('logout');
});
