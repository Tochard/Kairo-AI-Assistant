<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public function toggleDone(Task $task): void
    {
        $this->authorize('update', $task);

        $task->update([
            'status' => $task->status === TaskStatus::Done ? TaskStatus::Pending : TaskStatus::Done,
        ]);
    }

    public function delete(Task $task): void
    {
        $this->authorize('delete', $task);

        $task->delete();
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $tasks = auth()->user()->tasks()->with('goal')->orderBy('due_date')->get();

        $today = Carbon::today();

        $pending = $tasks->where('status', TaskStatus::Pending);

        return [
            'overdue' => $pending->filter(fn (Task $task) => $task->due_date?->lt($today)),
            'today' => $pending->filter(fn (Task $task) => $task->due_date?->isSameDay($today)),
            'upcoming' => $pending->filter(fn (Task $task) => ! $task->due_date || $task->due_date->gt($today)),
            'missed' => $tasks->where('status', TaskStatus::Missed),
            'done' => $tasks->where('status', TaskStatus::Done),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <div class="flex items-center justify-between">
        <flux:heading size="xl" class="font-display">Tasks</flux:heading>
        <flux:button href="{{ route('tasks.create') }}" wire:navigate variant="primary" icon="plus">New task</flux:button>
    </div>

    @if ($overdue->isEmpty() && $today->isEmpty() && $upcoming->isEmpty() && $missed->isEmpty() && $done->isEmpty())
        <div class="rounded-3xl border border-dashed border-border bg-surface p-8 text-center">
            <flux:text class="text-ink-muted">No tasks yet — what's the next small step?</flux:text>
        </div>
    @else
        @foreach (['Overdue' => $overdue, 'Today' => $today, 'Upcoming' => $upcoming, 'Missed' => $missed, 'Done' => $done] as $label => $group)
            @if ($group->isNotEmpty())
                <div class="flex flex-col gap-3">
                    <flux:text class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $label }}</flux:text>

                    @foreach ($group as $task)
                        <div wire:key="task-{{ $task->id }}" class="flex items-center gap-3 rounded-2xl border border-border bg-surface p-4">
                            <button
                                type="button"
                                wire:click="toggleDone({{ $task->id }})"
                                aria-label="{{ $task->status === \App\Enums\TaskStatus::Done ? 'Mark as pending' : 'Mark as done' }}"
                                class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 {{ $task->status === \App\Enums\TaskStatus::Done ? 'border-teal bg-teal text-white' : 'border-border text-transparent' }}"
                            >
                                <flux:icon name="check" class="size-4" />
                            </button>

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('tasks.edit', $task) }}" wire:navigate class="font-medium text-ink hover:text-teal {{ $task->status === \App\Enums\TaskStatus::Done ? 'line-through opacity-60' : '' }}">
                                    {{ $task->title }}
                                </a>
                                <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-ink-muted">
                                    @if ($task->due_date)
                                        <span>{{ $task->due_date->format('M j, Y') }}</span>
                                    @endif
                                    @if ($task->goal)
                                        <flux:badge size="sm" color="teal">{{ $task->goal->title }}</flux:badge>
                                    @endif
                                </div>
                            </div>

                            <flux:button
                                size="sm"
                                variant="ghost"
                                icon="trash"
                                wire:click="delete({{ $task->id }})"
                                wire:confirm="Delete this task? This can't be undone."
                                aria-label="Delete {{ $task->title }}"
                            />
                        </div>
                    @endforeach
                </div>
            @endif
        @endforeach
    @endif
</div>
