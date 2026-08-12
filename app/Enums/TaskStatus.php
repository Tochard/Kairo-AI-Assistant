<?php

namespace App\Enums;

enum TaskStatus: string
{
    case Pending = 'pending';
    case Done = 'done';
    case Missed = 'missed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Done => 'Done',
            self::Missed => 'Missed',
        };
    }

    /**
     * Tailwind classes for a status pill — hand-styled, no Flux badge component.
     */
    public function badgeClasses(): string
    {
        return match ($this) {
            self::Pending => 'bg-zinc-500/10 text-zinc-500 dark:text-zinc-400 border border-zinc-500/20',
            self::Done => 'bg-teal/10 text-teal border border-teal/20',
            // Muted, not alarming — Kairo never uses red for a routine miss.
            self::Missed => 'bg-zinc-500/10 text-zinc-500 dark:text-zinc-400 border border-zinc-500/20',
        };
    }
}
