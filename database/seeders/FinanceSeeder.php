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
        $sinv = NumberSeries::firstOrCreate(['code' => 'SINV'], [
            'description' => 'Sales Invoices', 'prefix' => 'SINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $psinv = NumberSeries::firstOrCreate(['code' => 'PSINV'], [
            'description' => 'Posted Sales Invoices', 'prefix' => 'PSINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $pinv = NumberSeries::firstOrCreate(['code' => 'PINV'], [
            'description' => 'Purchase Invoices', 'prefix' => 'PINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $ppinv = NumberSeries::firstOrCreate(['code' => 'PPINV'], [
            'description' => 'Posted Purchase Invoices', 'prefix' => 'PPINV-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        NumberSeries::firstOrCreate(['code' => 'PCST'], [
            'description' => 'Posted Documents', 'prefix' => 'PCST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $custNos = NumberSeries::firstOrCreate(['code' => 'CUST'], [
            'description' => 'Customer Numbers', 'prefix' => 'CUST-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $vendNos = NumberSeries::firstOrCreate(['code' => 'VEND'], [
            'description' => 'Vendor Numbers', 'prefix' => 'VEND-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $mbrNos = NumberSeries::firstOrCreate(['code' => 'MBR'], [
            'description' => 'Member Numbers', 'prefix' => 'MBR-', 'last_no' => 0, 'length' => 6,
            'is_manual_allowed' => false, 'prevent_repeats' => true, 'is_active' => true,
        ]);

        $memberCpg = CustomerPostingGroup::firstOrCreate(['code' => 'MEMBER'], [
            'description' => 'Member Customers', 'receivables_account_no' => '1100', 'service_charge_account_no' => null,
        ]);

        $domestic = CustomerPostingGroup::firstOrCreate(['code' => 'DOMESTIC'], [
            'description' => 'Domestic Customers', 'receivables_account_no' => '1100', 'service_charge_account_no' => null,
        ]);

        $foreign = CustomerPostingGroup::firstOrCreate(['code' => 'FOREIGN'], [
            'description' => 'Foreign Customers', 'receivables_account_no' => '1110', 'service_charge_account_no' => null,
        ]);

        VendorPostingGroup::firstOrCreate(['code' => 'MEMBER'], [
            'description' => 'Member Vendors', 'payables_account_no' => '2100',
        ]);

        VendorPostingGroup::firstOrCreate(['code' => 'LOCAL'], [
            'description' => 'Local Vendors', 'payables_account_no' => '2100',
        ]);

        $importVpg = VendorPostingGroup::firstOrCreate(['code' => 'IMPORT'], [
            'description' => 'Import Vendors', 'payables_account_no' => '2110',
        ]);

        $memberServices = ServicePostingGroup::firstOrCreate(['code' => 'MEMBER-SERVICES'], [
            'description' => 'Member Services', 'revenue_account_no' => '4000',
        ]);

        $admin = ServicePostingGroup::firstOrCreate(['code' => 'ADMIN'], [
            'description' => 'Administrative Services', 'revenue_account_no' => '4100',
        ]);

        ServicePostingGroup::firstOrCreate(['code' => 'FINANCE'], [
            'description' => 'Finance Services', 'revenue_account_no' => '4200',
        ]);

        BankPostingGroup::firstOrCreate(['code' => 'MAIN-BANK'], [
            'description' => 'Main Bank Account', 'bank_account_gl_no' => '1050',
        ]);

        GeneralPostingSetup::firstOrCreate([
            'customer_posting_group_id' => $memberCpg->id,
            'service_posting_group_id' => $memberServices->id,
        ], ['sales_account_no' => '4000']);

        GeneralPostingSetup::firstOrCreate([
            'customer_posting_group_id' => $domestic->id,
            'service_posting_group_id' => $memberServices->id,
        ], ['sales_account_no' => '4000']);

        GeneralPostingSetup::firstOrCreate([
            'customer_posting_group_id' => $domestic->id,
            'service_posting_group_id' => $admin->id,
        ], ['sales_account_no' => '4100']);

        PaymentTerms::firstOrCreate(['code' => 'COD'], ['description' => 'Cash on Delivery', 'due_days' => 0]);
        PaymentTerms::firstOrCreate(['code' => 'NET30'], ['description' => 'Net 30 Days', 'due_days' => 30]);
        PaymentTerms::firstOrCreate(['code' => 'NET60'], ['description' => 'Net 60 Days', 'due_days' => 60]);

        GlAccount::firstOrCreate(['no' => '1050'], ['name' => 'Bank Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1100'], ['name' => 'Accounts Receivable', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '2100'], ['name' => 'Accounts Payable', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '4000'], ['name' => 'Sales Revenue', 'account_type' => 'Posting']);

        Customer::firstOrCreate(['no' => 'CUST-001'], [
            'name' => 'Chakama Community Fund',
            'customer_posting_group_id' => $domestic->id,
            'payment_terms_code' => 'NET30',
        ]);

        Customer::firstOrCreate(['no' => 'CUST-002'], [
            'name' => 'SBF Development Trust',
            'customer_posting_group_id' => $foreign->id,
            'payment_terms_code' => 'NET60',
        ]);

        Vendor::firstOrCreate(['no' => 'VEND-001'], [
            'name' => 'Nairobi Office Supplies Ltd',
            'vendor_posting_group_id' => $importVpg->id,
            'payment_terms_code' => 'NET30',
        ]);

        Vendor::firstOrCreate(['no' => 'VEND-002'], [
            'name' => 'Kenya Power & Lighting Co.',
            'vendor_posting_group_id' => $importVpg->id,
            'payment_terms_code' => 'COD',
        ]);

        Service::firstOrCreate(['code' => 'MEM-REG'], [
            'description' => 'Member Registration Fee',
            'unit_price' => 500.0000,
            'service_posting_group_id' => $memberServices->id,
        ]);

        Service::firstOrCreate(['code' => 'MEM-ANNUAL'], [
            'description' => 'Annual Membership Subscription',
            'unit_price' => 2400.0000,
            'service_posting_group_id' => $memberServices->id,
        ]);

        Service::firstOrCreate(['code' => 'ADMIN-FEE'], [
            'description' => 'Administrative Processing Fee',
            'unit_price' => 200.0000,
            'service_posting_group_id' => $admin->id,
        ]);

        $salesSetup = SalesSetup::first();
        if ($salesSetup) {
            $salesSetup->update([
                'invoice_nos' => $sinv->code,
                'posted_invoice_nos' => $psinv->code,
                'customer_nos' => $custNos->code,
                'member_nos' => $mbrNos->code,
            ]);
        } else {
            SalesSetup::create([
                'invoice_nos' => $sinv->code,
                'posted_invoice_nos' => $psinv->code,
                'customer_nos' => $custNos->code,
                'member_nos' => $mbrNos->code,
            ]);
        }

        $purchaseSetup = PurchaseSetup::first();
        if ($purchaseSetup) {
            $purchaseSetup->update([
                'invoice_nos' => $pinv->code,
                'posted_invoice_nos' => $ppinv->code,
                'vendor_nos' => $vendNos->code,
            ]);
        } else {
            PurchaseSetup::create([
                'invoice_nos' => $pinv->code,
                'posted_invoice_nos' => $ppinv->code,
                'vendor_nos' => $vendNos->code,
            ]);
        }
    }
}
