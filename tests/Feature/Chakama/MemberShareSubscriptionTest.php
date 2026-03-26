<?php

namespace Tests\Feature\Chakama;

use App\Enums\ShareBillingFrequency;
use App\Enums\ShareStatus;
use App\Filament\Member\Resources\Shares\Pages\ListMyShares;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\Member;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MemberShareSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Member $member;

    private ShareBillingSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        NumberSeries::factory()->create([
            'code' => 'SHARE',
            'prefix' => 'SHR-',
            'last_no' => 0,
            'length' => 6,
            'is_active' => true,
        ]);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $this->schedule = ShareBillingSchedule::create([
            'name' => 'Standard Land Share',
            'price_per_share' => 100000.00,
            'acres_per_share' => 10,
            'billing_frequency' => ShareBillingFrequency::Once,
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
        ]);

        $this->user = User::factory()->create();
        $this->member = Member::factory()->for($this->user)->create([
            'is_chakama' => true,
            'is_sbf' => true,
        ]);

        $this->actingAs($this->user);
        Filament::setCurrentPanel(Filament::getPanel('member'));
    }

    public function test_chakama_member_can_access_my_shares_page(): void
    {
        $this->get(route('filament.member.resources.shares.my-shares.index'))
            ->assertOk();
    }

    public function test_non_chakama_member_cannot_see_my_shares_page(): void
    {
        $user = User::factory()->create();
        Member::factory()->for($user)->create(['is_chakama' => false, 'is_sbf' => true]);

        $this->actingAs($user);

        $this->get(route('filament.member.resources.shares.my-shares.index'))
            ->assertForbidden();
    }

    public function test_member_can_subscribe_to_share_via_portal(): void
    {
        Livewire::test(ListMyShares::class)
            ->callAction('subscribeToShare', [
                'billing_schedule_id' => $this->schedule->id,
                'number_of_shares' => 1,
                'subscribed_at' => today()->toDateString(),
            ])
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('share_subscriptions', [
            'member_id' => $this->member->id,
            'billing_schedule_id' => $this->schedule->id,
            'number_of_shares' => 1,
            'status' => ShareStatus::PendingPayment->value,
        ]);
    }

    public function test_subscribe_action_requires_billing_schedule(): void
    {
        Livewire::test(ListMyShares::class)
            ->callAction('subscribeToShare', [
                'number_of_shares' => 1,
                'subscribed_at' => today()->toDateString(),
            ])
            ->assertHasActionErrors(['billing_schedule_id' => 'required']);
    }

    public function test_member_can_only_see_own_share_subscriptions(): void
    {
        $other = Member::factory()->create(['is_chakama' => true]);
        ShareSubscription::create([
            'no' => 'SHR-000099',
            'member_id' => $other->id,
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

        $this->get(route('filament.member.resources.shares.my-shares.index'))
            ->assertOk()
            ->assertDontSee('SHR-000099');
    }
}
