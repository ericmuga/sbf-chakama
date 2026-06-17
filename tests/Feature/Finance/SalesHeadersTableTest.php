<?php

namespace Tests\Feature\Finance;

use App\Filament\Resources\Finance\SalesHeaders\Pages\ListSalesHeaders;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\ServicePostingGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class SalesHeadersTableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->actingAs(User::factory()->admin()->create());
    }

    private function makeHeader(string $status, float $lineAmount = 1000.00): SalesHeader
    {
        $cpg = CustomerPostingGroup::factory()->create();
        $spg = ServicePostingGroup::factory()->create();
        $customer = Customer::factory()->create(['customer_posting_group_id' => $cpg->id]);
        $header = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $cpg->id,
            'status' => $status,
        ]);
        SalesLine::factory()->create([
            'sales_header_id' => $header->id,
            'service_posting_group_id' => $spg->id,
            'customer_posting_group_id' => $cpg->id,
            'line_amount' => $lineAmount,
        ]);

        return $header;
    }

    public function test_table_shows_document_amount(): void
    {
        $header = $this->makeHeader('open', 2500.00);

        Livewire::test(ListSalesHeaders::class)
            ->assertCanSeeTableRecords([$header])
            ->assertTableColumnStateSet('sales_lines_sum_line_amount', 2500.00, $header);
    }

    public function test_bulk_delete_is_blocked_for_posted_documents(): void
    {
        $posted = $this->makeHeader('posted');

        Livewire::test(ListSalesHeaders::class)
            ->callTableBulkAction('delete', [$posted])
            ->assertNotified();

        $this->assertDatabaseHas('sales_headers', ['id' => $posted->id]);
    }

    public function test_bulk_delete_removes_open_documents(): void
    {
        $open = $this->makeHeader('open');

        Livewire::test(ListSalesHeaders::class)
            ->callTableBulkAction('delete', [$open]);

        $this->assertDatabaseMissing('sales_headers', ['id' => $open->id]);
    }
}
