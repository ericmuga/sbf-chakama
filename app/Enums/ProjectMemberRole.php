<?php

namespace App\Enums;

enum ProjectMemberRole: string
{
    case Owner = 'owner';
    case Manager = 'manager';
    case Contributor = 'contributor';
    case Viewer = 'viewer';

    public function label(): string
    {
        return match ($this) {
            self::Owner => 'Owner',
            self::Manager => 'Manager',
            self::Contributor => 'Contributor',
            self::Viewer => 'Viewer',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Owner => 'danger',
            self::Manager => 'warning',
            self::Contributor => 'primary',
            self::Viewer => 'gray',
        };
    }
}
