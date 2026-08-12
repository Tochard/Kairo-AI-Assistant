<?php

use App\Enums\GoalStatus;
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

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        $activeGoals = auth()->user()->goals()
            ->where('status', GoalStatus::Active)
            ->latest()
            ->take(4)
            ->get();

        $todaysTasks = auth()->user()->tasks()
            ->where('status', TaskStatus::Pending)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', Carbon::today())
            ->orderBy('due_date')
            ->take(6)
            ->get();

        return [
            'activeGoals' => $activeGoals,
            'activeGoalCount' => auth()->user()->goals()->where('status', GoalStatus::Active)->count(),
            'todaysTasks' => $todaysTasks,
            'completedCount' => auth()->user()->tasks()->where('status', TaskStatus::Done)->count(),
        ];
    }
};
?>

<div class="flex flex-col gap-8">
    <div class="relative overflow-hidden rounded-3xl border border-border bg-surface p-6 sm:p-8">
        <div class="pointer-events-none absolute inset-0 -z-10 bg-[radial-gradient(ellipse_70%_100%_at_100%_0%,var(--color-teal-glow),transparent)] opacity-[0.06] dark:opacity-20"></div>
        <h1 class="text-2xl font-bold tracking-tight text-ink sm:text-3xl">Hey {{ auth()->user()->name }} 👋</h1>
        <p class="mt-1.5 text-ink-muted">Here's where things stand today.</p>
    </div>

    <div class="grid grid-cols-3 gap-3 sm:gap-4">
        <div class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
            <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">Active goals</p>
            <p class="mt-1.5 text-2xl font-bold text-ink sm:text-3xl">{{ $activeGoalCount }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
            <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">Due today</p>
            <p class="mt-1.5 text-2xl font-bold text-ink sm:text-3xl">{{ $todaysTasks->count() }}</p>
        </div>
        <div class="rounded-2xl border border-border bg-surface p-4 sm:p-5">
            <p class="text-xs font-medium tracking-wide text-ink-muted uppercase">Completed</p>
            <p class="mt-1.5 text-2xl font-bold text-teal sm:text-3xl">{{ $completedCount }}</p>
        </div>
    </div>

    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold tracking-tight text-ink">Active goals</h2>
            <a href="{{ route('goals.index') }}" wire:navigate class="text-sm font-medium text-teal hover:underline">View all</a>
        </div>

        @if ($activeGoals->isEmpty())
            <div class="rounded-3xl border border-dashed border-border bg-surface p-6 text-center">
                <p class="text-ink-muted">No goals yet — tell me something you're working toward.</p>
                <a
                    href="{{ route('goals.create') }}"
                    wire:navigate
                    class="mt-3 inline-block cursor-pointer rounded-xl bg-accent px-4 py-2 text-sm font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
                >
                    Start a goal
                </a>
            </div>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($activeGoals as $goal)
                    <a
                        wire:key="dash-goal-{{ $goal->id }}"
                        href="{{ route('goals.edit', $goal) }}"
                        wire:navigate
                        class="flex items-center justify-between gap-4 rounded-2xl border border-border bg-surface p-4 transition hover:border-teal"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-5 shrink-0 text-teal">
                                <path d="M5 21V4" />
                                <path d="M5 4h13l-3 4 3 4H5" />
                            </svg>
                            <span class="truncate font-medium text-ink">{{ $goal->title }}</span>
                        </div>
                        @if ($goal->target_date)
                            <span class="shrink-0 text-xs text-ink-muted">{{ $goal->target_date->format('M j, Y') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($activeGoalCount > $activeGoals->count())
                <p class="text-xs text-ink-muted">+{{ $activeGoalCount - $activeGoals->count() }} more active</p>
            @endif
        @endif
    </div>

    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold tracking-tight text-ink">Today's tasks</h2>
            <a href="{{ route('tasks.index') }}" wire:navigate class="text-sm font-medium text-teal hover:underline">View all</a>
        </div>

        @if ($todaysTasks->isEmpty())
            <div class="rounded-3xl border border-dashed border-border bg-surface p-6 text-center">
                <p class="text-ink-muted">Nothing due today — enjoy the breathing room.</p>
            </div>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($todaysTasks as $task)
                    <div wire:key="dash-task-{{ $task->id }}" class="flex items-center gap-3 rounded-2xl border border-border bg-surface p-4">
                        <button
                            type="button"
                            wire:click="toggleDone({{ $task->id }})"
                            aria-label="Mark as done"
                            class="flex size-6 shrink-0 cursor-pointer items-center justify-center rounded-full border-2 border-border text-transparent transition hover:border-teal"
                        >
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="size-3.5">
                                <path d="M20 6 9 17l-5-5" />
                            </svg>
                        </button>

                        <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $task->title }}</span>

                        <span class="shrink-0 text-xs text-ink-muted">{{ $task->due_date->format('M j') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
