<?php

use App\Ai\Agents\Kairo;
use App\Ai\Tools\DraftPlanTool;
use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\Commitment;
use App\Models\Plan;
use App\Models\User;
use Laravel\Ai\Approvals\PendingApproval;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Tools\Request;

test('draft plan tool commits goal, tasks, trackable, plan, and commitment', function () {
    $user = User::factory()->create();

    $result = (new DraftPlanTool($user))->handle(new Request([
        'goal_title' => 'Save ₦500k',
        'goal_description' => 'Emergency fund',
        'target_date' => '2026-12-31',
        'tasks' => [
            ['title' => 'Open a savings account'],
            ['title' => 'Set up auto-transfer', 'due_date' => '2026-09-01'],
        ],
        'trackable_name' => 'Savings',
        'trackable_unit' => '₦',
        'trackable_direction' => 'up_good',
        'trackable_target_value' => 500000,
        'checkin_cadence' => 'weekly',
    ]));

    $goal = $user->goals()->first();
    expect($goal)->not->toBeNull();
    expect($goal->title)->toBe('Save ₦500k');
    expect($goal->status)->toBe(GoalStatus::Active);
    expect($goal->target_date->toDateString())->toBe('2026-12-31');

    expect($user->tasks()->count())->toBe(2);
    expect($user->tasks()->where('status', TaskStatus::Pending)->count())->toBe(2);

    $trackable = $user->trackables()->first();
    expect($trackable)->not->toBeNull();
    expect($trackable->name)->toBe('Savings');
    expect($trackable->goal_id)->toBe($goal->id);

    $plan = Plan::first();
    expect($plan)->not->toBeNull();
    expect($plan->user_id)->toBe($user->id);
    expect($plan->goal_id)->toBe($goal->id);
    expect($plan->status->value)->toBe('approved');

    $commitment = Commitment::first();
    expect($commitment)->not->toBeNull();
    expect($commitment->plan_id)->toBe($plan->id);
    expect($commitment->locked_payload['task_count'])->toBe(2);
    expect($commitment->locked_payload['checkin_cadence'])->toBe('weekly');
    expect($commitment->end_date->toDateString())->toBe('2026-12-31');

    expect((string) $result)->toContain('Save ₦500k')->toContain('2 tasks')->toContain('2026-12-31');
});

test('draft plan tool works with just a goal title and no tasks or trackable', function () {
    $user = User::factory()->create();

    $result = (new DraftPlanTool($user))->handle(new Request([
        'goal_title' => 'Read more',
    ]));

    $goal = $user->goals()->first();
    expect($goal)->not->toBeNull();
    expect($user->tasks()->count())->toBe(0);
    expect($user->trackables()->count())->toBe(0);
    expect(Plan::count())->toBe(1);
    expect(Commitment::count())->toBe(1);
    expect((string) $result)->toContain('Read more')->toContain('no tasks yet');
});

test('draft plan tool handles an unparseable target date gracefully', function () {
    $user = User::factory()->create();

    $result = (new DraftPlanTool($user))->handle(new Request([
        'goal_title' => 'Save money',
        'target_date' => 'not a date at all!!',
    ]));

    expect((string) $result)->toContain("isn't a date");
    expect($user->goals()->count())->toBe(0);
    expect(Plan::count())->toBe(0);
});

test('draft plan tool rejects a task with a blank title and commits nothing', function () {
    $user = User::factory()->create();

    $result = (new DraftPlanTool($user))->handle(new Request([
        'goal_title' => 'Save money',
        'tasks' => [
            ['title' => 'Fine'],
            ['title' => '  '],
        ],
    ]));

    expect((string) $result)->toContain('needs a title');
    expect($user->goals()->count())->toBe(0);
    expect(Plan::count())->toBe(0);
});

test('kairo pauses for approval when drafting a plan', function () {
    $user = User::factory()->create();

    Kairo::fake([
        AgentResponse::fakeWithPendingApprovals([
            new PendingApproval(
                id: 'call_draft_plan',
                tool: 'DraftPlanTool',
                arguments: ['goal_title' => 'Save ₦500k'],
                reason: null,
            ),
        ]),
    ]);

    $response = (new Kairo($user))->forUser($user)->prompt('I want to save ₦500k by December');

    expect($response->hasPendingApprovals())->toBeTrue();
    expect(Plan::count())->toBe(0);
});
