<?php

namespace App\Events;

use App\Models\ProjectDirectCost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectCostRejected
{
    use Dispatchable, SerializesModels;

    public function __construct(public ProjectDirectCost $cost) {}
}
