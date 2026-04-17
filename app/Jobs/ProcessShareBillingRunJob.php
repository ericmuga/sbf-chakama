<?php

namespace App\Jobs;

use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Member;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use App\Notifications\ShareBillingRunInvoiceNotification;
use App\Services\Finance\SalesPostingService;
use Filament\Notifications\Notification as FilamentNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessShareBillingRunJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $shareBillingRunId) {}

    public function handle(SalesPostingService $postingService): void
    {
        // Atomically transition from 'draft' to 'processing' to prevent duplicate runs
        $affected = ShareBillingRun::where('id', $this->shareBillingRunId)
            ->where('status', 'draft')
            ->update(['status' => 'processing']);

        if ($affected === 0) {
            Log::info("ShareBillingRun #{$this->shareBillingRunId} skipped — already processing or completed.");

            return;
        }

        $run = ShareBillingRun::with('billingSchedule')->findOrFail($this->shareBillingRunId);
        $schedule = $run->billingSchedule;

        // Get all active Chakama members who have a subscription (allocation) to this schedule
        $subscriptions = ShareSubscription::with('member.financeCustomer')
            ->where('billing_schedule_id', $schedule->id)
            ->whereNotIn('status', ['cancelled', 'transferred'])
            ->get();

        $totalInvoiced = 0.0;
        $memberCount = 0;
        $errors = [];

        foreach ($subscriptions as $subscription) {
            $member = $subscription->member;
            $customer = $member?->financeCustomer;

            if (! $customer) {
                $errors[] = "Member {$member?->no}: no linked customer record.";

                continue;
            }

            try {
                $lineAmount = (float) $subscription->number_of_shares * (float) $schedule->price_per_share;

                DB::transaction(function () use ($run, $schedule, $subscription, $customer, $postingService, $lineAmount, &$totalInvoiced, &$memberCount): void {
                    $customer->load('customerPostingGroup');

                    $header = SalesHeader::create([
                        'customer_id' => $customer->id,
                        'customer_posting_group_id' => $customer->customer_posting_group_id,
                        'document_type' => 'invoice',
                        'posting_date' => $run->billing_date,
                        'due_date' => $run->due_date ?? $run->billing_date->addDays(30),
                        'status' => 'open',
                        'share_subscription_id' => $subscription->id,
                        'share_billing_run_id' => $run->id,
                    ]);

                    SalesLine::create([
                        'sales_header_id' => $header->id,
                        'service_id' => $schedule->service_id,
                        'description' => "{$run->title} — {$subscription->number_of_shares} share(s)",
                        'quantity' => $subscription->number_of_shares,
                        'unit_price' => $schedule->price_per_share,
                        'line_amount' => $lineAmount,
                        'customer_posting_group_id' => $customer->customer_posting_group_id,
                    ]);

                    $header->load(['customer.customerPostingGroup', 'salesLines.service']);
                    $postingService->post($header);

                    $totalInvoiced += $lineAmount;
                    $memberCount++;
                });

                if ($run->notify_members && $member->user) {
                    $this->notifyMember($member, $run, $schedule, $subscription);
                }
            } catch (\Throwable $e) {
                $errors[] = "Member {$member?->no}: {$e->getMessage()}";
                Log::error("ShareBillingRun #{$run->id} failed for member {$member?->no}", ['error' => $e->getMessage()]);
            }
        }

        $run->update([
            'status' => empty($errors) ? 'completed' : ($memberCount > 0 ? 'completed' : 'failed'),
            'processed_at' => now(),
            'total_invoiced' => $totalInvoiced,
            'member_count' => $memberCount,
            'error_log' => empty($errors) ? null : implode("\n", $errors),
        ]);

        // Notify the admin who triggered it
        if ($run->createdBy) {
            FilamentNotification::make()
                ->title("Billing Run '{$run->title}' completed")
                ->body("{$memberCount} invoice(s) created — KES ".number_format($totalInvoiced, 2).($errors ? ' (with errors)' : ''))
                ->success()
                ->sendToDatabase($run->createdBy);
        }
    }

    private function notifyMember(
        Member $member,
        ShareBillingRun $run,
        ShareBillingSchedule $schedule,
        ShareSubscription $subscription
    ): void {
        if (! $member->user) {
            return;
        }

        $amount = (float) $subscription->number_of_shares * (float) $schedule->price_per_share;

        // In-app notification
        FilamentNotification::make()
            ->title('New Share Invoice: '.$run->title)
            ->body('Amount due: KES '.number_format($amount, 2).' — Due: '.($run->due_date ?? $run->billing_date)->format('d M Y'))
            ->info()
            ->sendToDatabase($member->user);

        // Email notification
        if ($run->send_email && $member->email) {
            try {
                $member->user->notify(new ShareBillingRunInvoiceNotification($run, $subscription, $amount));
            } catch (\Throwable $e) {
                Log::warning("Could not send billing run email to member {$member->no}: {$e->getMessage()}");
            }
        }
    }
}
