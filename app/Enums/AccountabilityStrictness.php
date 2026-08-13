<?php

namespace App\Enums;

enum AccountabilityStrictness: string
{
    case Gentle = 'gentle';
    case Firm = 'firm';

    public function label(): string
    {
        return match ($this) {
            self::Gentle => 'Gentle',
            self::Firm => 'Firm',
        };
    }
}
