<?php

namespace Tests\Feature;

use App\Models\DetailedLedgerEntry;
use App\Models\Member;
use App\Models\MemberLedgerEntry;
use App\Models\NoSeries;
use App\Models\Notification;
use App\Models\PostedPurchaseHeader;
use App\Models\PostedSalesHeader;
use App\Models\PostedSalesLine;
use App\Models\Project;
use App\Models\ProjectLedgerEntry;
use App\Models\PurchaseHeader;
use App\Models\PurchaseLine;
use App\Models\SalesHeader;
use App\Models\SalesLine;
use App\Models\Vendor;
use App\Models\VendorLedgerEntry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class DatabaseSchemaTest extends TestCase
{
    use RefreshDatabase;

    public function test_business_central_tables_are_created(): void
    {
        $tables = [
            'bus_no_series' => ['code', 'description', 'prefix', 'last_no_used', 'increment_by'],
            'bus_members' => ['no', 'user_id', 'national_id', 'phone', 'member_status', 'is_chakama', 'is_sbf', 'customer_no', 'vendor_no'],
            'bus_vendors' => ['no', 'name', 'vendor_type', 'member_id', 'payment_terms'],
            'bus_projects' => ['no', 'title', 'budget_lcy', 'total_actual_cost', 'status'],
            'doc_sales_headers' => ['no', 'member_no', 'posting_date', 'due_date', 'total_amount'],
            'doc_sales_lines' => ['header_id', 'description', 'amount', 'gl_account_no'],
            'pst_sales_headers' => ['no', 'member_no', 'posting_date', 'external_doc_no', 'total_amount'],
            'pst_sales_lines' => ['header_no', 'description', 'amount'],
            'doc_purchase_headers' => ['no', 'vendor_no', 'doc_type', 'project_id', 'status'],
            'doc_purchase_lines' => ['header_id', 'description', 'amount', 'project_id'],
            'pst_purchase_headers' => ['no', 'vendor_no', 'posting_date', 'project_id', 'total_amount'],
            'ent_member_ledger' => ['entry_no', 'member_no', 'posting_date', 'document_type', 'document_no', 'amount', 'remaining_amount', 'open'],
            'ent_vendor_ledger' => ['entry_no', 'vendor_no', 'posting_date', 'document_type', 'document_no', 'amount', 'remaining_amount', 'open'],
            'ent_detailed_ledger' => ['ledger_entry_no', 'ledger_type', 'entry_type', 'posting_date', 'amount'],
            'ent_project_ledger' => ['project_no', 'posting_date', 'document_no', 'entry_type', 'amount'],
            'bus_notifications' => ['member_no', 'title', 'body', 'status', 'scheduled_at', 'sent_at', 'error_log'],
        ];

        foreach ($tables as $table => $columns) {
            $this->assertTrue(Schema::hasTable($table));
            $this->assertTrue(Schema::hasColumns($table, $columns));
        }
    }

    public function test_models_are_mapped_to_expected_tables(): void
    {
        $models = [
            NoSeries::class => 'bus_no_series',
            Member::class => 'bus_members',
            Vendor::class => 'bus_vendors',
            Project::class => 'bus_projects',
            SalesHeader::class => 'doc_sales_headers',
            SalesLine::class => 'doc_sales_lines',
            PostedSalesHeader::class => 'pst_sales_headers',
            PostedSalesLine::class => 'pst_sales_lines',
            PurchaseHeader::class => 'doc_purchase_headers',
            PurchaseLine::class => 'doc_purchase_lines',
            PostedPurchaseHeader::class => 'pst_purchase_headers',
            MemberLedgerEntry::class => 'ent_member_ledger',
            VendorLedgerEntry::class => 'ent_vendor_ledger',
            DetailedLedgerEntry::class => 'ent_detailed_ledger',
            ProjectLedgerEntry::class => 'ent_project_ledger',
            Notification::class => 'bus_notifications',
        ];

        foreach ($models as $modelClass => $table) {
            $this->assertSame($table, (new $modelClass)->getTable());
        }
    }
}
