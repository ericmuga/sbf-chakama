<?php

namespace Database\Seeders;

use App\Enums\DirectCostType;
use App\Enums\ProjectMemberRole;
use App\Enums\ProjectModule;
use App\Enums\ProjectPriority;
use App\Enums\ProjectStatus;
use App\Models\Finance\GlAccount;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\PurchaseLine;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\Service;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use App\Models\Project;
use App\Models\ProjectAttachment;
use App\Models\ProjectBudgetLine;
use App\Models\ProjectComment;
use App\Models\User;
use App\Services\ProjectCostService;
use App\Services\ProjectService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProjectsDemoSeeder extends Seeder
{
    public function run(): void
    {
        $purchaseSetup = PurchaseSetup::query()->first();
        $purchasableService = Service::query()->where('is_purchasable', true)->orderBy('id')->first();

        if (! $purchaseSetup?->project_nos || ! $purchaseSetup?->direct_cost_nos || ! $purchaseSetup->invoice_nos || ! $purchasableService) {
            return;
        }

        $owner = $this->firstOrCreateUser('projects.owner@sbfchakama.co.ke', 'Projects Owner', true);
        $manager = $this->firstOrCreateUser('projects.manager@sbfchakama.co.ke', 'Projects Manager');
        $fieldOfficer = $this->firstOrCreateUser('projects.field@sbfchakama.co.ke', 'Field Officer');
        $volunteer = $this->firstOrCreateUser('projects.volunteer@sbfchakama.co.ke', 'Volunteer Coordinator');

        $vendorPostingGroup = VendorPostingGroup::query()->firstOrCreate(
            ['code' => 'LOCAL'],
            ['description' => 'Local Vendors', 'payables_account_no' => '2100'],
        );

        $vendor = Vendor::query()->firstOrCreate(
            ['name' => 'Horizon Works Limited'],
            ['no' => 'VEND-PROJECT-001', 'vendor_posting_group_id' => $vendorPostingGroup->id],
        );

        $projectService = app(ProjectService::class);
        $costService = app(ProjectCostService::class);

        $definitions = [
            [
                'name' => 'SBF Medical Outreach Camp',
                'description' => 'Mobile clinic and welfare outreach for alumni families in need.',
                'module' => ProjectModule::Sbf->value,
                'priority' => ProjectPriority::High->value,
                'budget' => 850000,
                'start_date' => now()->subWeeks(8)->toDateString(),
                'due_date' => now()->addWeeks(6)->toDateString(),
                'status' => ProjectStatus::InProgress,
                'members' => [
                    [$manager, ProjectMemberRole::Manager],
                    [$fieldOfficer, ProjectMemberRole::Contributor],
                ],
                'milestones' => [
                    ['title' => 'Beneficiary registration completed', 'status' => 'completed', 'due_date' => now()->subWeeks(5)->toDateString(), 'completed_at' => now()->subWeeks(5)],
                    ['title' => 'Medical supplies delivered', 'status' => 'completed', 'due_date' => now()->subWeeks(2)->toDateString(), 'completed_at' => now()->subWeeks(2)],
                    ['title' => 'Camp execution weekend', 'status' => 'pending', 'due_date' => now()->addWeeks(2)->toDateString(), 'completed_at' => null],
                ],
                'budget_lines' => [
                    ['gl_account_no' => '5200', 'description' => 'Medical assistance', 'budgeted_amount' => 350000, 'sort_order' => 10],
                    ['gl_account_no' => '5410', 'description' => 'Medical supplies', 'budgeted_amount' => 200000, 'sort_order' => 20],
                    ['gl_account_no' => '5420', 'description' => 'Field transport', 'budgeted_amount' => 120000, 'sort_order' => 30],
                ],
                'costs' => [
                    ['cost_type' => DirectCostType::PettyCash, 'description' => 'Community mobilisation and stationery', 'amount' => 27500, 'gl_account_no' => '5410'],
                    ['cost_type' => DirectCostType::BankTransfer, 'description' => 'Transport advance to outreach team', 'amount' => 68250, 'gl_account_no' => '5420'],
                ],
                'purchase_lines' => [
                    ['description' => 'First aid kits', 'quantity' => 20, 'unit_price' => 4500],
                    ['description' => 'Mobile tents', 'quantity' => 4, 'unit_price' => 18500],
                ],
            ],
            [
                'name' => 'Chakama Water Point Rehabilitation',
                'description' => 'Repair and secure the community water point infrastructure for dry season resilience.',
                'module' => ProjectModule::Chakama->value,
                'priority' => ProjectPriority::Critical->value,
                'budget' => 1450000,
                'start_date' => now()->subWeeks(4)->toDateString(),
                'due_date' => now()->addWeeks(10)->toDateString(),
                'status' => ProjectStatus::Planning,
                'members' => [
                    [$manager, ProjectMemberRole::Manager],
                    [$volunteer, ProjectMemberRole::Viewer],
                ],
                'milestones' => [
                    ['title' => 'Scope validation with ranch committee', 'status' => 'completed', 'due_date' => now()->subWeek()->toDateString(), 'completed_at' => now()->subWeek()],
                    ['title' => 'Procurement of pipes and fittings', 'status' => 'pending', 'due_date' => now()->addWeeks(2)->toDateString(), 'completed_at' => null],
                    ['title' => 'Civil works handover', 'status' => 'pending', 'due_date' => now()->addWeeks(8)->toDateString(), 'completed_at' => null],
                ],
                'budget_lines' => [
                    ['gl_account_no' => '5310', 'description' => 'Pipe and fitting materials', 'budgeted_amount' => 680000, 'sort_order' => 10],
                    ['gl_account_no' => '5420', 'description' => 'Site transport', 'budgeted_amount' => 180000, 'sort_order' => 20],
                    ['gl_account_no' => '5400', 'description' => 'Site supervision and compliance', 'budgeted_amount' => 120000, 'sort_order' => 30],
                ],
                'costs' => [
                    ['cost_type' => DirectCostType::MpesaPayment, 'description' => 'Survey mobilisation allowance', 'amount' => 18500, 'gl_account_no' => '5400'],
                ],
                'purchase_lines' => [
                    ['description' => 'HDPE pipes', 'quantity' => 120, 'unit_price' => 2650],
                    ['description' => 'Control valves', 'quantity' => 12, 'unit_price' => 9200],
                ],
            ],
        ];

        foreach ($definitions as $definition) {
            $project = Project::query()->where('name', $definition['name'])->first();

            if (! $project) {
                $project = $projectService->createProject([
                    'name' => $definition['name'],
                    'description' => $definition['description'],
                    'module' => $definition['module'],
                    'priority' => $definition['priority'],
                    'budget' => $definition['budget'],
                    'start_date' => $definition['start_date'],
                    'due_date' => $definition['due_date'],
                ], $owner);
            }

            $project = $project->fresh();

            if ($project->status === ProjectStatus::Draft && in_array($definition['status'], [ProjectStatus::Planning, ProjectStatus::InProgress], true)) {
                $projectService->changeStatus($project, ProjectStatus::Planning, $owner, 'Seeded planning stage');
                $project = $project->fresh();
            }

            if ($definition['status'] === ProjectStatus::InProgress && $project->status !== ProjectStatus::InProgress) {
                $projectService->changeStatus($project, ProjectStatus::InProgress, $owner, 'Seeded execution stage');
                $project = $project->fresh();
            }

            foreach ($definition['members'] as [$user, $role]) {
                $projectService->addMember($project, $user, $role, $owner);
            }

            foreach ($definition['milestones'] as $index => $milestone) {
                $project->milestones()->updateOrCreate(
                    ['title' => $milestone['title']],
                    [
                        'description' => $milestone['title'],
                        'due_date' => $milestone['due_date'],
                        'status' => $milestone['status'],
                        'completed_at' => $milestone['completed_at'],
                        'sort_order' => ($index + 1) * 10,
                    ],
                );
            }

            foreach ($definition['budget_lines'] as $budgetLine) {
                if (! GlAccount::query()->where('no', $budgetLine['gl_account_no'])->exists()) {
                    continue;
                }

                ProjectBudgetLine::query()->updateOrCreate(
                    [
                        'project_id' => $project->id,
                        'gl_account_no' => $budgetLine['gl_account_no'],
                    ],
                    [
                        'description' => $budgetLine['description'],
                        'budgeted_amount' => $budgetLine['budgeted_amount'],
                        'sort_order' => $budgetLine['sort_order'],
                    ],
                );
            }

            foreach ($definition['costs'] as $costDefinition) {
                $cost = $project->directCosts()->where('description', $costDefinition['description'])->first();

                if (! $cost) {
                    $cost = $costService->submitDirectCost($project, [
                        'cost_type' => $costDefinition['cost_type']->value,
                        'description' => $costDefinition['description'],
                        'amount' => $costDefinition['amount'],
                        'gl_account_no' => $costDefinition['gl_account_no'],
                        'posting_date' => now()->subDays(5)->toDateString(),
                        'receipt_number' => 'RCPT-'.str_pad((string) ($project->id * 10), 4, '0', STR_PAD_LEFT),
                    ], $owner);
                }

                if ($cost->status->value === 'pending') {
                    $costService->approveDirectCost($cost, $manager);
                    $cost = $cost->fresh();
                }

                if ($cost->status->value === 'approved') {
                    $costService->postDirectCost($cost, $manager);
                }
            }

            $purchaseHeader = PurchaseHeader::query()
                ->where('project_id', $project->id)
                ->where('vendor_id', $vendor->id)
                ->first();

            if (! $purchaseHeader) {
                $purchaseHeader = PurchaseHeader::query()->create([
                    'vendor_id' => $vendor->id,
                    'project_id' => $project->id,
                    'vendor_posting_group_id' => $vendor->vendor_posting_group_id,
                    'posting_date' => now()->subDays(3)->toDateString(),
                    'due_date' => now()->addDays(21)->toDateString(),
                    'number_series_code' => $purchaseSetup->invoice_nos,
                    'status' => 'Open',
                ]);

                foreach ($definition['purchase_lines'] as $index => $line) {
                    PurchaseLine::query()->create([
                        'purchase_header_id' => $purchaseHeader->id,
                        'line_no' => ($index + 1) * 10000,
                        'service_id' => $purchasableService->id,
                        'description' => $line['description'],
                        'quantity' => $line['quantity'],
                        'unit_price' => $line['unit_price'],
                        'line_amount' => $line['quantity'] * $line['unit_price'],
                    ]);
                }
            }

            ProjectComment::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'user_id' => $manager->id,
                ],
                [
                    'body' => 'Seeded working note: focus review on budget lines, supplier readiness, and milestone completion.',
                ],
            );

            $this->seedAttachments($project, $owner);
        }
    }

    private function firstOrCreateUser(string $email, string $name, bool $isAdmin = false): User
    {
        return User::query()->firstOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'email_verified_at' => now(),
                'password' => bcrypt('password'),
                'is_admin' => $isAdmin,
            ],
        );
    }

    private function seedAttachments(Project $project, User $uploader): void
    {
        $directory = 'project-attachments/'.$project->no;
        $pdfPath = $directory.'/project-brief.pdf';
        $pngPath = $directory.'/site-photo.png';

        if (! Storage::disk('public')->exists($pdfPath)) {
            Storage::disk('public')->put($pdfPath, $this->samplePdfContents());
        }

        if (! Storage::disk('public')->exists($pngPath)) {
            Storage::disk('public')->put($pngPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9pQfV2kAAAAASUVORK5CYII='));
        }

        foreach ([$pdfPath => 'application/pdf', $pngPath => 'image/png'] as $path => $mimeType) {
            ProjectAttachment::query()->updateOrCreate(
                [
                    'project_id' => $project->id,
                    'file_path' => $path,
                ],
                [
                    'uploaded_by' => $uploader->id,
                    'file_name' => basename($path),
                    'file_size' => (int) Storage::disk('public')->size($path),
                    'mime_type' => $mimeType,
                ],
            );
        }
    }

    private function samplePdfContents(): string
    {
        return "%PDF-1.4\n1 0 obj<<>>endobj\n2 0 obj<</Length 44>>stream\nBT /F1 12 Tf 72 720 Td (Project Brief Sample) Tj ET\nendstream endobj\n3 0 obj<</Type /Page /Parent 4 0 R /Contents 2 0 R>>endobj\n4 0 obj<</Type /Pages /Kids [3 0 R] /Count 1>>endobj\n5 0 obj<</Type /Catalog /Pages 4 0 R>>endobj\nxref\n0 6\n0000000000 65535 f \n0000000010 00000 n \n0000000031 00000 n \n0000000127 00000 n \n0000000186 00000 n \n0000000244 00000 n \ntrailer<</Root 5 0 R /Size 6>>\nstartxref\n295\n%%EOF";
    }
}
