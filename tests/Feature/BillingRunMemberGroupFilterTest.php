<?php

namespace Tests\Feature;

use App\Enums\MemberGroupMode;
use App\Enums\ShareStatus;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingRunMemberGroupFilterTest extends TestCase
{
    use RefreshDatabase;

    private ShareBillingSchedule $schedule;

    private Member $memberInGroup;

    private Member $memberOutsideGroup;

    protected function setUp(): void
    {
        parent::setUp();

        NumberSeries::factory()->create(['code' => 'SHARE', 'prefix' => 'SHR-']);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $this->schedule = ShareBillingSchedule::create([
            'name' => 'Standard Land Share',
            'price_per_share' => 100000,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
        ]);

        $this->memberInGroup = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $this->memberOutsideGroup = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);

        foreach ([$this->memberInGroup, $this->memberOutsideGroup] as $member) {
            ShareSubscription::create([
                'no' => 'SHR-'.$member->id,
                'member_id' => $member->id,
                'billing_schedule_id' => $this->schedule->id,
                'number_of_shares' => 1,
                'price_per_share' => 100000,
                'total_amount' => 100000,
                'amount_paid' => 0,
                'status' => ShareStatus::PendingPayment,
                'is_first_share' => true,
                'subscribed_at' => today(),
                'next_billing_date' => today(),
                'number_series_code' => 'SHARE',
            ]);
        }
    }

    public function test_billing_run_with_group_targets_only_resolved_members(): void
    {
        $group = MemberGroup::create([
            'name' => 'Founders',
            'mode' => MemberGroupMode::Include,
            'is_active' => true,
        ]);
        $group->members()->attach($this->memberInGroup->id);

        $admin = User::factory()->create(['is_admin' => true]);

        $run = ShareBillingRun::create([
            'title' => 'Targeted run',
            'billing_schedule_id' => $this->schedule->id,
            'member_group_id' => $group->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);

        $run->refresh()->load('memberGroup');

        $eligibleSubscriptions = ShareSubscription::query()
            ->where('billing_schedule_id', $this->schedule->id)
            ->whereNotIn('status', ['cancelled', 'transferred'])
            ->when($run->memberGroup, fn ($q) => $q->whereIn('member_id', $run->memberGroup->resolveMemberIds()))
            ->pluck('member_id')
            ->all();

        $this->assertSame([$this->memberInGroup->id], $eligibleSubscriptions);
    }

    public function test_billing_run_without_group_targets_all_subscriptions(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $run = ShareBillingRun::create([
            'title' => 'Full run',
            'billing_schedule_id' => $this->schedule->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);

        $run->refresh()->load('memberGroup');

        $eligibleSubscriptions = ShareSubscription::query()
            ->where('billing_schedule_id', $this->schedule->id)
            ->whereNotIn('status', ['cancelled', 'transferred'])
            ->when($run->memberGroup, fn ($q) => $q->whereIn('member_id', $run->memberGroup->resolveMemberIds()))
            ->pluck('member_id')
            ->all();

        $this->assertEqualsCanonicalizing(
            [$this->memberInGroup->id, $this->memberOutsideGroup->id],
            $eligibleSubscriptions
        );
    }
}
