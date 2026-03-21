<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\PurchaseHeader;
use App\Models\Finance\PurchaseLine;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorLedgerEntry;
use App\Models\Finance\VendorPostingGroup;
use App\Services\Finance\PurchasePostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PurchasePostingServiceTest extends TestCase
{
    use RefreshDatabase;

    private PurchasePostingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(PurchasePostingService::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeFullSetup(): array
    {
        $vpg = VendorPostingGroup::factory()->create(['payables_account_no' => '2100']);
        $vendor = Vendor::factory()->create(['vendor_posting_group_id' => $vpg->id]);
        $header = PurchaseHeader::factory()->create([
            'vendor_id' => $vendor->id,
            'vendor_posting_group_id' => $vpg->id,
            'status' => 'Open',
        ]);
        $line = PurchaseLine::factory()->create([
            'purchase_header_id' => $header->id,
            'line_amount' => 500.00,
        ]);

        return compact('vpg', 'vendor', 'header', 'line');
    }

    // ─── Happy Path ───────────────────────────────────────────────────────────

    public function test_posts_invoice_and_creates_ledger_and_gl_entries(): void
    {
        ['header' => $header, 'vpg' => $vpg] = $this->makeFullSetup();

        $this->service->post($header);

        $header->refresh();
        $this->assertEquals('posted', $header->status);

        $this->assertDatabaseHas('vendor_ledger_entries', [
            'vendor_id' => $header->vendor_id,
            'document_no' => $header->no,
            'document_type' => 'invoice',
            'amount' => 500.00,
            'remaining_amount' => 500.00,
            'is_open' => true,
        ]);

        // Credit payables
        $this->assertDatabaseHas('gl_entries', [
            'document_no' => $header->no,
            'account_no' => $vpg->payables_account_no,
            'debit_amount' => 0,
            'credit_amount' => 500.00,
        ]);
    }

    public function test_assigns_sequential_entry_no(): void
    {
        VendorLedgerEntry::factory()->create(['entry_no' => 5]);

        ['header' => $header] = $this->makeFullSetup();
        $this->service->post($header);

        $this->assertDatabaseHas('vendor_ledger_entries', [
            'document_no' => $header->no,
            'entry_no' => 6,
        ]);
    }

    // ─── Validation Failures ──────────────────────────────────────────────────

    public function test_throws_if_already_posted(): void
    {
        $header = PurchaseHeader::factory()->create(['status' => 'posted']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already posted');

        $this->service->post($header);
    }

    public function test_throws_if_no_lines(): void
    {
        $vpg = VendorPostingGroup::factory()->create(['payables_account_no' => '2100']);
        $vendor = Vendor::factory()->create(['vendor_posting_group_id' => $vpg->id]);
        $header = PurchaseHeader::factory()->create([
            'vendor_id' => $vendor->id,
            'vendor_posting_group_id' => $vpg->id,
            'status' => 'Open',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no lines');

        $this->service->post($header);
    }

    public function test_throws_if_vendor_posting_group_missing_payables_account(): void
    {
        $vpg = VendorPostingGroup::factory()->create(['payables_account_no' => '']);
        $vendor = Vendor::factory()->create(['vendor_posting_group_id' => $vpg->id]);
        $header = PurchaseHeader::factory()->create([
            'vendor_id' => $vendor->id,
            'vendor_posting_group_id' => $vpg->id,
            'status' => 'Open',
        ]);
        PurchaseLine::factory()->create(['purchase_header_id' => $header->id]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('payables account');

        $this->service->post($header);
    }
}
