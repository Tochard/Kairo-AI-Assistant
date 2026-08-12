<?php

use Livewire\Attributes\Layout;
use Livewire\Component;

new #[Layout('layouts::app')] class extends Component
{
    //
};
?>

<div>
    <flux:heading size="xl" class="font-display">Hey {{ auth()->user()->name }} 👋</flux:heading>
    <flux:text class="mt-1 text-ink-muted">Your dashboard is on its way — goals and tasks are coming up next.</flux:text>
</div>
