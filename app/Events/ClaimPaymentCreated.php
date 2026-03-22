<?php

namespace App\Events;

use App\Models\Claim;
use App\Models\Finance\PurchaseHeader;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClaimPaymentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(public Claim $claim, public PurchaseHeader $purchaseHeader) {}
}
