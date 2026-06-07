<?php

namespace Tests\Feature;

use App\Models\Finance\GlEntry;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\PurchaseLine;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use App\Models\Project;
use App\Models\User;
use App\Services\Finance\PurchasePostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchaseInvoiceProjectCostsTest extends TestCase
{
    use RefreshDatabase;

    private function makeProject(): Project
    {
        $user = User::factory()->create();
        NumberSeries::factory()->create(['code' => 'PROJECT', 'prefix' => 'PRJ-']);

        return Project::create([
            'no' => 'PRJ-'.uniqid(),
            'name' => 'Test Project '.uniqid(),
            'slug' => 'test-'.uniqid(),
            'module' => 'chakama',
            'budget' => 1_000_000,
            'spent' => 0,
            'status' => 'in_progress',
            'priority' => 'medium',
            'start_date' => today(),
            'due_date' => today()->addMonths(3),
            'number_series_code' => 'PROJECT',
            'created_by' => $user->id,
        ]);
    }

    public function test_posting_purchase_invoice_tags_gl_entries_with_project_id(): void
    {
        $project = $this->makeProject();

        $vpg = VendorPostingGroup::factory()->create(['payables_account_no' => '2100']);
        $vendor = Vendor::factory()->create(['vendor_posting_group_id' => $vpg->id]);

        $header = PurchaseHeader::factory()->create([
            'vendor_id' => $vendor->id,
            'vendor_posting_group_id' => $vpg->id,
            'project_id' => $project->id,
            'status' => 'Open',
        ]);

        PurchaseLine::factory()->create([
            'purchase_header_id' => $header->id,
            'line_amount' => 750.00,
        ]);

        app(PurchasePostingService::class)->post($header);

        $this->assertGreaterThan(
            0,
            GlEntry::where('project_id', $project->id)->count(),
            'Expected GL entries to be tagged with the project id after posting a project-linked purchase invoice.'
        );

        // Both the payables credit and the expense debit should carry the project id.
        $this->assertDatabaseHas('gl_entries', [
            'document_no' => $header->no,
            'project_id' => $project->id,
            'account_no' => $vpg->payables_account_no,
            'credit_amount' => 750.00,
        ]);
    }

    public function test_purchase_invoice_without_project_id_does_not_tag_gl_entries(): void
    {
        $vpg = VendorPostingGroup::factory()->create(['payables_account_no' => '2100']);
        $vendor = Vendor::factory()->create(['vendor_posting_group_id' => $vpg->id]);

        $header = PurchaseHeader::factory()->create([
            'vendor_id' => $vendor->id,
            'vendor_posting_group_id' => $vpg->id,
            'project_id' => null,
            'status' => 'Open',
        ]);

        PurchaseLine::factory()->create([
            'purchase_header_id' => $header->id,
            'line_amount' => 200.00,
        ]);

        app(PurchasePostingService::class)->post($header);

        $this->assertSame(
            0,
            GlEntry::whereNotNull('project_id')->count(),
            'Untagged purchase invoices must not leak project_id onto GL entries.'
        );
    }
}
