<?php

use App\Ai\Agents\Kairo;
use App\Models\Plan;
use App\Models\User;
use Laravel\Ai\Responses\Data\ToolCall;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

/**
 * Fakes a raw model tool call (rather than AgentResponse::fakeWithPendingApprovals(), which
 * doesn't round-trip arguments into the persisted tool_calls column) so the approval-pause
 * path runs for real against DraftPlanTool and the card has genuine arguments to read.
 *
 * @return array{0: Testable, 1: string}
 */
function startPendingDraftPlan(User $user, string $callId = 'call_draft_plan'): array
{
    Kairo::fake([
        new ToolCall(
            id: $callId,
            name: 'DraftPlanTool',
            arguments: [
                'goal_title' => 'Save ₦500k',
                'goal_description' => 'Emergency fund',
                'target_date' => '2026-12-31',
                'tasks' => [['title' => 'Open a savings account']],
                'checkin_cadence' => 'weekly',
            ],
        ),
    ]);

    $component = Livewire::actingAs($user)
        ->test('pages::chat')
        ->call('send', 'I want to save ₦500k by December');

    $conversationId = $component->conversationId;

    return [$component, $conversationId];
}

test('a plan proposal card renders when a draft plan is pending approval', function () {
    $user = User::factory()->create();

    [, $conversationId] = startPendingDraftPlan($user);

    $this->actingAs($user)
        ->get(route('chat', $conversationId))
        ->assertSee('Save ₦500k')
        ->assertSee('Plan proposal');
});

// Note: the AI SDK only executes a resumed tool call against the *real* gateway
// (Laravel\Ai\Providers\Concerns\ResumesToolApprovals::resumesAgainstRealGateway()
// deliberately skips execution when the agent is faked), so approve/edit can't be
// asserted end-to-end through DraftPlanTool's handle() here. What IS testable under
// a fake is that respondToApproval() builds the right Decision — approve as-is, edit
// with the submitted fields merged onto the original arguments. DraftPlanToolTest
// covers handle() actually committing those (possibly merged) arguments.
test('approving a pending draft plan sends an approve decision for the call', function () {
    $user = User::factory()->create();

    [$component] = startPendingDraftPlan($user);

    Kairo::fake(['Great, locked it in!']);

    $component->call('respondToApproval', 'call_draft_plan', 'approve', null, null)
        ->assertSet('error', null);

    Kairo::assertPrompted(function ($prompt) {
        return $prompt->hasApprovalDecisions()
            && $prompt->approvalDecisions->get('call_draft_plan')->isApproved();
    });
});

test('editing a pending draft plan sends an edit decision with the merged arguments', function () {
    $user = User::factory()->create();

    [$component] = startPendingDraftPlan($user);

    Kairo::fake(['Updated the plan for you.']);

    $component->call('respondToApproval', 'call_draft_plan', 'edit', [
        'goal_title' => 'Save ₦750k',
        'goal_description' => 'Emergency fund',
        'target_date' => '2027-01-31',
        'checkin_cadence' => 'weekly',
    ], null);

    Kairo::assertPrompted(function ($prompt) {
        if (! $prompt->hasApprovalDecisions()) {
            return false;
        }

        $decision = $prompt->approvalDecisions->get('call_draft_plan');

        return $decision->isEdited()
            && $decision->arguments['goal_title'] === 'Save ₦750k'
            && $decision->arguments['target_date'] === '2027-01-31'
            // Fields not covered by the edit form (tasks, in this case) survive the merge untouched.
            && $decision->arguments['tasks'][0]['title'] === 'Open a savings account';
    });
});

test('rejecting a pending draft plan creates nothing and forwards feedback to the model', function () {
    $user = User::factory()->create();

    [$component] = startPendingDraftPlan($user);

    Kairo::fake(['No problem, let me adjust that.']);

    $component->call('respondToApproval', 'call_draft_plan', 'reject', null, 'Push the target date back a month.');

    expect($user->goals()->count())->toBe(0);
    expect(Plan::count())->toBe(0);

    Kairo::assertPrompted(function ($prompt) {
        return $prompt->hasApprovalDecisions()
            && $prompt->approvalDecisions->get('call_draft_plan')->isRejected()
            && $prompt->approvalDecisions->get('call_draft_plan')->result === 'Push the target date back a month.';
    });
});

test('responding to an approval that no longer matches the pending call is a no-op', function () {
    $user = User::factory()->create();

    [$component] = startPendingDraftPlan($user);

    $component->call('respondToApproval', 'call_does_not_exist', 'approve', null, null)
        ->assertSet('error', "That plan isn't waiting for a decision anymore.");

    expect(Plan::count())->toBe(0);
});
