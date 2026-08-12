<?php

use App\Ai\Tools\CreateTrackableTool;
use App\Ai\Tools\GetProgressSummaryTool;
use App\Ai\Tools\LogTrackableTool;
use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Enums\TrackableDirection;
use App\Models\Goal;
use App\Models\Task;
use App\Models\Trackable;
use App\Models\User;
use Laravel\Ai\Tools\Request;

test('create trackable tool creates a trackable for the user', function () {
    $user = User::factory()->create();

    $result = (new CreateTrackableTool($user))->handle(new Request([
        'name' => 'Savings',
        'unit' => '₦',
        'direction' => 'up_good',
        'target_value' => 500000,
    ]));

    $trackable = $user->trackables()->first();

    expect($trackable)->not->toBeNull();
    expect($trackable->name)->toBe('Savings');
    expect($trackable->direction)->toBe(TrackableDirection::UpGood);
    expect((float) $trackable->target_value)->toBe(500000.0);
    expect((string) $result)->toContain('Savings')->toContain((string) $trackable->id);
});

test('create trackable tool defaults direction to up_good and rejects an invalid one', function () {
    $user = User::factory()->create();

    (new CreateTrackableTool($user))->handle(new Request(['name' => 'Gym minutes', 'unit' => 'minutes']));
    expect($user->trackables()->first()->direction)->toBe(TrackableDirection::UpGood);

    $result = (new CreateTrackableTool($user))->handle(new Request([
        'name' => 'Bad direction',
        'unit' => 'count',
        'direction' => 'sideways',
    ]));

    expect((string) $result)->toContain("isn't a valid direction");
    expect($user->trackables()->count())->toBe(1);
});

test('create trackable tool refuses to link to another users goal', function () {
    $user = User::factory()->create();
    $othersGoal = Goal::factory()->create();

    $result = (new CreateTrackableTool($user))->handle(new Request([
        'name' => 'Sneaky trackable',
        'unit' => 'count',
        'goal_id' => $othersGoal->id,
    ]));

    expect((string) $result)->toContain('no goal with id');
    expect($user->trackables()->count())->toBe(0);
});

test('log trackable tool logs a value by id', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create(['unit' => 'kg']);

    $result = (new LogTrackableTool($user))->handle(new Request([
        'trackable_id' => $trackable->id,
        'value' => 72.5,
        'note' => 'morning weigh-in',
    ]));

    expect($trackable->logs()->count())->toBe(1);
    expect((float) $trackable->logs()->first()->value)->toBe(72.5);
    expect((string) $result)->toContain('72.5')->toContain($trackable->name);
});

test('log trackable tool logs a value by exact name match', function () {
    $user = User::factory()->create();
    $trackable = Trackable::factory()->for($user)->create(['name' => 'Savings', 'unit' => '₦']);

    (new LogTrackableTool($user))->handle(new Request([
        'trackable_name' => 'savings',
        'value' => 5000,
    ]));

    expect($trackable->logs()->count())->toBe(1);
});

test('log trackable tool asks for clarification on an ambiguous name', function () {
    $user = User::factory()->create();
    Trackable::factory()->for($user)->create(['name' => 'Gym minutes']);
    Trackable::factory()->for($user)->create(['name' => 'Gym visits']);

    $result = (new LogTrackableTool($user))->handle(new Request([
        'trackable_name' => 'Gym',
        'value' => 30,
    ]));

    expect((string) $result)->toContain('More than one trackable matches');
});

test('log trackable tool cannot log against another users trackable', function () {
    $user = User::factory()->create();
    $othersTrackable = Trackable::factory()->create();

    $result = (new LogTrackableTool($user))->handle(new Request([
        'trackable_id' => $othersTrackable->id,
        'value' => 10,
    ]));

    expect((string) $result)->toContain('no trackable with id');
    expect($othersTrackable->logs()->count())->toBe(0);
});

test('get progress summary tool summarizes only the users own data', function () {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    Goal::factory()->for($user)->create(['status' => GoalStatus::Active]);
    Goal::factory()->for($otherUser)->create(['status' => GoalStatus::Active]);

    Task::factory()->for($user)->create(['status' => TaskStatus::Pending, 'due_date' => now()->subDay()]);

    $trackable = Trackable::factory()->for($user)->create(['name' => 'Savings', 'unit' => '₦', 'target_value' => 100]);
    $trackable->logs()->create(['value' => 40, 'logged_at' => now()]);

    $result = (string) (new GetProgressSummaryTool($user))->handle(new Request([]));

    expect($result)
        ->toContain('Active goals: 1')
        ->toContain('Overdue tasks: 1')
        ->toContain('Savings: 40')
        ->toContain('target 100');
});
