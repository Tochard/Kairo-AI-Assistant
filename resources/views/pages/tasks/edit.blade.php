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
    <flux:heading size="xl" class="font-display">Edit task</flux:heading>

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

        <flux:field>
            <flux:label>Status</flux:label>
            <flux:select wire:model="status">
                @foreach (TaskStatus::cases() as $case)
                    <flux:select.option value="{{ $case->value }}">{{ $case->label() }}</flux:select.option>
                @endforeach
            </flux:select>
            <flux:error name="status" />
        </flux:field>

        <div class="mt-2 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <flux:button type="submit" variant="primary">Save changes</flux:button>
                <flux:button href="{{ route('tasks.index') }}" wire:navigate variant="ghost">Cancel</flux:button>
            </div>
            <flux:button
                variant="ghost"
                icon="trash"
                wire:click="delete"
                wire:confirm="Delete this task? This can't be undone."
            >
                Delete
            </flux:button>
        </div>
    </form>
</div>
