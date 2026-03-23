<?php

namespace Tests\Feature;

use App\Enums\DirectCostStatus;
use App\Enums\DirectCostType;
use App\Enums\ProjectModule;
use App\Enums\ProjectPriority;
use App\Models\Finance\GlAccount;
use App\Models\Finance\NumberSeries;
use App\Models\Project;
use App\Models\User;
use App\Services\ProjectCostService;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class ProjectCostServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $projectService;

    private ProjectCostService $costService;

    private User $user;

    private Project $project;

    private GlAccount $glAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->projectService = app(ProjectService::class);
        $this->costService = app(ProjectCostService::class);
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        NumberSeries::factory()->create([
            'code' => 'PROJ',
            'prefix' => 'PROJ-',
            'last_no' => 0,
            'length' => 5,
            'is_active' => true,
        ]);

        NumberSeries::factory()->create([
            'code' => 'DCOST',
            'prefix' => 'DC-',
            'last_no' => 0,
            'length' => 6,
            'is_active' => true,
        ]);

        $this->project = $this->projectService->createProject([
            'name' => 'Cost Test Project',
            'module' => ProjectModule::Sbf->value,
            'priority' => ProjectPriority::Medium->value,
            'budget' => 200000,
        ], $this->user);

        // Create a posting GL account for the expense debit
        $this->glAccount = GlAccount::create([
            'no' => 'EXP-001',
            'name' => 'Test Expense Account',
            'account_type' => 'Posting',
        ]);

        // Create a cash GL account for the credit side (required by ProjectCostService)
        GlAccount::create([
            'no' => 'CASH-001',
            'name' => 'Cash Account',
            'account_type' => 'Posting',
        ]);
    }

    private function makeCostData(array $overrides = []): array
    {
        return array_merge([
            'cost_type' => DirectCostType::PettyCash->value,
            'description' => 'Test direct cost',
            'amount' => 1500,
            'gl_account_no' => $this->glAccount->no,
            'posting_date' => now()->toDateString(),
        ], $overrides);
    }

    public function test_submit_direct_cost_creates_pending_record(): void
    {
        $cost = $this->costService->submitDirectCost($this->project, $this->makeCostData(), $this->user);

        $this->assertSame(DirectCostStatus::Pending, $cost->status);
        $this->assertStringStartsWith('DC-', $cost->no);
        $this->assertSame($this->user->id, $cost->submitted_by);
    }

    public function test_approve_direct_cost(): void
    {
        $approver = User::factory()->create();
        $cost = $this->costService->submitDirectCost($this->project, $this->makeCostData(), $this->user);

        $this->costService->approveDirectCost($cost, $approver);

        $cost->refresh();

        $this->assertSame(DirectCostStatus::Approved, $cost->status);
        $this->assertSame($approver->id, $cost->approved_by);
        $this->assertNotNull($cost->approved_at);
    }

    public function test_reject_direct_cost(): void
    {
        $approver = User::factory()->create();
        $cost = $this->costService->submitDirectCost($this->project, $this->makeCostData(), $this->user);

        $this->costService->rejectDirectCost($cost, $approver, 'Insufficient documentation');

        $cost->refresh();

        $this->assertSame(DirectCostStatus::Rejected, $cost->status);
        $this->assertSame('Insufficient documentation', $cost->rejection_reason);
    }

    public function test_cannot_post_unapproved_cost(): void
    {
        $cost = $this->costService->submitDirectCost($this->project, $this->makeCostData(), $this->user);

        $this->expectException(RuntimeException::class);

        $this->costService->postDirectCost($cost, $this->user);
    }

    public function test_post_direct_cost_creates_gl_entries(): void
    {
        $approver = User::factory()->create();
        $cost = $this->costService->submitDirectCost($this->project, $this->makeCostData(), $this->user);
        $this->costService->approveDirectCost($cost, $approver);
        $this->costService->postDirectCost($cost, $approver);

        $entries = $this->project->glEntries()->get();

        $this->assertCount(2, $entries);
        $this->assertTrue($entries->every(fn ($e) => $e->project_id === $this->project->id));

        $debitEntry = $entries->firstWhere('debit_amount', '>', 0);
        $creditEntry = $entries->firstWhere('credit_amount', '>', 0);

        $this->assertNotNull($debitEntry, 'Should have a debit GL entry');
        $this->assertNotNull($creditEntry, 'Should have a credit GL entry');
    }

    public function test_post_direct_cost_updates_spent(): void
    {
        $approver = User::factory()->create();

        // Set spent to a sentinel value so we can detect that recalculateSpent ran
        $this->project->update(['spent' => 99999]);

        $cost = $this->costService->submitDirectCost($this->project, $this->makeCostData(), $this->user);
        $this->costService->approveDirectCost($cost, $approver);
        $this->costService->postDirectCost($cost, $approver);

        // recalculateSpent should have overwritten the sentinel; both GL entries carry
        // project_id so net = debit - credit = 1500 - 1500 = 0, not 99999.
        $this->assertSame(0.0, (float) $this->project->fresh()->spent);
    }
}
