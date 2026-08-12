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
    <body class="relative min-h-screen overflow-hidden bg-bg font-sans text-ink antialiased">
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_60%_50%_at_15%_0%,var(--color-teal-glow),transparent)] opacity-[0.12] dark:opacity-25"></div>
        <div class="pointer-events-none fixed inset-0 -z-10 bg-[radial-gradient(ellipse_40%_35%_at_100%_100%,var(--color-violet-glow),transparent)] opacity-[0.08] dark:opacity-15"></div>

        <div class="fixed right-4 top-4 z-50 sm:right-6 sm:top-6" x-data>
            <button
                type="button"
                x-on:click="$flux.dark = ! $flux.dark"
                aria-label="Toggle dark mode"
                class="flex size-10 cursor-pointer items-center justify-center rounded-full border border-border bg-surface/80 text-ink-muted backdrop-blur-lg transition hover:border-teal hover:text-teal"
            >
                <svg x-cloak x-show="$flux.dark" viewBox="0 0 24 24" fill="currentColor" class="size-5">
                    <path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                </svg>
                <svg x-cloak x-show="! $flux.dark" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-5">
                    <circle cx="12" cy="12" r="4" />
                    <path d="M12 2v2M12 20v2M4.93 4.93l1.41 1.41M17.66 17.66l1.41 1.41M2 12h2M20 12h2M6.34 17.66l-1.41 1.41M19.07 4.93l-1.41 1.41" />
                </svg>
            </button>
        </div>

        <div class="relative z-10 flex min-h-screen flex-col lg:flex-row">
            <div class="hidden flex-col justify-between overflow-hidden border-r border-border p-12 lg:flex lg:w-1/2">
                <a href="{{ route('login') }}" class="flex items-center gap-2.5">
                    <img src="/images/logo.webp" alt="Kairo" class="h-9 w-auto drop-shadow-[0_0_16px_var(--color-teal-glow)]">
                    <span class="text-xl font-bold tracking-tight text-ink">Kairo</span>
                </a>

                <div class="max-w-md">
                    <h1 class="text-4xl font-bold leading-tight tracking-tight text-ink xl:text-5xl">
                        The right plan,<br>at the right moment.
                    </h1>
                    <p class="mt-5 text-ink-muted">
                        Your AI planning partner — tell it what you're working toward, and it'll draft a plan, push back when it's unrealistic, and follow up so it actually happens.
                    </p>
                </div>

                <p class="text-xs text-ink-muted">&copy; {{ date('Y') }} Kairo</p>
            </div>

            <div class="flex w-full flex-1 items-center justify-center px-6 py-12 lg:w-1/2">
                <div class="w-full max-w-sm">
                    <div class="mb-8 flex flex-col items-center text-center lg:hidden">
                        <img src="/images/logo.webp" alt="Kairo" class="mb-3 h-14 w-auto drop-shadow-[0_0_18px_var(--color-teal-glow)]">
                        <span class="text-xl font-bold tracking-tight text-ink">Kairo</span>
                    </div>

                    {{ $slot }}
                </div>
            </div>
        </div>

        @fluxScripts
    </body>
</html>
