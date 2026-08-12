<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Kairo' }}</title>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        @fluxAppearance
    </head>
    <body class="flex h-full min-h-screen flex-col bg-bg font-sans text-ink antialiased">
        <header class="border-b border-border bg-surface">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-4 md:px-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="font-display text-xl font-semibold text-ink">
                    Kairo
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-ink-muted md:flex">
                    <a href="{{ route('dashboard') }}" wire:navigate class="hover:text-ink {{ request()->routeIs('dashboard') ? 'text-teal' : '' }}">Dashboard</a>
                    <a href="{{ route('goals.index') }}" wire:navigate class="hover:text-ink {{ request()->routeIs('goals.*') ? 'text-teal' : '' }}">Goals</a>
                    <a href="{{ route('tasks.index') }}" wire:navigate class="hover:text-ink {{ request()->routeIs('tasks.*') ? 'text-teal' : '' }}">Tasks</a>
                </nav>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" size="sm">Log out</flux:button>
                </form>
            </div>
        </header>

        <main class="mx-auto w-full max-w-4xl flex-1 px-4 pb-24 pt-6 md:px-6 md:pb-10">
            {{ $slot }}
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-border bg-surface/95 px-2 pb-[env(safe-area-inset-bottom)] backdrop-blur md:hidden"
            aria-label="Primary"
        >
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('dashboard') ? 'text-teal' : 'text-ink-muted' }}">
                <flux:icon name="home" class="size-6" />
                Home
            </a>
            <a href="{{ route('goals.index') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('goals.*') ? 'text-teal' : 'text-ink-muted' }}">
                <flux:icon name="flag" class="size-6" />
                Goals
            </a>
            <a href="{{ route('tasks.index') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('tasks.*') ? 'text-teal' : 'text-ink-muted' }}">
                <flux:icon name="check-circle" class="size-6" />
                Tasks
            </a>
        </nav>

        @fluxScripts
    </body>
</html>
