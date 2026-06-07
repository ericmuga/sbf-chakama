<?php

namespace Tests\Feature;

use App\Models\Finance\Customer;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\Service;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoicePdfExportTest extends TestCase
{
    use RefreshDatabase;

    private SalesHeader $invoice;

    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->customer = Customer::factory()->create();

        NumberSeries::factory()->create(['code' => 'INV', 'prefix' => 'INV-']);

        $this->invoice = SalesHeader::create([
            'no' => 'INV-000001',
            'customer_id' => $this->customer->id,
            'customer_posting_group_id' => $this->customer->customer_posting_group_id,
            'number_series_code' => 'INV',
            'document_type' => 'invoice',
            'posting_date' => today(),
            'due_date' => today()->addDays(30),
            'status' => 'open',
        ]);

        $service = Service::factory()->create();

        SalesLine::create([
            'sales_header_id' => $this->invoice->id,
            'service_id' => $service->id,
            'description' => 'Share Subscription — 1 share',
            'quantity' => 1,
            'unit_price' => 100000,
            'line_amount' => 100000,
        ]);
    }

    public function test_admin_can_download_invoice_pdf(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $this->actingAs($admin)
            ->get(route('admin.reports.invoice.pdf', ['invoice' => $this->invoice->no]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_owner_member_can_download_their_invoice(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        Member::factory()->for($user)->create(['customer_no' => $this->customer->no]);

        $this->actingAs($user)
            ->get(route('admin.reports.invoice.pdf', ['invoice' => $this->invoice->no]))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unrelated_member_cannot_download_invoice(): void
    {
        $stranger = User::factory()->create(['is_admin' => false]);
        Member::factory()->for($stranger)->create(['customer_no' => 'OTHER-CUST-9999']);

        $this->actingAs($stranger)
            ->get(route('admin.reports.invoice.pdf', ['invoice' => $this->invoice->no]))
            ->assertForbidden();
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $this->get(route('admin.reports.invoice.pdf', ['invoice' => $this->invoice->no]))
            ->assertRedirect();
    }
}
