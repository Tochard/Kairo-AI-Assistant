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
    <body class="relative flex h-screen overflow-hidden bg-bg font-sans text-ink antialiased">
        <div class="pointer-events-none fixed inset-x-0 top-0 -z-10 h-96 bg-[radial-gradient(ellipse_60%_50%_at_50%_-10%,var(--color-teal-glow),transparent)] opacity-[0.08] dark:opacity-[0.15]"></div>

        @php
            $navLinks = [
                ['route' => 'dashboard', 'pattern' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home'],
                ['route' => 'chat', 'pattern' => 'chat', 'label' => 'Chat', 'icon' => 'chat'],
                ['route' => 'goals.index', 'pattern' => 'goals.*', 'label' => 'Goals', 'icon' => 'goals'],
                ['route' => 'tasks.index', 'pattern' => 'tasks.*', 'label' => 'Tasks', 'icon' => 'tasks'],
                ['route' => 'trackables.index', 'pattern' => 'trackables.*', 'label' => 'Trackables', 'icon' => 'trackables'],
            ];
        @endphp

        {{-- Desktop sidebar --}}
        <aside class="hidden h-full w-60 shrink-0 flex-col border-r border-border bg-surface/60 backdrop-blur-lg md:flex">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2 px-6 py-5 text-lg font-bold tracking-tight text-ink">
                <img src="/images/logo.webp" alt="" class="h-8 w-auto">
                Kairo
            </a>

            <nav class="flex flex-1 flex-col gap-1 px-3" aria-label="Primary">
                @foreach ($navLinks as $link)
                    @if (Route::has($link['route']))
                        <a
                            href="{{ route($link['route']) }}"
                            wire:navigate
                            class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium transition {{ request()->routeIs($link['pattern']) ? 'bg-surface-muted text-teal' : 'text-ink-muted hover:bg-surface-muted hover:text-ink' }}"
                        >
                            @include('layouts.partials.nav-icon', ['icon' => $link['icon']])
                            {{ $link['label'] }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="flex flex-col gap-1 border-t border-border p-3" x-data>
                <button
                    type="button"
                    x-on:click="$flux.dark = ! $flux.dark"
                    class="flex cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-surface-muted hover:text-ink"
                >
                    <svg x-cloak x-show="$flux.dark" viewBox="0 0 24 24" fill="currentColor" class="size-5 shrink-0">
                        <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                    </svg>
                    <svg x-cloak x-show="! $flux.dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-5 shrink-0">
                        <circle cx="12" cy="12" r="4" />
                        <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                    </svg>
                    <span x-text="$flux.dark ? 'Dark mode' : 'Light mode'"></span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="flex w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-muted transition hover:bg-red-500/10 hover:text-red-400"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                            <path d="M16 17l5-5-5-5" />
                            <path d="M21 12H9" />
                        </svg>
                        Log out
                    </button>
                </form>
            </div>
        </aside>

        {{-- Main content --}}
        <main class="relative mx-auto flex h-full w-full max-w-5xl min-w-0 flex-1 flex-col overflow-hidden">
            @if (request()->routeIs('chat'))
                {{ $slot }}
            @else
                <div class="no-scrollbar h-full overflow-y-auto px-4 pt-6 pb-24 md:px-8 md:pt-8 md:pb-10">
                    {{ $slot }}
                </div>
            @endif
        </main>

        {{-- Mobile bottom nav + menu sheet --}}
        <div x-data="{ menuOpen: false }" class="md:hidden">
            <nav
                class="fixed inset-x-0 bottom-0 z-40 flex items-center justify-around border-t border-border bg-surface/90 px-1 pb-[env(safe-area-inset-bottom)] backdrop-blur-lg"
                aria-label="Primary"
            >
                @foreach ($navLinks as $link)
                    @if (Route::has($link['route']))
                        <a
                            href="{{ route($link['route']) }}"
                            wire:navigate
                            class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium {{ request()->routeIs($link['pattern']) ? 'text-teal' : 'text-ink-muted' }}"
                        >
                            @include('layouts.partials.nav-icon', ['icon' => $link['icon'], 'class' => 'size-6'])
                            {{ $link['label'] }}
                        </a>
                    @endif
                @endforeach

                <button
                    type="button"
                    x-on:click="menuOpen = true"
                    aria-label="Menu"
                    class="flex min-h-11 min-w-11 flex-1 flex-col items-center justify-center gap-0.5 py-2 text-xs font-medium text-ink-muted"
                >
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-6">
                        <path d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                    Menu
                </button>
            </nav>

            <div x-show="menuOpen" x-cloak class="fixed inset-0 z-50">
                <div class="absolute inset-0 bg-black/50" x-on:click="menuOpen = false"></div>
                <div
                    class="absolute inset-x-0 bottom-0 flex max-h-[80vh] flex-col gap-1 overflow-y-auto rounded-t-3xl bg-surface p-4 pb-[calc(env(safe-area-inset-bottom)+1rem)] shadow-2xl"
                    x-on:click.outside="menuOpen = false"
                >
                    <div class="mx-auto mb-2 h-1 w-10 shrink-0 rounded-full bg-border"></div>

                    @foreach ($navLinks as $link)
                        @if (Route::has($link['route']))
                            <a
                                href="{{ route($link['route']) }}"
                                wire:navigate
                                x-on:click="menuOpen = false"
                                class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium {{ request()->routeIs($link['pattern']) ? 'bg-surface-muted text-teal' : 'text-ink-muted' }}"
                            >
                                @include('layouts.partials.nav-icon', ['icon' => $link['icon']])
                                {{ $link['label'] }}
                            </a>
                        @endif
                    @endforeach

                    <div class="my-2 border-t border-border"></div>

                    <div x-data>
                        <button
                            type="button"
                            x-on:click="$flux.dark = ! $flux.dark"
                            class="flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-muted"
                        >
                            <svg x-cloak x-show="$flux.dark" viewBox="0 0 24 24" fill="currentColor" class="size-5 shrink-0">
                                <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                            </svg>
                            <svg x-cloak x-show="! $flux.dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-5 shrink-0">
                                <circle cx="12" cy="12" r="4" />
                                <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                            </svg>
                            <span x-text="$flux.dark ? 'Dark mode' : 'Light mode'"></span>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button
                            type="submit"
                            class="flex min-h-11 w-full cursor-pointer items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink-muted hover:text-red-400"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0">
                                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4" />
                                <path d="M16 17l5-5-5-5" />
                                <path d="M21 12H9" />
                            </svg>
                            Log out
                        </button>
                    </form>
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
