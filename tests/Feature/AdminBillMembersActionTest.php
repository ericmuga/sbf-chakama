<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Filament\Resources\Chakama\Pages\ListShareBillingSchedules;
use App\Jobs\ProcessShareBillingRunJob;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class AdminBillMembersActionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

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
            'billing_frequency' => 'once',
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
        ]);

        $this->admin = User::factory()->create([
            'is_admin' => true,
            'entity' => EntityDimension::Chakama,
        ]);

        $this->actingAs($this->admin);
        Filament::setCurrentPanel(Filament::getPanel('chakama'));
    }

    public function test_admin_can_create_a_draft_billing_run_for_future_date(): void
    {
        Queue::fake();

        $billingDate = today()->addDays(7);

        Livewire::test(ListShareBillingSchedules::class)
            ->callAction(
                TestAction::make('billMembers')->table($this->schedule),
                [
                    'title' => 'Future run',
                    'billing_date' => $billingDate->toDateString(),
                    'notify_members' => true,
                    'send_email' => false,
                    'run_now' => true,
                ]
            )
            ->assertHasNoActionErrors();

        $this->assertDatabaseHas('share_billing_runs', [
            'billing_schedule_id' => $this->schedule->id,
            'title' => 'Future run',
            'status' => 'draft',
            'send_email' => false,
        ]);

        Queue::assertNotPushed(ProcessShareBillingRunJob::class);
    }

    public function test_admin_can_run_billing_immediately_when_date_is_today(): void
    {
        Queue::fake();

        Livewire::test(ListShareBillingSchedules::class)
            ->callAction(
                TestAction::make('billMembers')->table($this->schedule),
                [
                    'title' => 'Immediate run',
                    'billing_date' => today()->toDateString(),
                    'notify_members' => true,
                    'send_email' => true,
                    'run_now' => true,
                ]
            )
            ->assertHasNoActionErrors();

        $run = ShareBillingRun::query()->where('title', 'Immediate run')->firstOrFail();

        $this->assertSame('draft', $run->status);
        Queue::assertPushed(ProcessShareBillingRunJob::class, fn ($job) => $job->shareBillingRunId === $run->id);
    }

    public function test_inactive_schedule_hides_bill_members_action(): void
    {
        $this->schedule->update(['is_active' => false]);

        Livewire::test(ListShareBillingSchedules::class)
            ->assertTableActionHidden('billMembers', $this->schedule);
    }
}
