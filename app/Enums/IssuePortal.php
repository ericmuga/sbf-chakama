<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IssuePortal: string implements HasColor, HasLabel
{
    case Sbf = 'sbf';
    case Chakama = 'chakama';
    case Both = 'both';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sbf => 'SBF',
            self::Chakama => 'Chakama',
            self::Both => 'Both',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Sbf => 'info',
            self::Chakama => 'success',
            self::Both => 'gray',
        };
    }
}
