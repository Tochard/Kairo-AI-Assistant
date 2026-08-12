<?php

use App\Enums\TaskStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|date')]
    public ?string $due_date = null;

    #[Validate('nullable|exists:goals,id')]
    public ?int $goal_id = null;

    #[Validate('nullable|string|max:255')]
    public ?string $recurrence_rule = null;

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->goal_id && ! auth()->user()->goals()->whereKey($this->goal_id)->exists()) {
            $this->addError('goal_id', 'That goal is not yours to link to.');

            return;
        }

        auth()->user()->tasks()->create([
            ...$validated,
            'status' => TaskStatus::Pending,
        ]);

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
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">New task</h1>
        <p class="mt-1 text-ink-muted">What's the next small step?</p>
    </div>

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

        <div class="mt-2 flex items-center gap-3">
            <button
                type="submit"
                class="cursor-pointer rounded-xl bg-accent px-4 py-2.5 font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
            >
                Save task
            </button>
            <a href="{{ route('tasks.index') }}" wire:navigate class="rounded-xl px-4 py-2.5 font-semibold text-ink-muted transition hover:bg-surface-muted hover:text-ink">
                Cancel
            </a>
        </div>
    </form>
</div>
