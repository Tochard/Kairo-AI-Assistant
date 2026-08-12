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
        <h1 class="text-2xl font-bold tracking-tight text-ink">Tasks</h1>
        <a
            href="{{ route('tasks.create') }}"
            wire:navigate
            class="flex cursor-pointer items-center gap-1.5 rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
        >
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" class="size-4">
                <path d="M12 5v14M5 12h14" />
            </svg>
            New task
        </a>
    </div>

    @if ($overdue->isEmpty() && $today->isEmpty() && $upcoming->isEmpty() && $missed->isEmpty() && $done->isEmpty())
        <div class="rounded-3xl border border-dashed border-border bg-surface p-8 text-center">
            <p class="text-ink-muted">No tasks yet — what's the next small step?</p>
        </div>
    @else
        @foreach (['Overdue' => $overdue, 'Today' => $today, 'Upcoming' => $upcoming, 'Missed' => $missed, 'Done' => $done] as $label => $group)
            @if ($group->isNotEmpty())
                <div class="flex flex-col gap-3">
                    <p class="text-xs font-semibold tracking-wide text-ink-muted uppercase">{{ $label }}</p>

                    @foreach ($group as $task)
                        <div wire:key="task-{{ $task->id }}" class="flex items-center gap-3 rounded-2xl border border-border bg-surface p-4">
                            <button
                                type="button"
                                wire:click="toggleDone({{ $task->id }})"
                                aria-label="{{ $task->status === \App\Enums\TaskStatus::Done ? 'Mark as pending' : 'Mark as done' }}"
                                class="flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full border-2 transition {{ $task->status === \App\Enums\TaskStatus::Done ? 'border-teal bg-teal text-white' : 'border-border text-transparent hover:border-teal' }}"
                            >
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
                                    <path d="M20 6 9 17l-5-5" />
                                </svg>
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
                                        <span class="rounded-full border border-teal/20 bg-teal/10 px-2 py-0.5 text-xs font-medium text-teal">
                                            {{ $task->goal->title }}
                                        </span>
                                    @endif
                                </div>
                            </div>

                            <button
                                type="button"
                                wire:click="delete({{ $task->id }})"
                                wire:confirm="Delete this task? This can't be undone."
                                aria-label="Delete {{ $task->title }}"
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
        @endforeach
    @endif
</div>
