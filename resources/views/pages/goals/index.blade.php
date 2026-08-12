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
        <flux:heading size="xl" class="font-display">Goals</flux:heading>
        <flux:button href="{{ route('goals.create') }}" wire:navigate variant="primary" icon="plus">New goal</flux:button>
    </div>

    @if ($goals->isEmpty())
        <div class="rounded-3xl border border-dashed border-border bg-surface p-8 text-center">
            <flux:text class="text-ink-muted">No goals yet — tell me something you're working toward.</flux:text>
        </div>
    @else
        <div class="flex flex-col gap-3">
            @foreach ($goals as $goal)
                <div wire:key="goal-{{ $goal->id }}" class="flex items-center justify-between gap-4 rounded-2xl border border-border bg-surface p-4">
                    <div class="min-w-0">
                        <a href="{{ route('goals.edit', $goal) }}" wire:navigate class="font-medium text-ink hover:text-teal">
                            {{ $goal->title }}
                        </a>
                        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                            <flux:badge size="sm" :color="$goal->status->color()">{{ $goal->status->label() }}</flux:badge>
                            @if ($goal->target_date)
                                <span>Target: {{ $goal->target_date->format('M j, Y') }}</span>
                            @endif
                        </div>
                    </div>
                    <flux:button
                        size="sm"
                        variant="ghost"
                        icon="trash"
                        wire:click="delete({{ $goal->id }})"
                        wire:confirm="Delete this goal? This can't be undone."
                        aria-label="Delete {{ $goal->title }}"
                    />
                </div>
            @endforeach
        </div>
    @endif
</div>
