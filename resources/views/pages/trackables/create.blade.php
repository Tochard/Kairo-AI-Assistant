<?php

use App\Enums\TrackableDirection;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Validate;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|string|max:20')]
    public string $unit = '';

    #[Validate('required|in:up_good,down_good')]
    public string $direction = 'up_good';

    #[Validate('nullable|numeric|min:0')]
    public ?string $target_value = null;

    #[Validate('nullable|exists:goals,id')]
    public ?int $goal_id = null;

    public function save(): void
    {
        $validated = $this->validate();

        if ($this->goal_id && ! auth()->user()->goals()->whereKey($this->goal_id)->exists()) {
            $this->addError('goal_id', 'That goal is not yours to link to.');

            return;
        }

        auth()->user()->trackables()->create($validated);

        $this->redirect(route('trackables.index'), navigate: true);
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
        <h1 class="text-2xl font-bold tracking-tight text-ink">New trackable</h1>
        <p class="mt-1 text-ink-muted">What do you want to keep an eye on?</p>
    </div>

    <form wire:submit="save" class="flex flex-col gap-5">
        <div>
            <label for="name" class="mb-1.5 block text-sm font-medium text-ink">Name</label>
            <input
                wire:model="name"
                id="name"
                type="text"
                placeholder="e.g. Savings, Gym minutes"
                autofocus
                class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
            @error('name')
                <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
            @enderror
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label for="unit" class="mb-1.5 block text-sm font-medium text-ink">Unit</label>
                <input
                    wire:model="unit"
                    id="unit"
                    type="text"
                    placeholder="e.g. ₦, kg, hours"
                    class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
                >
                @error('unit')
                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="target_value" class="mb-1.5 block text-sm font-medium text-ink">Target (optional)</label>
                <input
                    wire:model="target_value"
                    id="target_value"
                    type="number"
                    step="0.01"
                    min="0"
                    class="w-full rounded-xl border border-border bg-surface px-4 py-2.5 text-ink placeholder:text-ink-muted focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
                >
                @error('target_value')
                    <p class="mt-1.5 text-sm text-red-400">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div>
            <label for="direction" class="mb-1.5 block text-sm font-medium text-ink">Direction</label>
            <select
                wire:model="direction"
                id="direction"
                class="w-full cursor-pointer rounded-xl border border-border bg-surface px-4 py-2.5 text-ink focus:border-teal focus:ring-2 focus:ring-teal/30 focus:outline-none transition"
            >
                @foreach (TrackableDirection::cases() as $case)
                    <option value="{{ $case->value }}">{{ $case->label() }}</option>
                @endforeach
            </select>
            @error('direction')
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

        <div class="mt-2 flex items-center gap-3">
            <button
                type="submit"
                class="cursor-pointer rounded-xl bg-accent px-4 py-2.5 font-semibold text-accent-foreground shadow-lg shadow-teal/20 transition hover:brightness-110 hover:shadow-teal/40"
            >
                Save trackable
            </button>
            <a href="{{ route('trackables.index') }}" wire:navigate class="rounded-xl px-4 py-2.5 font-semibold text-ink-muted transition hover:bg-surface-muted hover:text-ink">
                Cancel
            </a>
        </div>
    </form>
</div>
