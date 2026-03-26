<?php

namespace App\Events;

use App\Models\Finance\CashReceipt;
use App\Models\ShareSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SharePaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public ShareSubscription $subscription,
        public CashReceipt $receipt,
    ) {}
}
