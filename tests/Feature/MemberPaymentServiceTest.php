<?php

namespace Tests\Feature;

use App\Models\Finance\BankAccount;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Member;
use App\Services\MemberPaymentService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MemberPaymentServiceTest extends TestCase
{
    use RefreshDatabase;

    private MemberPaymentService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(MemberPaymentService::class);

        // Required by MemberPaymentService::ensureCustomerForMember
        CustomerPostingGroup::factory()->create(['code' => 'MEMBER']);
        Notification::fake();
    }

    public function test_creates_cash_receipt_for_member(): void
    {
        BankAccount::factory()->create();
        $member = Member::factory()->create(['name' => 'John Doe']);

        $receipt = $this->service->initiatePayment($member, 5000.0, 'SBF Subscription');

        $this->assertInstanceOf(CashReceipt::class, $receipt);
        $this->assertEquals(5000.0, (float) $receipt->amount);
        $this->assertEquals('Open', $receipt->status);
    }

    public function test_auto_creates_customer_if_member_has_none(): void
    {
        BankAccount::factory()->create();
        $member = Member::factory()->create(['name' => 'New Member', 'customer_no' => null]);

        $this->assertNull($member->customer_no);

        $this->service->initiatePayment($member, 1000.0, 'Payment');

        $member->refresh();

        $this->assertNotNull($member->customer_no);
    }

    public function test_uses_existing_customer_if_member_already_has_one(): void
    {
        BankAccount::factory()->create();

        $member = Member::factory()->create(['name' => 'Existing Member']);

        // Create customer on first payment
        $receipt1 = $this->service->initiatePayment($member, 1000.0, 'First payment');
        $member->refresh();
        $customerNo = $member->customer_no;

        // Second payment should use same customer
        $receipt2 = $this->service->initiatePayment($member, 2000.0, 'Second payment');
        $member->refresh();

        $this->assertEquals($customerNo, $member->customer_no);
        $this->assertEquals($receipt1->customer_id, $receipt2->customer_id);
    }

    public function test_get_member_statement_returns_empty_when_no_customer(): void
    {
        $member = Member::factory()->create(['customer_no' => null]);

        $statement = $this->service->getMemberStatement($member);

        $this->assertEmpty($statement);
    }

    public function test_get_member_statement_filters_by_date(): void
    {
        BankAccount::factory()->create();
        $member = Member::factory()->create(['name' => 'Statement Test']);

        $this->service->initiatePayment($member, 1000.0, 'Payment 1');
        $member->refresh();

        // Statement with date range that excludes today should be empty
        $statement = $this->service->getMemberStatement(
            $member,
            Carbon::now()->subYear(),
            Carbon::now()->subDays(10)
        );

        $this->assertEmpty($statement);
    }
}
