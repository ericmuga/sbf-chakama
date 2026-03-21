<?php

namespace Database\Seeders;

use App\Models\Finance\BankPostingGroup;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\GlAccount;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PaymentTerms;
use App\Models\Finance\PurchaseSetup;
use App\Models\Finance\SalesSetup;
use App\Models\Finance\Service;
use App\Models\Finance\ServicePostingGroup;
use App\Models\Finance\Vendor;
use App\Models\Finance\VendorPostingGroup;
use Illuminate\Database\Seeder;

class FinanceSeeder extends Seeder
{
    public function run(): void
    {
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

        NumberSeries::create([
            'code' => 'PCST', 'description' => 'Posted Documents',
            'prefix' => 'PCST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

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

        $memberCpg = CustomerPostingGroup::create([
            'code' => 'MEMBER',
            'description' => 'Member Customers',
            'receivables_account_no' => '1100',
            'service_charge_account_no' => null,
        ]);

        $domestic = CustomerPostingGroup::create([
            'code' => 'DOMESTIC',
            'description' => 'Domestic Customers',
            'receivables_account_no' => '1100',
            'service_charge_account_no' => null,
        ]);

        $foreign = CustomerPostingGroup::create([
            'code' => 'FOREIGN',
            'description' => 'Foreign Customers',
            'receivables_account_no' => '1110',
            'service_charge_account_no' => null,
        ]);

        $memberVpg = VendorPostingGroup::create([
            'code' => 'MEMBER',
            'description' => 'Member Vendors',
            'payables_account_no' => '2100',
        ]);

        VendorPostingGroup::create([
            'code' => 'LOCAL',
            'description' => 'Local Vendors',
            'payables_account_no' => '2100',
        ]);

        $importVpg = VendorPostingGroup::create([
            'code' => 'IMPORT',
            'description' => 'Import Vendors',
            'payables_account_no' => '2110',
        ]);

        $memberServices = ServicePostingGroup::create([
            'code' => 'MEMBER-SERVICES',
            'description' => 'Member Services',
            'revenue_account_no' => '4000',
        ]);

        $admin = ServicePostingGroup::create([
            'code' => 'ADMIN',
            'description' => 'Administrative Services',
            'revenue_account_no' => '4100',
        ]);

        ServicePostingGroup::create([
            'code' => 'FINANCE',
            'description' => 'Finance Services',
            'revenue_account_no' => '4200',
        ]);

        BankPostingGroup::create([
            'code' => 'MAIN-BANK',
            'description' => 'Main Bank Account',
            'bank_account_gl_no' => '1050',
        ]);

        GeneralPostingSetup::create([
            'customer_posting_group_id' => $memberCpg->id,
            'service_posting_group_id' => $memberServices->id,
            'sales_account_no' => '4000',
        ]);

        GeneralPostingSetup::create([
            'customer_posting_group_id' => $domestic->id,
            'service_posting_group_id' => $memberServices->id,
            'sales_account_no' => '4000',
        ]);

        GeneralPostingSetup::create([
            'customer_posting_group_id' => $domestic->id,
            'service_posting_group_id' => $admin->id,
            'sales_account_no' => '4100',
        ]);

        PaymentTerms::create([
            'code' => 'COD',
            'description' => 'Cash on Delivery',
            'due_days' => 0,
        ]);

        PaymentTerms::create([
            'code' => 'NET30',
            'description' => 'Net 30 Days',
            'due_days' => 30,
        ]);

        PaymentTerms::create([
            'code' => 'NET60',
            'description' => 'Net 60 Days',
            'due_days' => 60,
        ]);

        GlAccount::create([
            'no' => '1050',
            'name' => 'Bank Account',
            'account_type' => 'Posting',
        ]);

        GlAccount::create([
            'no' => '1100',
            'name' => 'Accounts Receivable',
            'account_type' => 'Posting',
        ]);

        GlAccount::create([
            'no' => '2100',
            'name' => 'Accounts Payable',
            'account_type' => 'Posting',
        ]);

        GlAccount::create([
            'no' => '4000',
            'name' => 'Sales Revenue',
            'account_type' => 'Posting',
        ]);

        Customer::create([
            'no' => 'CUST-001',
            'name' => 'Chakama Community Fund',
            'customer_posting_group_id' => $domestic->id,
            'payment_terms_code' => 'NET30',
        ]);

        Customer::create([
            'no' => 'CUST-002',
            'name' => 'SBF Development Trust',
            'customer_posting_group_id' => $foreign->id,
            'payment_terms_code' => 'NET60',
        ]);

        Vendor::create([
            'no' => 'VEND-001',
            'name' => 'Nairobi Office Supplies Ltd',
            'vendor_posting_group_id' => $importVpg->id,
            'payment_terms_code' => 'NET30',
        ]);

        Vendor::create([
            'no' => 'VEND-002',
            'name' => 'Kenya Power & Lighting Co.',
            'vendor_posting_group_id' => $importVpg->id,
            'payment_terms_code' => 'COD',
        ]);

        Service::create([
            'code' => 'MEM-REG',
            'description' => 'Member Registration Fee',
            'unit_price' => 500.0000,
            'service_posting_group_id' => $memberServices->id,
        ]);

        Service::create([
            'code' => 'MEM-ANNUAL',
            'description' => 'Annual Membership Subscription',
            'unit_price' => 2400.0000,
            'service_posting_group_id' => $memberServices->id,
        ]);

        Service::create([
            'code' => 'ADMIN-FEE',
            'description' => 'Administrative Processing Fee',
            'unit_price' => 200.0000,
            'service_posting_group_id' => $admin->id,
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

        unset($memberVpg); // used implicitly via MEMBER code lookup in model events
    }
}
