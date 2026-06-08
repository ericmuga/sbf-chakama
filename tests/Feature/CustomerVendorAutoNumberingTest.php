<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Filament\Resources\Finance\CustomerResource\Pages\CreateCustomer;
use App\Filament\Resources\Finance\Vendors\Pages\CreateVendor;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\VendorPostingGroup;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class CustomerVendorAutoNumberingTest extends TestCase
{
    use RefreshDatabase;

    private CustomerPostingGroup $customerPostingGroup;

    private VendorPostingGroup $vendorPostingGroup;

    protected function setUp(): void
    {
        parent::setUp();

        NumberSeries::create([
            'code' => 'CUST', 'description' => 'Customer Numbers',
            'prefix' => 'CUST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        NumberSeries::create([
            'code' => 'VEND', 'description' => 'Vendor Numbers',
            'prefix' => 'VEND-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        SalesSetup::create([
            'invoice_nos' => 'CUST', 'posted_invoice_nos' => 'CUST', 'customer_nos' => 'CUST',
        ]);
        PurchaseSetup::create([
            'invoice_nos' => 'VEND', 'posted_invoice_nos' => 'VEND', 'vendor_nos' => 'VEND',
        ]);

        $this->customerPostingGroup = CustomerPostingGroup::create([
            'code' => 'TRADE', 'description' => 'Trade Receivables', 'receivables_account_no' => '1100',
        ]);

        $this->vendorPostingGroup = VendorPostingGroup::create([
            'code' => 'TRADE', 'description' => 'Trade Payables', 'payables_account_no' => '2100',
        ]);

        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('sbf'));
    }

    public function test_customer_no_previewed_on_create_form_without_incrementing(): void
    {
        Livewire::test(CreateCustomer::class)
            ->assertFormSet(['no' => 'CUST-000001']);

        $this->assertSame(0, (int) NumberSeries::where('code', 'CUST')->value('last_no'));
    }

    public function test_customer_no_assigned_from_series_only_on_successful_create(): void
    {
        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'Acme Corp',
                'customer_posting_group_id' => $this->customerPostingGroup->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', ['no' => 'CUST-000001', 'name' => 'Acme Corp']);
        $this->assertSame(1, (int) NumberSeries::where('code', 'CUST')->value('last_no'));
    }

    public function test_customer_series_increments_per_successful_create(): void
    {
        foreach (['First Ltd', 'Second Ltd'] as $name) {
            Livewire::test(CreateCustomer::class)
                ->fillForm([
                    'name' => $name,
                    'customer_posting_group_id' => $this->customerPostingGroup->id,
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        }

        $this->assertDatabaseHas('customers', ['no' => 'CUST-000001', 'name' => 'First Ltd']);
        $this->assertDatabaseHas('customers', ['no' => 'CUST-000002', 'name' => 'Second Ltd']);
        $this->assertSame(2, (int) NumberSeries::where('code', 'CUST')->value('last_no'));
    }

    public function test_customer_not_created_when_series_inactive_and_no_increment(): void
    {
        NumberSeries::where('code', 'CUST')->update(['is_active' => false]);

        Livewire::test(CreateCustomer::class)
            ->fillForm([
                'name' => 'No Series Ltd',
                'customer_posting_group_id' => $this->customerPostingGroup->id,
            ])
            ->call('create');

        $this->assertDatabaseMissing('customers', ['name' => 'No Series Ltd']);
        $this->assertSame(0, (int) NumberSeries::where('code', 'CUST')->value('last_no'));
    }

    public function test_vendor_no_previewed_on_create_form_without_incrementing(): void
    {
        Livewire::test(CreateVendor::class)
            ->assertFormSet(['no' => 'VEND-000001']);

        $this->assertSame(0, (int) NumberSeries::where('code', 'VEND')->value('last_no'));
    }

    public function test_vendor_no_assigned_from_series_only_on_successful_create(): void
    {
        Livewire::test(CreateVendor::class)
            ->fillForm([
                'name' => 'Supplier Co',
                'vendor_posting_group_id' => $this->vendorPostingGroup->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('vendors', ['no' => 'VEND-000001', 'name' => 'Supplier Co']);
        $this->assertSame(1, (int) NumberSeries::where('code', 'VEND')->value('last_no'));
    }

    public function test_chakama_panel_uses_the_same_customer_auto_numbering(): void
    {
        $chakamaAdmin = User::factory()->create([
            'is_admin' => true,
            'entity' => EntityDimension::Chakama,
        ]);
        $this->actingAs($chakamaAdmin);
        Filament::setCurrentPanel(Filament::getPanel('chakama'));

        Livewire::test(CreateCustomer::class)
            ->assertFormSet(['no' => 'CUST-000001'])
            ->fillForm([
                'name' => 'Chakama Customer',
                'customer_posting_group_id' => $this->customerPostingGroup->id,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('customers', ['no' => 'CUST-000001', 'name' => 'Chakama Customer']);
        $this->assertSame(1, (int) NumberSeries::where('code', 'CUST')->value('last_no'));
    }
}
