<?php

namespace Tests\Feature;

use App\Enums\DirectCostStatus;
use App\Enums\DirectCostType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectModule;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Filament\Resources\Projects\Pages\CreateProject;
use App\Filament\Resources\Projects\Pages\ListProjects;
use App\Filament\Resources\Projects\Pages\ViewProject;
use App\Filament\Resources\Projects\RelationManagers\BudgetLinesRelationManager;
use App\Filament\Resources\Projects\RelationManagers\DirectCostsRelationManager;
use App\Filament\Resources\Projects\RelationManagers\MilestonesRelationManager;
use App\Filament\Resources\Projects\RelationManagers\PurchaseOrdersRelationManager;
use App\Models\Finance\GlAccount;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\ProjectBudgetLine;
use App\Models\ProjectMilestone;
use App\Models\User;
use App\Notifications\AddedToProjectNotification;
use App\Services\ProjectCostService;
use App\Services\ProjectService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProjectFilamentTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private ProjectService $projectService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->admin()->create();
        $this->projectService = app(ProjectService::class);
        $this->actingAs($this->admin);

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

    private function createGlAccount(string $no = 'EXP-001'): GlAccount
    {
        return GlAccount::create([
            'no' => $no,
            'name' => 'Test Expense Account',
            'account_type' => 'Posting',
        ]);
    }

    private function createVendor(): array
    {
        $group = VendorPostingGroup::create([
            'code' => 'DEFAULT',
            'description' => 'Default',
            'payables_account_no' => 'PAY-001',
        ]);

        $vendor = Vendor::create([
            'no' => 'VEND-001',
            'name' => 'Test Vendor',
            'vendor_posting_group_id' => $group->id,
        ]);

        return [$vendor, $group];
    }

    public function test_admin_can_list_projects(): void
    {
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        Livewire::test(ListProjects::class)
            ->assertCanSeeTableRecords([$project]);
    }

    public function test_non_admin_cannot_access_list_projects(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/admin/projects')
            ->assertForbidden();
    }

    public function test_admin_can_create_project_and_number_is_auto_generated(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => 'Bursary Fund 2026',
                'module' => ProjectModule::Sbf->value,
                'priority' => ProjectPriority::High->value,
                'budget' => 500000,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $project = Project::query()->where('name', 'Bursary Fund 2026')->firstOrFail();

        $this->assertStringStartsWith('PROJ-', $project->no);
        $this->assertSame(ProjectStatus::Draft, $project->status);

        $member = $project->members()->where('users.id', $this->admin->id)->first();
        $this->assertNotNull($member, 'Creator should be a project member');
    }

    public function test_create_project_requires_name_module_and_budget(): void
    {
        Livewire::test(CreateProject::class)
            ->fillForm([
                'name' => null,
                'module' => null,
                'budget' => null,
            ])
            ->call('create')
            ->assertHasFormErrors(['name', 'module', 'budget']);
    }

    public function test_module_filter_shows_only_matching_projects(): void
    {
        $sbf = $this->projectService->createProject(
            $this->makeProjectData(['name' => 'SBF Project', 'module' => ProjectModule::Sbf->value]),
            $this->admin
        );
        $chakama = $this->projectService->createProject(
            $this->makeProjectData(['name' => 'Chakama Project', 'module' => ProjectModule::Chakama->value]),
            $this->admin
        );

        Livewire::test(ListProjects::class)
            ->filterTable('module', ProjectModule::Sbf->value)
            ->assertCanSeeTableRecords([$sbf])
            ->assertCanNotSeeTableRecords([$chakama]);
    }

    public function test_status_filter_shows_only_matching_projects(): void
    {
        $draft = $this->projectService->createProject(
            $this->makeProjectData(['name' => 'Draft Project']),
            $this->admin
        );
        $planning = $this->projectService->createProject(
            $this->makeProjectData(['name' => 'Planning Project']),
            $this->admin
        );
        $this->projectService->changeStatus($planning, ProjectStatus::Planning, $this->admin);

        Livewire::test(ListProjects::class)
            ->filterTable('status', ProjectStatus::Draft->value)
            ->assertCanSeeTableRecords([$draft])
            ->assertCanNotSeeTableRecords([$planning]);
    }

    public function test_valid_status_transition_from_view_page(): void
    {
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
            ->callAction('change_status', [
                'new_status' => ProjectStatus::Planning->value,
                'reason' => 'Moving to planning phase',
            ])
            ->assertNotified();

        $this->assertSame(ProjectStatus::Planning, $project->fresh()->status);
    }

    public function test_change_status_action_hidden_for_completed_project(): void
    {
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);
        $this->projectService->changeStatus($project, ProjectStatus::Planning, $this->admin);
        $this->projectService->changeStatus($project, ProjectStatus::InProgress, $this->admin);
        $this->projectService->changeStatus($project, ProjectStatus::Completed, $this->admin);

        Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
            ->assertActionHidden('change_status');
    }

    public function test_view_project_can_add_milestone_from_header_action(): void
    {
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
            ->callAction('add_milestone', [
                'title' => 'Launch approvals',
                'description' => 'All final approvals received.',
                'due_date' => now()->addWeek()->toDateString(),
                'sort_order' => 10,
            ])
            ->assertNotified();

        $this->assertDatabaseHas('project_milestones', [
            'project_id' => $project->id,
            'title' => 'Launch approvals',
        ]);
    }

    public function test_view_project_can_add_member_from_header_action_and_notify_user(): void
    {
        Notification::fake();

        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);
        $member = User::factory()->create();

        Livewire::test(ViewProject::class, ['record' => $project->getRouteKey()])
            ->callAction('add_member', [
                'user_id' => $member->id,
                'role' => ProjectMemberRole::Manager->value,
            ])
            ->assertNotified();

        Notification::assertSentTo($member, AddedToProjectNotification::class);
    }

    public function test_direct_cost_approve_then_post_flow(): void
    {
        $glAccount = $this->createGlAccount();
        GlAccount::create(['no' => 'CASH-001', 'name' => 'Cash', 'account_type' => 'Posting']);

        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        $costService = app(ProjectCostService::class);
        $cost = $costService->submitDirectCost($project, [
            'cost_type' => DirectCostType::PettyCash->value,
            'description' => 'Office supplies',
            'amount' => 2500,
            'gl_account_no' => $glAccount->no,
            'posting_date' => now()->toDateString(),
        ], $this->admin);

        $this->assertSame(DirectCostStatus::Pending, $cost->status);

        Livewire::test(DirectCostsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callTableAction('approve', $cost)
            ->assertNotified();

        $this->assertSame(DirectCostStatus::Approved, $cost->fresh()->status);

        Livewire::test(DirectCostsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callTableAction('post', $cost->fresh())
            ->assertNotified();

        $this->assertSame(DirectCostStatus::Posted, $cost->fresh()->status);
        $this->assertCount(2, $project->glEntries);
    }

    public function test_direct_cost_reject_sets_reason(): void
    {
        $glAccount = $this->createGlAccount();
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        $cost = app(ProjectCostService::class)->submitDirectCost($project, [
            'cost_type' => DirectCostType::PettyCash->value,
            'description' => 'Rejected cost',
            'amount' => 500,
            'gl_account_no' => $glAccount->no,
            'posting_date' => now()->toDateString(),
        ], $this->admin);

        Livewire::test(DirectCostsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callTableAction('reject', $cost, ['reason' => 'Not budgeted'])
            ->assertNotified();

        $this->assertSame(DirectCostStatus::Rejected, $cost->fresh()->status);
        $this->assertSame('Not budgeted', $cost->fresh()->rejection_reason);
    }

    public function test_direct_cost_void_action_reverses_posted_cost(): void
    {
        $glAccount = $this->createGlAccount();
        GlAccount::create(['no' => 'CASH-001', 'name' => 'Cash', 'account_type' => 'Posting']);

        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        $costService = app(ProjectCostService::class);
        $cost = $costService->submitDirectCost($project, [
            'cost_type' => DirectCostType::PettyCash->value,
            'description' => 'Temporary shelter materials',
            'amount' => 1250,
            'gl_account_no' => $glAccount->no,
            'posting_date' => now()->toDateString(),
        ], $this->admin);

        $costService->approveDirectCost($cost, $this->admin);
        $costService->postDirectCost($cost, $this->admin);

        Livewire::test(DirectCostsRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callTableAction('void_cost', $cost->fresh())
            ->assertNotified();

        $this->assertSame(DirectCostStatus::Voided, $cost->fresh()->status);
    }

    public function test_linked_purchase_orders_appear_in_relation_manager(): void
    {
        [$vendor, $group] = $this->createVendor();
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        $po = PurchaseHeader::create([
            'no' => 'PO-TEST-001',
            'vendor_id' => $vendor->id,
            'vendor_posting_group_id' => $group->id,
            'number_series_code' => 'PROJ',
            'posting_date' => now()->toDateString(),
            'status' => 'Open',
            'project_id' => $project->id,
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertCanSeeTableRecords([$po]);
    }

    public function test_unlinked_purchase_orders_not_visible_in_relation_manager(): void
    {
        [$vendor, $group] = $this->createVendor();
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        $unlinked = PurchaseHeader::create([
            'no' => 'PO-UNLINKED-001',
            'vendor_id' => $vendor->id,
            'vendor_posting_group_id' => $group->id,
            'number_series_code' => 'PROJ',
            'posting_date' => now()->toDateString(),
            'status' => 'Open',
            'project_id' => null,
            'created_by' => $this->admin->id,
        ]);

        Livewire::test(PurchaseOrdersRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertCanNotSeeTableRecords([$unlinked]);
    }

    public function test_completed_milestone_can_be_reopened_from_relation_manager(): void
    {
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);
        $milestone = ProjectMilestone::create([
            'project_id' => $project->id,
            'title' => 'Close procurement',
            'status' => 'completed',
            'completed_at' => now(),
            'sort_order' => 10,
        ]);

        Livewire::test(MilestonesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->callTableAction('reopen_milestone', $milestone)
            ->assertNotified();

        $this->assertSame('pending', $milestone->fresh()->status);
        $this->assertNull($milestone->fresh()->completed_at);
    }

    public function test_budget_lines_appear_in_relation_manager(): void
    {
        $glAccount = $this->createGlAccount();
        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        $line = ProjectBudgetLine::create([
            'project_id' => $project->id,
            'gl_account_no' => $glAccount->no,
            'description' => 'Operations',
            'budgeted_amount' => 50000,
            'sort_order' => 1,
        ]);

        Livewire::test(BudgetLinesRelationManager::class, [
            'ownerRecord' => $project,
            'pageClass' => ViewProject::class,
        ])
            ->assertCanSeeTableRecords([$line]);
    }

    public function test_project_attachment_view_url_uses_public_storage(): void
    {
        Storage::fake('public');

        Storage::disk('public')->put('project-attachments/demo/brief.pdf', 'pdf');

        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);
        $attachment = ProjectAttachment::create([
            'project_id' => $project->id,
            'uploaded_by' => $this->admin->id,
            'file_name' => 'brief.pdf',
            'file_path' => 'project-attachments/demo/brief.pdf',
            'file_size' => 3,
            'mime_type' => 'application/pdf',
        ]);

        $this->assertStringContainsString('project-attachments/demo/brief.pdf', $attachment->viewUrl());
        $this->assertTrue($attachment->isPdf());
    }

    public function test_get_budget_vs_actual_returns_correct_variance(): void
    {
        $glAccount = $this->createGlAccount();
        GlAccount::create(['no' => 'CASH-001', 'name' => 'Cash', 'account_type' => 'Posting']);

        $project = $this->projectService->createProject($this->makeProjectData(), $this->admin);

        ProjectBudgetLine::create([
            'project_id' => $project->id,
            'gl_account_no' => $glAccount->no,
            'description' => 'Operations',
            'budgeted_amount' => 50000,
            'sort_order' => 1,
        ]);

        $costService = app(ProjectCostService::class);
        $cost = $costService->submitDirectCost($project, [
            'cost_type' => DirectCostType::PettyCash->value,
            'description' => 'Ops spend',
            'amount' => 20000,
            'gl_account_no' => $glAccount->no,
            'posting_date' => now()->toDateString(),
        ], $this->admin);
        $costService->approveDirectCost($cost, $this->admin);
        $costService->postDirectCost($cost, $this->admin);

        $result = $this->projectService->getBudgetVsActual($project);

        $this->assertCount(1, $result);

        $row = $result->first();
        $this->assertEquals(50000, (float) $row['budgeted']);
        $this->assertEquals(20000, (float) $row['actual']);
        $this->assertEquals(30000, (float) $row['variance']);
    }
}
