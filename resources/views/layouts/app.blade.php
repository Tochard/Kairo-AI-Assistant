<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ $title ?? 'Kairo' }}</title>

        <link rel="icon" href="/images/favicon.ico" sizes="any">
        <link rel="apple-touch-icon" href="/images/logo.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.js'])

        <script>
            if (!window.localStorage.getItem('flux.appearance')) {
                window.localStorage.setItem('flux.appearance', 'dark');
            }
        </script>
        @fluxAppearance
    </head>
    <body class="relative flex h-full min-h-screen flex-col bg-bg font-sans text-ink antialiased">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 bg-[radial-gradient(ellipse_60%_50%_at_50%_-10%,var(--color-teal-glow),transparent)] opacity-[0.08] dark:opacity-[0.15]"></div>

        <header class="sticky top-0 z-30 border-b border-border bg-surface/80 backdrop-blur-lg">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3.5 md:px-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold tracking-tight text-ink">
                    <span class="inline-block size-2 rounded-full bg-teal shadow-[0_0_12px_var(--color-teal-glow)]"></span>
                    Kairo
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-ink-muted md:flex">
                    <a href="{{ route('dashboard') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('dashboard') ? 'text-teal' : '' }}">Dashboard</a>
                    @if (Route::has('goals.index'))
                        <a href="{{ route('goals.index') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('goals.*') ? 'text-teal' : '' }}">Goals</a>
                    @endif
                    @if (Route::has('tasks.index'))
                        <a href="{{ route('tasks.index') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('tasks.*') ? 'text-teal' : '' }}">Tasks</a>
                    @endif
                </nav>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <flux:button type="submit" variant="ghost" size="sm">Log out</flux:button>
                </form>
            </div>
        </header>

        <main class="relative mx-auto w-full max-w-4xl flex-1 px-4 pb-24 pt-6 md:px-6 md:pb-10">
            {{ $slot }}
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-border bg-surface/90 px-2 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg md:hidden"
            aria-label="Primary"
        >
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('dashboard') ? 'text-teal' : 'text-ink-muted' }}">
                <flux:icon name="home" class="size-6" />
                Home
            </a>
            @if (Route::has('goals.index'))
                <a href="{{ route('goals.index') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('goals.*') ? 'text-teal' : 'text-ink-muted' }}">
                    <flux:icon name="flag" class="size-6" />
                    Goals
                </a>
            @endif
            @if (Route::has('tasks.index'))
                <a href="{{ route('tasks.index') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('tasks.*') ? 'text-teal' : 'text-ink-muted' }}">
                    <flux:icon name="check-circle" class="size-6" />
                    Tasks
                </a>
            @endif
        </nav>

        @fluxScripts
    </body>
</html>
