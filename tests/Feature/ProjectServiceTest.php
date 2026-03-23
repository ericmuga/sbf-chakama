<?php

namespace Tests\Feature;

use App\Enums\ProjectMemberRole;
use App\Enums\ProjectModule;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Exceptions\InvalidStatusTransitionException;
use App\Models\Finance\GlEntry;
use App\Models\Finance\NumberSeries;
use App\Models\User;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProjectService $service;

    private User $creator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(ProjectService::class);
        $this->creator = User::factory()->create();
        $this->actingAs($this->creator);

        NumberSeries::factory()->create([
            'code' => 'PROJ',
            'prefix' => 'PROJ-',
            'last_no' => 0,
            'length' => 5,
            'is_active' => true,
        ]);
    }

    private function makeProjectData(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Project',
            'module' => ProjectModule::Sbf->value,
            'priority' => ProjectPriority::Medium->value,
            'budget' => 100000,
        ], $overrides);
    }

    public function test_create_project_generates_number_and_sets_owner(): void
    {
        $project = $this->service->createProject($this->makeProjectData(), $this->creator);

        $this->assertStringStartsWith('PROJ-', $project->no);

        $member = $project->members()
            ->where('users.id', $this->creator->id)
            ->first();

        $this->assertNotNull($member, 'Creator should be a project member');
        $this->assertSame(ProjectMemberRole::Owner, $member->pivot->role);

        $history = $project->statusHistory()
            ->where('to_status', ProjectStatus::Draft->value)
            ->first();

        $this->assertNotNull($history, 'Status history should have a draft entry');
    }

    public function test_change_status_valid_transition(): void
    {
        $project = $this->service->createProject($this->makeProjectData(), $this->creator);

        $this->service->changeStatus($project, ProjectStatus::Planning, $this->creator);

        $this->assertSame(ProjectStatus::Planning, $project->fresh()->status);
    }

    public function test_change_status_invalid_transition_throws(): void
    {
        $project = $this->service->createProject($this->makeProjectData(), $this->creator);

        $this->expectException(InvalidStatusTransitionException::class);

        // Draft → Completed is not allowed
        $this->service->changeStatus($project, ProjectStatus::Completed, $this->creator);
    }

    public function test_complete_status_sets_completed_at(): void
    {
        $project = $this->service->createProject($this->makeProjectData(), $this->creator);

        $this->service->changeStatus($project, ProjectStatus::Planning, $this->creator);
        $this->service->changeStatus($project, ProjectStatus::InProgress, $this->creator);
        $this->service->changeStatus($project, ProjectStatus::Completed, $this->creator);

        $this->assertNotNull($project->fresh()->completed_at);
    }

    public function test_add_member_attaches_to_pivot(): void
    {
        $project = $this->service->createProject($this->makeProjectData(), $this->creator);
        $contributor = User::factory()->create();

        $this->service->addMember($project, $contributor, ProjectMemberRole::Contributor, $this->creator);

        $member = $project->members()
            ->where('users.id', $contributor->id)
            ->first();

        $this->assertNotNull($member, 'Contributor should be a project member');
        $this->assertSame(ProjectMemberRole::Contributor, $member->pivot->role);
    }

    public function test_cannot_remove_last_owner(): void
    {
        $project = $this->service->createProject($this->makeProjectData(), $this->creator);

        $this->expectException(\RuntimeException::class);

        $this->service->removeMember($project, $this->creator);
    }

    public function test_recalculate_spent_sums_gl_entries(): void
    {
        $project = $this->service->createProject($this->makeProjectData(), $this->creator);

        GlEntry::create([
            'posting_date' => now()->toDateString(),
            'document_no' => 'TEST-001',
            'account_no' => 'ACCT-001',
            'debit_amount' => 5000,
            'credit_amount' => 0,
            'source_type' => 'Test',
            'source_id' => 1,
            'project_id' => $project->id,
            'created_by' => $this->creator->id,
        ]);

        GlEntry::create([
            'posting_date' => now()->toDateString(),
            'document_no' => 'TEST-002',
            'account_no' => 'ACCT-002',
            'debit_amount' => 0,
            'credit_amount' => 1000,
            'source_type' => 'Test',
            'source_id' => 2,
            'project_id' => $project->id,
            'created_by' => $this->creator->id,
        ]);

        $this->service->recalculateSpent($project);

        $this->assertEquals(4000.0, (float) $project->fresh()->spent);
    }
}
