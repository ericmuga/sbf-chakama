<?php

namespace App\Enums;

enum DirectCostStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Posted = 'posted';
    case Rejected = 'rejected';
    case Voided = 'voided';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Pending',
            self::Approved => 'Approved',
            self::Posted => 'Posted',
            self::Rejected => 'Rejected',
            self::Voided => 'Voided',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Approved => 'info',
            self::Posted => 'success',
            self::Rejected => 'danger',
            self::Voided => 'gray',
        };
    }
}
