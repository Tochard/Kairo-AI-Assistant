<?php

namespace App\Ai\Tools;

use App\Enums\GoalStatus;
use App\Enums\PlanStatus;
use App\Enums\TaskStatus;
use App\Enums\TrackableDirection;
use App\Models\Commitment;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Laravel\Ai\Concerns\InteractsWithApprovals;
use Laravel\Ai\Contracts\Approvable;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class DraftPlanTool implements Approvable, Tool
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
        return "Propose a full plan for a substantive goal — the goal itself, an optional list of tasks, an optional trackable metric, and a check-in cadence. Requires the user's approval before anything is saved. Use this instead of create_goal whenever the user is setting a real goal worth breaking down, not for quick one-off additions.";
    }

    /**
     * Execute the tool. Only runs after the user has approved the draft.
     */
    public function handle(Request $request): Stringable|string
    {
        $targetDate = null;

        if ($request->filled('target_date')) {
            try {
                $targetDate = Carbon::parse($request['target_date'])->toDateString();
            } catch (Throwable) {
                return "\"{$request['target_date']}\" isn't a date I can understand — ask the user to clarify or give it in YYYY-MM-DD format.";
            }
        }

        $tasks = $request->filled('tasks') ? $request['tasks'] : [];

        foreach ($tasks as $taskData) {
            if (! isset($taskData['title']) || trim((string) $taskData['title']) === '') {
                return 'Every task in the plan needs a title.';
            }
        }

        $plan = DB::transaction(function () use ($request, $targetDate, $tasks) {
            $goal = $this->user->goals()->create([
                'title' => $request['goal_title'],
                'description' => $request->filled('goal_description') ? $request['goal_description'] : null,
                'target_date' => $targetDate,
                'status' => GoalStatus::Active,
            ]);

            foreach ($tasks as $taskData) {
                $taskDueDate = null;

                if (! empty($taskData['due_date'])) {
                    try {
                        $taskDueDate = Carbon::parse($taskData['due_date'])->toDateString();
                    } catch (Throwable) {
                        $taskDueDate = null;
                    }
                }

                $this->user->tasks()->create([
                    'title' => $taskData['title'],
                    'due_date' => $taskDueDate,
                    'goal_id' => $goal->id,
                    'status' => TaskStatus::Pending,
                ]);
            }

            $trackable = null;

            if ($request->filled('trackable_name') && $request->filled('trackable_unit')) {
                $direction = $request->filled('trackable_direction')
                    ? TrackableDirection::tryFrom($request['trackable_direction']) ?? TrackableDirection::UpGood
                    : TrackableDirection::UpGood;

                $trackable = $this->user->trackables()->create([
                    'name' => $request['trackable_name'],
                    'unit' => $request['trackable_unit'],
                    'direction' => $direction,
                    'target_value' => $request->filled('trackable_target_value') ? $request['trackable_target_value'] : null,
                    'goal_id' => $goal->id,
                ]);
            }

            $plan = Plan::create([
                'user_id' => $this->user->id,
                'goal_id' => $goal->id,
                'draft_payload' => $request->all(),
                'status' => PlanStatus::Approved,
                'approved_at' => now(),
            ]);

            Commitment::create([
                'plan_id' => $plan->id,
                'locked_payload' => [
                    'checkin_cadence' => $request->filled('checkin_cadence') ? $request['checkin_cadence'] : null,
                    'task_count' => count($tasks),
                    'trackable_id' => $trackable?->id,
                ],
                'start_date' => now()->toDateString(),
                'end_date' => $targetDate,
                'renegotiation_count' => 0,
            ]);

            return $plan->load('goal');
        });

        $taskCount = count($tasks);
        $summary = "Locked in the plan for \"{$plan->goal->title}\" (goal id: {$plan->goal->id})";
        $summary .= $taskCount > 0 ? " with {$taskCount} task".($taskCount === 1 ? '' : 's') : ' with no tasks yet';
        $summary .= $targetDate ? ", targeting {$targetDate}." : '.';

        return $summary;
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'goal_title' => $schema->string()->required()->max(255)->description('A short, clear title for the goal.'),
            'goal_description' => $schema->string()->description('Optional longer description of the goal.'),
            'target_date' => $schema->string()->description('Optional target date for the goal, ideally in YYYY-MM-DD format.'),
            'tasks' => $schema->array()->items($schema->object([
                'title' => $schema->string()->required()->max(255)->description('A short, clear task title.'),
                'due_date' => $schema->string()->description('Optional due date, ideally in YYYY-MM-DD format.'),
            ]))->max(10)->description('Optional list of concrete tasks that break the goal down into action steps.'),
            'trackable_name' => $schema->string()->max(255)->description('Optional name for a metric to track toward this goal, e.g. "Savings" or "Gym sessions". Only suggest one when it genuinely fits the goal.'),
            'trackable_unit' => $schema->string()->max(20)->description('Required alongside trackable_name — the unit of measurement, e.g. "₦", "kg", "sessions".'),
            'trackable_direction' => $schema->string()->enum(['up_good', 'down_good'])->description('Whether higher values are better (up_good, the default) or lower values are better (down_good).'),
            'trackable_target_value' => $schema->number()->description('Optional target value for the trackable.'),
            'checkin_cadence' => $schema->string()->max(100)->description('How often to check in on this goal, e.g. "daily", "weekly", "every Monday".'),
        ];
    }
}
