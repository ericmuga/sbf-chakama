<?php

namespace App\Console\Commands;

use App\Enums\ApprovalAction;
use App\Models\ClaimApproval;
use App\Notifications\ClaimApprovalRequestNotification;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('claims:remind-approvers')]
#[Description('Send reminder notifications to approvers with pending claim approvals over 48 hours old.')]
class RemindApprovers extends Command
{
    public function handle(): int
    {
        $pending = ClaimApproval::with(['approver', 'claim'])
            ->where('action', ApprovalAction::Pending)
            ->where('created_at', '<', now()->subHours(48))
            ->get();

        $count = 0;

        foreach ($pending as $approval) {
            if (! $approval->approver) {
                continue;
            }

            $claim = $approval->claim;
            $totalSteps = $claim->approvals()->count();
            $daysPending = (int) $approval->created_at->diffInDays(now());

            $approval->approver->notify(
                new ClaimApprovalRequestNotification($claim, $approval->step_order, $totalSteps)
            );

            $count++;

            $this->line("Reminded {$approval->approver->name} for claim {$claim->no} ({$daysPending} days pending).");
        }

        $this->info("Sent {$count} reminders.");

        return Command::SUCCESS;
    }
}
