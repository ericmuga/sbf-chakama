<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\Customer;
use App\Models\Finance\CustomerLedgerEntry;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorLedgerEntry;
use App\Services\Finance\LedgerApplicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LedgerApplicationServiceTest extends TestCase
{
    use RefreshDatabase;

    private LedgerApplicationService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(LedgerApplicationService::class);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    private function makeInvoiceEntry(Customer $customer, float $amount = 1000.00, ?string $dueDate = null): CustomerLedgerEntry
    {
        return CustomerLedgerEntry::factory()->create([
            'customer_id' => $customer->id,
            'document_type' => 'invoice',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'is_open' => true,
            'due_date' => $dueDate ?? now()->addDays(30)->toDateString(),
        ]);
    }

    private function makePaymentEntry(Customer $customer, float $amount = -500.00): CustomerLedgerEntry
    {
        return CustomerLedgerEntry::factory()->create([
            'customer_id' => $customer->id,
            'document_type' => 'payment',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'is_open' => true,
            'due_date' => now()->toDateString(),
        ]);
    }

    private function makeVendorInvoice(Vendor $vendor, float $amount = 1000.00, ?string $dueDate = null): VendorLedgerEntry
    {
        return VendorLedgerEntry::factory()->create([
            'vendor_id' => $vendor->id,
            'document_type' => 'invoice',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'is_open' => true,
            'due_date' => $dueDate ?? now()->addDays(30)->toDateString(),
        ]);
    }

    private function makeVendorPayment(Vendor $vendor, float $amount = -500.00): VendorLedgerEntry
    {
        return VendorLedgerEntry::factory()->create([
            'vendor_id' => $vendor->id,
            'document_type' => 'payment',
            'amount' => $amount,
            'remaining_amount' => $amount,
            'is_open' => true,
            'due_date' => now()->toDateString(),
        ]);
    }

    // ─── Customer Apply Entries ───────────────────────────────────────────────

    public function test_apply_customer_entries_happy_path(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer, 1000.00);
        $payment = $this->makePaymentEntry($customer, -600.00);

        $this->service->applyCustomerEntries($payment, [
            ['customer_ledger_entry_id' => $invoice->id, 'amount_applied' => 600.00],
        ]);

        $this->assertDatabaseHas('customer_applications', [
            'payment_entry_id' => $payment->id,
            'invoice_entry_id' => $invoice->id,
            'amount_applied' => 600.00,
        ]);

        $invoice->refresh();
        $this->assertEquals(400.00, (float) $invoice->remaining_amount);
        $this->assertTrue($invoice->is_open);

        $payment->refresh();
        $this->assertEquals(0.00, (float) $payment->remaining_amount);
        $this->assertFalse($payment->is_open);
    }

    public function test_apply_customer_entries_closes_invoice_when_fully_applied(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer, 500.00);
        $payment = $this->makePaymentEntry($customer, -800.00);

        $this->service->applyCustomerEntries($payment, [
            ['customer_ledger_entry_id' => $invoice->id, 'amount_applied' => 500.00],
        ]);

        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->remaining_amount);
        $this->assertFalse($invoice->is_open);

        $payment->refresh();
        $this->assertEquals(-300.00, (float) $payment->remaining_amount);
        $this->assertTrue($payment->is_open);
    }

    public function test_apply_customer_entries_clamps_to_available_remaining(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer, 300.00);
        $payment = $this->makePaymentEntry($customer, -500.00);

        $this->service->applyCustomerEntries($payment, [
            ['customer_ledger_entry_id' => $invoice->id, 'amount_applied' => 9999.00],
        ]);

        $this->assertDatabaseHas('customer_applications', [
            'amount_applied' => 300.00,
        ]);

        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->remaining_amount);
    }

    public function test_apply_customer_entries_throws_for_different_customer(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer2, 500.00);
        $payment = $this->makePaymentEntry($customer1, -500.00);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/different customer/');

        $this->service->applyCustomerEntries($payment, [
            ['customer_ledger_entry_id' => $invoice->id, 'amount_applied' => 500.00],
        ]);
    }

    public function test_apply_customer_entries_throws_for_same_sign(): void
    {
        $customer = Customer::factory()->create();
        $invoice1 = $this->makeInvoiceEntry($customer, 500.00);
        $invoice2 = $this->makeInvoiceEntry($customer, 300.00);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/opposite signs/');

        $this->service->applyCustomerEntries($invoice1, [
            ['customer_ledger_entry_id' => $invoice2->id, 'amount_applied' => 300.00],
        ]);
    }

    public function test_apply_customer_entries_throws_when_source_already_closed(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer, 500.00);
        $payment = CustomerLedgerEntry::factory()->create([
            'customer_id' => $customer->id,
            'amount' => -500.00,
            'remaining_amount' => 0,
            'is_open' => false,
        ]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/already closed/');

        $this->service->applyCustomerEntries($payment, [
            ['customer_ledger_entry_id' => $invoice->id, 'amount_applied' => 500.00],
        ]);
    }

    public function test_apply_customer_entries_skips_zero_amount(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer, 1000.00);
        $payment = $this->makePaymentEntry($customer, -500.00);

        $this->service->applyCustomerEntries($payment, [
            ['customer_ledger_entry_id' => $invoice->id, 'amount_applied' => 0],
        ]);

        $this->assertDatabaseCount('customer_applications', 0);
    }

    public function test_apply_customer_entries_can_apply_from_invoice_side(): void
    {
        $customer = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer, 1000.00);
        $payment = $this->makePaymentEntry($customer, -400.00);

        $this->service->applyCustomerEntries($invoice, [
            ['customer_ledger_entry_id' => $payment->id, 'amount_applied' => 400.00],
        ]);

        $this->assertDatabaseHas('customer_applications', [
            'payment_entry_id' => $payment->id,
            'invoice_entry_id' => $invoice->id,
            'amount_applied' => 400.00,
        ]);

        $payment->refresh();
        $this->assertFalse($payment->is_open);

        $invoice->refresh();
        $this->assertEquals(600.00, (float) $invoice->remaining_amount);
    }

    // ─── Customer Bulk Apply ──────────────────────────────────────────────────

    public function test_bulk_apply_customer_entries_happy_path(): void
    {
        $customer = Customer::factory()->create();
        $invoice1 = $this->makeInvoiceEntry($customer, 400.00, now()->addDays(10)->toDateString());
        $invoice2 = $this->makeInvoiceEntry($customer, 600.00, now()->addDays(20)->toDateString());
        $payment = $this->makePaymentEntry($customer, -700.00);

        $entries = CustomerLedgerEntry::whereIn('id', [$invoice1->id, $invoice2->id, $payment->id])->get();

        $this->service->bulkApplyCustomerEntries($entries);

        $this->assertDatabaseCount('customer_applications', 2);

        $invoice1->refresh();
        $this->assertEquals(0.00, (float) $invoice1->remaining_amount);
        $this->assertFalse($invoice1->is_open);

        $invoice2->refresh();
        $this->assertEquals(300.00, (float) $invoice2->remaining_amount);
        $this->assertTrue($invoice2->is_open);

        $payment->refresh();
        $this->assertEquals(0.00, (float) $payment->remaining_amount);
        $this->assertFalse($payment->is_open);
    }

    public function test_bulk_apply_customer_throws_for_different_customers(): void
    {
        $customer1 = Customer::factory()->create();
        $customer2 = Customer::factory()->create();
        $invoice = $this->makeInvoiceEntry($customer1, 500.00);
        $payment = $this->makePaymentEntry($customer2, -500.00);

        $entries = CustomerLedgerEntry::whereIn('id', [$invoice->id, $payment->id])->get();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/same customer/');

        $this->service->bulkApplyCustomerEntries($entries);
    }

    public function test_bulk_apply_customer_throws_without_both_signs(): void
    {
        $customer = Customer::factory()->create();
        $invoice1 = $this->makeInvoiceEntry($customer, 500.00);
        $invoice2 = $this->makeInvoiceEntry($customer, 300.00);

        $entries = CustomerLedgerEntry::whereIn('id', [$invoice1->id, $invoice2->id])->get();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invoices.*payments/i');

        $this->service->bulkApplyCustomerEntries($entries);
    }

    // ─── Vendor Apply Entries ─────────────────────────────────────────────────

    public function test_apply_vendor_entries_happy_path(): void
    {
        $vendor = Vendor::factory()->create();
        $invoice = $this->makeVendorInvoice($vendor, 1000.00);
        $payment = $this->makeVendorPayment($vendor, -600.00);

        $this->service->applyVendorEntries($payment, [
            ['vendor_ledger_entry_id' => $invoice->id, 'amount_applied' => 600.00],
        ]);

        $this->assertDatabaseHas('vendor_applications', [
            'payment_entry_id' => $payment->id,
            'invoice_entry_id' => $invoice->id,
            'amount_applied' => 600.00,
        ]);

        $invoice->refresh();
        $this->assertEquals(400.00, (float) $invoice->remaining_amount);
        $this->assertTrue($invoice->is_open);

        $payment->refresh();
        $this->assertEquals(0.00, (float) $payment->remaining_amount);
        $this->assertFalse($payment->is_open);
    }

    public function test_apply_vendor_entries_closes_invoice_when_fully_applied(): void
    {
        $vendor = Vendor::factory()->create();
        $invoice = $this->makeVendorInvoice($vendor, 500.00);
        $payment = $this->makeVendorPayment($vendor, -800.00);

        $this->service->applyVendorEntries($payment, [
            ['vendor_ledger_entry_id' => $invoice->id, 'amount_applied' => 500.00],
        ]);

        $invoice->refresh();
        $this->assertFalse($invoice->is_open);

        $payment->refresh();
        $this->assertEquals(-300.00, (float) $payment->remaining_amount);
        $this->assertTrue($payment->is_open);
    }

    public function test_apply_vendor_entries_throws_for_different_vendor(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $invoice = $this->makeVendorInvoice($vendor2, 500.00);
        $payment = $this->makeVendorPayment($vendor1, -500.00);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/different vendor/');

        $this->service->applyVendorEntries($payment, [
            ['vendor_ledger_entry_id' => $invoice->id, 'amount_applied' => 500.00],
        ]);
    }

    public function test_apply_vendor_entries_throws_for_same_sign(): void
    {
        $vendor = Vendor::factory()->create();
        $invoice1 = $this->makeVendorInvoice($vendor, 500.00);
        $invoice2 = $this->makeVendorInvoice($vendor, 300.00);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/opposite signs/');

        $this->service->applyVendorEntries($invoice1, [
            ['vendor_ledger_entry_id' => $invoice2->id, 'amount_applied' => 300.00],
        ]);
    }

    public function test_apply_vendor_entries_clamps_to_available_remaining(): void
    {
        $vendor = Vendor::factory()->create();
        $invoice = $this->makeVendorInvoice($vendor, 300.00);
        $payment = $this->makeVendorPayment($vendor, -500.00);

        $this->service->applyVendorEntries($payment, [
            ['vendor_ledger_entry_id' => $invoice->id, 'amount_applied' => 9999.00],
        ]);

        $this->assertDatabaseHas('vendor_applications', [
            'amount_applied' => 300.00,
        ]);

        $invoice->refresh();
        $this->assertEquals(0.00, (float) $invoice->remaining_amount);
    }

    // ─── Vendor Bulk Apply ────────────────────────────────────────────────────

    public function test_bulk_apply_vendor_entries_happy_path(): void
    {
        $vendor = Vendor::factory()->create();
        $invoice1 = $this->makeVendorInvoice($vendor, 400.00, now()->addDays(10)->toDateString());
        $invoice2 = $this->makeVendorInvoice($vendor, 600.00, now()->addDays(20)->toDateString());
        $payment = $this->makeVendorPayment($vendor, -700.00);

        $entries = VendorLedgerEntry::whereIn('id', [$invoice1->id, $invoice2->id, $payment->id])->get();

        $this->service->bulkApplyVendorEntries($entries);

        $this->assertDatabaseCount('vendor_applications', 2);

        $invoice1->refresh();
        $this->assertFalse($invoice1->is_open);

        $payment->refresh();
        $this->assertFalse($payment->is_open);
    }

    public function test_bulk_apply_vendor_throws_for_different_vendors(): void
    {
        $vendor1 = Vendor::factory()->create();
        $vendor2 = Vendor::factory()->create();
        $invoice = $this->makeVendorInvoice($vendor1, 500.00);
        $payment = $this->makeVendorPayment($vendor2, -500.00);

        $entries = VendorLedgerEntry::whereIn('id', [$invoice->id, $payment->id])->get();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/same vendor/');

        $this->service->bulkApplyVendorEntries($entries);
    }

    public function test_bulk_apply_vendor_throws_without_both_signs(): void
    {
        $vendor = Vendor::factory()->create();
        $invoice1 = $this->makeVendorInvoice($vendor, 500.00);
        $invoice2 = $this->makeVendorInvoice($vendor, 300.00);

        $entries = VendorLedgerEntry::whereIn('id', [$invoice1->id, $invoice2->id])->get();

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/invoices.*payments/i');

        $this->service->bulkApplyVendorEntries($entries);
    }
}
