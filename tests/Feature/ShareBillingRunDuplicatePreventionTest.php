<?php

namespace Tests\Feature;

use App\Jobs\ProcessShareBillingRunJob;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use App\Models\Finance\VendorPostingGroup;
use App\Models\FundAccount;
use App\Models\Member;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use App\Models\User;
use App\Services\Finance\SalesPostingService;
use App\Services\ShareBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareBillingRunDuplicatePreventionTest extends TestCase
{
    use RefreshDatabase;

    private ShareBillingSchedule $schedule;

    protected function setUp(): void
    {
        parent::setUp();

        foreach ([['MBR', 'MBR-'], ['CUST', 'CUST-'], ['VEND', 'VEND-'], ['INV', 'INV-'], ['SHARE', 'SHR-']] as [$code, $prefix]) {
            NumberSeries::create([
                'code' => $code, 'description' => $code, 'prefix' => $prefix,
                'last_no' => 0, 'length' => 6, 'is_manual_allowed' => false,
                'prevent_repeats' => true, 'is_active' => true,
            ]);
        }

        SalesSetup::create([
            'invoice_nos' => 'INV', 'posted_invoice_nos' => 'INV',
            'customer_nos' => 'CUST', 'member_nos' => 'MBR',
        ]);
        PurchaseSetup::create([
            'invoice_nos' => 'VEND', 'posted_invoice_nos' => 'VEND', 'vendor_nos' => 'VEND',
        ]);

        $cpg = CustomerPostingGroup::create([
            'code' => 'MEMBER', 'description' => 'Members', 'receivables_account_no' => '1100',
        ]);
        VendorPostingGroup::create([
            'code' => 'MEMBER', 'description' => 'Members', 'payables_account_no' => '2100',
        ]);

        $spg = ServicePostingGroup::factory()->create();
        $service = Service::factory()->create(['service_posting_group_id' => $spg->id]);
        GeneralPostingSetup::factory()->create([
            'customer_posting_group_id' => $cpg->id,
            'service_posting_group_id' => $spg->id,
            'sales_account_no' => '4000',
        ]);

        $fund = FundAccount::create([
            'no' => 'FUND-0001', 'name' => 'Share Fund', 'balance' => 0, 'is_active' => true,
        ]);

        $this->schedule = ShareBillingSchedule::create([
            'name' => 'Standard Share',
            'price_per_share' => 1000,
            'billing_frequency' => 'once',
            'is_active' => true,
            'service_id' => $service->id,
            'fund_account_id' => $fund->id,
        ]);
    }

    public function test_two_billing_runs_on_the_same_schedule_and_date_do_not_double_invoice_members(): void
    {
        $member = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $this->subscribe($member, shares: 3);

        $admin = User::factory()->create(['is_admin' => true]);

        $runA = $this->makeRun($admin);
        $runB = $this->makeRun($admin);

        (new ProcessShareBillingRunJob($runA->id))->handle(app(SalesPostingService::class));
        (new ProcessShareBillingRunJob($runB->id))->handle(app(SalesPostingService::class));

        // Member owes 3 shares x 1000 exactly once, not twice.
        $this->assertSame(3000.0, $this->ledgerBalance($member));
        $this->assertSame(1, $this->subscription($member)->invoices()->count());
    }

    public function test_billing_run_adopts_subscriptions_already_invoiced_by_another_mechanism_for_the_same_day(): void
    {
        $member = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $subscription = $this->subscribe($member, shares: 4);

        // Simulate the daily cron generator having already invoiced this subscription today.
        $existingInvoice = app(ShareBillingService::class)->generateInvoice($subscription->fresh());

        $run = $this->makeRun(User::factory()->create(['is_admin' => true]));
        (new ProcessShareBillingRunJob($run->id))->handle(app(SalesPostingService::class));

        // No double-posting: the ledger still reflects a single 4-share invoice.
        $this->assertSame(4000.0, $this->ledgerBalance($member));
        $this->assertSame(1, $subscription->invoices()->count());

        // The run reports the member and amount it covers, and adopts the invoice.
        $fresh = $run->fresh();
        $this->assertSame(1, $fresh->member_count);
        $this->assertSame(4000.0, (float) $fresh->total_invoiced);
        $this->assertSame($run->id, $existingInvoice->fresh()->share_billing_run_id);
        $this->assertSame(1, $run->invoices()->count());
    }

    private function makeRun(User $admin): ShareBillingRun
    {
        return ShareBillingRun::create([
            'title' => 'Billing run',
            'billing_schedule_id' => $this->schedule->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);
    }

    private function subscribe(Member $member, int $shares): ShareSubscription
    {
        return ShareSubscription::create([
            'no' => NumberSeries::generate('SHARE'),
            'member_id' => $member->id,
            'billing_schedule_id' => $this->schedule->id,
            'number_of_shares' => $shares,
            'price_per_share' => 1000,
            'total_amount' => $shares * 1000,
            'amount_paid' => 0,
            'status' => 'pending_payment',
            'is_first_share' => true,
            'is_nominee' => false,
            'subscribed_at' => today(),
            'next_billing_date' => today(),
            'number_series_code' => 'SHARE',
        ]);
    }

    private function subscription(Member $member): ShareSubscription
    {
        return ShareSubscription::where('member_id', $member->id)->firstOrFail();
    }

    private function ledgerBalance(Member $member): float
    {
        $customer = Customer::where('no', $member->fresh()->customer_no)->first();

        return (float) CustomerLedgerEntry::where('customer_id', $customer->id)->sum('amount');
    }
}
