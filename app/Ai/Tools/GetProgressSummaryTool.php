<?php

namespace App\Ai\Tools;

use App\Enums\GoalStatus;
use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class GetProgressSummaryTool implements Tool
{
    public function __construct(protected User $user)
    {
        //
    }

    /**
     * Get the description of the tool's purpose.
     */
    public function description(): Stringable|string
    {
        return "Get a summary of the user's current progress: active/completed goals, overdue and completed tasks, and the latest value for each trackable. Use this when the user asks how they're doing.";
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $today = Carbon::today();

        $activeGoalCount = $this->user->goals()->where('status', GoalStatus::Active)->count();
        $completedGoalCount = $this->user->goals()->where('status', GoalStatus::Completed)->count();

        $overdueTaskCount = $this->user->tasks()
            ->where('status', TaskStatus::Pending)
            ->whereNotNull('due_date')
            ->whereDate('due_date', '<', $today)
            ->count();

        $doneTaskCount = $this->user->tasks()->where('status', TaskStatus::Done)->count();

        $trackables = $this->user->trackables()
            ->with(['logs' => fn ($query) => $query->latest('logged_at')->limit(1)])
            ->get();

        $lines = [
            "Active goals: {$activeGoalCount}, completed goals: {$completedGoalCount}.",
            "Overdue tasks: {$overdueTaskCount}, completed tasks (all time): {$doneTaskCount}.",
        ];

        if ($trackables->isEmpty()) {
            $lines[] = 'No trackables set up yet.';
        } else {
            $lines[] = 'Trackables: '.$trackables->map(function ($trackable) {
                $latest = $trackable->logs->first();

                if (! $latest) {
                    return "{$trackable->name} (no logs yet)";
                }

                $target = $trackable->target_value !== null ? " / target {$trackable->target_value}" : '';

                return "{$trackable->name}: {$latest->value} {$trackable->unit}{$target}";
            })->implode('; ').'.';
        }

        return implode("\n", $lines);
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [];
    }
}
