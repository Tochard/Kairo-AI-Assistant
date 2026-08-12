<?php

use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected away from the dashboard', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('a user sees their own active goals and due tasks, not another users', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Goal::factory()->for($user)->create(['title' => 'My active goal', 'status' => GoalStatus::Active]);
    Goal::factory()->for($user)->create(['title' => 'My archived goal', 'status' => GoalStatus::Archived]);
    Goal::factory()->for($otherUser)->create(['title' => 'Someone elses goal', 'status' => GoalStatus::Active]);

    Task::factory()->for($user)->create(['title' => 'Due today', 'status' => TaskStatus::Pending, 'due_date' => now()]);
    Task::factory()->for($user)->create(['title' => 'Due next week', 'status' => TaskStatus::Pending, 'due_date' => now()->addWeek()]);
    Task::factory()->for($otherUser)->create(['title' => 'Someone elses task', 'status' => TaskStatus::Pending, 'due_date' => now()]);

    $response = $this->actingAs($user)->get(route('dashboard'));

    $response->assertSee('My active goal')
        ->assertDontSee('My archived goal')
        ->assertDontSee('Someone elses goal')
        ->assertSee('Due today')
        ->assertDontSee('Due next week')
        ->assertDontSee('Someone elses task');
});

test('a user can mark a due task done from the dashboard', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['status' => TaskStatus::Pending, 'due_date' => now()]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->call('toggleDone', $task->id);

    expect($task->fresh()->status)->toBe(TaskStatus::Done);
});
