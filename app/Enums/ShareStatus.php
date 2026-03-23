<?php

namespace App\Enums;

enum ShareStatus: string
{
    case Active = 'active';
    case PendingPayment = 'pending_payment';
    case Suspended = 'suspended';
    case Transferred = 'transferred';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Active',
            self::PendingPayment => 'Pending Payment',
            self::Suspended => 'Suspended',
            self::Transferred => 'Transferred',
            self::Cancelled => 'Cancelled',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::PendingPayment => 'warning',
            self::Suspended => 'danger',
            self::Transferred => 'info',
            self::Cancelled => 'gray',
        };
    }
}
