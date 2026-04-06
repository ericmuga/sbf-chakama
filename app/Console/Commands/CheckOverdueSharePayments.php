<?php

namespace App\Console\Commands;

use App\Enums\ShareStatus;
use App\Models\ShareSubscription;
use App\Notifications\SharePaymentOverdueNotification;
use App\Services\ShareService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('chakama:check-overdue')]
#[Description('Check and notify overdue share subscriptions')]
class CheckOverdueSharePayments extends Command
{
    public function handle(ShareService $shareService): int
    {
        $notifyCount = 0;
        $suspendCount = 0;

        // Notify members 30+ days overdue
        ShareSubscription::where('status', ShareStatus::PendingPayment->value)
            ->where('subscribed_at', '<=', now()->subDays(30))
            ->where('amount_paid', '<', DB::raw('total_amount'))
            ->with('member.user')
            ->get()
            ->each(function (ShareSubscription $sub) use (&$notifyCount): void {
                $user = $sub->member?->user;

                if ($user) {
                    $user->notify(new SharePaymentOverdueNotification($sub));
                    $this->line("Notified {$user->name} — outstanding KES {$sub->amount_outstanding}.");
                    $notifyCount++;
                }
            });

        // Suspend members 90+ days overdue
        ShareSubscription::where('status', ShareStatus::PendingPayment->value)
            ->where('subscribed_at', '<=', now()->subDays(90))
            ->get()
            ->each(function (ShareSubscription $sub) use ($shareService, &$suspendCount): void {
                $shareService->suspendSubscription($sub, 'Overdue 90+ days auto-suspension');
                $this->line("Suspended subscription {$sub->no} — overdue 90+ days.");
                $suspendCount++;
            });

        $this->info("Notified {$notifyCount} member(s). Suspended {$suspendCount} subscription(s).");

        return Command::SUCCESS;
    }
}
