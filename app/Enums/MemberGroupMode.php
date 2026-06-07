<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum MemberGroupMode: string implements HasLabel
{
    case Include = 'include';
    case AllExcept = 'all_except';

    public function getLabel(): string
    {
        return match ($this) {
            self::Include => 'Include only listed members',
            self::AllExcept => 'All active Chakama members except listed',
        };
    }
}
