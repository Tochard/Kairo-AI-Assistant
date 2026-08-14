<?php

namespace App\Ai\Agents;

use App\Ai\Tools\CreateGoalTool;
use App\Ai\Tools\CreateTaskTool;
use App\Ai\Tools\CreateTrackableTool;
use App\Ai\Tools\DraftPlanTool;
use App\Ai\Tools\GetProgressSummaryTool;
use App\Ai\Tools\LogTrackableTool;
use App\Ai\Tools\RenegotiateCommitmentTool;
use App\Ai\Tools\UpdateTaskStatusTool;
use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Concerns\RemembersConversations;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Contracts\HasTools;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;
use Stringable;

#[Provider([Lab::Gemini, Lab::Groq])]
class Kairo implements Agent, Conversational, HasTools
{
    use Promptable, RemembersConversations;

    public function __construct(public User $user)
    {
        //
    }

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): Stringable|string
    {
        $name = $this->user->name;

        return <<<INSTRUCTIONS
        You are Kairo, a warm, competent AI personal assistant. Your tagline is "the right plan, at the right moment." You help {$name} turn messy goals into realistic plans, track progress on tasks and trackable metrics, and follow up without nagging.

        Personality:
        - Warm and a little playful — talk like a capable friend, not a corporate assistant. An occasional emoji is fine, don't overdo it.
        - Concise. Don't pad responses with filler.
        - Encouraging, but never sycophantic. If a plan sounds unrealistic given what you know about {$name} (an unrealistic deadline or amount, for example), push back gently and suggest an alternative rather than agreeing to everything.
        - Never guilt-trip over missed tasks or check-ins. Stay curious, not scolding — ask what got in the way.
        - When you take an action (create a goal, log a value, mark a task done), confirm it in one warm, natural sentence — not a robotic "Entry recorded successfully."

        Planning:
        - When the user is setting a real, substantive goal (not a quick one-off addition), use draft_plan to propose the goal, a short list of concrete tasks, and — only when it genuinely fits — a trackable metric and check-in cadence. draft_plan always pauses for the user's approval before anything is saved, so lay out the full proposal in your message and let them approve, ask for changes, or reject it.
        - Reserve create_goal for quick, low-stakes additions the user clearly doesn't want a full plan built around.
        - If the user rejects a drafted plan with feedback, redraft a new draft_plan proposal that addresses it rather than falling back to create_goal.
        - When the user wants to push back a deadline or change check-ins on a goal that already has a committed plan, use renegotiate_commitment rather than editing the goal directly — it also pauses for approval.
        - Accountability style: {$this->user->strictness_level->label()}. When gentle, keep any follow-up on missed check-ins soft and curious. When firm, be more direct about patterns of slipping deadlines while staying respectful. Either way, never hard-code a scolding script — if a goal's renegotiation count (shown below) is climbing, gently name the pattern in your own words before agreeing to renegotiate again, more directly so if the style is firm.

        {$this->contextSnapshot()}
        INSTRUCTIONS;
    }

    /**
     * A compact snapshot of the user's current goals, tasks, and trackables for grounding.
     */
    protected function contextSnapshot(): string
    {
        $today = Carbon::today();

        $activeGoals = $this->user->goals()
            ->where('status', GoalStatus::Active)
            ->with('plan.commitment')
            ->get(['id', 'title', 'target_date']);

        $dueTasks = $this->user->tasks()
            ->where('status', TaskStatus::Pending)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<=', $today->copy()->addDays(7))
            ->orderBy('due_date')
            ->get(['id', 'title', 'due_date']);

        $trackables = $this->user->trackables()->get(['id', 'name', 'unit']);

        $lines = [
            "Today's date: {$today->toFormattedDateString()}.",
            'When you reference an existing goal, task, or trackable in a tool call, use the numeric id shown below in brackets — never guess an id.',
        ];

        $lines[] = $activeGoals->isEmpty()
            ? 'The user has no active goals yet.'
            : "Active goals:\n".$activeGoals->map(function ($goal) {
                $line = "- [id: {$goal->id}] {$goal->title}";
                $line .= $goal->target_date ? ' (target: '.$goal->target_date->format('M j, Y').')' : '';

                $renegotiations = $goal->plan?->commitment->renegotiation_count ?? 0;

                if ($renegotiations > 0) {
                    $line .= " (renegotiated {$renegotiations}x)";
                }

                return $line;
            })->implode("\n");

        $lines[] = $dueTasks->isEmpty()
            ? 'No tasks due in the next 7 days.'
            : "Tasks due soon:\n".$dueTasks->map(
                fn ($task) => "- [id: {$task->id}] {$task->title} (due ".$task->due_date->format('M j').')'
            )->implode("\n");

        $lines[] = $trackables->isEmpty()
            ? 'The user has no trackables set up yet.'
            : "Trackables:\n".$trackables->map(fn ($t) => "- [id: {$t->id}] {$t->name} ({$t->unit})")->implode("\n");

        return implode("\n\n", $lines);
    }

    /**
     * Get the tools available to the agent.
     *
     * @return Tool[]
     */
    public function tools(): iterable
    {
        return [
            new DraftPlanTool($this->user),
            new RenegotiateCommitmentTool($this->user),
            new CreateGoalTool($this->user),
            new CreateTaskTool($this->user),
            new UpdateTaskStatusTool($this->user),
            new CreateTrackableTool($this->user),
            new LogTrackableTool($this->user),
            new GetProgressSummaryTool($this->user),
        ];
    }
}
