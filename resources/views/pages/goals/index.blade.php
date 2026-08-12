<?php

use App\Models\Goal;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public function delete(Goal $goal): void
    {
        $this->authorize('delete', $goal);

        $goal->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'goals' => auth()->user()->goals()->latest()->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-bold tracking-tight text-ink">Goals</h1>
        <a
            href="{{ route('goals.create') }}"
            wire:navigate
            class="flex cursor-pointer items-center gap-1.5 rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4">
                <path d="M12 5v14M5 12h14" />
            </svg>
            New goal
        </a>
    </div>

    @if ($goals->isEmpty())
        <div class="rounded-3xl border border-dashed border-border bg-surface p-8 text-center">
            <p class="text-ink-muted">No goals yet — tell me something you're working toward.</p>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($goals as $goal)
                <div wire:key="goal-{{ $goal->id }}" class="flex items-center justify-between gap-4 rounded-2xl border border-border bg-surface p-4">
                    <div class="min-w-0">
                        <a href="{{ route('goals.edit', $goal) }}" wire:navigate class="font-medium text-ink hover:text-teal">
                            {{ $goal->title }}
                        </a>
                        <div class="mt-1.5 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $goal->status->badgeClasses() }}">
                                {{ $goal->status->label() }}
                            </span>
                            @if ($goal->target_date)
                                <span>Target: {{ $goal->target_date->format('M j, Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <button
                        type="button"
                        wire:click="delete({{ $goal->id }})"
                        wire:confirm="Delete this goal? This can't be undone."
                        aria-label="Delete {{ $goal->title }}"
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
