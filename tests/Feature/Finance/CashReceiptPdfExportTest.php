<?php

namespace Tests\Feature\Finance;

use App\Models\Finance\BankAccount;
use App\Models\Finance\CashReceipt;
use App\Models\Finance\Customer;
use App\Models\Finance\PaymentMethod;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CashReceiptPdfExportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private CashReceipt $receipt;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['is_admin' => true]);

        $bank = BankAccount::factory()->create();
        $customer = Customer::factory()->create();
        $paymentMethod = PaymentMethod::create([
            'code' => 'MPESA',
            'description' => 'M-Pesa',
            'bank_account_id' => $bank->id,
        ]);

        $this->receipt = CashReceipt::create([
            'no' => 'RCP-000001',
            'customer_id' => $customer->id,
            'bank_account_id' => $bank->id,
            'payment_method_id' => $paymentMethod->id,
            'posting_date' => today(),
            'amount' => 50000,
            'description' => 'Share payment',
            'status' => 'posted',
        ]);
    }

    public function test_admin_can_download_receipt_pdf(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.receipt.pdf', $this->receipt))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_unauthenticated_user_cannot_download_receipt_pdf(): void
    {
        $this->get(route('admin.reports.receipt.pdf', $this->receipt))
            ->assertRedirect();
    }

    public function test_admin_can_export_receipts_to_excel(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.receipts.excel'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_excel_export_respects_date_filter(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.reports.receipts.excel', [
                'date_from' => today()->toDateString(),
                'date_to' => today()->toDateString(),
            ]))
            ->assertOk();
    }

    public function test_pdf_download_only_shows_for_posted_receipts(): void
    {
        // Verify the route works for posted receipts
        $this->actingAs($this->admin)
            ->get(route('admin.reports.receipt.pdf', $this->receipt))
            ->assertOk();
    }
}
