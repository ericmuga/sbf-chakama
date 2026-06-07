<?php

namespace Tests\Feature;

use App\Enums\ShareStatus;
use App\Jobs\ProcessShareBillingRunJob;
use App\Models\Finance\NumberSeries;
use App\Models\FundAccount;
use App\Models\Member;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use App\Models\User;
use App\Services\ShareBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareBillingScheduleServiceGuardTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_invoice_throws_when_schedule_has_no_service(): void
    {
        NumberSeries::factory()->create(['code' => 'SHARE', 'prefix' => 'SHR-']);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $schedule = ShareBillingSchedule::create([
            'name' => 'Missing Service Schedule',
            'price_per_share' => 1000,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => false,
            'is_active' => true,
            'fund_account_id' => $fund->id,
            'service_id' => null,
        ]);

        $member = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);

        $subscription = ShareSubscription::create([
            'no' => 'SHR-000001',
            'member_id' => $member->id,
            'billing_schedule_id' => $schedule->id,
            'number_of_shares' => 1,
            'price_per_share' => 1000,
            'total_amount' => 1000,
            'amount_paid' => 0,
            'status' => ShareStatus::PendingPayment,
            'is_first_share' => true,
            'subscribed_at' => today(),
            'next_billing_date' => today(),
            'number_series_code' => 'SHARE',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/no Service configured/');

        app(ShareBillingService::class)->generateInvoice($subscription);
    }

    public function test_billing_run_with_serviceless_schedule_marks_run_failed(): void
    {
        NumberSeries::factory()->create(['code' => 'SHARE', 'prefix' => 'SHR-']);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $schedule = ShareBillingSchedule::create([
            'name' => 'Bad Schedule',
            'price_per_share' => 1000,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => false,
            'is_active' => true,
            'fund_account_id' => $fund->id,
            'service_id' => null,
        ]);

        $admin = User::factory()->create(['is_admin' => true]);

        $run = ShareBillingRun::create([
            'title' => 'Bad run',
            'billing_schedule_id' => $schedule->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);

        ProcessShareBillingRunJob::dispatch($run->id);

        $run->refresh();

        $this->assertSame('failed', $run->status);
        $this->assertStringContainsString('no Service configured', $run->error_log);
    }
}
