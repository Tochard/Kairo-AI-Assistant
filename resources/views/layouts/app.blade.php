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
    <body class="relative flex h-screen flex-col overflow-hidden bg-bg font-sans text-ink antialiased">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 bg-[radial-gradient(ellipse_60%_50%_at_50%_-10%,var(--color-teal-glow),transparent)] opacity-[0.08] dark:opacity-[0.15]"></div>

        <header class="sticky top-0 z-30 border-b border-border bg-surface/80 backdrop-blur-lg">
            <div class="mx-auto flex max-w-4xl items-center justify-between px-4 py-3.5 md:px-6">
                <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 text-lg font-bold tracking-tight text-ink">
                    <img src="/images/logo.webp" alt="" class="h-8 w-auto">
                    Kairo
                </a>

                <nav class="hidden items-center gap-6 text-sm font-medium text-ink-muted md:flex">
                    <a href="{{ route('dashboard') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('dashboard') ? 'text-teal' : '' }}">Dashboard</a>
                    @if (Route::has('chat'))
                        <a href="{{ route('chat') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('chat') ? 'text-teal' : '' }}">Chat</a>
                    @endif
                    @if (Route::has('goals.index'))
                        <a href="{{ route('goals.index') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('goals.*') ? 'text-teal' : '' }}">Goals</a>
                    @endif
                    @if (Route::has('tasks.index'))
                        <a href="{{ route('tasks.index') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('tasks.*') ? 'text-teal' : '' }}">Tasks</a>
                    @endif
                    @if (Route::has('trackables.index'))
                        <a href="{{ route('trackables.index') }}" wire:navigate class="transition-colors hover:text-ink {{ request()->routeIs('trackables.*') ? 'text-teal' : '' }}">Trackables</a>
                    @endif
                </nav>

                <div class="flex items-center gap-2">
                    <div x-data>
                        <button
                            type="button"
                            x-on:click="$flux.dark = ! $flux.dark"
                            aria-label="Toggle dark mode"
                            class="flex size-9 cursor-pointer items-center justify-center rounded-full border border-border text-ink-muted transition hover:border-teal hover:text-teal"
                        >
                            <svg x-cloak x-show="$flux.dark" viewBox="0 0 24 24" fill="currentColor" class="size-4.5">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                            </svg>
                            <svg x-cloak x-show="! $flux.dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4.5">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="cursor-pointer rounded-full px-3.5 py-1.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink">
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </header>

        <main class="relative mx-auto flex w-full max-w-4xl flex-1 flex-col overflow-hidden">
            @if (request()->routeIs('chat'))
                {{ $slot }}
            @else
                <div class="no-scrollbar h-full overflow-y-auto px-4 pt-6 pb-24 md:px-6 md:pb-10">
                    {{ $slot }}
                </div>
            @endif
        </main>

        <nav
            class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-border bg-surface/90 px-2 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg md:hidden"
            aria-label="Primary"
        >
            <a href="{{ route('dashboard') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('dashboard') ? 'text-teal' : 'text-ink-muted' }}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                    <path d="M3 11.5 12 4l9 7.5" />
                    <path d="M5.5 10v9a1 1 0 0 0 1 1H9a1 1 0 0 0 1-1v-4a1 1 0 0 1 1-1h2a1 1 0 0 1 1 1v4a1 1 0 0 0 1 1h2.5a1 1 0 0 0 1-1v-9" />
                </svg>
                Home
            </a>
            @if (Route::has('chat'))
                <a href="{{ route('chat') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('chat') ? 'text-teal' : 'text-ink-muted' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                        <path d="M4 5h16v11H8l-4 4V5Z" />
                    </svg>
                    Chat
                </a>
            @endif
            @if (Route::has('goals.index'))
                <a href="{{ route('goals.index') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('goals.*') ? 'text-teal' : 'text-ink-muted' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                        <path d="M5 21V4" />
                        <path d="M5 4h13l-3 4 3 4H5" />
                    </svg>
                    Goals
                </a>
            @endif
            @if (Route::has('tasks.index'))
                <a href="{{ route('tasks.index') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('tasks.*') ? 'text-teal' : 'text-ink-muted' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                        <circle cx="12" cy="12" r="9" />
                        <path d="M8.5 12.5l2.5 2.5 4.5-5" />
                    </svg>
                    Tasks
                </a>
            @endif
            @if (Route::has('trackables.index'))
                <a href="{{ route('trackables.index') }}" wire:navigate class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs('trackables.*') ? 'text-teal' : 'text-ink-muted' }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                        <path d="M5 20V13" />
                        <path d="M12 20V8" />
                        <path d="M19 20v-5" />
                    </svg>
                    Trackables
                </a>
            @endif
        </nav>

        @fluxScripts
    </body>
</html>
