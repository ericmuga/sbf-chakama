<?php

namespace App\Events;

use App\Models\FundWithdrawal;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FundWithdrawalApproved
{
    use Dispatchable, SerializesModels;

    public function __construct(public FundWithdrawal $withdrawal) {}
}
