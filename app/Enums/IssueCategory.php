<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum IssueCategory: string implements HasLabel
{
    case Development = 'development';
    case Functional = 'functional';

    public function getLabel(): string
    {
        return match ($this) {
            self::Development => 'Development',
            self::Functional => 'Functional',
        };
    }
}
