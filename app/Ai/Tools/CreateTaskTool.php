<?php

namespace App\Ai\Tools;

use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CreateTaskTool implements Tool
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
        return 'Create a new task for the user, optionally linked to one of their existing goals by id.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $dueDate = null;

        if ($request->filled('due_date')) {
            try {
                $dueDate = Carbon::parse($request['due_date'])->toDateString();
            } catch (Throwable) {
                return "\"{$request['due_date']}\" isn't a date I can understand — ask the user to clarify or give it in YYYY-MM-DD format.";
            }
        }

        $goalId = null;

        if ($request->filled('goal_id')) {
            $goalId = (int) $request['goal_id'];

            if (! $this->user->goals()->whereKey($goalId)->exists()) {
                return "There's no goal with id {$goalId} for this user — double-check the id from the active goals list, or create the goal first.";
            }
        }

        $task = $this->user->tasks()->create([
            'title' => $request['title'],
            'due_date' => $dueDate,
            'goal_id' => $goalId,
            'recurrence_rule' => $request->filled('recurrence_rule') ? $request['recurrence_rule'] : null,
            'status' => TaskStatus::Pending,
        ]);

        return "Created task \"{$task->title}\" (id: {$task->id}).";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required()->max(255)->description('A short, clear title for the task.'),
            'due_date' => $schema->string()->description('Optional due date, ideally in YYYY-MM-DD format.'),
            'goal_id' => $schema->integer()->description("Optional id of an existing goal to link this task to — use the id shown in the user's active goals list, never guess."),
            'recurrence_rule' => $schema->string()->description('Optional simple recurrence description, e.g. "daily" or "weekly".'),
        ];
    }
}
