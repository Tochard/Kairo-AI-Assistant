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
        <flux:heading size="xl" class="font-display">New task</flux:heading>
        <flux:text class="mt-1 text-ink-muted">What's the next small step?</flux:text>
    </div>

    <form wire:submit="save" class="flex flex-col gap-4">
        <flux:field>
            <flux:label>Title</flux:label>
            <flux:input wire:model="title" autofocus />
            <flux:error name="title" />
        </flux:field>

        <flux:field>
            <flux:label>Due date (optional)</flux:label>
            <flux:input wire:model="due_date" type="date" />
            <flux:error name="due_date" />
        </flux:field>

        @if ($goals->isNotEmpty())
            <flux:field>
                <flux:label>Linked goal (optional)</flux:label>
                <flux:select wire:model="goal_id" placeholder="No goal">
                    @foreach ($goals as $goal)
                        <flux:select.option value="{{ $goal->id }}">{{ $goal->title }}</flux:select.option>
                    @endforeach
                </flux:select>
                <flux:error name="goal_id" />
            </flux:field>
        @endif

        <flux:field>
            <flux:label>Recurrence (optional)</flux:label>
            <flux:input wire:model="recurrence_rule" placeholder="e.g. daily, weekly" />
            <flux:error name="recurrence_rule" />
        </flux:field>

        <div class="mt-2 flex items-center gap-3">
            <flux:button type="submit" variant="primary">Save task</flux:button>
            <flux:button href="{{ route('tasks.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
        </div>
    </form>
</div>
