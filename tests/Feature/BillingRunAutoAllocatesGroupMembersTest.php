<?php

namespace Tests\Feature;

use App\Enums\MemberGroupMode;
use App\Enums\ShareStatus;
use App\Jobs\ProcessShareBillingRunJob;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\Service;
use App\Models\FundAccount;
use App\Models\Member;
use App\Models\MemberGroup;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BillingRunAutoAllocatesGroupMembersTest extends TestCase
{
    use RefreshDatabase;

    public function test_run_with_member_list_creates_missing_subscriptions(): void
    {
        NumberSeries::factory()->create(['code' => 'SHARE', 'prefix' => 'SHR-']);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $service = Service::factory()->create();

        $schedule = ShareBillingSchedule::create([
            'name' => 'Standard Land Share',
            'price_per_share' => 100000,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
            'service_id' => $service->id,
        ]);

        $listed = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $notListed = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);

        $group = MemberGroup::create([
            'name' => 'Pilot list',
            'mode' => MemberGroupMode::Include,
            'is_active' => true,
        ]);
        $group->members()->attach($listed->id);

        $admin = User::factory()->create(['is_admin' => true]);

        $run = ShareBillingRun::create([
            'title' => 'Auto-allocate run',
            'billing_schedule_id' => $schedule->id,
            'member_group_id' => $group->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);

        $this->assertSame(0, ShareSubscription::count());

        // Run the job synchronously. Posting will fail for missing posting groups,
        // which is fine — the auto-allocation step happens *before* posting.
        ProcessShareBillingRunJob::dispatch($run->id);

        $subscription = ShareSubscription::query()
            ->where('member_id', $listed->id)
            ->where('billing_schedule_id', $schedule->id)
            ->first();

        $this->assertNotNull($subscription, 'A subscription should have been auto-created for the listed member.');
        $this->assertSame(1, $subscription->number_of_shares);
        $this->assertEquals(ShareStatus::PendingPayment, $subscription->status);
        $this->assertTrue((bool) $subscription->is_first_share);

        $this->assertDatabaseMissing('share_subscriptions', [
            'member_id' => $notListed->id,
            'billing_schedule_id' => $schedule->id,
        ]);
    }

    public function test_run_without_member_list_does_not_auto_allocate(): void
    {
        NumberSeries::factory()->create(['code' => 'SHARE', 'prefix' => 'SHR-']);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $service = Service::factory()->create();

        $schedule = ShareBillingSchedule::create([
            'name' => 'Standard Land Share',
            'price_per_share' => 100000,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
            'service_id' => $service->id,
        ]);

        Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);

        $admin = User::factory()->create(['is_admin' => true]);

        $run = ShareBillingRun::create([
            'title' => 'Untargeted run',
            'billing_schedule_id' => $schedule->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);

        ProcessShareBillingRunJob::dispatch($run->id);

        $this->assertSame(0, ShareSubscription::count());
    }

    public function test_existing_subscription_is_not_duplicated(): void
    {
        NumberSeries::factory()->create(['code' => 'SHARE', 'prefix' => 'SHR-']);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $service = Service::factory()->create();

        $schedule = ShareBillingSchedule::create([
            'name' => 'Standard Land Share',
            'price_per_share' => 100000,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
            'service_id' => $service->id,
        ]);

        $listed = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);

        ShareSubscription::create([
            'no' => 'SHR-EXISTING',
            'member_id' => $listed->id,
            'billing_schedule_id' => $schedule->id,
            'number_of_shares' => 5,
            'price_per_share' => 100000,
            'total_amount' => 500000,
            'amount_paid' => 0,
            'status' => ShareStatus::PendingPayment,
            'is_first_share' => true,
            'subscribed_at' => today(),
            'next_billing_date' => today(),
            'number_series_code' => 'SHARE',
        ]);

        $group = MemberGroup::create([
            'name' => 'Pilot list',
            'mode' => MemberGroupMode::Include,
            'is_active' => true,
        ]);
        $group->members()->attach($listed->id);

        $admin = User::factory()->create(['is_admin' => true]);

        $run = ShareBillingRun::create([
            'title' => 'Re-bill',
            'billing_schedule_id' => $schedule->id,
            'member_group_id' => $group->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);

        ProcessShareBillingRunJob::dispatch($run->id);

        // No second subscription created for the same member+schedule
        $this->assertSame(
            1,
            ShareSubscription::query()
                ->where('member_id', $listed->id)
                ->where('billing_schedule_id', $schedule->id)
                ->count()
        );
    }
}
