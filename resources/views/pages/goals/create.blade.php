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
        <flux:heading size="xl">New goal</flux:heading>
        <flux:text class="mt-1 text-ink-muted">What are you working toward?</flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:field>
            <flux:label>Title</flux:label>
            <flux:input wire:model="title" placeholder="e.g. Save ₦500k by December" autofocus />
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

        <div class="mt-2 flex items-center gap-3">
            <flux:button type="submit" variant="primary">Save goal</flux:button>
            <flux:button href="{{ route('goals.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
