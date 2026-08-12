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
    <body class="flex min-h-screen flex-col items-center justify-center bg-bg px-4 py-10 font-sans text-ink antialiased">
        <div class="w-full max-w-sm">
            <div class="mb-8 text-center">
                <span class="font-display text-3xl font-semibold text-ink">Kairo</span>
                <p class="mt-1 text-sm text-ink-muted">The right plan, at the right moment.</p>
            </div>

            <div class="rounded-3xl border border-border bg-surface p-6 shadow-sm sm:p-8">
                {{ $slot }}
            </div>
        </div>

        @fluxScripts
    </body>
</html>
