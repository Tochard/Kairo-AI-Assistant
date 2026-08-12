<?php

use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

test('a user can see their own tasks but not another users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Task::factory()->for($user)->create(['title' => 'My task']);
    Task::factory()->for($otherUser)->create(['title' => 'Not my task']);

    $this->actingAs($user)
        ->get(route('tasks.index'))
        ->assertSee('My task')
        ->assertDontSee('Not my task');
});

test('a user can create a task optionally linked to their own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.create')
        ->set('title', 'Log today\'s spending')
        ->set('due_date', '2026-12-01')
        ->set('goal_id', $goal->id)
        ->call('save')
        ->assertRedirect(route('tasks.index'));

    $this->assertDatabaseHas('tasks', [
        'user_id' => $user->id,
        'goal_id' => $goal->id,
        'title' => 'Log today\'s spending',
        'status' => TaskStatus::Pending->value,
    ]);
});

test('a task requires a title', function () {
    $user = User::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.create')
        ->set('title', '')
        ->call('save')
        ->assertHasErrors('title');
});

test('a user cannot link a task to another users goal', function () {
    $user = User::factory()->create();
    $otherUsersGoal = Goal::factory()->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.create')
        ->set('title', 'Sneaky task')
        ->set('goal_id', $otherUsersGoal->id)
        ->call('save')
        ->assertHasErrors('goal_id');

    $this->assertDatabaseMissing('tasks', ['title' => 'Sneaky task']);
});

test('a user can toggle a task done and back to pending', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.index')
        ->call('toggleDone', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Done);

    Livewire::actingAs($user)
        ->test('pages::tasks.index')
        ->call('toggleDone', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Pending);
});

test('a user can update their own task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.edit', ['task' => $task])
        ->set('title', 'Updated title')
        ->set('status', TaskStatus::Missed->value)
        ->call('save')
        ->assertRedirect(route('tasks.index'));

    expect($task->fresh()->title)->toBe('Updated title');
    expect($task->fresh()->status)->toBe(TaskStatus::Missed);
});

test('a user cannot edit another users task', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.edit', ['task' => $task])
        ->assertForbidden();
});

test('a user can delete their own task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.index')
        ->call('delete', $task->id);

    $this->assertModelMissing($task);
});

test('a user cannot delete another users task', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $task = Task::factory()->for($otherUser)->create();

    Livewire::actingAs($user)
        ->test('pages::tasks.index')
        ->call('delete', $task->id)
        ->assertForbidden();

    $this->assertModelExists($task);
});
