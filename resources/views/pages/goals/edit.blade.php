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
    <div>
        <flux:heading size="xl" class="font-display">Edit goal</flux:heading>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:field>
            <flux:label>Title</flux:label>
            <flux:input wire:model="title" autofocus />
            <flux:error name="title" />
        </flux:field>

        <flux:field>
            <flux:label>Description (optional)</flux:label>
            <flux:textarea wire:model="description" rows="3" />
            <flux:error name="description" />
        </flux:field>

        <flux:field>
            <flux:label>Target date (optional)</flux:label>
            <flux:input wire:model="target_date" type="date" />
            <flux:error name="target_date" />
        </flux:field>

        <flux:field>
            <flux:label>Status</flux:label>
            <flux:select wire:model="status">
                @foreach (GoalStatus::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="status" />
        </flux:field>

        <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary">Save changes</flux:button>
                <flux:button href="{{ route('goals.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
            </div>
            <flux:button
                variant="ghost"
                icon="trash"
                wire:click="delete"
                wire:confirm="Delete this goal? This can't be undone."
            >
                Delete
            </flux:button>
        </div>
    </form>
</div>
