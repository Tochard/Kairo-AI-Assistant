<?php

use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::guest')] class extends Component
{
    #[Validate('required|string|email')]
    public string $email = '';

    #[Validate('required|string')]
    public string $password = '';

    public bool $remember = false;

    public function login(): void
    {
        $credentials = $this->validate();

        if (! Auth::attempt($credentials, $this->remember)) {
            $this->addError('email', "Those credentials don't match anything I know — want to try again?");

            return;
        }

        session()->regenerate();

        $this->redirect(route('dashboard'), navigate: true);
    }
};
?>

<div>
    <h1 class="text-2xl font-bold tracking-tight text-ink">Welcome back</h1>
    <p class="mt-1.5 mb-7 text-sm text-ink-muted">Good to see you. Let's pick up where we left off.</p>

    <form wire:submit="login" class="flex flex-col gap-5">
        <div>
            <label for="email" class="mb-1.5 block text-sm font-medium text-ink">Email</label>
            <input
                wire:model="email"
                id="email"
                type="email"
                autocomplete="username"
                autofocus
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
                autocomplete="current-password"
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('password')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex cursor-pointer items-center gap-2 text-sm text-ink-muted select-none">
            <input
                wire:model="remember"
                type="checkbox"
                class="size-4 rounded border-border bg-surface text-teal accent-teal focus:ring-2 focus:ring-teal/30"
            >
            Keep me signed in
        </label>

        <button
            type="submit"
            class="mt-2 cursor-pointer rounded-xl bg-accent px-4 py-2.5 font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40 focus:outline-none focus:ring-2 focus:ring-teal/50"
        >
            Log in
        </button>
    </form>

    <p class="mt-6 text-center text-sm text-ink-muted">
        New to Kairo?
        <a href="{{ route('register') }}" wire:navigate class="font-medium text-teal hover:underline">Create an account</a>
    </p>
</div>
