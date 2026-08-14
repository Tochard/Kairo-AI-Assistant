<?php

use App\Ai\Tools\RenegotiateCommitmentTool;
use App\Models\Commitment;
use App\Models\Goal;
use App\Models\Plan;
use App\Models\User;
use Laravel\Ai\Tools\Request;

test('renegotiate commitment tool updates the target date and cadence', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create(['target_date' => '2026-12-31']);
    $plan = Plan::factory()->for($user)->for($goal)->create();
    $commitment = Commitment::factory()->for($plan)->create([
        'end_date' => '2026-12-31',
        'renegotiation_count' => 0,
        'locked_payload' => ['checkin_cadence' => 'weekly'],
    ]);

    $result = (new RenegotiateCommitmentTool($user))->handle(new Request([
        'goal_id' => $goal->id,
        'new_target_date' => '2027-03-31',
        'new_cadence' => 'monthly',
        'reason' => 'Life got busy',
    ]));

    expect($goal->fresh()->target_date->toDateString())->toBe('2027-03-31');

    $commitment = $commitment->fresh();
    expect($commitment->end_date->toDateString())->toBe('2027-03-31');
    expect($commitment->renegotiation_count)->toBe(1);
    expect($commitment->locked_payload['checkin_cadence'])->toBe('monthly');
    expect($commitment->locked_payload['last_renegotiation_reason'])->toBe('Life got busy');

    expect((string) $result)->toContain($goal->title)->toContain('2027-03-31')->toContain('monthly');
});

test('renegotiate commitment tool refuses a goal that belongs to another user', function () {
    $user = User::factory()->create();
    $othersGoal = Goal::factory()->create();

    $result = (new RenegotiateCommitmentTool($user))->handle(new Request([
        'goal_id' => $othersGoal->id,
        'new_target_date' => '2027-01-01',
    ]));

    expect((string) $result)->toContain('no goal with id');
});

test('renegotiate commitment tool refuses a goal with no committed plan', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();

    $result = (new RenegotiateCommitmentTool($user))->handle(new Request([
        'goal_id' => $goal->id,
        'new_target_date' => '2027-01-01',
    ]));

    expect((string) $result)->toContain("doesn't have a committed plan");
});

test('renegotiate commitment tool handles an unparseable target date gracefully', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $plan = Plan::factory()->for($user)->for($goal)->create();
    Commitment::factory()->for($plan)->create(['renegotiation_count' => 0]);

    $result = (new RenegotiateCommitmentTool($user))->handle(new Request([
        'goal_id' => $goal->id,
        'new_target_date' => 'not a date at all!!',
    ]));

    expect((string) $result)->toContain("isn't a date");
});

test('renegotiate commitment tool requires at least one change', function () {
    $user = User::factory()->create();
    $goal = Goal::factory()->for($user)->create();
    $plan = Plan::factory()->for($user)->for($goal)->create();
    $commitment = Commitment::factory()->for($plan)->create(['renegotiation_count' => 0]);

    $result = (new RenegotiateCommitmentTool($user))->handle(new Request([
        'goal_id' => $goal->id,
    ]));

    expect((string) $result)->toContain('Nothing to renegotiate');
    expect($commitment->fresh()->renegotiation_count)->toBe(0);
});
