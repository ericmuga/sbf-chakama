<?php

namespace Tests\Feature\Chakama;

use App\Enums\ShareStatus;
use App\Filament\Resources\Chakama\Pages\ViewShareSubscription;
use App\Models\Finance\BankAccount;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
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

class AdminShareSubscriptionViewTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private Member $member;

    private ShareSubscription $subscription;

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

        $schedule = ShareBillingSchedule::create([
            'name' => 'Standard Land Share',
            'price_per_share' => 100000.00,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
        ]);

        $this->admin = User::factory()->create(['is_admin' => true]);
        $memberUser = User::factory()->create();
        $this->member = Member::factory()->for($memberUser)->create(['is_chakama' => true]);

        $this->subscription = ShareSubscription::create([
            'no' => 'SHR-000001',
            'member_id' => $this->member->id,
            'billing_schedule_id' => $schedule->id,
            'number_of_shares' => 2,
            'price_per_share' => 100000,
            'total_amount' => 200000,
            'amount_paid' => 50000,
            'status' => ShareStatus::PendingPayment,
            'is_first_share' => true,
            'is_nominee' => false,
            'subscribed_at' => today(),
            'next_billing_date' => today()->addMonth(),
            'number_series_code' => 'SHARE',
        ]);

        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('chakama'));
    }

    public function test_admin_can_view_share_subscription_detail_page(): void
    {
        Livewire::test(ViewShareSubscription::class, ['record' => $this->subscription->id])
            ->assertSuccessful()
            ->assertSeeText('SHR-000001')
            ->assertSeeText($this->member->name)
            ->assertSeeText('Standard Land Share');
    }

    public function test_view_page_shows_subscription_financial_details(): void
    {
        Livewire::test(ViewShareSubscription::class, ['record' => $this->subscription->id])
            ->assertSuccessful()
            ->assertSeeText('200,000')
            ->assertSeeText('50,000');
    }

    public function test_view_page_shows_payment_history(): void
    {
        $customer = Customer::factory()->create();
        $bank = BankAccount::factory()->create();

        CashReceipt::create([
            'no' => 'RCP-000001',
            'customer_id' => $customer->id,
            'bank_account_id' => $bank->id,
            'posting_date' => today(),
            'amount' => 50000,
            'description' => 'Share payment',
            'status' => 'posted',
            'share_subscription_id' => $this->subscription->id,
        ]);

        Livewire::test(ViewShareSubscription::class, ['record' => $this->subscription->id])
            ->assertSuccessful()
            ->assertSeeText('RCP-000001');
    }

    public function test_nominee_section_hidden_when_not_nominee(): void
    {
        Livewire::test(ViewShareSubscription::class, ['record' => $this->subscription->id])
            ->assertSuccessful()
            ->assertDontSeeText('Nominee Details');
    }
}
