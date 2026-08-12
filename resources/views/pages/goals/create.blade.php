<?php

use App\Enums\GoalStatus;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $title = '';

    #[Validate('nullable|string|max:2000')]
    public ?string $description = null;

    #[Validate('nullable|date')]
    public ?string $target_date = null;

    public function save(): void
    {
        $validated = $this->validate();

        auth()->user()->goals()->create([
            ...$validated,
            'status' => GoalStatus::Active,
        ]);

        $this->redirect(route('goals.index'), navigate: true);
    }
};
?>

<div class="flex flex-col gap-6">
    <div>
        <h1 class="text-2xl font-bold tracking-tight text-ink">New goal</h1>
        <p class="mt-1 text-ink-muted">What are you working toward?</p>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        <div>
            <label for="title" class="mb-1.5 block text-sm font-medium text-ink">Title</label>
            <input
                wire:model="title"
                id="title"
                type="text"
                placeholder="e.g. Save ₦500k by December"
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

        <div class="mt-2 flex items-center gap-3">
            <button
                type="submit"
                class="cursor-pointer rounded-xl bg-accent px-4 py-2.5 font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
            >
                Save goal
            </button>
            <a href="{{ route('goals.index') }}" wire:navigate class="rounded-xl px-4 py-2.5 font-semibold text-ink-muted transition hover:bg-surface-muted hover:text-ink">
                Cancel
            </a>
        </div>
    </form>
</div>
