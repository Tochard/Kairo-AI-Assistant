<?php

namespace App\Ai\Tools;

use App\Enums\TaskStatus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class UpdateTaskStatusTool implements Tool
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
        return "Update the status of one of the user's existing tasks (e.g. mark it done). Use the task id from the user's tasks list.";
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $task = $this->user->tasks()->find((int) $request['task_id']);

        if (! $task) {
            return "There's no task with id {$request['task_id']} for this user — double-check the id, or ask the user which task they mean.";
        }

        $status = TaskStatus::tryFrom($request['status']);

        if (! $status) {
            return "\"{$request['status']}\" isn't a valid task status — use one of: pending, done, missed.";
        }

        $task->update(['status' => $status]);

        return "Updated task \"{$task->title}\" to {$status->label()}.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'task_id' => $schema->integer()->required()->description("The id of the task to update, from the user's tasks list."),
            'status' => $schema->string()->required()->enum(['pending', 'done', 'missed'])->description('The new status for the task.'),
        ];
    }
}
