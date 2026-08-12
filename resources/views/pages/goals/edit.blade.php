<?php

use App\Enums\GoalStatus;
use App\Models\Goal;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    public Goal $goal;

    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public ?string $description = null;

    #[Validate('nullable|date')]
    public ?string $target_date = null;

    #[Validate('required|in:active,completed,archived')]
    public string $status = '';

    public function mount(Goal $goal): void
    {
        $this->authorize('update', $goal);

        $this->goal = $goal;
        $this->title = $goal->title;
        $this->description = $goal->description;
        $this->target_date = $goal->target_date?->toDateString();
        $this->status = $goal->status->value;
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->goal->update($validated);

        $this->redirect(route('goals.index'), navigate: true);
    }

    public function delete(): void
    {
        $this->authorize('delete', $this->goal);

        $this->goal->delete();

        $this->redirect(route('goals.index'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-6">
    <h1 class="text-2xl font-bold tracking-tight text-ink">Edit goal</h1>

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
            <label for="description" class="mb-1.5 block text-sm font-medium text-ink">Description (optional)</label>
            <textarea
                wire:model="description"
                id="description"
                rows="3"
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            ></textarea>
            @error('description')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="target_date" class="mb-1.5 block text-sm font-medium text-ink">Target date (optional)</label>
            <input
                wire:model="target_date"
                id="target_date"
                type="date"
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('target_date')
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
                @foreach (GoalStatus::cases() as $case)
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
                <a href="{{ route('goals.index') }}" wire:navigate class="rounded-xl px-4 py-2.5 font-semibold text-ink-muted transition hover:bg-surface-muted hover:text-ink">
                    Cancel
                </a>
            </div>
            <button
                type="button"
                wire:click="delete"
                wire:confirm="Delete this goal? This can't be undone."
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
