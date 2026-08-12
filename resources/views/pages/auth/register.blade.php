<?php

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|email|max:255|unique:users,email')]
    public string $email = '';

    #[Validate('required|string|min:8|confirmed')]
    public string $password = '';

    public string $password_confirmation = '';

    public function register(): void
    {
        $validated = $this->validate();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        Auth::login($user);

        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }
};
?>

<div>
    <flux:heading size="lg" class="font-display">Let's get you set up</flux:heading>
    <flux:text class="mt-1 mb-6 text-ink-muted">A minute of setup, and I'll take it from there.</flux:text>

    <form wire:submit="register" class="flex flex-col gap-4">
        <flux:field>
            <flux:label>Name</flux:label>
            <flux:input wire:model="name" type="text" autocomplete="name" autofocus />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input wire:model="email" type="email" autocomplete="username" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input wire:model="password" type="password" autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label>Confirm password</flux:label>
            <flux:input wire:model="password_confirmation" type="password" autocomplete="new-password" />
        </flux:field>

        <flux:button type="submit" variant="primary" class="mt-2">Create my account</flux:button>
    </form>

    <flux:text class="mt-6 text-center text-sm">
        Already planning with Kairo?
        <flux:link href="{{ route('login') }}" wire:navigate>Log in</flux:link>
    </flux:text>
</div>
