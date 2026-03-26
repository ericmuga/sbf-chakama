<?php

namespace App\Events;

use App\Models\ShareSubscription;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ShareSubscribed
{
    use Dispatchable, SerializesModels;

    public function __construct(public ShareSubscription $subscription) {}
}
