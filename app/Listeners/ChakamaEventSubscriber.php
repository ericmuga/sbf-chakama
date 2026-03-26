<?php

namespace App\Listeners;

use App\Events\FundWithdrawalApproved;
use App\Events\FundWithdrawalRejected;
use App\Events\FundWithdrawalSubmitted;
use App\Events\ShareActivated;
use App\Events\SharePaymentReceived;
use App\Events\ShareSubscribed;
use App\Notifications\FundWithdrawalApprovalRequest;
use App\Notifications\FundWithdrawalApprovedNotification;
use App\Notifications\FundWithdrawalRejectedNotification;
use App\Notifications\ShareInvoiceNotification;
use App\Notifications\SharePaymentConfirmation;
use Illuminate\Events\Dispatcher;
use Illuminate\Support\Facades\Log;

class ChakamaEventSubscriber
{
    public function handleShareSubscribed(ShareSubscribed $event): void
    {
        $subscription = $event->subscription->load('member.user');
        $memberUser = $subscription->member?->user;

        if ($memberUser) {
            $memberUser->notify(new ShareInvoiceNotification($subscription));
        }
    }

    public function handleSharePaymentReceived(SharePaymentReceived $event): void
    {
        $subscription = $event->subscription->load('member.user');
        $memberUser = $subscription->member?->user;

        if ($memberUser) {
            $memberUser->notify(new SharePaymentConfirmation($subscription, $event->receipt));
        }
    }

    public function handleShareActivated(ShareActivated $event): void
    {
        // Activation is typically triggered after a payment, so no separate notification needed.
        // Log for audit purposes.
        Log::info("Share subscription {$event->subscription->no} activated.");
    }

    public function handleFundWithdrawalSubmitted(FundWithdrawalSubmitted $event): void
    {
        $withdrawal = $event->withdrawal->load('approvals.approver');

        $firstApproval = $withdrawal->approvals->sortBy('step_order')->first();

        if ($firstApproval?->approver) {
            $totalSteps = $withdrawal->approvals->count();
            $firstApproval->approver->notify(
                new FundWithdrawalApprovalRequest($withdrawal, $firstApproval->step_order, $totalSteps)
            );
        }
    }

    public function handleFundWithdrawalApproved(FundWithdrawalApproved $event): void
    {
        $withdrawal = $event->withdrawal->load('submitter');

        if ($withdrawal->submitter) {
            $withdrawal->submitter->notify(new FundWithdrawalApprovedNotification($withdrawal));
        }
    }

    public function handleFundWithdrawalRejected(FundWithdrawalRejected $event): void
    {
        $withdrawal = $event->withdrawal->load('submitter');

        if ($withdrawal->submitter) {
            $withdrawal->submitter->notify(new FundWithdrawalRejectedNotification($withdrawal));
        }
    }

    public function subscribe(Dispatcher $events): array
    {
        return [
            ShareSubscribed::class => 'handleShareSubscribed',
            SharePaymentReceived::class => 'handleSharePaymentReceived',
            ShareActivated::class => 'handleShareActivated',
            FundWithdrawalSubmitted::class => 'handleFundWithdrawalSubmitted',
            FundWithdrawalApproved::class => 'handleFundWithdrawalApproved',
            FundWithdrawalRejected::class => 'handleFundWithdrawalRejected',
        ];
    }
}
