<?php

namespace Tests\Feature;

use App\Enums\ApprovalAction;
use App\Enums\ClaimStatus;
use App\Models\Claim;
use App\Models\ClaimApproval;
use App\Models\Finance\NumberSeries;
use App\Models\Member;
use App\Models\User;
use App\Services\ClaimApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use InvalidArgumentException;
use Tests\TestCase;

class ClaimApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private ClaimApprovalService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ClaimApprovalService::class);

        NumberSeries::factory()->create(['code' => 'CLAIM', 'prefix' => 'CLM-', 'length' => 6]);
        Notification::fake();
    }

    private function makePendingApproval(Claim $claim, User $approver, int $stepOrder = 1): ClaimApproval
    {
        return ClaimApproval::create([
            'claim_id' => $claim->id,
            'step_order' => $stepOrder,
            'approver_user_id' => $approver->id,
            'action' => ApprovalAction::Pending,
            'due_by' => now()->addDays(3),
        ]);
    }

    public function test_approve_on_single_step_sets_claim_approved(): void
    {
        $approver = User::factory()->create();
        $member = Member::factory()->create();
        $claim = Claim::factory()->submitted()->create(['member_id' => $member->id]);

        $approval = $this->makePendingApproval($claim, $approver);

        $this->service->approve($approval, $approver);

        $claim->refresh();

        $this->assertEquals(ApprovalAction::Approved, $approval->fresh()->action);
        $this->assertEquals(ClaimStatus::Approved, $claim->status);
        $this->assertNotNull($claim->approved_at);
    }

    public function test_approve_advances_to_next_step(): void
    {
        $approver1 = User::factory()->create();
        $approver2 = User::factory()->create();
        $member = Member::factory()->create();
        $claim = Claim::factory()->submitted()->create([
            'member_id' => $member->id,
            'current_step' => 1,
        ]);

        $approval1 = $this->makePendingApproval($claim, $approver1, 1);
        $approval2 = $this->makePendingApproval($claim, $approver2, 2);

        $this->service->approve($approval1, $approver1);

        $claim->refresh();

        $this->assertEquals(ClaimStatus::UnderReview, $claim->status);
        $this->assertEquals(2, $claim->current_step);
    }

    public function test_reject_sets_claim_rejected_with_reason(): void
    {
        $approver = User::factory()->create();
        $member = Member::factory()->create();
        $claim = Claim::factory()->submitted()->create(['member_id' => $member->id]);

        $approval = $this->makePendingApproval($claim, $approver);

        $this->service->reject($approval, $approver, 'Not eligible.');

        $claim->refresh();

        $this->assertEquals(ClaimStatus::Rejected, $claim->status);
        $this->assertEquals('Not eligible.', $claim->rejection_reason);
        $this->assertNotNull($claim->rejected_at);
    }

    public function test_return_resets_claim_to_draft(): void
    {
        $approver = User::factory()->create();
        $member = Member::factory()->create();
        $claim = Claim::factory()->submitted()->create(['member_id' => $member->id]);

        $approval = $this->makePendingApproval($claim, $approver);

        $this->service->return($approval, $approver, 'Please provide more documents.');

        $claim->refresh();

        $this->assertEquals(ClaimStatus::Draft, $claim->status);
        $this->assertNull($claim->submitted_at);
        $this->assertEquals(0, $claim->current_step);
    }

    public function test_wrong_approver_throws_exception(): void
    {
        $correctApprover = User::factory()->create();
        $wrongApprover = User::factory()->create();
        $member = Member::factory()->create();
        $claim = Claim::factory()->submitted()->create(['member_id' => $member->id]);

        $approval = $this->makePendingApproval($claim, $correctApprover);

        $this->expectException(InvalidArgumentException::class);

        $this->service->approve($approval, $wrongApprover);
    }

    public function test_already_actioned_approval_throws_exception(): void
    {
        $approver = User::factory()->create();
        $member = Member::factory()->create();
        $claim = Claim::factory()->submitted()->create(['member_id' => $member->id]);

        $approval = ClaimApproval::create([
            'claim_id' => $claim->id,
            'step_order' => 1,
            'approver_user_id' => $approver->id,
            'action' => ApprovalAction::Approved,
            'due_by' => now()->addDays(3),
        ]);

        $this->expectException(InvalidArgumentException::class);

        $this->service->approve($approval, $approver);
    }
}
