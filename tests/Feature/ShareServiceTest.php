<?php

namespace Tests\Feature;

use App\Enums\ShareBillingFrequency;
use App\Enums\ShareStatus;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\Member;
use App\Models\ShareBillingSchedule;
use App\Models\User;
use App\Services\ShareService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareServiceTest extends TestCase
{
    use RefreshDatabase;

    private ShareService $service;

    private User $user;

    private Member $member;

    private FundAccount $fund;

    private ShareBillingSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ShareService::class);

        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        NumberSeries::factory()->create([
            'code' => 'SHARE',
            'prefix' => 'SHR-',
            'last_no' => 0,
            'length' => 6,
            'is_active' => true,
        ]);

        NumberSeries::factory()->create([
            'code' => 'FUND',
            'prefix' => 'FUND-',
            'last_no' => 0,
            'length' => 4,
            'is_active' => true,
        ]);

        $this->fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Share Capital Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $this->schedule = ShareBillingSchedule::create([
            'name' => 'Standard Share Plan',
            'price_per_share' => 50000.00,
            'acres_per_share' => 10,
            'billing_frequency' => ShareBillingFrequency::Once,
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $this->fund->id,
        ]);

        $this->member = Member::factory()->create([
            'is_chakama' => true,
            'user_id' => $this->user->id,
        ]);
    }

    private function makeSubscriptionData(array $overrides = []): array
    {
        return array_merge([
            'billing_schedule_id' => $this->schedule->id,
            'number_of_shares' => 1,
            'subscribed_at' => today()->toDateString(),
        ], $overrides);
    }

    public function test_subscribe_generates_number_from_series(): void
    {
        $sub = $this->service->subscribe($this->member, $this->makeSubscriptionData());

        $this->assertStringStartsWith('SHR-', $sub->no);
    }

    public function test_first_share_is_auto_tagged(): void
    {
        $sub = $this->service->subscribe($this->member, $this->makeSubscriptionData());

        $this->assertTrue($sub->is_first_share);
    }

    public function test_second_share_is_not_first_share(): void
    {
        $this->service->subscribe($this->member, $this->makeSubscriptionData());
        $second = $this->service->subscribe($this->member, $this->makeSubscriptionData());

        $this->assertFalse($second->is_first_share);
    }

    public function test_subscribe_with_nominee_creates_nominee_record(): void
    {
        $data = $this->makeSubscriptionData([
            'is_nominee' => true,
            'nominee' => [
                'full_name' => 'Jane Doe',
                'national_id' => '12345678',
                'phone' => '0700000000',
                'relationship' => 'Spouse',
            ],
        ]);

        $sub = $this->service->subscribe($this->member, $data);

        $this->assertTrue($sub->is_nominee);
        $this->assertNotNull($sub->nominee_id);
        $this->assertDatabaseHas('share_nominees', [
            'national_id' => '12345678',
            'member_id' => $this->member->id,
        ]);
    }

    public function test_subscribe_calculates_total_amount_correctly(): void
    {
        $sub = $this->service->subscribe($this->member, $this->makeSubscriptionData([
            'number_of_shares' => 3,
        ]));

        $this->assertEquals(150000.00, (float) $sub->total_amount);
    }

    public function test_activate_sets_status_to_active(): void
    {
        $sub = $this->service->subscribe($this->member, $this->makeSubscriptionData());

        $this->assertEquals(ShareStatus::PendingPayment, $sub->status);

        $this->service->activateSubscription($sub);

        $this->assertEquals(ShareStatus::Active, $sub->fresh()->status);
    }

    public function test_suspend_sets_status_to_suspended(): void
    {
        $sub = $this->service->subscribe($this->member, $this->makeSubscriptionData());
        $this->service->activateSubscription($sub);

        $this->service->suspendSubscription($sub->fresh(), 'Non-payment');

        $this->assertEquals(ShareStatus::Suspended, $sub->fresh()->status);
    }

    public function test_transfer_creates_new_subscription_for_new_member(): void
    {
        $sub = $this->service->subscribe($this->member, $this->makeSubscriptionData());

        $newMember = Member::factory()->create(['is_chakama' => true]);

        $this->service->transferSubscription($sub, $newMember);

        $this->assertEquals(ShareStatus::Transferred, $sub->fresh()->status);

        $this->assertDatabaseHas('share_subscriptions', [
            'member_id' => $newMember->id,
            'billing_schedule_id' => $this->schedule->id,
            'status' => ShareStatus::PendingPayment->value,
        ]);
    }

    public function test_get_member_share_summary_returns_correct_totals(): void
    {
        $sub = $this->service->subscribe($this->member, $this->makeSubscriptionData([
            'number_of_shares' => 2,
        ]));
        $this->service->activateSubscription($sub);

        $summary = $this->service->getMemberShareSummary($this->member);

        $this->assertEquals(2, $summary['total_shares']);
        $this->assertEquals(20, $summary['total_acres']);
        $this->assertArrayHasKey('total_paid', $summary);
        $this->assertArrayHasKey('total_outstanding', $summary);
    }
}
