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

    #[Validate('required|string|min:8')]
    public string $password = '';

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
    <h1 class="text-2xl font-bold tracking-tight text-ink">Let's get you set up</h1>
    <p class="mt-1.5 mb-7 text-sm text-ink-muted">A minute of setup, and I'll take it from there.</p>

    <form wire:submit="register" class="flex flex-col gap-5">
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-ink">Name</label>
            <input
                wire:model="name"
                id="name"
                type="text"
                autocomplete="name"
                autofocus
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('name')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-ink">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="username"
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('email')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-sm font-medium text-ink">Password</label>
            <input
                wire:model="password"
                id="password"
                type="password"
                autocomplete="new-password"
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <button
            type="submit"
            class="mt-2 cursor-pointer rounded-xl bg-accent px-4 py-2.5 font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40 focus:outline-none focus:ring-2 focus:ring-teal/50"
        >
            Create my account
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        Already planning with Kairo?
        <a href="{{ route('login') }}" wire:navigate class="font-medium text-teal hover:underline">Log in</a>
    </p>
</div>
