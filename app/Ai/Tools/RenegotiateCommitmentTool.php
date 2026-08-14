<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class RenegotiateCommitmentTool implements Approvable, Tool
{
    use InteractsWithApprovals;

    public function __construct(protected User $user)
    {
        //
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return "Renegotiate an existing committed plan for one of the user's goals — push back the target date and/or change the check-in cadence. Requires the user's approval before anything changes.";
    }

    /**
     * Execute the tool. Only runs after the user has approved the change.
     */
    public function handle(Request $request): Stringable|string
    {
        $goalId = (int) $request['goal_id'];

        $goal = $this->user->goals()->find($goalId);

        if (! $goal) {
            return "There's no goal with id {$goalId} for this user — double-check the id from the active goals list.";
        }

        $plan = $goal->plan;

        if (! $plan || ! $plan->commitment) {
            return "\"{$goal->title}\" doesn't have a committed plan to renegotiate yet.";
        }

        $commitment = $plan->commitment;

        $newTargetDate = null;

        if ($request->filled('new_target_date')) {
            try {
                $newTargetDate = Carbon::parse($request['new_target_date'])->toDateString();
            } catch (Throwable) {
                return "\"{$request['new_target_date']}\" isn't a date I can understand — ask the user to clarify or give it in YYYY-MM-DD format.";
            }
        }

        $newCadence = $request->filled('new_cadence') ? $request['new_cadence'] : null;

        if ($newTargetDate === null && $newCadence === null) {
            return 'Nothing to renegotiate — give a new target date, a new check-in cadence, or both.';
        }

        if ($newTargetDate !== null) {
            $goal->update(['target_date' => $newTargetDate]);
            $commitment->end_date = Carbon::parse($newTargetDate);
        }

        $lockedPayload = $commitment->locked_payload ?? [];

        if ($newCadence !== null) {
            $lockedPayload['checkin_cadence'] = $newCadence;
        }

        if ($request->filled('reason')) {
            $lockedPayload['last_renegotiation_reason'] = $request['reason'];
        }

        $commitment->locked_payload = $lockedPayload;
        $commitment->renegotiation_count++;
        $commitment->save();

        $summary = "Renegotiated \"{$goal->title}\" (renegotiation #{$commitment->renegotiation_count})";
        $summary .= $newTargetDate ? ", new target {$newTargetDate}" : '';
        $summary .= $newCadence ? ", check-ins now {$newCadence}" : '';
        $summary .= '.';

        return $summary;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'goal_id' => $schema->integer()->required()->description("The id of the goal whose committed plan is being renegotiated — use the id shown in the user's active goals list, never guess."),
            'new_target_date' => $schema->string()->description('Optional new target date for the goal and commitment, ideally in YYYY-MM-DD format.'),
            'new_cadence' => $schema->string()->max(100)->description('Optional new check-in cadence, e.g. "daily", "weekly", "every Monday".'),
            'reason' => $schema->string()->description("Optional short note on why the plan is being renegotiated, from the user's own words."),
        ];
    }
}
