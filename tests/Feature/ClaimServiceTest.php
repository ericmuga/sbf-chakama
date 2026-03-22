<?php

namespace Tests\Feature;

use App\Enums\ClaimStatus;
use App\Enums\ClaimType;
use App\Models\Claim;
use App\Models\ClaimApprovalTemplate;
use App\Models\ClaimApprovalTemplateStep;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\Service;
use App\Models\Finance\VendorPostingGroup;
use App\Models\Member;
use App\Models\User;
use App\Services\ClaimService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class ClaimServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClaimService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClaimService::class);

        // Create a CLAIM number series so FK constraint passes
        NumberSeries::factory()->create(['code' => 'CLAIM', 'prefix' => 'CLM-', 'length' => 6]);
        Notification::fake();
    }

    public function test_creates_claim_with_fallback_number_when_no_series_configured(): void
    {
        $member = Member::factory()->create(['name' => 'Test Member']);

        $claim = $this->service->createClaim($member, [
            'claim_type' => ClaimType::Medical->value,
            'subject' => 'Test Claim',
            'claimed_amount' => 5000,
            'payee_name' => 'Test Member',
        ]);

        $this->assertInstanceOf(Claim::class, $claim);
        $this->assertStringStartsWith('CLM-', $claim->no);
        $this->assertEquals($member->id, $claim->member_id);
        $this->assertEquals(ClaimStatus::Draft, $claim->status);
    }

    public function test_creates_claim_populating_payee_from_member_when_not_provided(): void
    {
        $member = Member::factory()->create([
            'name' => 'Jane Doe',
            'bank_name' => 'KCB',
            'bank_account_no' => '123456789',
        ]);

        $claim = $this->service->createClaim($member, [
            'claim_type' => ClaimType::Medical->value,
            'subject' => 'Test',
            'claimed_amount' => 1000,
        ]);

        $this->assertEquals('Jane Doe', $claim->payee_name);
        $this->assertEquals('KCB', $claim->bank_name);
        $this->assertEquals('123456789', $claim->bank_account_no);
    }

    public function test_adds_line_to_draft_claim_and_calculates_amount(): void
    {
        $member = Member::factory()->create();
        $claim = Claim::factory()->draft()->create(['member_id' => $member->id]);

        $line = $this->service->addLine($claim, [
            'description' => 'Doctor visit',
            'quantity' => 2,
            'unit_amount' => 1500,
        ]);

        $this->assertEquals(3000.0, (float) $line->line_amount);
        $this->assertEquals(10, $line->line_no);
    }

    public function test_cannot_add_line_to_submitted_claim(): void
    {
        $member = Member::factory()->create();
        $claim = Claim::factory()->submitted()->create(['member_id' => $member->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->addLine($claim, [
            'description' => 'Test',
            'quantity' => 1,
            'unit_amount' => 100,
        ]);
    }

    public function test_submit_creates_approval_chain_from_template(): void
    {
        $user = User::factory()->create();
        $approver = User::factory()->create();
        $member = Member::factory()->for($user)->create();
        $claim = Claim::factory()->draft()->create([
            'member_id' => $member->id,
            'claim_type' => ClaimType::Medical->value,
        ]);

        $this->service->addLine($claim, ['description' => 'Test', 'quantity' => 1, 'unit_amount' => 1000]);

        $template = ClaimApprovalTemplate::factory()->create([
            'claim_type' => null,
            'is_default' => true,
            'is_active' => true,
        ]);

        ClaimApprovalTemplateStep::factory()->create([
            'template_id' => $template->id,
            'step_order' => 1,
            'approver_user_id' => $approver->id,
        ]);

        $this->service->submitClaim($claim, $user);

        $claim->refresh();

        $this->assertEquals(ClaimStatus::Submitted, $claim->status);
        $this->assertNotNull($claim->submitted_at);
        $this->assertEquals(1, $claim->approvals()->count());
        $this->assertEquals($template->id, $claim->approval_template_id);
    }

    public function test_submit_fails_when_no_lines(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->for($user)->create();
        $claim = Claim::factory()->draft()->create(['member_id' => $member->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->submitClaim($claim, $user);
    }

    public function test_cancel_works_on_draft_claim(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->for($user)->create();
        $claim = Claim::factory()->draft()->create(['member_id' => $member->id]);

        $this->service->cancelClaim($claim, $user, 'Changed my mind');

        $claim->refresh();

        $this->assertEquals(ClaimStatus::Cancelled, $claim->status);
        $this->assertEquals('Changed my mind', $claim->rejection_reason);
    }

    public function test_cancel_fails_on_approved_claim(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->for($user)->create();
        $claim = Claim::factory()->approved()->create(['member_id' => $member->id]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->cancelClaim($claim, $user, 'Too late');
    }

    public function test_convert_to_purchase_creates_po_with_correct_lines(): void
    {
        $user = User::factory()->create();
        $member = Member::factory()->for($user)->create([
            'name' => 'Jane Surgery',
            'bank_name' => 'Equity',
            'bank_account_no' => '9876543210',
        ]);

        VendorPostingGroup::factory()->create(['code' => 'MEMBER']);
        $pinv = NumberSeries::factory()->create(['code' => 'PINV', 'prefix' => 'PINV-', 'length' => 6]);
        $ppinv = NumberSeries::factory()->create(['code' => 'PPINV', 'prefix' => 'PPINV-', 'length' => 6]);
        $vendNos = NumberSeries::factory()->create(['code' => 'VEND', 'prefix' => 'VEND-', 'length' => 6]);
        PurchaseSetup::create(['invoice_nos' => $pinv->code, 'posted_invoice_nos' => $ppinv->code, 'vendor_nos' => $vendNos->code]);

        // Start as Draft so we can add lines, then manually set to Approved
        $claim = Claim::factory()->draft()->create([
            'member_id' => $member->id,
            'claimed_amount' => 20000,
            'approved_amount' => 18000,
        ]);

        $service = Service::factory()->create();
        $this->service->addLine($claim, ['description' => 'Op fee', 'quantity' => 1, 'unit_amount' => 12000, 'service_id' => $service->id]);
        $this->service->addLine($claim, ['description' => 'Pharmacy', 'quantity' => 2, 'unit_amount' => 3000, 'service_id' => $service->id]);

        $claim->update(['status' => ClaimStatus::Approved]);
        $claim->refresh();

        $purchaseHeader = $this->service->convertToPurchase($claim);

        $this->assertInstanceOf(PurchaseHeader::class, $purchaseHeader);
        $this->assertEquals($claim->id, $purchaseHeader->claim_id);
        $this->assertEquals(2, $purchaseHeader->purchaseLines()->count());

        $claim->refresh();
        $this->assertEquals(ClaimStatus::PurchaseCreated, $claim->status);
        $this->assertEquals($purchaseHeader->id, $claim->purchase_header_id);

        // Lines are correctly mirrored
        $lines = $purchaseHeader->purchaseLines()->orderBy('id')->get();
        $this->assertEquals('Op fee', $lines->first()->description);
        $this->assertEquals(6000.0, (float) $lines->last()->line_amount); // 2 × 3000
    }
}
