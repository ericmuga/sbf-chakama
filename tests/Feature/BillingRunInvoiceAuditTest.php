<?php

namespace Tests\Feature;

use App\Enums\EntityDimension;
use App\Filament\Resources\Chakama\ShareBillingRuns\Pages\ViewShareBillingRun;
use App\Filament\Resources\Chakama\ShareBillingRuns\RelationManagers\InvoicesRelationManager;
use App\Models\Finance\Customer;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\SalesHeader;
use App\Models\Finance\SalesLine;
use App\Models\Finance\Service;
use App\Models\FundAccount;
use App\Models\ShareBillingRun;
use App\Models\ShareBillingSchedule;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BillingRunInvoiceAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_billing_run_invoices_relation_manager_lists_generated_invoices(): void
    {
        NumberSeries::factory()->create(['code' => 'INV', 'prefix' => 'INV-']);
        NumberSeries::factory()->create(['code' => 'SHARE', 'prefix' => 'SHR-']);

        $fund = FundAccount::create([
            'no' => 'FUND-0001',
            'name' => 'Chakama Land Fund',
            'balance' => 0,
            'is_active' => true,
        ]);

        $schedule = ShareBillingSchedule::create([
            'name' => 'Standard Land Share',
            'price_per_share' => 100000,
            'acres_per_share' => 10,
            'billing_frequency' => 'once',
            'is_default' => true,
            'is_active' => true,
            'fund_account_id' => $fund->id,
        ]);

        $admin = User::factory()->create([
            'is_admin' => true,
            'entity' => EntityDimension::Chakama,
        ]);

        $run = ShareBillingRun::create([
            'title' => 'April run',
            'billing_schedule_id' => $schedule->id,
            'billing_date' => today(),
            'status' => 'completed',
            'notify_members' => false,
            'send_email' => false,
            'created_by' => $admin->id,
        ]);

        $service = Service::factory()->create();
        $customer = Customer::factory()->create();

        $invoice = SalesHeader::create([
            'no' => 'INV-000777',
            'customer_id' => $customer->id,
            'customer_posting_group_id' => $customer->customer_posting_group_id,
            'number_series_code' => 'INV',
            'document_type' => 'invoice',
            'posting_date' => today(),
            'due_date' => today()->addDays(30),
            'status' => 'open',
            'share_billing_run_id' => $run->id,
        ]);

        SalesLine::create([
            'sales_header_id' => $invoice->id,
            'service_id' => $service->id,
            'description' => 'April run — 1 share',
            'quantity' => 1,
            'unit_price' => 100000,
            'line_amount' => 100000,
        ]);

        $this->actingAs($admin);
        Filament::setCurrentPanel(Filament::getPanel('chakama'));

        Livewire::test(InvoicesRelationManager::class, [
            'ownerRecord' => $run,
            'pageClass' => ViewShareBillingRun::class,
        ])
            ->assertCanSeeTableRecords([$invoice])
            ->assertSeeHtml('INV-000777');
    }
}
