<?php

namespace App\Services;

use App\Models\Finance\CashReceipt;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\ShareSubscription;
use Illuminate\Support\Facades\DB;

class ShareBillingService
{
    public function __construct(private ShareService $shareService) {}

    public function generateInvoice(ShareSubscription $subscription): SalesHeader
    {
        return DB::transaction(function () use ($subscription) {
            $member = $subscription->member;
            $customer = $member->financeCustomer;

            $header = SalesHeader::create([
                'customer_id' => $customer?->id,
                'posting_date' => today(),
                'status' => 'Open',
                'share_subscription_id' => $subscription->id,
            ]);

            SalesLine::create([
                'sales_header_id' => $header->id,
                'description' => "Share subscription {$subscription->no} — {$subscription->number_of_shares} share(s)",
                'quantity' => $subscription->number_of_shares,
                'unit_price' => $subscription->price_per_share,
                'line_amount' => $subscription->total_amount,
                'service_id' => $subscription->billingSchedule?->service_id,
            ]);

            return $header;
        });
    }

    public function recordPayment(ShareSubscription $sub, CashReceipt $receipt): void
    {
        $sub->amount_paid = (float) $sub->amount_paid + (float) $receipt->amount;
        $sub->save();

        if ($sub->is_fully_paid) {
            $this->shareService->activateSubscription($sub);
        }
    }

    public function generateRecurringInvoices(): int
    {
        $subscriptions = ShareSubscription::active()
            ->whereHas('billingSchedule', fn ($q) => $q->where('billing_frequency', '!=', 'once'))
            ->where('next_billing_date', '<=', today())
            ->get();

        $count = 0;

        foreach ($subscriptions as $subscription) {
            $this->generateInvoice($subscription);

            $frequency = $subscription->billingSchedule?->billing_frequency;
            $days = $frequency?->periodInDays() ?? 0;

            if ($days > 0) {
                $subscription->next_billing_date = $subscription->next_billing_date->addDays($days);
                $subscription->save();
            }

            $count++;
        }

        return $count;
    }
}
