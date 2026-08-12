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
    <flux:heading size="lg" class="font-display">Welcome back</flux:heading>
    <flux:text class="mt-1 mb-6 text-ink-muted">Good to see you. Let's pick up where we left off.</flux:text>

    <form wire:submit="login" class="flex flex-col gap-4">
        <flux:field>
            <flux:label>Email</flux:label>
            <flux:input wire:model="email" type="email" autocomplete="username" autofocus />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label>Password</flux:label>
            <flux:input wire:model="password" type="password" autocomplete="current-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:checkbox wire:model="remember" label="Keep me signed in" />

        <flux:button type="submit" variant="primary" class="mt-2">Log in</flux:button>
    </form>

    <flux:text class="mt-6 text-center text-sm">
        New to Kairo?
        <flux:link href="{{ route('register') }}" wire:navigate>Create an account</flux:link>
    </flux:text>
</div>
