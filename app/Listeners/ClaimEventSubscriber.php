<?php

namespace App\Listeners;

use App\Enums\ApprovalAction;
use App\Enums\EntityDimension;
use App\Events\ClaimApprovalActioned;
use App\Events\ClaimFullyApproved;
use App\Events\ClaimPaymentCreated;
use App\Events\ClaimRejected;
use App\Events\ClaimSubmitted;
use App\Events\MemberPaymentReceived;
use App\Models\User;
use App\Notifications\ClaimApprovalRequestNotification;
use App\Notifications\ClaimApprovedNotification;
use App\Notifications\ClaimPaymentNotification;
use App\Notifications\ClaimRejectedNotification;
use App\Notifications\ClaimSubmittedNotification;
use App\Notifications\PaymentReceivedNotification;
use App\Services\ClaimService;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class ClaimEventSubscriber
{
    public function handleClaimSubmitted(ClaimSubmitted $event): void
    {
        $claim = $event->claim->load(['approvals.approver', 'member']);

        // Notify SBF admins only (entity = null or 'sbf')
        User::where('is_admin', true)
            ->where(fn ($q) => $q->whereNull('entity')->orWhere('entity', EntityDimension::Sbf->value))
            ->each(fn (User $admin) => $admin->notify(new ClaimSubmittedNotification($claim)));

        // Notify first approver
        $firstApproval = $claim->approvals->sortBy('step_order')->first();
        if ($firstApproval?->approver) {
            $totalSteps = $claim->approvals->count();
            $firstApproval->approver->notify(
                new ClaimApprovalRequestNotification($claim, $firstApproval->step_order, $totalSteps)
            );
        }
    }

    public function handleClaimApprovalActioned(ClaimApprovalActioned $event): void
    {
        $claim = $event->claim->load(['approvals.approver', 'member.user']);
        $totalSteps = $claim->approvals->count();

        // Notify next approver if more steps remain
        $nextApproval = $claim->approvals
            ->where('action', ApprovalAction::Pending)
            ->sortBy('step_order')
            ->first();

        if ($nextApproval?->approver) {
            $nextApproval->approver->notify(
                new ClaimApprovalRequestNotification($claim, $nextApproval->step_order, $totalSteps)
            );
        }
    }

    public function handleClaimFullyApproved(ClaimFullyApproved $event): void
    {
        $claim = $event->claim->load('member.user');

        // Auto-create purchase document from the approved claim
        try {
            app(ClaimService::class)->convertToPurchase($claim->fresh());
        } catch (\Throwable $e) {
            Log::error("Failed to auto-create purchase for claim {$claim->no}: {$e->getMessage()}");
        }

        $memberUser = $claim->member?->user;

        if ($memberUser) {
            $memberUser->notify(new ClaimApprovedNotification($claim));
        }
    }

    public function handleClaimRejected(ClaimRejected $event): void
    {
        $claim = $event->claim->load('member.user');
        $memberUser = $claim->member?->user;

        if ($memberUser) {
            $memberUser->notify(new ClaimRejectedNotification($claim));
        }
    }

    public function handleClaimPaymentCreated(ClaimPaymentCreated $event): void
    {
        $claim = $event->claim->load('member.user');
        $memberUser = $claim->member?->user;

        if ($memberUser) {
            $memberUser->notify(new ClaimPaymentNotification($claim));
        }
    }

    public function handleMemberPaymentReceived(MemberPaymentReceived $event): void
    {
        $memberUser = $event->member->user;

        if ($memberUser) {
            $memberUser->notify(new PaymentReceivedNotification($event->receipt));
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ClaimSubmitted::class => 'handleClaimSubmitted',
            ClaimApprovalActioned::class => 'handleClaimApprovalActioned',
            ClaimFullyApproved::class => 'handleClaimFullyApproved',
            ClaimRejected::class => 'handleClaimRejected',
            ClaimPaymentCreated::class => 'handleClaimPaymentCreated',
            MemberPaymentReceived::class => 'handleMemberPaymentReceived',
        ];
    }
}
