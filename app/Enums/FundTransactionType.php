<?php

namespace App\Enums;

enum FundTransactionType: string
{
    case SharePayment = 'share_payment';
    case Contribution = 'contribution';
    case Withdrawal = 'withdrawal';
    case ProjectAllocation = 'project_allocation';
    case Interest = 'interest';
    case Adjustment = 'adjustment';
    case Refund = 'refund';

    public function label(): string
    {
        return match ($this) {
            self::SharePayment => 'Share Payment',
            self::Contribution => 'Contribution',
            self::Withdrawal => 'Withdrawal',
            self::ProjectAllocation => 'Project Allocation',
            self::Interest => 'Interest',
            self::Adjustment => 'Adjustment',
            self::Refund => 'Refund',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::SharePayment => 'success',
            self::Contribution => 'success',
            self::Withdrawal => 'danger',
            self::ProjectAllocation => 'warning',
            self::Interest => 'info',
            self::Adjustment => 'gray',
            self::Refund => 'warning',
        };
    }
}
