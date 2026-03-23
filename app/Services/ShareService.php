<?php

namespace App\Services;

use App\Enums\ShareStatus;
use App\Models\Finance\NumberSeries;
use App\Models\Member;
use App\Models\ShareBillingSchedule;
use App\Models\ShareNominee;
use App\Models\ShareSubscription;
use Illuminate\Support\Facades\DB;

class ShareService
{
    public function subscribe(Member $member, array $data): ShareSubscription
    {
        return DB::transaction(function () use ($member, $data) {
            $no = NumberSeries::generate('SHARE');

            $schedule = ShareBillingSchedule::findOrFail($data['billing_schedule_id']);
            $pricePerShare = (float) $schedule->price_per_share;
            $numberOfShares = (int) $data['number_of_shares'];
            $totalAmount = $numberOfShares * $pricePerShare;

            $isFirstShare = ! ShareSubscription::where('member_id', $member->id)->exists();

            $nomineeId = null;
            if (! empty($data['is_nominee']) && $data['is_nominee']) {
                $nominee = ShareNominee::create(array_merge(
                    $data['nominee'] ?? [],
                    ['member_id' => $member->id]
                ));
                $nomineeId = $nominee->id;
            }

            return ShareSubscription::create([
                'no' => $no,
                'member_id' => $member->id,
                'billing_schedule_id' => $data['billing_schedule_id'],
                'number_of_shares' => $numberOfShares,
                'price_per_share' => $pricePerShare,
                'total_amount' => $totalAmount,
                'amount_paid' => 0,
                'status' => ShareStatus::PendingPayment,
                'is_first_share' => $isFirstShare,
                'is_nominee' => ! empty($data['is_nominee']) && $data['is_nominee'],
                'nominee_id' => $nomineeId,
                'subscribed_at' => $data['subscribed_at'] ?? today(),
                'next_billing_date' => $data['next_billing_date'] ?? today(),
                'number_series_code' => 'SHARE',
            ]);
        });
    }

    public function activateSubscription(ShareSubscription $subscription): void
    {
        $subscription->status = ShareStatus::Active;
        $subscription->save();
    }

    public function suspendSubscription(ShareSubscription $subscription, string $reason): void
    {
        $subscription->status = ShareStatus::Suspended;
        $subscription->save();
    }

    public function transferSubscription(ShareSubscription $sub, Member $newMember): void
    {
        DB::transaction(function () use ($sub, $newMember) {
            $sub->status = ShareStatus::Transferred;
            $sub->save();

            $no = NumberSeries::generate('SHARE');

            ShareSubscription::create([
                'no' => $no,
                'member_id' => $newMember->id,
                'billing_schedule_id' => $sub->billing_schedule_id,
                'number_of_shares' => $sub->number_of_shares,
                'price_per_share' => $sub->price_per_share,
                'total_amount' => $sub->total_amount,
                'amount_paid' => 0,
                'status' => ShareStatus::PendingPayment,
                'is_first_share' => false,
                'is_nominee' => false,
                'subscribed_at' => today(),
                'next_billing_date' => today(),
                'number_series_code' => 'SHARE',
            ]);
        });
    }

    public function getMemberShareSummary(Member $member): array
    {
        $subscriptions = ShareSubscription::where('member_id', $member->id)
            ->whereNotIn('status', [ShareStatus::Cancelled->value, ShareStatus::Transferred->value])
            ->get();

        $activeSubscriptions = $subscriptions->where('status', ShareStatus::Active->value);

        $totalShares = (int) $activeSubscriptions->sum('number_of_shares');
        $totalPaid = (float) $subscriptions->sum('amount_paid');
        $totalOutstanding = $subscriptions->sum(fn (ShareSubscription $sub) => $sub->amount_outstanding);

        $sharesByStatus = ShareSubscription::where('member_id', $member->id)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total_shares' => $totalShares,
            'total_acres' => $totalShares * 10,
            'total_paid' => $totalPaid,
            'total_outstanding' => (float) $totalOutstanding,
            'shares_by_status' => $sharesByStatus,
        ];
    }
}
