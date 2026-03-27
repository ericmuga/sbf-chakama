<?php

namespace App\Enums;

enum EntityDimension: string
{
    case Chakama = 'chakama';
    case Sbf = 'sbf';

    public function label(): string
    {
        return match ($this) {
            self::Chakama => 'Chakama Ranch',
            self::Sbf => 'SOBA Benevolent Fund',
        };
    }
}
