<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasLabel;

enum IssueStatus: string implements HasColor, HasLabel
{
    case Open = 'open';
    case PendingQaReview = 'pending_testing';
    case PendingRework = 'pending_rework';
    case ReworkedPendingTesting = 'reworked_pending_testing';
    case Closed = 'closed';

    public function getLabel(): string
    {
        return match ($this) {
            self::Open => 'Open',
            self::PendingQaReview => 'Pending QA Review',
            self::PendingRework => 'Pending Rework',
            self::ReworkedPendingTesting => 'Reworked — Pending Testing',
            self::Closed => 'Closed',
        };
    }

    public function getColor(): string
    {
        return match ($this) {
            self::Open => 'danger',
            self::PendingQaReview => 'info',
            self::PendingRework => 'warning',
            self::ReworkedPendingTesting => 'info',
            self::Closed => 'success',
        };
    }
}
