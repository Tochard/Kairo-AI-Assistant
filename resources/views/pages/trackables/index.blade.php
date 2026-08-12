<?php

use App\Models\Trackable;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public function delete(Trackable $trackable): void
    {
        $this->authorize('delete', $trackable);

        $trackable->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'trackables' => auth()->user()->trackables()
                ->with(['logs' => fn ($query) => $query->latest('logged_at')->limit(1)])
                ->latest()
                ->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Trackables</h1>
        <a
            href="{{ route('trackables.create') }}"
            wire:navigate
            class="flex cursor-pointer items-center gap-1.5 rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4">
                <path d="M12 5v14M5 12h14" />
            </svg>
            New trackable
        </a>
    </div>

    @if ($trackables->isEmpty())
        <div class="rounded-3xl border border-dashed border-border bg-surface p-8 text-center">
            <p class="text-ink-muted">Nothing to track yet — what do you want to keep an eye on?</p>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($trackables as $trackable)
                <div wire:key="trackable-{{ $trackable->id }}" class="flex items-center justify-between gap-4 rounded-2xl border border-border bg-surface p-4">
                    <a href="{{ route('trackables.show', $trackable) }}" wire:navigate class="min-w-0 flex-1">
                        <span class="font-medium text-ink hover:text-teal">{{ $trackable->name }}</span>
                        <div class="mt-1 text-xs text-ink-muted">
                            {{ $trackable->direction->label() }}
                            @if ($trackable->target_value !== null)
                                &middot; Target {{ number_format($trackable->target_value, 2) }} {{ $trackable->unit }}
                            @endif
                        </div>
                    </a>

                    <div class="shrink-0 text-right">
                        @if ($trackable->logs->isNotEmpty())
                            <div class="text-lg font-bold text-teal">
                                {{ number_format($trackable->logs->first()->value, 2) }}
                                <span class="text-xs font-normal text-ink-muted">{{ $trackable->unit }}</span>
                            </div>
                        @else
                            <span class="text-xs text-ink-muted">No logs yet</span>
                        @endif
                    </div>

                    <button
                        type="button"
                        wire:click="delete({{ $trackable->id }})"
                        wire:confirm="Delete this trackable and all its logs? This can't be undone."
                        aria-label="Delete {{ $trackable->name }}"
                        class="flex size-9 shrink-0 cursor-pointer items-center justify-center rounded-full text-ink-muted transition hover:bg-surface-muted hover:text-ink"
                    >
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                            <path d="M4 7h16" />
                            <path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13" />
                            <path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3" />
                        </svg>
                    </button>
                </div>
            @endforeach
        </div>
    @endif
</div>
