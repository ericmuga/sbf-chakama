<?php

namespace App\Events;

use App\Models\ProjectDirectCost;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class DirectCostSubmitted
{
    use Dispatchable, SerializesModels;

    public function __construct(public ProjectDirectCost $cost) {}
}
