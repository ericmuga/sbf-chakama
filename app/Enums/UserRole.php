<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum UserRole: string implements HasLabel
{
    case Developer = 'developer';
    case BusinessAnalyst = 'business_analyst';

    public function getLabel(): string
    {
        return match ($this) {
            self::Developer => 'Developer',
            self::BusinessAnalyst => 'Business Analyst',
        };
    }
}
