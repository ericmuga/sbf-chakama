<?php

namespace App\Services;

use App\Enums\ApprovalAction;
use App\Enums\ClaimStatus;
use App\Events\ClaimApprovalActioned;
use App\Events\ClaimFullyApproved;
use App\Events\ClaimRejected;
use App\Models\Claim;
use App\Models\ClaimApproval;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ClaimApprovalService
{
    public function approve(ClaimApproval $approval, User $approver, ?string $comments = null, ?string $approvedAmount = null): void
    {
        $this->validateApprover($approval, $approver);

        DB::transaction(function () use ($approval, $comments, $approvedAmount) {
            $approval->update([
                'action' => ApprovalAction::Approved,
                'comments' => $comments,
                'actioned_at' => now(),
            ]);

            $claim = $approval->claim->fresh(['approvals']);

            $requiredApprovals = $claim->approvals->filter(fn (ClaimApproval $a) => true); // all steps required
            $allApproved = $requiredApprovals->every(fn (ClaimApproval $a) => $a->action === ApprovalAction::Approved);

            if ($allApproved) {
                $claim->update([
                    'status' => ClaimStatus::Approved,
                    'approved_at' => now(),
                    'approved_amount' => $approvedAmount ?? $claim->claimed_amount,
                ]);

                ClaimFullyApproved::dispatch($claim);
            } else {
                // Advance to next pending step
                $nextApproval = $claim->approvals
                    ->where('action', ApprovalAction::Pending)
                    ->sortBy('step_order')
                    ->first();

                $claim->update([
                    'status' => ClaimStatus::UnderReview,
                    'current_step' => $nextApproval?->step_order ?? $claim->current_step,
                ]);

                ClaimApprovalActioned::dispatch($approval, $claim);
            }
        });
    }

    public function reject(ClaimApproval $approval, User $approver, string $reason): void
    {
        $this->validateApprover($approval, $approver);

        DB::transaction(function () use ($approval, $reason) {
            $approval->update([
                'action' => ApprovalAction::Rejected,
                'comments' => $reason,
                'actioned_at' => now(),
            ]);

            $claim = $approval->claim;
            $claim->update([
                'status' => ClaimStatus::Rejected,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            ClaimRejected::dispatch($claim);
        });
    }

    public function return(ClaimApproval $approval, User $approver, string $comments): void
    {
        $this->validateApprover($approval, $approver);

        DB::transaction(function () use ($approval, $comments) {
            $approval->update([
                'action' => ApprovalAction::Returned,
                'comments' => $comments,
                'actioned_at' => now(),
            ]);

            $claim = $approval->claim;

            // Reset all pending approvals so they can be re-submitted
            $claim->approvals()->where('action', ApprovalAction::Pending->value)->delete();

            $claim->update([
                'status' => ClaimStatus::Draft,
                'submitted_at' => null,
                'approval_template_id' => null,
                'current_step' => 0,
            ]);
        });
    }

    public function getNextApprover(Claim $claim): ?User
    {
        $nextApproval = $claim->approvals()
            ->where('step_order', $claim->current_step)
            ->where('action', ApprovalAction::Pending->value)
            ->with('approver')
            ->first();

        return $nextApproval?->approver;
    }

    private function validateApprover(ClaimApproval $approval, User $approver): void
    {
        if ($approval->action !== ApprovalAction::Pending) {
            throw new InvalidArgumentException('This approval step has already been actioned.');
        }

        if ($approval->approver_user_id !== $approver->id) {
            throw new InvalidArgumentException('You are not authorised to action this approval step.');
        }
    }
}
