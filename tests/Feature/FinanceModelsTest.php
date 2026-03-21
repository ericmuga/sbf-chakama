<?php

namespace Tests\Feature;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankPostingGroup;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorLedgerEntry;
use App\Models\Finance\VendorPayment;
use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FinanceModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_number_series_can_be_created(): void
    {
        $series = NumberSeries::factory()->create([
            'code' => 'TEST-NS',
            'description' => 'Test Number Series',
            'last_no' => 0,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('number_series', ['code' => 'TEST-NS']);
        $this->assertTrue($series->is_active);
        $this->assertFalse($series->is_manual_allowed);
    }

    public function test_customer_posting_group_can_be_created_with_unique_code(): void
    {
        $group = CustomerPostingGroup::factory()->create([
            'code' => 'DOMESTIC',
            'receivables_account_no' => '1100',
        ]);

        $this->assertDatabaseHas('customer_posting_groups', ['code' => 'DOMESTIC']);
        $this->assertEquals('1100', $group->receivables_account_no);

        $this->expectException(QueryException::class);
        CustomerPostingGroup::factory()->create(['code' => 'DOMESTIC']);
    }

    public function test_general_posting_setup_has_unique_customer_and_service_group_combination(): void
    {
        $customerGroup = CustomerPostingGroup::factory()->create();
        $serviceGroup = ServicePostingGroup::factory()->create();

        GeneralPostingSetup::factory()->create([
            'customer_posting_group_id' => $customerGroup->id,
            'service_posting_group_id' => $serviceGroup->id,
        ]);

        $this->expectException(QueryException::class);
        GeneralPostingSetup::factory()->create([
            'customer_posting_group_id' => $customerGroup->id,
            'service_posting_group_id' => $serviceGroup->id,
        ]);
    }

    public function test_customer_belongs_to_posting_group_and_has_many_sales_headers(): void
    {
        $postingGroup = CustomerPostingGroup::factory()->create();
        $numberSeries = NumberSeries::factory()->create();
        $customer = Customer::factory()->create([
            'customer_posting_group_id' => $postingGroup->id,
        ]);

        SalesHeader::factory()->count(3)->create([
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $postingGroup->id,
            'number_series_code' => $numberSeries->code,
        ]);

        $this->assertInstanceOf(CustomerPostingGroup::class, $customer->customerPostingGroup);
        $this->assertEquals($postingGroup->id, $customer->customerPostingGroup->id);
        $this->assertCount(3, $customer->salesHeaders);
    }

    public function test_vendor_belongs_to_posting_group(): void
    {
        $postingGroup = VendorPostingGroup::factory()->create();
        $vendor = Vendor::factory()->create([
            'vendor_posting_group_id' => $postingGroup->id,
        ]);

        $this->assertInstanceOf(VendorPostingGroup::class, $vendor->vendorPostingGroup);
        $this->assertEquals($postingGroup->id, $vendor->vendorPostingGroup->id);
    }

    public function test_sales_header_belongs_to_customer_and_has_many_lines(): void
    {
        $customer = Customer::factory()->create();
        $numberSeries = NumberSeries::factory()->create();
        $service = Service::factory()->create();
        $customerPostingGroup = CustomerPostingGroup::factory()->create();
        $servicePostingGroup = ServicePostingGroup::factory()->create();
        $generalPostingSetup = GeneralPostingSetup::factory()->create([
            'customer_posting_group_id' => $customerPostingGroup->id,
            'service_posting_group_id' => $servicePostingGroup->id,
        ]);

        $salesHeader = SalesHeader::factory()->create([
            'customer_id' => $customer->id,
            'number_series_code' => $numberSeries->code,
            'customer_posting_group_id' => $customerPostingGroup->id,
        ]);

        SalesLine::factory()->count(2)->create([
            'sales_header_id' => $salesHeader->id,
            'service_id' => $service->id,
            'customer_posting_group_id' => $customerPostingGroup->id,
            'service_posting_group_id' => $servicePostingGroup->id,
            'general_posting_setup_id' => $generalPostingSetup->id,
        ]);

        $this->assertInstanceOf(Customer::class, $salesHeader->customer);
        $this->assertEquals($customer->id, $salesHeader->customer->id);
        $this->assertCount(2, $salesHeader->salesLines);
    }

    public function test_sales_line_belongs_to_sales_header_and_service(): void
    {
        $service = Service::factory()->create();
        $customerPostingGroup = CustomerPostingGroup::factory()->create();
        $servicePostingGroup = ServicePostingGroup::factory()->create();
        $generalPostingSetup = GeneralPostingSetup::factory()->create([
            'customer_posting_group_id' => $customerPostingGroup->id,
            'service_posting_group_id' => $servicePostingGroup->id,
        ]);
        $salesHeader = SalesHeader::factory()->create([
            'customer_posting_group_id' => $customerPostingGroup->id,
        ]);

        $salesLine = SalesLine::factory()->create([
            'sales_header_id' => $salesHeader->id,
            'service_id' => $service->id,
            'customer_posting_group_id' => $customerPostingGroup->id,
            'service_posting_group_id' => $servicePostingGroup->id,
            'general_posting_setup_id' => $generalPostingSetup->id,
        ]);

        $this->assertInstanceOf(SalesHeader::class, $salesLine->salesHeader);
        $this->assertEquals($salesHeader->id, $salesLine->salesHeader->id);
        $this->assertInstanceOf(Service::class, $salesLine->service);
        $this->assertEquals($service->id, $salesLine->service->id);
    }

    public function test_customer_ledger_entry_belongs_to_customer(): void
    {
        $customer = Customer::factory()->create();
        $entry = CustomerLedgerEntry::factory()->create([
            'customer_id' => $customer->id,
        ]);

        $this->assertInstanceOf(Customer::class, $entry->customer);
        $this->assertEquals($customer->id, $entry->customer->id);
        $this->assertTrue($entry->is_open);
    }

    public function test_vendor_ledger_entry_belongs_to_vendor(): void
    {
        $vendor = Vendor::factory()->create();
        $entry = VendorLedgerEntry::factory()->create([
            'vendor_id' => $vendor->id,
        ]);

        $this->assertInstanceOf(Vendor::class, $entry->vendor);
        $this->assertEquals($vendor->id, $entry->vendor->id);
        $this->assertTrue($entry->is_open);
    }

    public function test_cash_receipt_belongs_to_customer_and_bank_account(): void
    {
        $customer = Customer::factory()->create();
        $bankPostingGroup = BankPostingGroup::factory()->create();
        $bankAccount = BankAccount::factory()->create([
            'bank_posting_group_id' => $bankPostingGroup->id,
        ]);

        $cashReceipt = CashReceipt::create([
            'no' => 'CR-001',
            'customer_id' => $customer->id,
            'bank_account_id' => $bankAccount->id,
            'posting_date' => now()->toDateString(),
            'amount' => 1000.0000,
            'status' => 'Open',
        ]);

        $this->assertInstanceOf(Customer::class, $cashReceipt->customer);
        $this->assertEquals($customer->id, $cashReceipt->customer->id);
        $this->assertInstanceOf(BankAccount::class, $cashReceipt->bankAccount);
        $this->assertEquals($bankAccount->id, $cashReceipt->bankAccount->id);
    }

    public function test_vendor_payment_belongs_to_vendor_and_bank_account(): void
    {
        $vendor = Vendor::factory()->create();
        $bankPostingGroup = BankPostingGroup::factory()->create();
        $bankAccount = BankAccount::factory()->create([
            'bank_posting_group_id' => $bankPostingGroup->id,
        ]);

        $vendorPayment = VendorPayment::create([
            'no' => 'VP-001',
            'vendor_id' => $vendor->id,
            'bank_account_id' => $bankAccount->id,
            'posting_date' => now()->toDateString(),
            'amount' => 5000.0000,
            'status' => 'Open',
        ]);

        $this->assertInstanceOf(Vendor::class, $vendorPayment->vendor);
        $this->assertEquals($vendor->id, $vendorPayment->vendor->id);
        $this->assertInstanceOf(BankAccount::class, $vendorPayment->bankAccount);
        $this->assertEquals($bankAccount->id, $vendorPayment->bankAccount->id);
    }
}
