<?php

namespace App\Enums;

enum ClaimPaymentMethod: string
{
    case Mpesa = 'mpesa';
    case BankTransfer = 'bank_transfer';
    case Cheque = 'cheque';

    public function label(): string
    {
        return match ($this) {
            self::Mpesa => 'M-PESA',
            self::BankTransfer => 'Bank Transfer',
            self::Cheque => 'Cheque',
        };
    }
}
