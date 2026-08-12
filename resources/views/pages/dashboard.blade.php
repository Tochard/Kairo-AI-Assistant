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
        ];
    }
};
?>

<div class="flex flex-col gap-8">
    <div>
        <flux:heading size="xl">Hey {{ auth()->user()->name }} 👋</flux:heading>
        <flux:text class="mt-1 text-ink-muted">Here's where things stand today.</flux:text>
    </div>

    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Active goals</flux:heading>
            <flux:link href="{{ route('goals.index') }}" wire:navigate>View all</flux:link>
        </div>

        @if ($activeGoals->isEmpty())
            <div class="rounded-3xl border border-dashed border-border bg-surface p-6 text-center">
                <flux:text class="text-ink-muted">No goals yet — tell me something you're working toward.</flux:text>
                <flux:button href="{{ route('goals.create') }}" wire:navigate variant="primary" size="sm" class="mt-3">
                    Start a goal
                </flux:button>
            </div>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($activeGoals as $goal)
                    <a
                        wire:key="dash-goal-{{ $goal->id }}"
                        href="{{ route('goals.edit', $goal) }}"
                        wire:navigate
                        class="flex items-center justify-between gap-4 rounded-2xl border border-border bg-surface p-4 hover:border-teal"
                    >
                        <span class="font-medium text-ink">{{ $goal->title }}</span>
                        @if ($goal->target_date)
                            <span class="shrink-0 text-xs text-ink-muted">{{ $goal->target_date->format('M j, Y') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            @if ($activeGoalCount > $activeGoals->count())
                <flux:text class="text-xs text-ink-muted">+{{ $activeGoalCount - $activeGoals->count() }} more active</flux:text>
            @endif
        @endif
    </div>

    <div class="flex flex-col gap-3">
        <div class="flex items-center justify-between">
            <flux:heading size="lg">Today's tasks</flux:heading>
            <flux:link href="{{ route('tasks.index') }}" wire:navigate>View all</flux:link>
        </div>

        @if ($todaysTasks->isEmpty())
            <div class="rounded-3xl border border-dashed border-border bg-surface p-6 text-center">
                <flux:text class="text-ink-muted">Nothing due today — enjoy the breathing room.</flux:text>
            </div>
        @else
            <div class="flex flex-col gap-3">
                @foreach ($todaysTasks as $task)
                    <div wire:key="dash-task-{{ $task->id }}" class="flex items-center gap-3 rounded-2xl border border-border bg-surface p-4">
                        <button
                            type="button"
                            wire:click="toggleDone({{ $task->id }})"
                            aria-label="Mark as done"
                            class="flex size-6 shrink-0 items-center justify-center rounded-full border-2 border-border text-transparent"
                        >
                            <flux:icon name="check" class="size-4" />
                        </button>

                        <span class="min-w-0 flex-1 truncate font-medium text-ink">{{ $task->title }}</span>

                        <span class="shrink-0 text-xs text-ink-muted">{{ $task->due_date->format('M j') }}</span>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
