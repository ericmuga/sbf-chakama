<?php

namespace App\Events;

use App\Models\Finance\CashReceipt;
use App\Models\Member;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MemberPaymentReceived
{
    use Dispatchable, SerializesModels;

    public function __construct(public CashReceipt $receipt, public Member $member) {}
}
