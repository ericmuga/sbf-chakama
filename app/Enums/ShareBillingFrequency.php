<?php

namespace App\Enums;

enum ShareBillingFrequency: string
{
    case Once = 'once';
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case Annually = 'annually';

    public function label(): string
    {
        return match ($this) {
            self::Once => 'One-Time',
            self::Monthly => 'Monthly',
            self::Quarterly => 'Quarterly',
            self::Annually => 'Annually',
        };
    }

    public function periodInDays(): int
    {
        return match ($this) {
            self::Once => 0,
            self::Monthly => 30,
            self::Quarterly => 90,
            self::Annually => 365,
        };
    }
}
