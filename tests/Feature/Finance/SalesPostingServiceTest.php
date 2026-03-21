<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\ServicePostingGroup;
use App\Services\Finance\SalesPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SalesPostingServiceTest extends TestCase
{
    use RefreshDatabase;

    private SalesPostingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SalesPostingService::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeFullSetup(): array
    {
        $cpg = CustomerPostingGroup::factory()->create(['receivables_account_no' => '1100']);
        $spg = ServicePostingGroup::factory()->create();
        $gps = GeneralPostingSetup::factory()->create([
            'customer_posting_group_id' => $cpg->id,
            'service_posting_group_id' => $spg->id,
            'sales_account_no' => '4000',
        ]);
        $customer = Customer::factory()->create(['customer_posting_group_id' => $cpg->id]);
        $header = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $cpg->id,
            'status' => 'Open',
        ]);
        $line = SalesLine::factory()->create([
            'sales_header_id' => $header->id,
            'service_posting_group_id' => $spg->id,
            'customer_posting_group_id' => $cpg->id,
            'general_posting_setup_id' => $gps->id,
            'line_amount' => 1000.00,
        ]);

        return compact('cpg', 'spg', 'gps', 'customer', 'header', 'line');
    }

    // ─── Happy Path ───────────────────────────────────────────────────────────

    public function test_posts_invoice_and_creates_ledger_and_gl_entries(): void
    {
        ['header' => $header, 'cpg' => $cpg, 'gps' => $gps] = $this->makeFullSetup();

        $this->service->post($header);

        $header->refresh();
        $this->assertEquals('posted', $header->status);

        $this->assertDatabaseHas('customer_ledger_entries', [
            'customer_id' => $header->customer_id,
            'document_no' => $header->no,
            'amount' => 1000.00,
            'remaining_amount' => 1000.00,
            'is_open' => true,
        ]);

        $this->assertDatabaseHas('gl_entries', [
            'document_no' => $header->no,
            'account_no' => $cpg->receivables_account_no,
            'debit_amount' => 1000.00,
            'credit_amount' => 0,
        ]);

        $this->assertDatabaseHas('gl_entries', [
            'document_no' => $header->no,
            'account_no' => $gps->sales_account_no,
            'debit_amount' => 0,
            'credit_amount' => 1000.00,
        ]);
    }

    public function test_assigns_sequential_entry_no(): void
    {
        CustomerLedgerEntry::factory()->create(['entry_no' => 10]);

        ['header' => $header] = $this->makeFullSetup();
        $this->service->post($header);

        $this->assertDatabaseHas('customer_ledger_entries', [
            'document_no' => $header->no,
            'entry_no' => 11,
        ]);
    }

    // ─── Validation Failures ──────────────────────────────────────────────────

    public function test_throws_if_already_posted(): void
    {
        $header = SalesHeader::factory()->create(['status' => 'posted']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already posted');

        $this->service->post($header);
    }

    public function test_throws_if_no_lines(): void
    {
        $cpg = CustomerPostingGroup::factory()->create();
        $customer = Customer::factory()->create(['customer_posting_group_id' => $cpg->id]);
        $header = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $cpg->id,
            'status' => 'Open',
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('no lines');

        $this->service->post($header);
    }

    public function test_throws_if_customer_posting_group_missing_receivables_account(): void
    {
        $cpg = CustomerPostingGroup::factory()->create(['receivables_account_no' => '']);
        $spg = ServicePostingGroup::factory()->create();
        $customer = Customer::factory()->create(['customer_posting_group_id' => $cpg->id]);
        $header = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $cpg->id,
            'status' => 'Open',
        ]);
        SalesLine::factory()->create([
            'sales_header_id' => $header->id,
            'service_posting_group_id' => $spg->id,
            'customer_posting_group_id' => $cpg->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('receivables account');

        $this->service->post($header);
    }

    public function test_throws_if_general_posting_setup_not_found(): void
    {
        $cpg = CustomerPostingGroup::factory()->create(['receivables_account_no' => '1100']);
        $spg = ServicePostingGroup::factory()->create();
        $customer = Customer::factory()->create(['customer_posting_group_id' => $cpg->id]);
        $header = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $cpg->id,
            'status' => 'Open',
        ]);
        SalesLine::factory()->create([
            'sales_header_id' => $header->id,
            'service_posting_group_id' => $spg->id,
            'customer_posting_group_id' => $cpg->id,
            'general_posting_setup_id' => null,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('General Posting Setup');

        $this->service->post($header);
    }

    public function test_throws_if_general_posting_setup_missing_sales_account(): void
    {
        $cpg = CustomerPostingGroup::factory()->create(['receivables_account_no' => '1100']);
        $spg = ServicePostingGroup::factory()->create();
        $gps = GeneralPostingSetup::factory()->create([
            'customer_posting_group_id' => $cpg->id,
            'service_posting_group_id' => $spg->id,
            'sales_account_no' => '',
        ]);
        $customer = Customer::factory()->create(['customer_posting_group_id' => $cpg->id]);
        $header = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $cpg->id,
            'status' => 'Open',
        ]);
        SalesLine::factory()->create([
            'sales_header_id' => $header->id,
            'service_posting_group_id' => $spg->id,
            'customer_posting_group_id' => $cpg->id,
            'general_posting_setup_id' => $gps->id,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Sales Account');

        $this->service->post($header);
    }
}
