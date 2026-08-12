<?php

namespace App\Enums;

enum GoalStatus: string
{
    case Active = 'active';
    case Completed = 'completed';
    case Archived = 'archived';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::Completed => 'Completed',
            self::Archived => 'Archived',
        };
    }

    /**
     * Tailwind classes for a status pill — hand-styled, no Flux badge component.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Active => 'bg-teal/10 text-teal border border-teal/20',
            self::Completed => 'bg-violet-500/10 text-violet-500 dark:text-violet-400 border border-violet-500/20',
            self::Archived => 'bg-zinc-500/10 text-zinc-500 dark:text-zinc-400 border border-zinc-500/20',
        };
    }
}
