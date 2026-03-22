<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'FirstName', 'MiddleName', 'LastName', 'TransactionType',
    'TransID', 'TransTime', 'BusinessShortCode', 'BillRefNumber',
    'InvoiceNumber', 'ThirdPartyTransID', 'MSISDN',
    'TransAmount', 'OrgAccountBalance', 'PaymentType',
    'checkout_request_id', 'merchant_request_id', 'result_code', 'result_desc',
    'is_claimed',
])]
class MpesaTransaction extends Model
{
    protected $table = 'mpesa_transactions';

    protected function casts(): array
    {
        return [
            'TransAmount' => 'decimal:2',
            'OrgAccountBalance' => 'decimal:2',
            'is_claimed' => 'boolean',
        ];
    }
}
