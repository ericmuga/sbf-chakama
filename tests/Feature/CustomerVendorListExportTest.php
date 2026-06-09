<?php

namespace Tests\Feature;

use App\Exports\CustomerListExport;
use App\Exports\VendorListExport;
use App\Filament\Resources\Finance\CustomerResource\Pages\ListCustomers;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorLedgerEntry;
use App\Models\Finance\VendorPostingGroup;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Tests\TestCase;

class CustomerVendorListExportTest extends TestCase
{
    use RefreshDatabase;

    private CustomerPostingGroup $pgA;

    private CustomerPostingGroup $pgB;

    private Customer $c1;

    private Customer $c2;

    private Customer $c3;

    protected function setUp(): void
    {
        parent::setUp();

        $this->pgA = CustomerPostingGroup::create(['code' => 'PGA', 'description' => 'Group A', 'receivables_account_no' => '1100']);
        $this->pgB = CustomerPostingGroup::create(['code' => 'PGB', 'description' => 'Group B', 'receivables_account_no' => '1200']);

        // c1: 5000 - 2000 = 3000 (DR)
        $this->c1 = Customer::create(['no' => 'C1', 'name' => 'Alpha', 'customer_posting_group_id' => $this->pgA->id]);
        $this->ledger($this->c1, 5000);
        $this->ledger($this->c1, -2000);

        // c2: 1000 - 1000 = 0 (zero balance)
        $this->c2 = Customer::create(['no' => 'C2', 'name' => 'Beta', 'customer_posting_group_id' => $this->pgA->id]);
        $this->ledger($this->c2, 1000);
        $this->ledger($this->c2, -1000);

        // c3: 800 (DR), different posting group
        $this->c3 = Customer::create(['no' => 'C3', 'name' => 'Gamma', 'customer_posting_group_id' => $this->pgB->id]);
        $this->ledger($this->c3, 800);
    }

    private function ledger(Customer $customer, float $amount): void
    {
        CustomerLedgerEntry::create([
            'entry_no' => CustomerLedgerEntry::max('entry_no') + 1,
            'customer_id' => $customer->id,
            'document_type' => $amount > 0 ? 'invoice' : 'payment',
            'document_no' => 'DOC-'.uniqid(),
            'posting_date' => '2026-01-15',
            'due_date' => '2026-02-15',
            'amount' => $amount,
            'remaining_amount' => $amount > 0 ? $amount : 0,
            'is_open' => $amount > 0,
        ]);
    }

    public function test_customer_export_includes_all_with_computed_balance(): void
    {
        $rows = (new CustomerListExport)->collection();

        $this->assertCount(3, $rows);
        $this->assertSame(3000.0, (float) $rows->firstWhere('no', 'C1')->balance_sum);
        $this->assertSame(0.0, (float) $rows->firstWhere('no', 'C2')->balance_sum);
    }

    public function test_customer_export_filters_by_posting_group(): void
    {
        $rows = (new CustomerListExport($this->pgA->id))->collection();

        $this->assertEqualsCanonicalizing(['C1', 'C2'], $rows->pluck('no')->all());
    }

    public function test_customer_export_only_with_balance_excludes_zero(): void
    {
        $rows = (new CustomerListExport(null, true))->collection();

        $this->assertEqualsCanonicalizing(['C1', 'C3'], $rows->pluck('no')->all());
    }

    public function test_customer_export_combined_filters(): void
    {
        $rows = (new CustomerListExport($this->pgA->id, true))->collection();

        $this->assertEqualsCanonicalizing(['C1'], $rows->pluck('no')->all());
    }

    public function test_customer_export_map_outputs_direction(): void
    {
        $export = new CustomerListExport;
        $c1 = $export->collection()->firstWhere('no', 'C1');

        $this->assertSame(['C1', 'Alpha', 'Group A', null, '3,000.00', 'DR'], $export->map($c1));
    }

    public function test_customer_export_route_downloads_for_admin(): void
    {
        Excel::fake();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.reports.customers.export-excel'))
            ->assertOk();

        Excel::assertDownloaded('customers-'.now()->format('Y-m-d').'.xlsx');
    }

    public function test_export_route_requires_authentication(): void
    {
        $this->get(route('admin.reports.customers.export-excel'))->assertRedirect();
    }

    public function test_customers_list_page_renders_with_balance_and_filter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('sbf'));

        Livewire::test(ListCustomers::class)
            ->assertCanSeeTableRecords([$this->c1, $this->c2, $this->c3])
            ->filterTable('with_balance', true)
            ->assertCanSeeTableRecords([$this->c1, $this->c3])
            ->assertCanNotSeeTableRecords([$this->c2]);
    }

    public function test_vendor_export_computes_balance_and_filters(): void
    {
        $pg = VendorPostingGroup::create(['code' => 'VPG', 'description' => 'Vendors', 'payables_account_no' => '2100']);
        $v1 = Vendor::create(['no' => 'V1', 'name' => 'Supplier A', 'vendor_posting_group_id' => $pg->id]);
        $v2 = Vendor::create(['no' => 'V2', 'name' => 'Supplier B', 'vendor_posting_group_id' => $pg->id]);

        VendorLedgerEntry::create([
            'entry_no' => 1, 'vendor_id' => $v1->id, 'document_type' => 'invoice', 'document_no' => 'PI-1',
            'posting_date' => '2026-01-15', 'due_date' => '2026-02-15', 'amount' => -4000, 'remaining_amount' => 0, 'is_open' => false,
        ]);

        $all = (new VendorListExport)->collection();
        $this->assertSame(-4000.0, (float) $all->firstWhere('no', 'V1')->balance_sum);

        $withBalance = (new VendorListExport(null, true))->collection();
        $this->assertEqualsCanonicalizing(['V1'], $withBalance->pluck('no')->all());

        $export = new VendorListExport;
        $v1Row = $export->collection()->firstWhere('no', 'V1');
        $this->assertSame(['V1', 'Supplier A', 'Vendors', null, '4,000.00', 'CR'], $export->map($v1Row));
        unset($v1, $v2);
    }
}
