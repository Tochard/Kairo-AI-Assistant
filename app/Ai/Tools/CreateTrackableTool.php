<?php

namespace App\Ai\Tools;

use App\Enums\TrackableDirection;
use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class CreateTrackableTool implements Tool
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
        return 'Create a new trackable metric for the user (e.g. savings, gym minutes, water intake) so it can be logged over time.';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $direction = $request->filled('direction')
            ? TrackableDirection::tryFrom($request['direction'])
            : TrackableDirection::UpGood;

        if (! $direction) {
            return "\"{$request['direction']}\" isn't a valid direction — use either up_good or down_good.";
        }

        $goalId = null;

        if ($request->filled('goal_id')) {
            $goalId = (int) $request['goal_id'];

            if (! $this->user->goals()->whereKey($goalId)->exists()) {
                return "There's no goal with id {$goalId} for this user — double-check the id, or create the trackable without a linked goal.";
            }
        }

        $trackable = $this->user->trackables()->create([
            'name' => $request['name'],
            'unit' => $request['unit'],
            'direction' => $direction,
            'target_value' => $request->filled('target_value') ? $request['target_value'] : null,
            'goal_id' => $goalId,
        ]);

        return "Created trackable \"{$trackable->name}\" (id: {$trackable->id}), tracked in {$trackable->unit}.";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->required()->max(255)->description('A short name for the trackable, e.g. "Savings" or "Gym minutes".'),
            'unit' => $schema->string()->required()->max(20)->description('The unit of measurement, e.g. "₦", "kg", "hours", "count", "%".'),
            'direction' => $schema->string()->enum(['up_good', 'down_good'])->description('Whether higher values are better (up_good, the default) or lower values are better (down_good).'),
            'target_value' => $schema->number()->description('Optional target value to work toward.'),
            'goal_id' => $schema->integer()->description("Optional id of an existing goal to link this trackable to — use the id shown in the user's active goals list, never guess."),
        ];
    }
}
