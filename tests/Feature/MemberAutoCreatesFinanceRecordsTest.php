<?php

namespace Tests\Feature;

use App\Models\Finance\Customer as FinanceCustomer;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\Vendor as FinanceVendor;
use App\Models\Finance\VendorPostingGroup;
use App\Models\Member;
use App\Models\User;
use App\Models\Vendor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MemberAutoCreatesFinanceRecordsTest extends TestCase
{
    use RefreshDatabase;

    private function seedSetup(): void
    {
        $custNos = NumberSeries::create([
            'code' => 'CUST', 'description' => 'Customer Numbers',
            'prefix' => 'CUST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $vendNos = NumberSeries::create([
            'code' => 'VEND', 'description' => 'Vendor Numbers',
            'prefix' => 'VEND-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $mbrNos = NumberSeries::create([
            'code' => 'MBR', 'description' => 'Member Numbers',
            'prefix' => 'MBR-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $sinv = NumberSeries::create([
            'code' => 'SINV', 'description' => 'Sales Invoices',
            'prefix' => 'SINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $psinv = NumberSeries::create([
            'code' => 'PSINV', 'description' => 'Posted Sales Invoices',
            'prefix' => 'PSINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $pinv = NumberSeries::create([
            'code' => 'PINV', 'description' => 'Purchase Invoices',
            'prefix' => 'PINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $ppinv = NumberSeries::create([
            'code' => 'PPINV', 'description' => 'Posted Purchase Invoices',
            'prefix' => 'PPINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $cpg = CustomerPostingGroup::create([
            'code' => 'MEMBER', 'description' => 'Member Customers',
            'receivables_account_no' => '1100',
        ]);

        $vpg = VendorPostingGroup::create([
            'code' => 'MEMBER', 'description' => 'Member Vendors',
            'payables_account_no' => '2100',
        ]);

        SalesSetup::create([
            'invoice_nos' => $sinv->code,
            'posted_invoice_nos' => $psinv->code,
            'customer_nos' => $custNos->code,
            'member_nos' => $mbrNos->code,
        ]);

        PurchaseSetup::create([
            'invoice_nos' => $pinv->code,
            'posted_invoice_nos' => $ppinv->code,
            'vendor_nos' => $vendNos->code,
        ]);

        unset($cpg, $vpg);
    }

    public function test_creating_member_auto_assigns_member_number(): void
    {
        $this->seedSetup();

        $member = Member::create(['name' => 'Test Member', 'type' => 'member', 'identity_type' => 'national_id']);

        $this->assertStringStartsWith('MBR-', $member->fresh()->no);
    }

    public function test_creating_member_auto_creates_finance_customer(): void
    {
        $this->seedSetup();

        $member = Member::create(['name' => 'John Doe', 'type' => 'member', 'identity_type' => 'national_id']);

        $this->assertNotNull($member->fresh()->customer_no);
        $this->assertDatabaseHas('customers', ['no' => $member->fresh()->customer_no, 'name' => 'John Doe']);

        $customer = FinanceCustomer::where('no', $member->fresh()->customer_no)->first();
        $this->assertNotNull($customer);
        $this->assertEquals('MEMBER', $customer->customerPostingGroup->code);
    }

    public function test_creating_member_auto_creates_finance_vendor(): void
    {
        $this->seedSetup();

        $member = Member::create(['name' => 'Jane Doe', 'type' => 'member', 'identity_type' => 'national_id']);

        $this->assertNotNull($member->fresh()->vendor_no);
        $this->assertDatabaseHas('vendors', ['no' => $member->fresh()->vendor_no]);

        $vendor = FinanceVendor::where('no', $member->fresh()->vendor_no)->first();
        $this->assertNotNull($vendor);
        $this->assertEquals('MEMBER', $vendor->vendorPostingGroup->code);
    }

    public function test_customer_number_series_increments(): void
    {
        $this->seedSetup();

        $m1 = Member::create(['name' => 'Member One', 'type' => 'member', 'identity_type' => 'national_id']);
        $m2 = Member::create(['name' => 'Member Two', 'type' => 'member', 'identity_type' => 'national_id']);

        $this->assertNotEquals($m1->fresh()->customer_no, $m2->fresh()->customer_no);
    }

    public function test_creating_bus_vendor_auto_creates_finance_vendor(): void
    {
        $this->seedSetup();

        $busVendor = Vendor::create(['no' => 'EXT-001', 'name' => 'External Supplier', 'vendor_type' => 'External', 'payment_terms' => 'COD']);

        $this->assertDatabaseHas('vendors', ['no' => 'EXT-001', 'name' => 'External Supplier']);
        $this->assertEquals('MEMBER', FinanceVendor::where('no', 'EXT-001')->first()?->vendorPostingGroup?->code);
    }

    public function test_number_series_generate_is_padded_correctly(): void
    {
        NumberSeries::create([
            'code' => 'TEST', 'description' => 'Test',
            'prefix' => 'T-', 'last_no' => 0, 'length' => 4,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $this->assertEquals('T-0001', NumberSeries::generate('TEST'));
        $this->assertEquals('T-0002', NumberSeries::generate('TEST'));
    }

    public function test_finance_customer_resource_requires_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $this->actingAs($user)
            ->get('/admin/finance/customers')
            ->assertForbidden();
    }

    public function test_finance_customer_resource_accessible_by_admin(): void
    {
        $this->seedSetup();
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get('/admin/finance/customers')
            ->assertOk();
    }
}
