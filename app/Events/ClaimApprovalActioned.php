<?php

namespace App\Events;

use App\Models\Claim;
use App\Models\ClaimApproval;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ClaimApprovalActioned
{
    use Dispatchable, SerializesModels;

    public function __construct(public ClaimApproval $approval, public Claim $claim) {}
}
