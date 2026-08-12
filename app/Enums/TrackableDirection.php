<?php

namespace App\Enums;

enum TrackableDirection: string
{
    case UpGood = 'up_good';
    case DownGood = 'down_good';

    public function label(): string
    {
        return match ($this) {
            self::UpGood => 'Higher is better',
            self::DownGood => 'Lower is better',
        };
    }
}
