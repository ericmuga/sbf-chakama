<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum ReleaseStatus: string implements HasColor, HasLabel
{
    case Planned = 'planned';
    case InProgress = 'in_progress';
    case Released = 'released';

    public function getLabel(): string
    {
        return match ($this) {
            self::Planned => 'Planned',
            self::InProgress => 'In Progress',
            self::Released => 'Released',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Planned => 'gray',
            self::InProgress => 'warning',
            self::Released => 'success',
        };
    }
}
