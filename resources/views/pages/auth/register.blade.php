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
            <div class="relative" x-data="{ show: false }">
                <input
                    wire:model="password"
                    id="password"
                    :type="show ? 'text' : 'password'"
                    autocomplete="new-password"
                    class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 pr-11 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
                >
                <button
                    type="button"
                    x-on:click="show = ! show"
                    :aria-label="show ? 'Hide password' : 'Show password'"
                    class="absolute inset-y-0 right-0 flex w-11 cursor-pointer items-center justify-center text-ink-muted hover:text-teal"
                >
                    <svg x-cloak x-show="! show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                        <circle cx="12" cy="12" r="3" />
                    </svg>
                    <svg x-cloak x-show="show" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5">
                        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7Z" />
                        <circle cx="12" cy="12" r="3" />
                        <path d="M3 3l18 18" />
                    </svg>
                </button>
            </div>
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
