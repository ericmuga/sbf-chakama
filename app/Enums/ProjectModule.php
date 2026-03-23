<?php

namespace App\Enums;

enum ProjectModule: string
{
    case Sbf = 'sbf';
    case Chakama = 'chakama';

    public function label(): string
    {
        return match ($this) {
            self::Sbf => 'SOBA Benevolent Fund',
            self::Chakama => 'Chakama Ranch',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Sbf => 'primary',
            self::Chakama => 'success',
        };
    }
}
