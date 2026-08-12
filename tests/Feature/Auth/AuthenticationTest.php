<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

test('a user can register', function () {
    Livewire::test('pages::auth.register')
        ->set('name', 'Tochard')
        ->set('email', 'tochard@example.com')
        ->set('password', 'password123')
        ->call('register')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'tochard@example.com',
        'name' => 'Tochard',
    ]);
});

test('registration requires a password of at least 8 characters', function () {
    Livewire::test('pages::auth.register')
        ->set('name', 'Tochard')
        ->set('email', 'tochard@example.com')
        ->set('password', 'short')
        ->call('register')
        ->assertHasErrors('password');

    $this->assertGuest();
});

test('a user can log in with correct credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'password123')
        ->call('login')
        ->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('login fails with incorrect credentials', function () {
    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    Livewire::test('pages::auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest();
});

test('a user can log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest();
});
