<?php

use App\Models\Trackable;
use App\Models\TrackableLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public Trackable $trackable;

    #[Validate('required|numeric')]
    public string $value = '';

    #[Validate('nullable|string|max:255')]
    public ?string $note = null;

    #[Validate('required|date')]
    public string $logged_at = '';

    public function mount(Trackable $trackable): void
    {
        $this->authorize('view', $trackable);

        $this->trackable = $trackable;
        $this->logged_at = now()->format('Y-m-d\TH:i');
    }

    public function log(): void
    {
        $this->authorize('update', $this->trackable);

        $validated = $this->validate();

        $this->trackable->logs()->create($validated);

        $this->reset(['value', 'note']);
        $this->logged_at = now()->format('Y-m-d\TH:i');
    }

    public function deleteLog(TrackableLog $log): void
    {
        $this->authorize('update', $this->trackable);

        abort_unless($log->trackable_id === $this->trackable->id, 403);

        $log->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $logs = $this->trackable->logs()->orderBy('logged_at')->get();

        return [
            'chartLabels' => $logs->map(fn (TrackableLog $log) => $log->logged_at->format('M j'))->values()->all(),
            'chartValues' => $logs->pluck('value')->values()->all(),
            'latest' => $logs->last(),
            'history' => $logs->sortByDesc('logged_at')->values(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-ink">{{ $trackable->name }}</h1>
            <p class="mt-1 text-sm text-ink-muted">{{ $trackable->direction->label() }}</p>
        </div>
        <a href="{{ route('trackables.edit', $trackable) }}" wire:navigate class="rounded-xl px-4 py-2 text-sm font-semibold text-ink-muted transition hover:bg-surface-muted hover:text-ink">
            Edit
        </a>
    </div>

    <div class="rounded-3xl border border-border bg-surface p-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">Latest</p>
                <p class="mt-1 text-3xl font-bold text-teal">
                    {{ $latest ? number_format($latest->value, 2) : '—' }}
                    <span class="text-base font-normal text-ink-muted">{{ $trackable->unit }}</span>
                </p>
            </div>
            @if ($trackable->target_value !== null)
                <div class="text-right">
                    <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">Target</p>
                    <p class="mt-1 text-lg font-semibold text-ink">{{ number_format($trackable->target_value, 2) }} {{ $trackable->unit }}</p>
                </div>
            @endif
        </div>

        @if ($trackable->target_value !== null && $latest)
            <div class="mt-4 h-2 w-full overflow-hidden rounded-full bg-surface-muted">
                <div
                    class="h-full rounded-full bg-teal transition-all"
                    style="width: {{ min(100, max(0, ($latest->value / max($trackable->target_value, 0.01)) * 100)) }}%"
                ></div>
            </div>
        @endif

        <div class="mt-6 h-48 w-full">
            @if (empty($chartValues))
                <div class="flex h-full items-center justify-center text-sm text-ink-muted">
                    No logs yet — add your first one below.
                </div>
            @else
                <div x-data="trackableChart(@js($chartLabels), @js($chartValues), '#14b8a6')" class="h-full w-full">
                    <canvas x-ref="canvas"></canvas>
                </div>
            @endif
        </div>
    </div>

    <div class="rounded-3xl border border-border bg-surface p-6">
        <h2 class="text-lg font-bold tracking-tight text-ink">Log a value</h2>

        <form wire:submit="log" class="mt-4 flex flex-col gap-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label for="value" class="mb-1.5 block text-sm font-medium text-ink">Value ({{ $trackable->unit }})</label>
                    <input
                        wire:model="value"
                        id="value"
                        type="number"
                        step="0.01"
                        autofocus
                        class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
                    >
                    @error('value')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="logged_at" class="mb-1.5 block text-sm font-medium text-ink">When</label>
                    <input
                        wire:model="logged_at"
                        id="logged_at"
                        type="datetime-local"
                        class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
                    >
                    @error('logged_at')
                        <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            <div>
                <label for="note" class="mb-1.5 block text-sm font-medium text-ink">Note (optional)</label>
                <input
                    wire:model="note"
                    id="note"
                    type="text"
                    placeholder="e.g. transport, groceries"
                    class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
                >
                @error('note')
                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="cursor-pointer self-start rounded-xl bg-accent px-4 py-2.5 font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
            >
                Log it
            </button>
        </form>
    </div>

    @if ($history->isNotEmpty())
        <div class="flex flex-col gap-3">
            <h2 class="text-lg font-bold tracking-tight text-ink">History</h2>

            <div class="flex flex-col gap-2">
                @foreach ($history as $log)
                    <div wire:key="log-{{ $log->id }}" class="flex items-center justify-between gap-4 rounded-2xl border border-border bg-surface p-3.5">
                        <div class="min-w-0">
                            <span class="font-medium text-ink">{{ number_format($log->value, 2) }} {{ $trackable->unit }}</span>
                            <div class="mt-0.5 text-xs text-ink-muted">
                                {{ $log->logged_at->format('M j, Y g:ia') }}
                                @if ($log->note)
                                    &middot; {{ $log->note }}
                                @endif
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="deleteLog({{ $log->id }})"
                            wire:confirm="Delete this log entry?"
                            aria-label="Delete log entry"
                            class="flex size-8 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-ink"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
                                <path d="M4 7h16" />
                                <path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13" />
                                <path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3" />
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
