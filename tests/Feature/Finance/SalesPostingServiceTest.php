<?php

namespace Tests\Feature\Finance;

use App\Filament\Resources\Finance\SalesHeaders\SalesHeaderResource;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\ServicePostingGroup;
use App\Models\User;
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

    public function test_posts_credit_memo_as_negative_and_mirrors_gl(): void
    {
        ['header' => $header, 'cpg' => $cpg, 'gps' => $gps] = $this->makeFullSetup();
        $header->update(['document_type' => 'credit_memo']);

        $this->service->post($header);

        $this->assertDatabaseHas('customer_ledger_entries', [
            'document_no' => $header->no,
            'document_type' => 'credit_memo',
            'amount' => -1000.00,
            'remaining_amount' => -1000.00,
        ]);

        // Receivables is credited (not debited) for a credit memo
        $this->assertDatabaseHas('gl_entries', [
            'document_no' => $header->no,
            'account_no' => $cpg->receivables_account_no,
            'debit_amount' => 0,
            'credit_amount' => 1000.00,
        ]);

        // Revenue is debited (not credited) for a credit memo
        $this->assertDatabaseHas('gl_entries', [
            'document_no' => $header->no,
            'account_no' => $gps->sales_account_no,
            'debit_amount' => 1000.00,
            'credit_amount' => 0,
        ]);
    }

    public function test_credit_memo_allocates_against_named_open_invoice(): void
    {
        ['customer' => $customer, 'cpg' => $cpg, 'spg' => $spg, 'gps' => $gps] = $this->makeFullSetup();

        // An open invoice ledger entry to apply the credit memo against
        $invoiceEntry = CustomerLedgerEntry::factory()->create([
            'customer_id' => $customer->id,
            'document_type' => 'invoice',
            'document_no' => 'SI-OPEN-1',
            'amount' => 1000.00,
            'remaining_amount' => 1000.00,
            'is_open' => true,
        ]);

        $creditMemo = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $cpg->id,
            'document_type' => 'credit_memo',
            'applies_to_doc_no' => 'SI-OPEN-1',
            'status' => 'open',
        ]);
        SalesLine::factory()->create([
            'sales_header_id' => $creditMemo->id,
            'service_posting_group_id' => $spg->id,
            'customer_posting_group_id' => $cpg->id,
            'general_posting_setup_id' => $gps->id,
            'line_amount' => 400.00,
        ]);

        $this->service->post($creditMemo);

        // Invoice remaining reduced by the credit memo amount
        $invoiceEntry->refresh();
        $this->assertEquals(600.00, (float) $invoiceEntry->remaining_amount);
        $this->assertTrue($invoiceEntry->is_open);

        // Credit memo entry fully applied and closed
        $this->assertDatabaseHas('customer_ledger_entries', [
            'document_no' => $creditMemo->no,
            'amount' => -400.00,
            'remaining_amount' => 0,
            'is_open' => false,
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

    public function test_throws_if_already_posted_with_legacy_status_casing(): void
    {
        $header = SalesHeader::factory()->create(['status' => 'Posted']);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('already posted');

        $this->service->post($header);
    }

    public function test_resource_disallows_editing_and_deleting_posted_sales_documents(): void
    {
        $this->actingAs(User::factory()->admin()->create());

        $openHeader = SalesHeader::factory()->create(['status' => 'open']);
        $postedHeader = SalesHeader::factory()->create(['status' => 'Posted']);

        $this->assertTrue(SalesHeaderResource::canEdit($openHeader));
        $this->assertTrue(SalesHeaderResource::canDelete($openHeader));
        $this->assertFalse(SalesHeaderResource::canEdit($postedHeader));
        $this->assertFalse(SalesHeaderResource::canDelete($postedHeader));
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
