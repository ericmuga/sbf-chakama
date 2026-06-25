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
use App\Models\MemberGroup;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\ShareSubscription;
use App\Models\User;
use App\Services\Finance\SalesPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShareBillingPerMemberTest extends TestCase
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

    public function test_each_member_is_billed_only_their_own_share_amount(): void
    {
        $memberA = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);
        $memberB = Member::factory()->create(['is_chakama' => true, 'member_status' => 'active']);

        $this->subscribe($memberA, shares: 2);
        $this->subscribe($memberB, shares: 5);

        $group = MemberGroup::create(['name' => 'Category A', 'mode' => 'include']);
        $group->members()->attach([$memberA->id, $memberB->id]);

        $run = ShareBillingRun::create([
            'title' => 'Allocation run',
            'billing_schedule_id' => $this->schedule->id,
            'member_group_id' => $group->id,
            'billing_date' => today(),
            'status' => 'draft',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => User::factory()->create(['is_admin' => true])->id,
        ]);

        (new ProcessShareBillingRunJob($run->id))->handle(app(SalesPostingService::class));

        // Each member's customer ledger must reflect only their own billable amount,
        // not the category total (2*1000 + 5*1000 = 7000).
        $this->assertSame(2000.0, $this->ledgerBalance($memberA), 'Member A should owe 2 shares × 1000.');
        $this->assertSame(5000.0, $this->ledgerBalance($memberB), 'Member B should owe 5 shares × 1000.');
    }

    private function subscribe(Member $member, int $shares): void
    {
        ShareSubscription::create([
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

    private function ledgerBalance(Member $member): float
    {
        $customer = Customer::where('no', $member->fresh()->customer_no)->first();

        return (float) CustomerLedgerEntry::where('customer_id', $customer->id)->sum('amount');
    }
}
