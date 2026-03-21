<?php

namespace Database\Seeders;

use App\Models\Finance\BankAccount;
use App\Models\Finance\BankPostingGroup;
use App\Models\Finance\Customer;
use App\Models\Finance\CustomerPostingGroup;
use App\Models\Finance\GeneralPostingSetup;
use App\Models\Finance\GlAccount;
use App\Models\Finance\NumberSeries;
use App\Models\Finance\PaymentMethod;
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

        $rcptNos = NumberSeries::firstOrCreate(['code' => 'RCPT'], [
            'description' => 'Cash Receipts', 'prefix' => 'RCPT-', 'last_no' => 0, 'length' => 6,
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

        $mainBankPg = BankPostingGroup::firstOrCreate(['code' => 'MAIN-BANK'], [
            'description' => 'Main Bank Account', 'bank_account_gl_no' => '1050',
        ]);

        $mpesaPg = BankPostingGroup::firstOrCreate(['code' => 'MPESA'], [
            'description' => 'M-Pesa Mobile Money', 'bank_account_gl_no' => '1055',
        ]);

        $cashPg = BankPostingGroup::firstOrCreate(['code' => 'CASH'], [
            'description' => 'Cash on Hand', 'bank_account_gl_no' => '1010',
        ]);

        // Bank Accounts
        $kcbAccount = BankAccount::firstOrCreate(['code' => 'KCB-MAIN'], [
            'name' => 'KCB Main Account', 'bank_account_no' => '1234567890',
            'bank_posting_group_id' => $mainBankPg->id, 'currency_code' => 'KES',
        ]);

        $equityAccount = BankAccount::firstOrCreate(['code' => 'EQUITY-OPS'], [
            'name' => 'Equity Operations Account', 'bank_account_no' => '0987654321',
            'bank_posting_group_id' => $mainBankPg->id, 'currency_code' => 'KES',
        ]);

        $mpesaAccount = BankAccount::firstOrCreate(['code' => 'MPESA-PAYBILL'], [
            'name' => 'M-Pesa Paybill', 'bank_account_no' => '400200',
            'bank_posting_group_id' => $mpesaPg->id, 'currency_code' => 'KES',
        ]);

        $cashAccount = BankAccount::firstOrCreate(['code' => 'CASH-MAIN'], [
            'name' => 'Main Cash Drawer', 'bank_account_no' => '',
            'bank_posting_group_id' => $cashPg->id, 'currency_code' => 'KES',
        ]);

        $posAccount = BankAccount::firstOrCreate(['code' => 'POS-TERMINAL'], [
            'name' => 'POS Card Terminal', 'bank_account_no' => 'POS-001',
            'bank_posting_group_id' => $mainBankPg->id, 'currency_code' => 'KES',
        ]);

        // Payment Methods
        PaymentMethod::firstOrCreate(['code' => 'CASH'], [
            'description' => 'Cash', 'bank_account_id' => $cashAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'CHEQUE'], [
            'description' => 'Cheque', 'bank_account_id' => $kcbAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'MPESA'], [
            'description' => 'M-Pesa', 'bank_account_id' => $mpesaAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'CARD'], [
            'description' => 'Card (Debit/Credit)', 'bank_account_id' => $posAccount->id,
        ]);

        PaymentMethod::firstOrCreate(['code' => 'CREDIT'], [
            'description' => 'Credit (Internal)', 'bank_account_id' => null,
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

        GlAccount::firstOrCreate(['no' => '1010'], ['name' => 'Cash on Hand', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1050'], ['name' => 'KCB Bank Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1051'], ['name' => 'Equity Bank Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1055'], ['name' => 'M-Pesa Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1060'], ['name' => 'POS / Card Account', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1100'], ['name' => 'Accounts Receivable', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '1110'], ['name' => 'Accounts Receivable (Foreign)', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '2100'], ['name' => 'Accounts Payable', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '2110'], ['name' => 'Accounts Payable (Import)', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '4000'], ['name' => 'Member Services Revenue', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '4100'], ['name' => 'Administrative Revenue', 'account_type' => 'Posting']);
        GlAccount::firstOrCreate(['no' => '4200'], ['name' => 'Finance Services Revenue', 'account_type' => 'Posting']);

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
                'receipt_nos' => $rcptNos->code,
            ]);
        } else {
            SalesSetup::create([
                'invoice_nos' => $sinv->code,
                'posted_invoice_nos' => $psinv->code,
                'customer_nos' => $custNos->code,
                'member_nos' => $mbrNos->code,
                'receipt_nos' => $rcptNos->code,
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
