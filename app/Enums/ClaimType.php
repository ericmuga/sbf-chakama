<?php

namespace App\Enums;

enum ClaimType: string
{
    case Medical = 'medical';
    case Funeral = 'funeral';
    case Education = 'education';
    case Emergency = 'emergency';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Medical => 'Medical',
            self::Funeral => 'Funeral',
            self::Education => 'Education',
            self::Emergency => 'Emergency',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Medical => 'info',
            self::Funeral => 'gray',
            self::Education => 'primary',
            self::Emergency => 'danger',
            self::Other => 'warning',
        };
    }
}
