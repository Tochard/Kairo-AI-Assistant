<?php

use App\Enums\TaskStatus;
use App\Models\Task;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public Task $task;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|date')]
    public ?string $due_date = null;

    #[Validate('nullable|exists:goals,id')]
    public ?int $goal_id = null;

    #[Validate('nullable|string|max:255')]
    public ?string $recurrence_rule = null;

    #[Validate('required|in:pending,done,missed')]
    public string $status = '';

    public function mount(Task $task): void
    {
        $this->authorize('update', $task);

        $this->task = $task;
        $this->title = $task->title;
        $this->due_date = $task->due_date?->toDateString();
        $this->goal_id = $task->goal_id;
        $this->recurrence_rule = $task->recurrence_rule;
        $this->status = $task->status->value;
    }

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->goal_id && ! auth()->user()->goals()->whereKey($this->goal_id)->exists()) {
            $this->addError('goal_id', 'That goal is not yours to link to.');

            return;
        }

        $this->task->update($validated);

        $this->redirect(route('tasks.index'), navigate: true);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->task);

        $this->task->delete();

        $this->redirect(route('tasks.index'), navigate: true);
    }

    /**
     * @return array<string, mixed>
     */
    public function with(): array
    {
        return [
            'goals' => auth()->user()->goals()->orderBy('title')->get(),
        ];
    }
};
?>

<div class="flex flex-col gap-6">
    <h1 class="text-2xl font-bold tracking-tight text-ink">Edit task</h1>

    <form wire:submit="save" class="flex flex-col gap-5">
        <div>
            <label for="title" class="mb-1.5 block text-sm font-medium text-ink">Title</label>
            <input
                wire:model="title"
                id="title"
                type="text"
                autofocus
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('title')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="due_date" class="mb-1.5 block text-sm font-medium text-ink">Due date (optional)</label>
            <input
                wire:model="due_date"
                id="due_date"
                type="date"
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('due_date')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        @if ($goals->isNotEmpty())
            <div>
                <label for="goal_id" class="mb-1.5 block text-sm font-medium text-ink">Linked goal (optional)</label>
                <select
                    wire:model="goal_id"
                    id="goal_id"
                    class="w-full cursor-pointer rounded-xl border border-border bg-surface px-4 py-2.5 text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
                >
                    <option value="">No goal</option>
                    @foreach ($goals as $goal)
                        <option value="{{ $goal->id }}">{{ $goal->title }}</option>
                    @endforeach
                </select>
                @error('goal_id')
                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        @endif

        <div>
            <label for="recurrence_rule" class="mb-1.5 block text-sm font-medium text-ink">Recurrence (optional)</label>
            <input
                wire:model="recurrence_rule"
                id="recurrence_rule"
                type="text"
                placeholder="e.g. daily, weekly"
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('recurrence_rule')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="status" class="mb-1.5 block text-sm font-medium text-ink">Status</label>
            <select
                wire:model="status"
                id="status"
                class="w-full cursor-pointer rounded-xl border border-border bg-surface px-4 py-2.5 text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
                @foreach (TaskStatus::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('status')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <button
                    type="submit"
                    class="cursor-pointer rounded-xl bg-accent px-4 py-2.5 font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
                >
                    Save changes
                </button>
                <a href="{{ route('tasks.index') }}" wire:navigate class="rounded-xl px-4 py-2.5 font-semibold text-ink-muted transition hover:bg-surface-muted hover:text-ink">
                    Cancel
                </a>
            </div>
            <button
                type="button"
                wire:click="delete"
                wire:confirm="Delete this task? This can't be undone."
                class="flex cursor-pointer items-center gap-1.5 rounded-xl px-4 py-2.5 text-sm font-semibold text-ink-muted transition hover:bg-surface-muted hover:text-ink"
            >
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="size-4">
                    <path d="M4 7h16" />
                    <path d="M6 7l1 13a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2l1-13" />
                    <path d="M9 7V4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v3" />
                </svg>
                Delete
            </button>
        </div>
    </form>
</div>
