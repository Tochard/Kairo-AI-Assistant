<?php

namespace App\Ai\Tools;

use App\Models\User;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Tool;
use Laravel\Ai\Tools\Request;
use Stringable;

class LogTrackableTool implements Tool
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
        return 'Log a new value against one of the user\'s existing trackables, by id or by name (e.g. "log 5000 spent on transport").';
    }

    /**
     * Execute the tool.
     */
    public function handle(Request $request): Stringable|string
    {
        $trackable = null;

        if ($request->filled('trackable_id')) {
            $trackable = $this->user->trackables()->find((int) $request['trackable_id']);

            if (! $trackable) {
                return "There's no trackable with id {$request['trackable_id']} for this user — double-check the id.";
            }
        } elseif ($request->filled('trackable_name')) {
            $name = $request['trackable_name'];

            $matches = $this->user->trackables()
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->get();

            if ($matches->isEmpty()) {
                $matches = $this->user->trackables()
                    ->whereRaw('LOWER(name) LIKE ?', ['%'.mb_strtolower($name).'%'])
                    ->get();
            }

            if ($matches->isEmpty()) {
                return "There's no trackable called \"{$name}\" — ask the user if they'd like to create it first.";
            }

            if ($matches->count() > 1) {
                $options = $matches->map(fn ($t) => "\"{$t->name}\" (id: {$t->id})")->implode(', ');

                return "More than one trackable matches \"{$name}\": {$options}. Ask the user which one they mean, or use the id.";
            }

            $trackable = $matches->first();
        } else {
            return 'Provide either a trackable_id or a trackable_name to log against.';
        }

        $log = $trackable->logs()->create([
            'value' => $request['value'],
            'note' => $request->filled('note') ? $request['note'] : null,
            'logged_at' => now(),
        ]);

        return "Logged {$log->value} {$trackable->unit} against \"{$trackable->name}\".";
    }

    /**
     * Get the tool's schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'trackable_id' => $schema->integer()->description("The id of the trackable to log against, from the user's trackables list. Prefer this over trackable_name when the id is known."),
            'trackable_name' => $schema->string()->description('The name of the trackable to log against, if the id is not known.'),
            'value' => $schema->number()->required()->description('The value to log.'),
            'note' => $schema->string()->description('Optional short note about this log entry.'),
        ];
    }
}
