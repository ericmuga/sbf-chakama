<?php

namespace App\Exceptions;

use App\Enums\ProjectStatus;
use RuntimeException;

class InvalidStatusTransitionException extends RuntimeException
{
    public function __construct(ProjectStatus $from, ProjectStatus $to)
    {
        parent::__construct("Cannot transition project from {$from->label()} to {$to->label()}");
    }
}
