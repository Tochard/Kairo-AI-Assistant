<?php

use App\Ai\Tools\CreateGoalTool;
use App\Ai\Tools\CreateTaskTool;
use App\Ai\Tools\UpdateTaskStatusTool;
use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\Goal;
use App\Models\Task;
use App\Models\User;
use Laravel\Ai\Tools\Request;

test('create goal tool creates a goal for the user', function () {
    $user = User::factory()->create();

    $result = (new CreateGoalTool($user))->handle(new Request([
        'title' => 'Save ₦500k',
        'description' => 'Emergency fund',
        'target_date' => '2026-12-31',
    ]));

    $goal = $user->goals()->first();

    expect($goal)->not->toBeNull();
    expect($goal->title)->toBe('Save ₦500k');
    expect($goal->status)->toBe(GoalStatus::Active);
    expect($goal->target_date->toDateString())->toBe('2026-12-31');
    expect((string) $result)->toContain('Save ₦500k')->toContain((string) $goal->id);
});

test('create goal tool handles an unparseable date gracefully', function () {
    $user = User::factory()->create();

    $result = (new CreateGoalTool($user))->handle(new Request([
        'title' => 'Save money',
        'target_date' => 'not a date at all!!',
    ]));

    expect((string) $result)->toContain("isn't a date");
    expect($user->goals()->count())->toBe(0);
});

test('create task tool creates a task optionally linked to the users own goal', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    $result = (new CreateTaskTool($user))->handle(new Request([
        'title' => 'Log spending',
        'due_date' => '2026-12-01',
        'goal_id' => $goal->id,
    ]));

    $task = $user->tasks()->first();

    expect($task)->not->toBeNull();
    expect($task->title)->toBe('Log spending');
    expect($task->goal_id)->toBe($goal->id);
    expect($task->status)->toBe(TaskStatus::Pending);
    expect((string) $result)->toContain('Log spending');
});

test('create task tool refuses to link to another users goal', function () {
    $user = User::factory()->create();
    $othersGoal = Goal::factory()->create();

    $result = (new CreateTaskTool($user))->handle(new Request([
        'title' => 'Sneaky task',
        'goal_id' => $othersGoal->id,
    ]));

    expect((string) $result)->toContain('no goal with id');
    expect($user->tasks()->count())->toBe(0);
});

test('update task status tool updates the users own task', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['status' => TaskStatus::Pending]);

    $result = (new UpdateTaskStatusTool($user))->handle(new Request([
        'task_id' => $task->id,
        'status' => 'done',
    ]));

    expect($task->fresh()->status)->toBe(TaskStatus::Done);
    expect((string) $result)->toContain('Done');
});

test('update task status tool refuses to update another users task', function () {
    $user = User::factory()->create();
    $othersTask = Task::factory()->create(['status' => TaskStatus::Pending]);

    $result = (new UpdateTaskStatusTool($user))->handle(new Request([
        'task_id' => $othersTask->id,
        'status' => 'done',
    ]));

    expect((string) $result)->toContain('no task with id');
    expect($othersTask->fresh()->status)->toBe(TaskStatus::Pending);
});

test('update task status tool rejects an invalid status', function () {
    $user = User::factory()->create();
    $task = Task::factory()->for($user)->create(['status' => TaskStatus::Pending]);

    $result = (new UpdateTaskStatusTool($user))->handle(new Request([
        'task_id' => $task->id,
        'status' => 'archived',
    ]));

    expect((string) $result)->toContain("isn't a valid task status");
    expect($task->fresh()->status)->toBe(TaskStatus::Pending);
});
