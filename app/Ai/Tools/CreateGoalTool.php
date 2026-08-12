<?php

namespace App\Ai\Tools;

use App\Enums\GoalStatus;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Illuminate\Support\Carbon;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;
use Throwable;

class CreateGoalTool implements Tool
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
        return 'Create a new goal for the user. Use this when the user states something they want to achieve (e.g. "I want to save ₦500k by December").';
    }

    /**
     * Execute the tool.
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

        $goal = $this->user->goals()->create([
            'title' => $request['title'],
            'description' => $request->filled('description') ? $request['description'] : null,
            'target_date' => $targetDate,
            'status' => GoalStatus::Active,
        ]);

        return "Created goal \"{$goal->title}\" (id: {$goal->id}).";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'title' => $schema->string()->required()->max(255)->description('A short, clear title for the goal.'),
            'description' => $schema->string()->description('Optional extra detail about the goal.'),
            'target_date' => $schema->string()->description('Optional target date, ideally in YYYY-MM-DD format.'),
        ];
    }
}
