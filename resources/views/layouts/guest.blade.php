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
    <body class="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-bg px-4 py-10 font-sans text-ink antialiased">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_50%_40%_at_50%_0%,var(--color-teal-glow),transparent)] opacity-[0.12] dark:opacity-25"></div>
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_40%_30%_at_85%_90%,var(--color-violet-glow),transparent)] opacity-[0.08] dark:opacity-15"></div>

        <div class="w-full max-w-sm">
            <div class="mb-8 flex flex-col items-center text-center">
                <img src="/images/logo.webp" alt="Kairo" class="mb-3 h-16 w-auto drop-shadow-[0_0_20px_var(--color-teal-glow)]">
                <span class="text-2xl font-bold tracking-tight text-ink">Kairo</span>
                <p class="mt-1 text-sm text-ink-muted">The right plan, at the right moment.</p>
            </div>

            <div class="rounded-3xl border border-border bg-surface/80 p-6 shadow-2xl backdrop-blur-xl sm:p-8">
                {{ $slot }}
            </div>
        </div>

        @fluxScripts
    </body>
</html>
