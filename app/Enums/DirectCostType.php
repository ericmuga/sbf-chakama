<?php

namespace App\Enums;

enum DirectCostType: string
{
    case PettyCash = 'petty_cash';
    case MpesaPayment = 'mpesa_payment';
    case BankTransfer = 'bank_transfer';
    case CashWithdrawal = 'cash_withdrawal';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::PettyCash => 'Petty Cash',
            self::MpesaPayment => 'M-PESA Payment',
            self::BankTransfer => 'Bank Transfer',
            self::CashWithdrawal => 'Cash Withdrawal',
            self::Other => 'Other',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::PettyCash => 'warning',
            self::MpesaPayment => 'success',
            self::BankTransfer => 'primary',
            self::CashWithdrawal => 'info',
            self::Other => 'gray',
        };
    }
}
